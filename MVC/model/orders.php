<?php require_once(__DIR__ . "/../config/cashfy.php");

/**
 * Estrutura atual:
 *   orders (id, buyer_id, seller_id, total, status, explanation, date)
 *   sales  (id, order_id, product_id, quantity, price)   <- itens do pedido
 *
 * Cria um pedido, valida/abate o estoque dos produtos controlados e grava todos os itens
 * numa única transação: ou grava tudo, ou nada.
 *
 * @param int         $buyer_id    quem comprou
 * @param int         $seller_id   dono da loja
 * @param array       $items       lista de ['product_id' => int, 'quantity' => int, 'price' => float]
 * @param string      $status      pending | accepted | rejected | completed
 * @param string|null $date        'Y-m-d H:i:s'. Se null, o banco usa o horário atual
 * @param string|null &$error_msg  mensagem detalhada em caso de falha de validação/estoque
 *
 * @return int|false id do pedido criado, ou false se algo falhou
 */
function create_order(int $buyer_id, int $seller_id, array $items, string $status = 'pending', ?string $date = null, ?string &$error_msg = null)
{
    if (empty($items)) {
        $error_msg = 'O pedido não possui itens.';
        return false;
    }

    $conn = conn();

    // Agrupa quantidades por product_id caso o mesmo produto apareça mais de uma vez
    $grouped_qty = [];
    $total = 0.0;
    foreach ($items as $item) {
        $pid = (int) $item['product_id'];
        $qty = (int) $item['quantity'];
        $price = (float) $item['price'];

        if ($qty <= 0 || $price < 0) {
            $error_msg = 'Quantidade ou preço inválido no pedido.';
            return false;
        }

        $grouped_qty[$pid] = ($grouped_qty[$pid] ?? 0) + $qty;
        $total += $qty * $price;
    }
    $total = round($total, 2);

    try {
        $conn->begin_transaction();

        // Trava os registros dos produtos com FOR UPDATE para evitar condição de corrida
        // no controle de estoque e impedir exclusão simultânea
        $stmt_check = $conn->prepare("
            SELECT id, name, stock
            FROM products
            WHERE id = ? AND user_id = ? AND deleted_at IS NULL
            FOR UPDATE
        ");

        $stmt_deduct = $conn->prepare("
            UPDATE products
            SET stock = stock - ?
            WHERE id = ? AND stock IS NOT NULL AND stock >= ?
        ");

        foreach ($grouped_qty as $product_id => $needed_qty) {
            $stmt_check->bind_param('ii', $product_id, $seller_id);
            $stmt_check->execute();

            $res = $stmt_check->get_result();
            $prod = $res->fetch_assoc();
            $res->free();

            if (!$prod) {
                $error_msg = 'Um dos produtos não está mais disponível.';
                throw new RuntimeException("Produto $product_id indisponível (excluído ou inexistente).");
            }

            if ($prod['stock'] !== null) {
                $available = (int) $prod['stock'];
                if ($available <= 0) {
                    $error_msg = 'O produto "' . $prod['name'] . '" está esgotado.';
                    throw new RuntimeException("Produto $product_id esgotado.");
                }
                if ($needed_qty > $available) {
                    $error_msg = 'Estoque insuficiente para "' . $prod['name'] . '" (disponível: ' . $available . ').';
                    throw new RuntimeException("Estoque insuficiente para produto $product_id.");
                }

                $stmt_deduct->bind_param('iii', $needed_qty, $product_id, $needed_qty);
                $stmt_deduct->execute();

                if ($stmt_deduct->affected_rows < 1) {
                    $error_msg = 'Estoque insuficiente para "' . $prod['name'] . '".';
                    throw new RuntimeException("Falha ao abater estoque do produto $product_id.");
                }
            }
        }

        $stmt_check->close();
        $stmt_deduct->close();

        if ($date === null) {
            $stmt = $conn->prepare("
                INSERT INTO orders (buyer_id, seller_id, total, status) VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param('iids', $buyer_id, $seller_id, $total, $status);
        } else {
            $stmt = $conn->prepare("
                INSERT INTO orders (buyer_id, seller_id, total, status, `date`) VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('iidss', $buyer_id, $seller_id, $total, $status, $date);
        }

        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();

        $stmt = $conn->prepare("
            INSERT INTO sales (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)
        ");

        foreach ($items as $item) {
            $product_id = (int) $item['product_id'];
            $quantity = (int) $item['quantity'];
            $price = (float) $item['price'];

            $stmt->bind_param('iiid', $order_id, $product_id, $quantity, $price);
            $stmt->execute();
        }

        $stmt->close();
        $conn->commit();

        return $order_id;
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('create_order: ' . $e->getMessage());

        return false;
    }
}

function get_order_by_id(int $order_id): ?array
{
    $conn = conn();

    $stmt = $conn->prepare("
        SELECT o.*,
               b.name AS buyer_name,
               b.role_id AS buyer_role_id,
               s.name AS seller_name
        FROM orders o
        INNER JOIN users b ON b.id = o.buyer_id
        INNER JOIN users s ON s.id = o.seller_id
        WHERE o.id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $order_id);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $result ?: null;
}

/**
 * "Assinatura" dos pedidos pendentes de um vendedor: quantos são e qual o maior id.
 * É o que o SSE compara para saber se chegou (ou saiu) algum pedido.
 *
 * @return array{pending: int, last_id: int}
 */
function get_order_signature(int $seller_id): array
{
    $conn = conn();

    $stmt = $conn->prepare("
    SELECT COUNT(*) AS pending, COALESCE(MAX(id), 0) AS last_id
    FROM orders
    WHERE seller_id = ?
    AND status = 'pending'
    ");
    $stmt->bind_param('i', $seller_id);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return ['pending' => (int) $row['pending'], 'last_id' => (int) $row['last_id']];
}

/**
 * Estado atual dos pedidos realizados por um comprador (excluindo vendas manuais do Diário,
 * onde buyer_id = seller_id). Usado pelo SSE para detectar em tempo real quando um pedido
 * do comprador passa para 'accepted', 'rejected' ou 'completed'.
 *
 * @return array{hash: string, map: array<int, array{id: int, status: string, explanation: ?string, seller_name: string, total: float}>}
 */
function get_buyer_orders_state(int $buyer_id): array
{
    $conn = conn();

    $stmt = $conn->prepare("
    SELECT o.id, o.status, o.explanation, o.total, u.name AS seller_name
    FROM orders o
    INNER JOIN users u ON u.id = o.seller_id
    WHERE o.buyer_id = ?
    AND o.buyer_id <> o.seller_id
    ORDER BY o.id DESC
    LIMIT 50
    ");
    $stmt->bind_param('i', $buyer_id);
    $stmt->execute();

    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $map = [];
    $parts = [];

    foreach ($rows as $r) {
        $id = (int) $r['id'];
        $status = (string) $r['status'];
        $explanation = $r['explanation'] !== null ? (string) $r['explanation'] : '';

        $map[$id] = [
            'id' => $id,
            'status' => $status,
            'explanation' => $explanation,
            'seller_name' => (string) $r['seller_name'],
            'total' => round((float) $r['total'], 2),
        ];
        $parts[] = $id . ':' . $status . ':' . md5($explanation);
    }

    return [
        'hash' => md5(implode('|', $parts)),
        'map' => $map,
    ];
}

/**
 * Pedidos em aberto (pending e accepted) de um vendedor, do mais antigo para o mais
 * novo, cada um com o nome/telefone do comprador e a lista de itens em $order['items'].
 * Pedidos completed e rejected não aparecem.
 */
function get_seller_orders(int $seller_id): array
{
    $conn = conn();

    $stmt = $conn->prepare("
    SELECT o.id, o.buyer_id, o.total, o.status, o.`date`,
           u.name AS buyer_name, u.phone_number AS buyer_phone
    FROM orders o
    INNER JOIN users u ON u.id = o.buyer_id
    WHERE o.seller_id = ?
    AND o.status IN ('pending', 'accepted')
    ORDER BY o.`date` ASC, o.id ASC
    ");
    $stmt->bind_param('i', $seller_id);
    $stmt->execute();

    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($orders)) {
        return [];
    }

    $by_id = [];
    foreach ($orders as $order) {
        $order['items'] = [];
        $by_id[(int) $order['id']] = $order;
    }

    $ids = array_keys($by_id);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $conn->prepare("
    SELECT s.id, s.order_id, s.product_id, s.quantity, s.price, p.name AS product_name
    FROM sales s
    LEFT JOIN products p ON p.id = s.product_id
    WHERE s.order_id IN ($placeholders)
    ORDER BY s.id
    ");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();

    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $item) {
        $by_id[(int) $item['order_id']]['items'][] = $item;
    }

    $stmt->close();

    return array_values($by_id);
}

/**
 * Muda o status de um pedido do vendedor: pending -> accepted.
 * O UPDATE só acontece se o pedido ainda estiver no status de origem, então dois
 * cliques (ou duas abas) não aplicam a mesma ação duas vezes.
 */
function change_order_status(int $order_id, int $seller_id, string $from, string $to): bool
{
    // 'rejected' tem função própria (reject_order), pois exige o motivo da recusa e repõe estoque
    $allowed = [
        'pending' => ['accepted'],
    ];

    if (!in_array($to, $allowed[$from] ?? [], true)) {
        return false;
    }

    $conn = conn();

    $stmt = $conn->prepare("
    UPDATE orders SET status = ?
    WHERE id = ? AND seller_id = ? AND status = ?
    ");
    $stmt->bind_param('siis', $to, $order_id, $seller_id, $from);
    $stmt->execute();

    $changed = $stmt->affected_rows > 0;
    $stmt->close();

    return $changed;
}

/**
 * Recusa um pedido pendente do vendedor, grava o motivo em orders.explanation
 * e repõe automaticamente o estoque dos produtos que possuem controle de estoque.
 * Tudo numa única transação com bloqueio FOR UPDATE para evitar reposição duplicada.
 */
function reject_order(int $order_id, int $seller_id, string $explanation): bool
{
    $conn = conn();

    try {
        $conn->begin_transaction();

        // Trava o pedido e garante que ainda está 'pending'
        $stmt = $conn->prepare("
            SELECT id FROM orders
            WHERE id = ? AND seller_id = ? AND status = 'pending'
            FOR UPDATE
        ");
        $stmt->bind_param('ii', $order_id, $seller_id);
        $stmt->execute();
        $found = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if (!$found) {
            $conn->rollback();
            return false;
        }

        // Atualiza status e motivo da recusa
        $stmt = $conn->prepare("
            UPDATE orders SET status = 'rejected', explanation = ?
            WHERE id = ? AND seller_id = ? AND status = 'pending'
        ");
        $stmt->bind_param('sii', $explanation, $order_id, $seller_id);
        $stmt->execute();
        $changed = $stmt->affected_rows > 0;
        $stmt->close();

        if (!$changed) {
            $conn->rollback();
            return false;
        }

        // Busca os itens do pedido para devolver ao estoque (apenas produtos com stock IS NOT NULL)
        $stmt = $conn->prepare("
            SELECT product_id, quantity FROM sales WHERE order_id = ?
        ");
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (!empty($items)) {
            $stmt_restore = $conn->prepare("
                UPDATE products
                SET stock = stock + ?
                WHERE id = ? AND stock IS NOT NULL
            ");

            foreach ($items as $item) {
                $qty = (int) $item['quantity'];
                $pid = (int) $item['product_id'];
                if ($qty > 0) {
                    $stmt_restore->bind_param('ii', $qty, $pid);
                    $stmt_restore->execute();
                }
            }

            $stmt_restore->close();
        }

        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('reject_order: ' . $e->getMessage());
        return false;
    }
}

/**
 * Conclui um pedido aceito (accepted -> completed). Antes de concluir o vendedor pode
 * ajustar quantidade e valor de cada item; o total do pedido é recalculado e a diferença
 * de quantidade ajusta o estoque automaticamente.
 *
 * @param array       $edits      [sale_id => ['quantity' => int, 'price' => float]]
 * @param string|null &$error_msg mensagem de erro se faltar estoque ao aumentar a quantidade
 */
function complete_order(int $order_id, int $seller_id, array $edits = [], ?string &$error_msg = null): bool
{
    $conn = conn();

    try {
        $conn->begin_transaction();

        // Trava o pedido enquanto ele é conferido/alterado
        $stmt = $conn->prepare("
        SELECT id FROM orders
        WHERE id = ? AND seller_id = ? AND status = 'accepted'
        FOR UPDATE
        ");
        $stmt->bind_param('ii', $order_id, $seller_id);
        $stmt->execute();
        $found = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if (!$found) {
            $conn->rollback();
            return false;
        }

        // Carrega os itens atuais do pedido para calcular diferenças de quantidade no estoque
        $stmt = $conn->prepare("
            SELECT id, product_id, quantity FROM sales WHERE order_id = ? FOR UPDATE
        ");
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $current_sales = [];
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $current_sales[(int) $row['id']] = [
                'product_id' => (int) $row['product_id'],
                'quantity' => (int) $row['quantity'],
            ];
        }
        $stmt->close();

        $stmt_update_sale = $conn->prepare("UPDATE sales SET quantity = ?, price = ? WHERE id = ? AND order_id = ?");
        $stmt_prod_lock = $conn->prepare("SELECT name, stock FROM products WHERE id = ? FOR UPDATE");
        $stmt_prod_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock IS NOT NULL");

        foreach ($edits as $sale_id => $edit) {
            $sale_id = (int) $sale_id;
            if (!isset($current_sales[$sale_id])) {
                continue;
            }

            $quantity = (int) $edit['quantity'];
            $price = (float) $edit['price'];

            if ($quantity <= 0 || $price <= 0) {
                $conn->rollback();
                return false;
            }

            $old_qty = $current_sales[$sale_id]['quantity'];
            $product_id = $current_sales[$sale_id]['product_id'];
            $diff = $quantity - $old_qty; // positivo = precisa abater mais; negativo = devolve ao estoque

            if ($diff !== 0) {
                $stmt_prod_lock->bind_param('i', $product_id);
                $stmt_prod_lock->execute();
                $prod = $stmt_prod_lock->get_result()->fetch_assoc();

                if ($prod && $prod['stock'] !== null) {
                    $avail = (int) $prod['stock'];
                    if ($diff > 0 && $avail < $diff) {
                        $error_msg = 'Estoque insuficiente para "' . $prod['name'] . '" ao aumentar a quantidade (disponível: ' . $avail . ').';
                        throw new RuntimeException($error_msg);
                    }
                    $stmt_prod_stock->bind_param('ii', $diff, $product_id);
                    $stmt_prod_stock->execute();
                }
            }

            $stmt_update_sale->bind_param('idii', $quantity, $price, $sale_id, $order_id);
            $stmt_update_sale->execute();
        }

        $stmt_update_sale->close();
        $stmt_prod_lock->close();
        $stmt_prod_stock->close();

        $stmt = $conn->prepare("
        UPDATE orders
        SET status = 'completed',
            total = (SELECT ROUND(SUM(quantity * price), 2) FROM sales WHERE order_id = ?)
        WHERE id = ?
        ");
        $stmt->bind_param('ii', $order_id, $order_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('complete_order: ' . $e->getMessage());

        return false;
    }
}

/**
 * Pedido pendente mais recente do vendedor (id, nome do comprador e total).
 * Usado no texto da notificação.
 */
function get_latest_pending_order(int $seller_id): ?array
{
    $conn = conn();

    $stmt = $conn->prepare("
    SELECT o.id, u.name AS buyer, COALESCE(SUM(s.quantity * s.price), 0) AS total
    FROM orders o
    INNER JOIN users u ON u.id = o.buyer_id
    LEFT JOIN sales s ON s.order_id = o.id
    WHERE o.seller_id = ?
    AND o.status = 'pending'
    GROUP BY o.id, u.name
    ORDER BY o.id DESC
    LIMIT 1
    ");
    $stmt->bind_param('i', $seller_id);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }

    return ['id' => (int) $row['id'], 'buyer' => $row['buyer'], 'total' => round((float) $row['total'], 2)];
}


function get_client_orders(int $buyer_id): array
{
    $conn = conn();

    $stmt = $conn->prepare("
    SELECT o.id, o.buyer_id, o.seller_id, o.total, o.status, o.explanation, o.`date`,
           u.name AS seller_name, u.phone_number AS seller_phone,
           u.profile_photo AS seller_photo
    FROM orders o
    INNER JOIN users u ON u.id = o.seller_id
    WHERE o.buyer_id = ?
    AND o.buyer_id <> o.seller_id
    AND o.status IN ('pending', 'accepted', 'completed', 'rejected')
    ORDER BY
    CASE o.status
        WHEN 'pending' THEN 1
        WHEN 'accepted' THEN 2
        WHEN 'rejected' THEN 3
        WHEN 'completed' THEN 4
        ELSE 5
    END,
    o.`date` DESC,
    o.id DESC
    ");
    $stmt->bind_param('i', $buyer_id);
    $stmt->execute();

    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($orders)) {
        return [];
    }

    $by_id = [];
    foreach ($orders as $order) {
        $order['items'] = [];
        $by_id[(int) $order['id']] = $order;
    }

    $ids = array_keys($by_id);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $conn->prepare("
    SELECT s.id, s.order_id, s.product_id, s.quantity, s.price, p.name AS product_name,
           p.product_photo
    FROM sales s
    LEFT JOIN products p ON p.id = s.product_id
    WHERE s.order_id IN ($placeholders)
    ORDER BY s.id
    ");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();

    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $item) {
        $by_id[(int) $item['order_id']]['items'][] = $item;
    }

    $stmt->close();

    return array_values($by_id);
}

/**
 * Histórico do vendedor: pedidos concluídos (do mais novo para o mais antigo), cada um com
 * o nome do comprador, a flag manual (venda avulsa do Diário: o vendedor é o próprio
 * comprador) e a lista de itens em $order['items'].
 */
function get_seller_history(int $seller_id, int $limit = 30): array
{
    $conn = conn();

    $stmt = $conn->prepare("
    SELECT o.id, o.buyer_id, o.total, o.status, o.`date`,
           u.name AS buyer_name,
           (o.buyer_id = o.seller_id) AS is_manual
    FROM orders o
    INNER JOIN users u ON u.id = o.buyer_id
    WHERE o.seller_id = ?
    AND o.status = 'completed'
    ORDER BY o.`date` DESC, o.id DESC
    LIMIT ?
    ");
    $stmt->bind_param('ii', $seller_id, $limit);
    $stmt->execute();

    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($orders)) {
        return [];
    }

    $by_id = [];
    foreach ($orders as $order) {
        $order['items'] = [];
        $by_id[(int) $order['id']] = $order;
    }

    $ids = array_keys($by_id);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $conn->prepare("
    SELECT s.id, s.order_id, s.product_id, s.quantity, s.price, p.name AS product_name
    FROM sales s
    LEFT JOIN products p ON p.id = s.product_id
    WHERE s.order_id IN ($placeholders)
    ORDER BY s.id
    ");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();

    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $item) {
        $by_id[(int) $item['order_id']]['items'][] = $item;
    }

    $stmt->close();

    return array_values($by_id);
}
?>
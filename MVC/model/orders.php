<?php require_once(__DIR__ . "/../config/cashfy.php");

/**
 * Estrutura atual:
 *   orders (id, buyer_id, seller_id, total, status, date)
 *   sales  (id, order_id, product_id, quantity, price)   <- itens do pedido
 *
 * Cria um pedido e todos os seus itens numa única transação: ou grava tudo, ou nada.
 *
 * @param int         $buyer_id  quem comprou
 * @param int         $seller_id dono da loja
 * @param array       $items     lista de ['product_id' => int, 'quantity' => int, 'price' => float]
 * @param string      $status    pending | accepted | rejected | completed
 * @param string|null $date      'Y-m-d H:i:s'. Se null, o banco usa o horário atual
 *
 * @return int|false id do pedido criado, ou false se algo falhou
 */
function create_order(int $buyer_id, int $seller_id, array $items, string $status = 'pending', ?string $date = null)
{
    if (empty($items)) {
        return false;
    }

    $conn = conn();

    $total = 0.0;
    foreach ($items as $item) {
        $total += $item['quantity'] * $item['price'];
    }
    $total = round($total, 2);

    try {
        $conn->begin_transaction();

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

function get_order_by_id(int $order_id)
{
    $conn = conn();

    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
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
 * Muda o status de um pedido do vendedor: pending -> accepted | rejected.
 * O UPDATE só acontece se o pedido ainda estiver no status de origem, então dois
 * cliques (ou duas abas) não aplicam a mesma ação duas vezes.
 */
function change_order_status(int $order_id, int $seller_id, string $from, string $to): bool
{
    $allowed = [
        'pending' => ['accepted', 'rejected'],
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
 * Conclui um pedido aceito (accepted -> completed). Antes de concluir o vendedor pode
 * ajustar quantidade e valor de cada item; o total do pedido é recalculado.
 *
 * @param array $edits [sale_id => ['quantity' => int, 'price' => float]]
 *                     itens que não pertencem ao pedido são ignorados
 */
function complete_order(int $order_id, int $seller_id, array $edits = []): bool
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

        $stmt = $conn->prepare("UPDATE sales SET quantity = ?, price = ? WHERE id = ? AND order_id = ?");

        foreach ($edits as $sale_id => $edit) {
            $quantity = (int) $edit['quantity'];
            $price = (float) $edit['price'];
            $sale_id = (int) $sale_id;

            if ($quantity <= 0 || $price <= 0) {
                $conn->rollback();
                return false;
            }

            $stmt->bind_param('idii', $quantity, $price, $sale_id, $order_id);
            $stmt->execute();
        }

        $stmt->close();

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

?>

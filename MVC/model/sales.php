<?php require_once(__DIR__ . "/../config/cashfy.php");
require_once(__DIR__ . "/orders.php");

/*
 * Estrutura atual do banco:
 *   orders (id, buyer_id, seller_id, total, status, date)
 *   sales  (id, order_id, product_id, quantity, price)
 *
 * A tabela sales não tem mais user_id nem date: o vendedor é orders.seller_id e a
 * data é orders.date. Todo faturamento (histórico, totais e gráfico) conta apenas
 * pedidos com status 'completed'.
 */

/**
 * Últimas vendas (itens de pedidos concluídos) de um vendedor.
 * Cada linha traz também: user_id (vendedor), date (do pedido) e product_name.
 */
function get_sales(int $user_id, int $limit = 10)
{
    $conn = conn();

    $stmt = $conn->prepare("
    SELECT
        s.id,
        s.order_id,
        s.product_id,
        s.quantity,
        s.price,
        o.seller_id AS user_id,
        o.`date` AS `date`,
        p.name AS product_name
    FROM sales s
    INNER JOIN orders o ON o.id = s.order_id
    LEFT JOIN products p ON p.id = s.product_id
    WHERE o.seller_id = ?
    AND o.status = 'completed'
    ORDER BY o.`date` DESC, s.id DESC
    LIMIT ?
    ");
    $stmt->bind_param('ii', $user_id, $limit);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $result;
}

/**
 * Registro manual do Diário: cria um pedido já 'completed' com um item.
 * Como orders.buyer_id é obrigatório, o próprio vendedor entra como comprador
 * (venda presencial/avulsa, sem cliente cadastrado).
 *
 * @return int|false id do pedido criado
 */
function register_sale(int $user_id, int $product_id, int $quantity, float $price, string $date)
{
    // A data vem do <input type="date">; o horário usado é o do momento do registro
    $datetime = date('Y-m-d', strtotime($date)) . ' ' . date('H:i:s');

    return create_order(
        $user_id,
        $user_id,
        [['product_id' => $product_id, 'quantity' => $quantity, 'price' => $price]],
        'completed',
        $datetime
    );
}

function get_total_week(int $user_id)
{
    $conn = conn();

    $stmt = $conn->prepare("
    SELECT SUM(s.quantity * s.price) AS total_week
    FROM sales s
    INNER JOIN orders o ON o.id = s.order_id
    WHERE o.seller_id = ?
    AND o.status = 'completed'
    AND o.`date` >= CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY
    AND o.`date` < CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY + INTERVAL 7 DAY
    ");
    $stmt->bind_param('i', $user_id);

    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return number_format((float) ($result['total_week'] ?? 0), 2, ',', '.');
}

function get_total_month(int $user_id)
{
    $conn = conn();

    $stmt = $conn->prepare("
    SELECT SUM(s.quantity * s.price) AS total_month
    FROM sales s
    INNER JOIN orders o ON o.id = s.order_id
    WHERE o.seller_id = ?
    AND o.status = 'completed'
    AND o.`date` >= CURDATE() - INTERVAL (DAY(CURDATE()) - 1) DAY
    AND o.`date` < DATE_ADD(
        CURDATE() - INTERVAL (DAY(CURDATE()) - 1) DAY,
        INTERVAL 1 MONTH
    )
    ");
    $stmt->bind_param('i', $user_id);

    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return number_format((float) ($result['total_month'] ?? 0), 2, ',', '.');
}

function get_saled_items_month(int $user_id)
{
    $conn = conn();

    $stmt = $conn->prepare("
    SELECT SUM(s.quantity) AS saled_items
    FROM sales s
    INNER JOIN orders o ON o.id = s.order_id
    WHERE o.seller_id = ?
    AND o.status = 'completed'
    AND o.`date` >= CURDATE() - INTERVAL (DAY(CURDATE()) - 1) DAY
    AND o.`date` < DATE_ADD(
        CURDATE() - INTERVAL (DAY(CURDATE()) - 1) DAY,
        INTERVAL 1 MONTH
    )
    ");
    $stmt->bind_param('i', $user_id);

    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int) ($result['saled_items'] ?? 0);
}

/**
 * Período do gráfico de faturamento de um vendedor: do mês da primeira venda até
 * o mês atual do banco (limitado aos últimos $max_months meses). Vendas com data
 * futura ficam fora. O mês atual sempre faz parte do período, mesmo sem vendas.
 *
 * @return array{start: DateTime, current: DateTime, end: DateTime, has_sales: bool}
 *         end = primeiro dia do mês seguinte ao atual (limite superior exclusivo)
 */
function get_revenue_period($user_id, $max_months = 24)
{
    $conn = conn();

    $stmt = $conn->prepare("
    SELECT
        DATE_FORMAT(MIN(o.`date`), '%Y-%m-01') AS first_month,
        DATE_FORMAT(CURDATE(), '%Y-%m-01') AS current_month
    FROM sales s
    INNER JOIN orders o ON o.id = s.order_id
    WHERE o.seller_id = ?
    AND o.status = 'completed'
    AND o.`date` < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $bounds = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $current = new DateTime($bounds['current_month']);
    $has_sales = !empty($bounds['first_month']);
    $start = $has_sales ? new DateTime($bounds['first_month']) : clone $current;
    $limit = (clone $current)->modify('-' . ($max_months - 1) . ' months');

    if ($start < $limit) {
        $start = $limit;
    }

    return [
        'start' => $start,
        'current' => $current,
        'end' => (clone $current)->modify('+1 month'),
        'has_sales' => $has_sales,
    ];
}

/**
 * Meses disponíveis no seletor do gráfico ("YYYY-MM"), do mais recente (mês atual)
 * ao mais antigo do período.
 *
 * @return string[]
 */
function get_revenue_months($user_id, $max_months = 24)
{
    $period = get_revenue_period($user_id, $max_months);
    $months = [];

    for ($month = clone $period['current']; $month >= $period['start']; $month->modify('-1 month')) {
        $months[] = $month->format('Y-m');
    }

    return $months;
}

/**
 * Faturamento mensal de um vendedor, com TODOS os meses do período (meses sem
 * vendas entram com 0). Se $product_id for informado, considera só aquele produto.
 *
 * O período é sempre calculado sobre TODAS as vendas do vendedor, para que o
 * modo "por produto" use os mesmos meses do modo "por mês".
 *
 * Faturamento = SUM(quantity * price), igual ao "Total" exibido no histórico do Diário.
 *
 * @return array{labels: string[], values: float[], total: float, details: array}  labels no formato "YYYY-MM"
 */
function get_monthly_revenue_series($user_id, $product_id = null, $max_months = 24)
{
    $period = get_revenue_period($user_id, $max_months);

    if (!$period['has_sales']) {
        return ['labels' => [], 'values' => [], 'total' => 0.0, 'details' => []];
    }

    $totals = query_revenue_totals(
        "DATE_FORMAT(o.`date`, '%Y-%m')",
        $user_id,
        $period['start']->format('Y-m-d'),
        $period['end']->format('Y-m-d'),
        $product_id
    );

    $details = query_revenue_details(
        'month',
        $user_id,
        $period['start']->format('Y-m-d'),
        $period['end']->format('Y-m-d'),
        $product_id
    );

    $labels = [];
    $values = [];
    $sum = 0.0;

    // Preenche os meses sem venda com 0, para não sumirem do histórico
    for ($month = clone $period['start']; $month < $period['end']; $month->modify('+1 month')) {
        $key = $month->format('Y-m');
        $value = $totals[$key] ?? 0.0;

        $labels[] = $key;
        $values[] = $value;
        $sum += $value;
    }

    return ['labels' => $labels, 'values' => $values, 'total' => round($sum, 2), 'details' => $details];
}

/**
 * Faturamento por dia dentro de um mês ("YYYY-MM", já validado pelo controller),
 * com todos os dias do mês (dias sem venda = 0). Aceita filtro por produto.
 *
 * @return array{labels: string[], values: float[], total: float, details: array}  labels no formato "YYYY-MM-DD"
 */
function get_daily_revenue_series($user_id, $month, $product_id = null)
{
    $start = DateTime::createFromFormat('!Y-m-d', $month . '-01');
    $end = (clone $start)->modify('+1 month');

    $totals = query_revenue_totals(
        "DATE_FORMAT(o.`date`, '%Y-%m-%d')",
        $user_id,
        $start->format('Y-m-d'),
        $end->format('Y-m-d'),
        $product_id
    );

    $details = query_revenue_details('day', $user_id, $start->format('Y-m-d'), $end->format('Y-m-d'), $product_id);

    $labels = [];
    $values = [];
    $sum = 0.0;

    for ($day = clone $start; $day < $end; $day->modify('+1 day')) {
        $key = $day->format('Y-m-d');
        $value = $totals[$key] ?? 0.0;

        $labels[] = $key;
        $values[] = $value;
        $sum += $value;
    }

    return ['labels' => $labels, 'values' => $values, 'total' => round($sum, 2), 'details' => $details];
}

/**
 * Consulta compartilhada: faturamento agrupado por uma expressão de data FIXA
 * (nunca vinda do usuário) no intervalo [$from, $to). Retorna [chave => total].
 */
function query_revenue_totals($group_expr, $user_id, $from, $to, $product_id = null)
{
    $conn = conn();

    $sql = "
    SELECT
        $group_expr AS k,
        ROUND(SUM(s.quantity * CAST(s.price AS DECIMAL(10,2))), 2) AS total
    FROM sales s
    INNER JOIN orders o ON o.id = s.order_id
    WHERE o.seller_id = ?
    AND o.status = 'completed'
    AND o.`date` >= ?
    AND o.`date` < ?
    ";
    $types = 'iss';
    $params = [$user_id, $from, $to];

    if ($product_id !== null) {
        $sql .= " AND s.product_id = ?";
        $types .= 'i';
        $params[] = $product_id;
    }

    $sql .= " GROUP BY k ORDER BY k";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $totals = [];
    foreach ($rows as $row) {
        $totals[$row['k']] = (float) $row['total'];
    }

    return $totals;
}

/**
 * Detalhe das vendas que formam cada barra do gráfico (balão ao passar o mouse). Mesmo filtro
 * de query_revenue_totals: pedidos 'completed' do vendedor em [$from, $to), produto opcional.
 *
 * O nome do produto vem de LEFT JOIN SEM filtrar deleted_at: produto excluído logicamente
 * continua aparecendo com o nome no histórico.
 *
 * $granularity = 'day'   -> ['YYYY-MM-DD' => [['hour' => 'HH:MM', 'product' => nome,
 *                            'quantity' => int, 'total' => float], ...]]   (em ordem de horário)
 * $granularity = 'month' -> ['YYYY-MM' => [['product' => nome, 'quantity' => int,
 *                            'total' => float], ...]]   (somado por produto, maior valor primeiro)
 */
function query_revenue_details($granularity, $user_id, $from, $to, $product_id = null)
{
    $conn = conn();

    $sql = "
    SELECT
        DATE_FORMAT(o.`date`, '%Y-%m-%d') AS d,
        DATE_FORMAT(o.`date`, '%H:%i') AS h,
        s.product_id,
        p.name AS product_name,
        s.quantity,
        ROUND(s.quantity * CAST(s.price AS DECIMAL(10,2)), 2) AS total
    FROM sales s
    INNER JOIN orders o ON o.id = s.order_id
    LEFT JOIN products p ON p.id = s.product_id
    WHERE o.seller_id = ?
    AND o.status = 'completed'
    AND o.`date` >= ?
    AND o.`date` < ?
    ";
    $types = 'iss';
    $params = [$user_id, $from, $to];

    if ($product_id !== null) {
        $sql .= " AND s.product_id = ?";
        $types .= 'i';
        $params[] = $product_id;
    }

    $sql .= " ORDER BY o.`date`, s.id";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $details = [];

    foreach ($rows as $row) {
        $name = $row['product_name'] ?? 'Produto removido';

        if ($granularity === 'day') {
            $details[$row['d']][] = [
                'hour' => $row['h'],
                'product' => $name,
                'quantity' => (int) $row['quantity'],
                'total' => (float) $row['total'],
            ];

            continue;
        }

        $key = substr($row['d'], 0, 7);
        $product_key = (int) $row['product_id'];

        if (!isset($details[$key][$product_key])) {
            $details[$key][$product_key] = ['product' => $name, 'quantity' => 0, 'total' => 0.0];
        }

        $details[$key][$product_key]['quantity'] += (int) $row['quantity'];
        $details[$key][$product_key]['total'] = round($details[$key][$product_key]['total'] + (float) $row['total'], 2);
    }

    if ($granularity !== 'day') {
        foreach ($details as $key => $products) {
            $list = array_values($products);
            usort($list, fn($a, $b) => $b['total'] <=> $a['total']);
            $details[$key] = $list;
        }
    }

    return $details;
}

/**
 * Faturamento e itens vendidos de um mês ("YYYY-MM", já validado pelo controller) ou,
 * com $month = null, de todo o período do gráfico ("Todos os meses").
 * Mesma regra do restante: só pedidos 'completed'.
 *
 * @return array{revenue: float, items: int}
 */
function get_month_summary($user_id, $month = null)
{
    if ($month === null) {
        $period = get_revenue_period($user_id);

        if (!$period['has_sales']) {
            return ['revenue' => 0.0, 'items' => 0];
        }

        $from = $period['start']->format('Y-m-d');
        $to = $period['end']->format('Y-m-d');
    } else {
        $start = DateTime::createFromFormat('!Y-m-d', $month . '-01');
        $from = $start->format('Y-m-d');
        $to = (clone $start)->modify('+1 month')->format('Y-m-d');
    }

    $conn = conn();

    $stmt = $conn->prepare("
    SELECT
        COALESCE(ROUND(SUM(s.quantity * CAST(s.price AS DECIMAL(10,2))), 2), 0) AS revenue,
        COALESCE(SUM(s.quantity), 0) AS items
    FROM sales s
    INNER JOIN orders o ON o.id = s.order_id
    WHERE o.seller_id = ?
    AND o.status = 'completed'
    AND o.`date` >= ?
    AND o.`date` < ?
    ");
    $stmt->bind_param('iss', $user_id, $from, $to);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return ['revenue' => (float) $row['revenue'], 'items' => (int) $row['items']];
}

?>
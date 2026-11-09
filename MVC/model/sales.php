<?php require_once(__DIR__ . "/../config/cashfy.php");

function get_sales()
{
    $conn = conn();

    $stmt = $conn->prepare("
    SELECT * FROM sales
    ORDER BY date DESC
    LIMIT 10;
    ");
    $stmt->execute();

    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $result;
}

function register_sale(int $user_id, int $product_id, int $quantity, float $price, string $date)
{
    $conn = conn();

    $date = date('y-m-d', strtotime($date));

    $stmt = $conn->prepare("
    INSERT INTO sales (user_id, product_id, quantity, price, date) VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiids", $user_id, $product_id, $quantity, $price, $date);

    $stmt->execute();
    $stmt->close();

    return;
}

function get_total_week(int $user_id)
{
    $conn = conn();

    $stmt = $conn->prepare("
    SELECT SUM(price) AS 'total_week' FROM sales WHERE user_id = ?
    AND date >= CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY
    AND date < CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY + INTERVAL 7 DAY
    ");
    $stmt->bind_param('i', $user_id);

    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $total = number_format($result['total_week'], 2, ',', '.');

    return $total;
}

function get_total_month(int $user_id)
{
    $conn = conn();

    $stmt = $conn->prepare("
    SELECT SUM(price) AS 'total_month'
    FROM sales
    WHERE user_id = ?
    AND date >= CURDATE() - INTERVAL (DAY(CURDATE()) - 1) DAY
    AND date < DATE_ADD(
        CURDATE() - INTERVAL (DAY(CURDATE()) - 1) DAY,
        INTERVAL 1 MONTH
    );
    ");
    $stmt->bind_param('i', $user_id);

    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    $total = number_format($result['total_month'], 2, ',', '.');

    return $total;
}

function get_saled_items_month(int $user_id)
{
    $conn = conn();

    $stmt = $conn->prepare("
    SELECT SUM(quantity) as 'saled_items'
    FROM sales
    WHERE user_id = ?
    AND date >= CURDATE() - INTERVAL DAY(CURDATE() - 1) DAY
    AND date < DATE_ADD(
        CURDATE() - INTERVAL (DAY(CURDATE()) - 1) DAY,
        INTERVAL 1 MONTH
    );
    ");
    $stmt->bind_param('i', $user_id);

    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    $stmt->close(); 

    return $result['saled_items'];
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
        DATE_FORMAT(MIN(`date`), '%Y-%m-01') AS first_month,
        DATE_FORMAT(CURDATE(), '%Y-%m-01') AS current_month
    FROM sales
    WHERE user_id = ?
    AND `date` < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
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
 * @return array{labels: string[], values: float[], total: float}  labels no formato "YYYY-MM"
 */
function get_monthly_revenue_series($user_id, $product_id = null, $max_months = 24)
{
    $period = get_revenue_period($user_id, $max_months);

    if (!$period['has_sales']) {
        return ['labels' => [], 'values' => [], 'total' => 0.0];
    }

    $totals = query_revenue_totals(
        "DATE_FORMAT(`date`, '%Y-%m')",
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

    return ['labels' => $labels, 'values' => $values, 'total' => round($sum, 2)];
}

/**
 * Faturamento por dia dentro de um mês ("YYYY-MM", já validado pelo controller),
 * com todos os dias do mês (dias sem venda = 0). Aceita filtro por produto.
 *
 * @return array{labels: string[], values: float[], total: float}  labels no formato "YYYY-MM-DD"
 */
function get_daily_revenue_series($user_id, $month, $product_id = null)
{
    $start = DateTime::createFromFormat('!Y-m-d', $month . '-01');
    $end = (clone $start)->modify('+1 month');

    $totals = query_revenue_totals(
        "DATE_FORMAT(`date`, '%Y-%m-%d')",
        $user_id,
        $start->format('Y-m-d'),
        $end->format('Y-m-d'),
        $product_id
    );

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

    return ['labels' => $labels, 'values' => $values, 'total' => round($sum, 2)];
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
        ROUND(SUM(quantity * CAST(price AS DECIMAL(10,2))), 2) AS total
    FROM sales
    WHERE user_id = ?
    AND `date` >= ?
    AND `date` < ?
    ";
    $types = 'iss';
    $params = [$user_id, $from, $to];

    if ($product_id !== null) {
        $sql .= " AND product_id = ?";
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

?>
<?php session_start();
require_once(__DIR__ . '/../model/user.php');
require_once(__DIR__ . '/../model/products.php');
require_once(__DIR__ . '/../model/sales.php');

/*
 * Endpoint JSON do gráfico de faturamento do painel do vendedor.
 *
 * GET revenue_chart.php?mode=month&month=all|YYYY-MM
 * GET revenue_chart.php?mode=product&product_id=<id>&month=all|YYYY-MM
 *
 * month=all     -> histórico mensal (uma barra por mês)
 * month=YYYY-MM -> faturamento por dia daquele mês
 *
 * O vendedor vem SEMPRE da sessão. Qualquer "user_id" enviado pelo navegador é ignorado.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function json_response($status, $payload)
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(405, ['ok' => false, 'error' => 'Método não permitido.']);
}

// Autenticação: sessão válida
if (empty($_SESSION['id'])) {
    json_response(401, ['ok' => false, 'error' => 'Sessão expirada. Faça login novamente.']);
}

try {
    // Autorização: apenas vendedores (role_id 2), mesma regra do painel
    $user = get_user_by_id((int) $_SESSION['id']);

    if (!$user || (int) $user['role_id'] !== 2) {
        json_response(403, ['ok' => false, 'error' => 'Acesso negado.']);
    }

    $user_id = (int) $user['id'];

    // Validação do modo (lista permitida)
    $mode = $_GET['mode'] ?? 'month';

    if (!in_array($mode, ['month', 'product'], true)) {
        json_response(400, ['ok' => false, 'error' => 'Modo de visualização inválido.']);
    }

    // Validação do mês: "all" ou YYYY-MM
    $month = $_GET['month'] ?? 'all';

    if ($month !== 'all') {
        if (!is_string($month) || !preg_match('/^(20\d{2})-(0[1-9]|1[0-2])$/', $month)) {
            json_response(400, ['ok' => false, 'error' => 'Mês inválido.']);
        }
    }

    $product = null;
    $product_id = null;

    if ($mode === 'product') {
        $product_id = filter_var($_GET['product_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($product_id === false || $product_id === null) {
            json_response(400, ['ok' => false, 'error' => 'Selecione um produto válido.']);
        }

        // O produto precisa pertencer ao vendedor autenticado.
        // Responde 404 tanto para "não existe" quanto para "é de outro vendedor",
        // para não revelar a existência de produtos alheios.
        $product = get_product_of_user($product_id, $user_id);

        if (!$product) {
            json_response(404, ['ok' => false, 'error' => 'Produto não encontrado.']);
        }

        $product_id = (int) $product['id'];
    }

    $series = $month === 'all'
        ? get_monthly_revenue_series($user_id, $product_id)
        : get_daily_revenue_series($user_id, $month, $product_id);

    json_response(200, [
        'ok' => true,
        'mode' => $mode,
        'month' => $month,
        'granularity' => $month === 'all' ? 'month' : 'day',
        'product' => $product ? ['id' => (int) $product['id'], 'name' => $product['name']] : null,
        'labels' => $series['labels'],
        'values' => $series['values'],
        'total' => $series['total'],
    ]);
} catch (Throwable $e) {
    error_log('revenue_chart: ' . $e->getMessage());
    json_response(500, ['ok' => false, 'error' => 'Não foi possível carregar os dados do gráfico.']);
}

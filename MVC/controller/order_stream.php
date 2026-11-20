<?php session_start();
require_once(__DIR__ . '/../model/user.php');
require_once(__DIR__ . '/../model/orders.php');

/*
 * SSE (Server-Sent Events) unificado de pedidos:
 * - Para vendedores (role_id == 2): envia evento 'orders' quando os pedidos recebidos (pendentes) mudam.
 * - Para todos os usuários logados (compradores e vendedores que compraram de outras lojas):
 *   envia evento 'buyer_orders' com o estado dos pedidos realizados pelo usuário, permitindo
 *   notificar em tempo real quando um pedido é aceito ('accepted'), recusado ('rejected', com
 *   o motivo em explanation) ou concluído ('completed').
 *
 * A conexão dura ~30s e é encerrada de propósito para não prender processos PHP.
 * O EventSource reconecta automaticamente.
 */

if (empty($_SESSION['id'])) {
    http_response_code(401);
    exit;
}

$user_id = (int) $_SESSION['id'];
$user = get_user_by_id($user_id);

if (!$user) {
    http_response_code(403);
    exit;
}

$is_seller = ((int) $user['role_id'] === 2);

// Libera a sessão: sem isso o stream trava as outras páginas do mesmo usuário
session_write_close();

ignore_user_abort(false);
set_time_limit(60);

ini_set('zlib.output_compression', '0');
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-store');
header('X-Accel-Buffering: no');

echo "retry: 2000\n\n";
flush();

$started = time();
$last_seller_sent = null;
$last_buyer_hash = null;
$last_beat = time();

while (!connection_aborted() && (time() - $started) < 30) {
    $sent_anything = false;

    if ($is_seller) {
        $signature = get_order_signature($user_id);
        $key = $signature['pending'] . '-' . $signature['last_id'];

        if ($key !== $last_seller_sent) {
            $last_seller_sent = $key;
            $sent_anything = true;

            $signature['latest'] = $signature['pending'] > 0 ? get_latest_pending_order($user_id) : null;

            echo "event: orders\n";
            echo 'data: ' . json_encode($signature, JSON_UNESCAPED_UNICODE) . "\n\n";
            flush();
        }
    }

    $buyer_state = get_buyer_orders_state($user_id);
    if ($buyer_state['hash'] !== $last_buyer_hash) {
        $last_buyer_hash = $buyer_state['hash'];
        $sent_anything = true;

        echo "event: buyer_orders\n";
        echo 'data: ' . json_encode([
            'hash' => $buyer_state['hash'],
            'orders' => array_values($buyer_state['map']),
        ], JSON_UNESCAPED_UNICODE) . "\n\n";
        flush();
    }

    if ($sent_anything) {
        $last_beat = time();
    } elseif (time() - $last_beat >= 10) {
        $last_beat = time();
        echo ": ping\n\n";
        flush();
    }

    sleep(2);
}
?>
<?php session_start();
require_once(__DIR__ . '/../model/user.php');
require_once(__DIR__ . '/../model/orders.php');

/*
 * SSE (Server-Sent Events) dos pedidos do vendedor.
 *
 * O navegador abre UMA conexão (EventSource) e o servidor só escreve nela quando
 * os pedidos pendentes mudam. Quem confere o banco é o servidor, não o navegador:
 * o cliente não fica perguntando "tem pedido novo?".
 *
 * A conexão dura ~30s e é encerrada de propósito (não deixa um processo PHP preso
 * para sempre). O EventSource reconecta sozinho e, a cada conexão, o servidor envia
 * o estado atual, então nada se perde entre uma conexão e outra.
 */

if (empty($_SESSION['id'])) {
    http_response_code(401);
    exit;
}

$user_id = (int) $_SESSION['id'];
$user = get_user_by_id($user_id);

if (!$user || (int) $user['role_id'] !== 2) {
    http_response_code(403);
    exit;
}

// Libera a sessão: sem isso o stream trava as outras páginas do mesmo usuário
session_write_close();

ignore_user_abort(false);
set_time_limit(60); // no Windows/XAMPP o sleep() conta no tempo de execução

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
$last_sent = null;
$last_beat = time();

while (!connection_aborted() && (time() - $started) < 30) {
    $signature = get_order_signature($user_id);
    $key = $signature['pending'] . '-' . $signature['last_id'];

    if ($key !== $last_sent) {
        $last_sent = $key;
        $last_beat = time();

        // Dados do pedido mais novo, só para montar o texto da notificação
        $signature['latest'] = $signature['pending'] > 0 ? get_latest_pending_order($user_id) : null;

        echo "event: orders\n";
        echo 'data: ' . json_encode($signature, JSON_UNESCAPED_UNICODE) . "\n\n";
        flush();
    } elseif (time() - $last_beat >= 10) {
        // Comentário SSE: mantém a conexão viva e deixa o PHP perceber se o navegador saiu
        $last_beat = time();

        echo ": ping\n\n";
        flush();
    }

    sleep(2);
}

?>

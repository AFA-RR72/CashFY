<?php
session_start();
require_once(__DIR__ . '/../config/init.php');
require_once(__DIR__ . '/../config/vapid.php');
require_once(__DIR__ . '/../model/user.php');
require_once(__DIR__ . '/../model/push_subscription.php');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function push_json_response(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (empty($_SESSION['id'])) {
    push_json_response(401, ['ok' => false, 'error' => 'Sessão não autenticada.']);
}

$user_id = (int) $_SESSION['id'];
$user = get_user_by_id($user_id);

if (!$user) {
    push_json_response(403, ['ok' => false, 'error' => 'Usuário inválido.']);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $vapid = get_vapid_config();
    push_json_response(200, [
        'ok' => true,
        'publicKey' => $vapid['public_key'],
    ]);
}

$rawBody = (string) file_get_contents('php://input');
$body = json_decode($rawBody, true);
if (!is_array($body)) {
    push_json_response(400, ['ok' => false, 'error' => 'Payload JSON inválido.']);
}

if ($method === 'POST') {
    $subscription = isset($body['subscription']) && is_array($body['subscription'])
        ? $body['subscription']
        : $body;

    if (!empty($body['oldEndpoint']) && is_string($body['oldEndpoint'])) {
        delete_push_subscription_by_endpoint($body['oldEndpoint'], $user_id);
    }

    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $saved = save_push_subscription($user_id, $subscription, $userAgent);

    if (!$saved) {
        push_json_response(422, ['ok' => false, 'error' => 'Subscription inválida ou incompleta.']);
    }

    $_SESSION['push_endpoint'] = (string) ($subscription['endpoint'] ?? '');

    push_json_response(200, ['ok' => true]);
}

if ($method === 'DELETE') {
    $endpoint = trim((string) ($body['endpoint'] ?? ($_SESSION['push_endpoint'] ?? '')));
    if ($endpoint === '') {
        push_json_response(400, ['ok' => false, 'error' => 'Endpoint não informado.']);
    }

    delete_push_subscription_by_endpoint($endpoint, $user_id);
    unset($_SESSION['push_endpoint']);

    push_json_response(200, ['ok' => true]);
}

push_json_response(405, ['ok' => false, 'error' => 'Método não permitido.']);
?>

<?php session_start();
require_once(__DIR__ . '/../model/user.php');
require_once(__DIR__ . '/../model/orders.php');

/*
 * Ações do vendedor sobre os pedidos recebidos (POST):
 *   action=accept   pending  -> accepted
 *   action=reject   pending  -> rejected
 *   action=complete accepted -> completed (aceita items[sale_id][quantity|price] para ajustar valores)
 *
 * O vendedor vem SEMPRE da sessão; o pedido precisa ser dele.
 */

$back = '../view/perfil.php?orders#msg';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $back");
    exit;
}

if (empty($_SESSION['id'])) {
    header('Location: ../view/login.php');
    exit;
}

$user = get_user_by_id((int) $_SESSION['id']);

if (!$user || (int) $user['role_id'] !== 2) {
    header('Location: ../../index.php');
    exit;
}

$seller_id = (int) $user['id'];
$order_id = (int) ($_POST['order_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($order_id <= 0) {
    $_SESSION['msg-form'] = 'Pedido inválido';
    header("Location: $back");
    exit;
}

if ($action === 'accept') {
    $ok = change_order_status($order_id, $seller_id, 'pending', 'accepted');
    $_SESSION['msg-form'] = $ok ? 'Pedido aceito' : 'Não foi possível aceitar este pedido';
} elseif ($action === 'reject') {
    $ok = change_order_status($order_id, $seller_id, 'pending', 'rejected');
    $_SESSION['msg-form'] = $ok ? 'Pedido recusado' : 'Não foi possível recusar este pedido';
} elseif ($action === 'complete') {
    $edits = [];

    foreach (($_POST['items'] ?? []) as $sale_id => $item) {
        $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $price = filter_var($item['price'] ?? null, FILTER_VALIDATE_FLOAT);

        if ($quantity === false || $price === false || $price <= 0) {
            $_SESSION['msg-form'] = 'Quantidade e valor precisam ser maiores que zero';
            header("Location: $back");
            exit;
        }

        $edits[(int) $sale_id] = ['quantity' => $quantity, 'price' => $price];
    }

    $ok = complete_order($order_id, $seller_id, $edits);
    $_SESSION['msg-form'] = $ok ? 'Pedido concluído' : 'Não foi possível concluir este pedido';
} else {
    $_SESSION['msg-form'] = 'Ação inválida';
}

header("Location: $back");
exit;

?>

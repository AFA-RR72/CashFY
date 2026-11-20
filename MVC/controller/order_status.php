<?php session_start();
require_once(__DIR__ . '/../config/init.php');
require_once(__DIR__ . '/../model/user.php');
require_once(__DIR__ . '/../model/orders.php');
require_once(__DIR__ . '/../model/push_subscription.php');

/*
 * Ações do vendedor sobre os pedidos recebidos (POST):
 *   action=accept   pending  -> accepted
 *   action=reject   pending  -> rejected (exige explanation: o motivo, 5 a 255 caracteres, e repõe estoque)
 *   action=complete accepted -> completed (aceita items[sale_id][quantity|price] para ajustar valores/estoque)
 *
 * O vendedor vem SEMPRE da sessão; o pedido precisa ser dele.
 */

$allowed_returns = [
    'perfil.php?orders' => '../view/perfil.php?orders#msg',
    'myOrders.php?scope=received' => '../view/myOrders.php?scope=received#msg',
];
$return_to = (string) ($_POST['return_to'] ?? 'perfil.php?orders');
$back = $allowed_returns[$return_to] ?? '../view/perfil.php?orders#msg';

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

    if ($ok) {
        $order = get_order_by_id($order_id);
        if ($order && (int) $order['buyer_id'] !== $seller_id) {
            $buyer_is_seller = ((int) ($order['buyer_role_id'] ?? 3) === 2);
            $target_url = BASE_URL . 'MVC/view/myOrders.php' . ($buyer_is_seller ? '?scope=mine' : '');
            $formatted_total = 'R$ ' . number_format((float) $order['total'], 2, ',', '.');

            send_push_notification_to_user((int) $order['buyer_id'], [
                'title' => 'Pedido #' . $order_id . ' aceito!',
                'body' => $order['seller_name'] . ' aceitou seu pedido (' . $formatted_total . '). Combine a retirada ou entrega.',
                'url' => $target_url,
                'tag' => 'cashfy-buyer-order-' . $order_id . '-accepted',
                'order_id' => $order_id,
                'status' => 'accepted',
            ]);
        }
    }
} elseif ($action === 'reject') {
    $explanation = trim((string) ($_POST['explanation'] ?? ''));
    $explanation = preg_replace('/\s+/u', ' ', $explanation);
    $length = mb_strlen($explanation, 'UTF-8');

    if ($length < 5 || $length > 255) {
        $_SESSION['msg-form'] = 'Informe o motivo da recusa (de 5 a 255 caracteres)';
    } else {
        $ok = reject_order($order_id, $seller_id, $explanation);
        $_SESSION['msg-form'] = $ok ? 'Pedido recusado' : 'Não foi possível recusar este pedido';

        if ($ok) {
            $order = get_order_by_id($order_id);
            if ($order && (int) $order['buyer_id'] !== $seller_id) {
                $buyer_is_seller = ((int) ($order['buyer_role_id'] ?? 3) === 2);
                $target_url = BASE_URL . 'MVC/view/myOrders.php' . ($buyer_is_seller ? '?scope=mine#recusados' : '#recusados');

                send_push_notification_to_user((int) $order['buyer_id'], [
                    'title' => 'Pedido #' . $order_id . ' recusado',
                    'body' => $order['seller_name'] . ' recusou seu pedido. Motivo: ' . $explanation,
                    'url' => $target_url,
                    'tag' => 'cashfy-buyer-order-' . $order_id . '-rejected',
                    'order_id' => $order_id,
                    'status' => 'rejected',
                    'reason' => $explanation,
                ]);
            }
        }
    }
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

    $error_msg = null;
    $ok = complete_order($order_id, $seller_id, $edits, $error_msg);
    $_SESSION['msg-form'] = $ok ? 'Pedido concluído' : ($error_msg ?: 'Não foi possível concluir este pedido');

    if ($ok) {
        $order = get_order_by_id($order_id);
        if ($order && (int) $order['buyer_id'] !== $seller_id) {
            $buyer_is_seller = ((int) ($order['buyer_role_id'] ?? 3) === 2);
            $target_url = BASE_URL . 'MVC/view/myOrders.php' . ($buyer_is_seller ? '?scope=mine#concluidos' : '#concluidos');
            $formatted_total = 'R$ ' . number_format((float) $order['total'], 2, ',', '.');

            send_push_notification_to_user((int) $order['buyer_id'], [
                'title' => 'Pedido #' . $order_id . ' concluído!',
                'body' => 'Sua compra com ' . $order['seller_name'] . ' (' . $formatted_total . ') foi marcada como concluída.',
                'url' => $target_url,
                'tag' => 'cashfy-buyer-order-' . $order_id . '-completed',
                'order_id' => $order_id,
                'status' => 'completed',
            ]);
        }
    }
} else {
    $_SESSION['msg-form'] = 'Ação inválida';
}

header("Location: $back");
exit;
?>

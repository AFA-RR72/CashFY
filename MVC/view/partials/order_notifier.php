<?php
/*
 * Mantém o SSE de pedidos ligado em qualquer página para o vendedor logado, para que
 * as notificações cheguem mesmo fora do perfil. Para quem não é vendedor não imprime nada.
 */
require_once(__DIR__ . '/../../config/init.php');
require_once(__DIR__ . '/../../model/user.php');
require_once(__DIR__ . '/../../model/orders.php');

$notifier_user = !empty($_SESSION['id']) ? get_user_by_id((int) $_SESSION['id']) : null;

if ($notifier_user && (int) $notifier_user['role_id'] === 2):
    $notifier_sig = get_order_signature((int) $notifier_user['id']);
?>
    <link rel="stylesheet" href="<?= BASE_URL; ?>assets/css/orders.css">
    <span id="orders-notify" data-sw-url="<?= BASE_URL; ?>service-worker.js" hidden
        data-endpoint="<?= BASE_URL; ?>MVC/controller/order_stream.php"
        data-orders-url="<?= BASE_URL; ?>MVC/view/perfil.php?orders"
        data-pending="<?= $notifier_sig['pending']; ?>"
        data-last-id="<?= $notifier_sig['last_id']; ?>"></span>
    <script src="<?= BASE_URL; ?>assets/js/orders-sse.js"></script>
<?php endif; ?>

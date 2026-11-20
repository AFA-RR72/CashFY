<?php
/*
 * Mantém o Web Push + SSE de pedidos ativos em qualquer página para qualquer usuário logado:
 * - Compradores recebem notificações Web Push (mesmo com o navegador fechado) e avisos em tempo real
 *   quando seus pedidos são aceitos, recusados (com motivo) ou concluídos.
 * - Vendedores recebem notificações de novos pedidos pendentes e atualizações de compras próprias.
 */
require_once(__DIR__ . '/../../config/init.php');
require_once(__DIR__ . '/../../config/vapid.php');
require_once(__DIR__ . '/../../model/user.php');
require_once(__DIR__ . '/../../model/orders.php');

$notifier_user = !empty($_SESSION['id']) ? get_user_by_id((int) $_SESSION['id']) : null;

if ($notifier_user):
    $notifier_uid = (int) $notifier_user['id'];
    $notifier_role = (int) $notifier_user['role_id'];
    $notifier_sig = $notifier_role === 2 ? get_order_signature($notifier_uid) : ['pending' => 0, 'last_id' => 0];
    $buyer_state = get_buyer_orders_state($notifier_uid);
    $vapid_cfg = get_vapid_config();
?>
    <link rel="stylesheet" href="<?= BASE_URL; ?>assets/css/orders.css">
    <span id="orders-notify" data-sw-url="<?= BASE_URL; ?>service-worker.js" hidden
        data-user-id="<?= $notifier_uid; ?>"
        data-role-id="<?= $notifier_role; ?>"
        data-endpoint="<?= BASE_URL; ?>MVC/controller/order_stream.php"
        data-push-endpoint="<?= BASE_URL; ?>MVC/controller/push_subscription.php"
        data-vapid-public-key="<?= htmlspecialchars($vapid_cfg['public_key'], ENT_QUOTES); ?>"
        data-orders-url="<?= BASE_URL; ?>MVC/view/perfil.php?orders"
        data-my-orders-url="<?= BASE_URL; ?>MVC/view/myOrders.php<?= $notifier_role === 2 ? '?scope=mine' : ''; ?>"
        data-pending="<?= $notifier_sig['pending']; ?>"
        data-last-id="<?= $notifier_sig['last_id']; ?>"
        data-buyer-hash="<?= htmlspecialchars($buyer_state['hash'], ENT_QUOTES); ?>"
        data-buyer-orders="<?= htmlspecialchars(json_encode(array_values($buyer_state['map']), JSON_UNESCAPED_UNICODE), ENT_QUOTES); ?>"></span>
    <script src="<?= BASE_URL; ?>assets/js/orders-sse.js"></script>
<?php endif; ?>

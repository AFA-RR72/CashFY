<?php
/*
 * Barra de navegação inferior flutuante (Floating Bottom Navigation) para mobile.
 *
 * Organização adaptativa:
 *   - Deslogado: Início | Carrinho | Entrar
 *   - Comprador (role_id = 3): Início | Pedidos | Carrinho | Perfil
 *   - Vendedor (role_id = 2): Início | Pedidos | Carrinho | Perfil
 *
 * Comprador e Vendedor possuem exatamente a mesma organização visual.
 * Para ambos, "Pedidos" leva a myOrders.php (onde o vendedor possui as abas
 * "Recebidos" e "Feitos por mim").
 */
require_once(__DIR__ . '/../../config/init.php');
require_once(__DIR__ . '/../../model/user.php');
require_once(__DIR__ . '/../../model/orders.php');

$bnav_user = !empty($_SESSION['id']) ? get_user_by_id((int) $_SESSION['id']) : null;
$bnav_logged = !empty($bnav_user);
$bnav_role = $bnav_logged ? (int) $bnav_user['role_id'] : 0;

// Caminho limpo via parse_url (independente de query strings GET ou hashes)
$bnav_path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$bnav_script = basename($_SERVER['SCRIPT_NAME'] ?? $bnav_path);
if ($bnav_script === '' || $bnav_script === 'CashFY') {
    $bnav_script = 'index.php';
}

// Identificação confiável do item ativo considerando abas internas e parâmetros GET
$bnav_is_seller_orders_tab = ($bnav_script === 'perfil.php' && isset($_GET['orders']));

$bnav_active = '';
if (in_array($bnav_script, ['index.php', 'categoria.php', 'seller.php'], true)) {
    $bnav_active = 'home';
} elseif ($bnav_script === 'myOrders.php' || $bnav_is_seller_orders_tab) {
    $bnav_active = 'orders';
} elseif (in_array($bnav_script, ['perfil.php', 'perfil_photo.php', 'new_product.php', 'update_product.php'], true) && !$bnav_is_seller_orders_tab) {
    $bnav_active = 'profile';
} elseif (in_array($bnav_script, ['login.php', 'cadastro.php'], true)) {
    $bnav_active = 'login';
}

// Quantidade total de itens no carrinho
$bnav_cart_items = $_SESSION['cart'] ?? [];
$bnav_cart_qty = array_sum(array_map(static fn($item) => (int) ($item['quantity'] ?? 0), $bnav_cart_items));
$bnav_cart_anim = !empty($_SESSION['cart-msg']['ok']);

// Contador de pedidos pendentes para vendedor
$bnav_pending_orders = 0;
if ($bnav_logged && $bnav_role === 2) {
    $bnav_sig = get_order_signature((int) $bnav_user['id']);
    $bnav_pending_orders = (int) ($bnav_sig['pending'] ?? 0);
}
?>

<nav class="floating-bottom-nav" aria-label="Navegação principal mobile">
    <a href="<?= BASE_URL; ?>index.php"
       class="fbn-item<?= $bnav_active === 'home' ? ' is-active' : ''; ?>"
       <?= $bnav_active === 'home' ? 'aria-current="page"' : ''; ?>>
        <span class="fbn-icon">
            <i class="fa-solid fa-house" aria-hidden="true"></i>
        </span>
        <span class="fbn-label">Início</span>
    </a>

    <?php if ($bnav_logged): ?>
        <a href="<?= BASE_URL; ?>MVC/view/myOrders.php"
           class="fbn-item<?= $bnav_active === 'orders' ? ' is-active' : ''; ?>"
           <?= $bnav_active === 'orders' ? 'aria-current="page"' : ''; ?>>
            <span class="fbn-icon">
                <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                <?php if ($bnav_role === 2): ?>
                    <span class="fbn-badge" id="bottom-nav-orders-badge" <?= $bnav_pending_orders > 0 ? '' : 'hidden'; ?>>
                        <?= $bnav_pending_orders > 0 ? $bnav_pending_orders : ''; ?>
                    </span>
                <?php endif; ?>
            </span>
            <span class="fbn-label">Pedidos</span>
        </a>
    <?php endif; ?>

    <button type="button"
            class="fbn-item fbn-cart-btn<?= $bnav_cart_anim ? ' is-animated' : ''; ?>"
            id="bottom-nav-cart-btn"
            onclick="document.getElementById('cart')?.showModal()"
            aria-label="Abrir carrinho">
        <span class="fbn-icon">
            <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
            <?php if ($bnav_cart_qty > 0): ?>
                <span class="fbn-badge is-cart" id="bottom-nav-cart-count"><?= $bnav_cart_qty; ?></span>
            <?php endif; ?>
        </span>
        <span class="fbn-label">Carrinho</span>
    </button>

    <?php if ($bnav_logged): ?>
        <a href="<?= BASE_URL; ?>MVC/view/perfil.php"
           class="fbn-item<?= $bnav_active === 'profile' ? ' is-active' : ''; ?>"
           <?= $bnav_active === 'profile' ? 'aria-current="page"' : ''; ?>>
            <span class="fbn-icon">
                <i class="fa-solid fa-user" aria-hidden="true"></i>
            </span>
            <span class="fbn-label">Perfil</span>
        </a>
    <?php else: ?>
        <a href="<?= BASE_URL; ?>MVC/view/login.php"
           class="fbn-item<?= $bnav_active === 'login' ? ' is-active' : ''; ?>"
           <?= $bnav_active === 'login' ? 'aria-current="page"' : ''; ?>>
            <span class="fbn-icon">
                <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
            </span>
            <span class="fbn-label">Entrar</span>
        </a>
    <?php endif; ?>
</nav>
<?php session_start();
require_once(__DIR__ . '/../config/init.php');
require_once(__DIR__ . '/../config/cart_return.php');
require_once(__DIR__ . '/../model/products.php');
require_once(__DIR__ . '/../model/orders.php');
require_once(__DIR__ . '/../model/push_subscription.php');

// Só aceita o envio do formulário do carrinho
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Precisa estar logado para comprar
if (empty($_SESSION['id'])) {
    header('Location: ' . BASE_URL . 'MVC/view/login.php');
    exit;
}

$buyer_id = (int) $_SESSION['id'];
$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Volta para a página onde o carrinho foi aberto; se não vier, para a loja do carrinho
$seller_id = (int) reset($cart)['seller'];
$back = cart_return_url($_POST['return'] ?? '', BASE_URL . 'MVC/view/seller.php?id=' . $seller_id);

function cart_notice(string $text, bool $ok = false): void
{
    $_SESSION['cart-msg'] = ['text' => $text, 'ok' => $ok];
}

if ($buyer_id === $seller_id) {
    cart_notice('Você não pode finalizar um pedido para a sua própria loja.');
    header("Location: $back");
    exit;
}

/*
 * Os preços, o estoque e o vendedor NUNCA vêm apenas da sessão: cada item é conferido no banco,
 * assim o preço gravado no pedido é o preço real do produto e o estoque é respeitado.
 */
$items = [];
$unavailable = [];
$stock_adjusted = [];

foreach ($cart as $product_id => $cart_item) {
    $product = get_product_by_id((int) $product_id);
    $quantity = (int) $cart_item['quantity'];

    // Produto excluído (ou removido) ou esgotado depois de entrar no carrinho
    if (!$product || $quantity <= 0 || ($product['stock'] !== null && (int) $product['stock'] <= 0)) {
        $unavailable[(int) $product_id] = (string) ($cart_item['product'] ?? ($product['name'] ?? ''));
        continue;
    }

    if ((int) $product['user_id'] !== $seller_id) {
        cart_notice('Só é possível comprar em uma loja por vez');
        header("Location: $back");
        exit;
    }

    if ($product['stock'] !== null && $quantity > (int) $product['stock']) {
        $avail = (int) $product['stock'];
        $_SESSION['cart'][(int) $product_id]['quantity'] = $avail;
        $_SESSION['cart'][(int) $product_id]['price'] = (float) $product['price'];
        $_SESSION['cart'][(int) $product_id]['total'] = $avail * (float) $product['price'];
        $stock_adjusted[] = '"' . $product['name'] . '" (máx. ' . $avail . ')';
        continue;
    }

    $items[] = [
        'product_id' => (int) $product['id'],
        'quantity' => $quantity,
        'price' => (float) $product['price'],
    ];
}

// Se algum produto foi excluído ou esgotou
if (!empty($unavailable)) {
    foreach (array_keys($unavailable) as $product_id) {
        unset($_SESSION['cart'][$product_id]);
    }

    $names = array_map(fn($name) => '"' . $name . '"', array_filter($unavailable, fn($name) => $name !== ''));

    if (empty($names)) {
        $text = 'Um dos produtos do carrinho esgotou ou não está mais disponível e foi removido. Confira o carrinho e tente novamente.';
    } elseif (count($names) === 1) {
        $text = reset($names) . ' esgotou ou não está mais disponível e foi removido do carrinho. Confira o carrinho e tente novamente.';
    } else {
        $text = implode(', ', $names) . ' esgotaram ou não estão mais disponíveis e foram removidos do carrinho. Confira o carrinho e tente novamente.';
    }

    cart_notice($text);
    $_SESSION['open_cart'] = true;
    header("Location: $back");
    exit;
}

// Se algum item tinha quantidade maior que o estoque disponível no momento do checkout
if (!empty($stock_adjusted)) {
    cart_notice('A quantidade de ' . implode(', ', $stock_adjusted) . ' foi ajustada para o estoque disponível. Confira o carrinho e confirme novamente.');
    $_SESSION['open_cart'] = true;
    header("Location: $back");
    exit;
}

$error_msg = null;
$order_id = create_order($buyer_id, $seller_id, $items, 'pending', null, $error_msg);

if ($order_id === false) {
    cart_notice($error_msg ?: 'Não foi possível finalizar a compra. Tente novamente.');
    $_SESSION['open_cart'] = true;
    header("Location: $back");
    exit;
}

$created_order = get_order_by_id((int) $order_id);
if ($created_order) {
    $formatted_total = 'R$ ' . number_format((float) $created_order['total'], 2, ',', '.');
    send_push_notification_to_user($seller_id, [
        'title' => 'Novo pedido no CashFY',
        'body' => 'Pedido #' . (int) $order_id . ' de ' . $created_order['buyer_name'] . ' — ' . $formatted_total,
        'url' => BASE_URL . 'MVC/view/perfil.php?orders',
        'tag' => 'cashfy-seller-order-' . (int) $order_id,
        'order_id' => (int) $order_id,
        'status' => 'pending',
    ]);
}

unset($_SESSION['cart']);
cart_notice('Pedido realizado! Aguarde o vendedor aceitar.', true);

header("Location: $back");
exit;
?>
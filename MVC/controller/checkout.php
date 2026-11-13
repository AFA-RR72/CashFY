<?php session_start();
require_once(__DIR__ . '/../config/init.php');
require_once(__DIR__ . '/../config/cart_return.php');
require_once(__DIR__ . '/../model/products.php');
require_once(__DIR__ . '/../model/orders.php');

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
// (cart.php só permite uma loja por vez)
$seller_id = (int) reset($cart)['seller'];
$back = cart_return_url($_POST['return'] ?? '', BASE_URL . 'MVC/view/seller.php?id=' . $seller_id);

function cart_notice(string $text, bool $ok = false): void
{
    $_SESSION['cart-msg'] = ['text' => $text, 'ok' => $ok];
}

/*
 * Os preços e o vendedor NUNCA vêm da sessão: cada item é conferido no banco,
 * assim o preço gravado no pedido é o preço real do produto.
 */
$items = [];
$unavailable = [];

foreach ($cart as $product_id => $cart_item) {
    // get_product_by_id só devolve produto ativo (deleted_at IS NULL)
    $product = get_product_by_id((int) $product_id);
    $quantity = (int) $cart_item['quantity'];

    // Produto excluído (ou removido) depois de entrar no carrinho: não pode ser comprado
    if (!$product || $quantity <= 0) {
        $unavailable[(int) $product_id] = (string) ($cart_item['product'] ?? '');
        continue;
    }

    if ((int) $product['user_id'] !== $seller_id) {
        cart_notice('Só é possível comprar em uma loja por vez');
        header("Location: $back");
        exit;
    }

    $items[] = [
        'product_id' => (int) $product['id'],
        'quantity' => $quantity,
        'price' => (float) $product['price'],
    ];
}

// Nenhum pedido é criado enquanto houver item indisponível: os itens saem do carrinho
// e o cliente é avisado para conferir o carrinho e finalizar de novo.
if (!empty($unavailable)) {
    foreach (array_keys($unavailable) as $product_id) {
        unset($_SESSION['cart'][$product_id]);
    }

    $names = array_map(fn($name) => '"' . $name . '"', array_filter($unavailable, fn($name) => $name !== ''));

    if (empty($names)) {
        $text = 'Um dos produtos do carrinho não está mais disponível e foi removido. Confira o carrinho e tente novamente.';
    } elseif (count($names) === 1) {
        $text = reset($names) . ' não está mais disponível e foi removido do carrinho. Confira o carrinho e tente novamente.';
    } else {
        $text = implode(', ', $names) . ' não estão mais disponíveis e foram removidos do carrinho. Confira o carrinho e tente novamente.';
    }

    cart_notice($text);
    $_SESSION['open_cart'] = true;
    header("Location: $back");
    exit;
}

$order_id = create_order($buyer_id, $seller_id, $items, 'pending');

if ($order_id === false) {
    cart_notice('Não foi possível finalizar a compra. Tente novamente.');
    $_SESSION['open_cart'] = true;
    header("Location: $back");
    exit;
}

unset($_SESSION['cart']);
cart_notice('Pedido realizado! Aguarde o vendedor aceitar.', true);

header("Location: $back");
exit;

?>

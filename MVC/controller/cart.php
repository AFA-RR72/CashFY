<?php
session_start();

require_once(__DIR__ . '/../config/init.php');
require_once(__DIR__ . '/../config/cart_return.php');
require_once(__DIR__ . '/../model/products.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

function cart_set_notice(string $message, bool $success = false): void
{
    $_SESSION['cart-msg'] = [
        'text' => $message,
        'ok' => $success,
    ];
}

function cart_redirect_back(string $rawReturn, string $fallback): void
{
    header('Location: ' . cart_return_url($rawReturn, $fallback));
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$rawReturn = (string) ($_POST['return'] ?? '');
$fallback = BASE_URL . 'index.php';

/*
 * Atualizações feitas dentro do modal: aumentar, diminuir ou remover.
 */
if (isset($_POST['action'])) {
    $action = (string) $_POST['action'];
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

    if ($productId && isset($cart[$productId])) {
        $product = get_product_by_id($productId);

        // Produto excluído enquanto estava no carrinho: não dá para aumentar a quantidade
        if ($action === 'increase' && !$product) {
            $name = (string) ($cart[$productId]['product'] ?? 'Um dos produtos');

            unset($cart[$productId]);
            $_SESSION['cart'] = $cart;

            cart_set_notice('"' . $name . '" não está mais disponível e foi removido do carrinho.');
            $_SESSION['open_cart'] = true;
            cart_redirect_back($rawReturn, $fallback);
        }

        $quantity = (int) $cart[$productId]['quantity'];

        if ($action === 'increase') {
            if ($product && $product['stock'] !== null) {
                $maxStock = (int) $product['stock'];
                if ($maxStock <= 0) {
                    unset($cart[$productId]);
                    $_SESSION['cart'] = $cart;
                    cart_set_notice('"' . $product['name'] . '" esgotou e foi removido do carrinho.');
                    $_SESSION['open_cart'] = true;
                    cart_redirect_back($rawReturn, $fallback);
                }
                if ($quantity + 1 > $maxStock) {
                    $cart[$productId]['quantity'] = $maxStock;
                    $cart[$productId]['price'] = (float) $product['price'];
                    $cart[$productId]['total'] = $maxStock * (float) $product['price'];
                    $_SESSION['cart'] = $cart;
                    cart_set_notice('Limite de estoque atingido para "' . $product['name'] . '" (' . $maxStock . ' disponível' . ($maxStock > 1 ? 'is' : '') . ').');
                    $_SESSION['open_cart'] = true;
                    cart_redirect_back($rawReturn, $fallback);
                }
            }
            $quantity++;
        } elseif ($action === 'decrease') {
            $quantity--;
        } elseif ($action === 'remove') {
            $quantity = 0;
        }

        if ($quantity <= 0) {
            unset($cart[$productId]);
        } else {
            $price = $product ? (float) $product['price'] : (float) $cart[$productId]['price'];
            $cart[$productId]['price'] = $price;
            $cart[$productId]['quantity'] = $quantity;
            $cart[$productId]['total'] = $quantity * $price;
        }

        $_SESSION['cart'] = $cart;
    }

    $_SESSION['open_cart'] = true;
    cart_redirect_back($rawReturn, $fallback);
}

/*
 * Inclusão de produto a partir de seller.php ou categoria.php.
 */
$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

if (!$productId || !$quantity || $quantity < 1) {
    cart_set_notice('Selecione um produto e uma quantidade válida.');
    cart_redirect_back($rawReturn, $fallback);
}

$product = get_product_by_id($productId);

if (!$product) {
    cart_set_notice('Este produto não está mais disponível.');
    cart_redirect_back($rawReturn, $fallback);
}

$sellerId = (int) $product['user_id'];

// Impede que o vendedor compre produtos da própria loja
if (isset($_SESSION['id']) && (int) $_SESSION['id'] === $sellerId) {
    cart_set_notice('Você não pode comprar produtos da sua própria loja.');
    cart_redirect_back($rawReturn, $fallback);
}

/*
 * O carrinho aceita produtos de apenas um vendedor por vez.
 */
if (!empty($cart)) {
    $currentSellerId = (int) (reset($cart)['seller'] ?? 0);

    if ($currentSellerId !== $sellerId) {
        cart_set_notice('Só é possível comprar em uma loja por vez.');
        cart_redirect_back($rawReturn, $fallback);
    }
}

$currentInCart = isset($cart[$productId]) ? (int) $cart[$productId]['quantity'] : 0;
$newQuantity = $currentInCart + $quantity;

// Validação de estoque (quando stock !== null)
if ($product['stock'] !== null) {
    $availableStock = (int) $product['stock'];

    if ($availableStock <= 0) {
        cart_set_notice('Este produto está esgotado no momento.');
        cart_redirect_back($rawReturn, $fallback);
    }

    if ($newQuantity > $availableStock) {
        $remaining = max(0, $availableStock - $currentInCart);
        if ($remaining === 0) {
            cart_set_notice('Você já adicionou todo o estoque disponível (' . $availableStock . ') deste produto no carrinho.');
        } else {
            cart_set_notice('Estoque insuficiente: restam apenas ' . $availableStock . ' unidade(s) de "' . $product['name'] . '".');
        }
        cart_redirect_back($rawReturn, $fallback);
    }
}

if (isset($cart[$productId])) {
    $cart[$productId]['quantity'] = $newQuantity;
    $cart[$productId]['price'] = (float) $product['price'];
    $cart[$productId]['product'] = $product['name'];
    $cart[$productId]['total'] = $newQuantity * (float) $product['price'];
} else {
    $cart[$productId] = [
        'seller' => $sellerId,
        'product' => $product['name'],
        'price' => (float) $product['price'],
        'quantity' => $quantity,
        'total' => $quantity * (float) $product['price'],
    ];
}

$_SESSION['cart'] = $cart;
cart_set_notice('Produto adicionado ao carrinho.', true);
cart_redirect_back($rawReturn, $fallback);
?>
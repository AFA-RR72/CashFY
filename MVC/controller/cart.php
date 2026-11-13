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
        // Produto excluído enquanto estava no carrinho: não dá para aumentar a quantidade
        // (diminuir e remover continuam permitidos). O item sai do carrinho.
        if ($action === 'increase' && !get_product_by_id($productId)) {
            $name = (string) ($cart[$productId]['product'] ?? 'Um dos produtos');

            unset($cart[$productId]);
            $_SESSION['cart'] = $cart;

            cart_set_notice('"' . $name . '" não está mais disponível e foi removido do carrinho.');
            $_SESSION['open_cart'] = true;
            cart_redirect_back($rawReturn, $fallback);
        }

        $quantity = (int) $cart[$productId]['quantity'];

        if ($action === 'increase') {
            $quantity++;
        } elseif ($action === 'decrease') {
            $quantity--;
        } elseif ($action === 'remove') {
            $quantity = 0;
        }

        if ($quantity <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId]['quantity'] = $quantity;
            $cart[$productId]['total'] = $quantity * (float) $cart[$productId]['price'];
        }

        $_SESSION['cart'] = $cart;
    }

    $_SESSION['open_cart'] = true;
    cart_redirect_back($rawReturn, $fallback);
}

/*
 * Inclusão de produto. O controlador não depende mais de parâmetros GET
 * específicos do perfil do vendedor: funciona também na página de categoria.
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

if (isset($cart[$productId])) {
    $cart[$productId]['quantity'] += $quantity;
    $cart[$productId]['price'] = (float) $product['price'];
    $cart[$productId]['product'] = $product['name'];
    $cart[$productId]['total'] = $cart[$productId]['quantity'] * (float) $product['price'];
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

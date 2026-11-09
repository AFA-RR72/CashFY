<?php session_start();
require_once(__DIR__ . '/../model/products.php');

// Ações dentro do modal do carrinho: aumentar, diminuir e remover
if (isset($_POST['action'])) {
    $params = $_GET;
    unset($params['product']);
    $url = '?' . http_build_query($params);

    $action = $_POST['action'];
    $id = (int) ($_POST['product_id'] ?? 0);

    if ($id > 0 && isset($_SESSION['cart'][$id])) {
        $quantity = (int) $_SESSION['cart'][$id]['quantity'];

        if ($action === 'increase') {
            $quantity++;
        } elseif ($action === 'decrease') {
            $quantity--;
        } elseif ($action === 'remove') {
            $quantity = 0;
        }

        if ($quantity <= 0) {
            unset($_SESSION['cart'][$id]);
        } else {
            $_SESSION['cart'][$id]['quantity'] = $quantity;
            $_SESSION['cart'][$id]['total'] = $quantity * $_SESSION['cart'][$id]['price'];
        }
    }

    $_SESSION['open_cart'] = true;

    header("Location: ../view/seller.php$url");
    exit;
}

if (
    empty($_GET['id']) ||
    empty($_GET['product']) ||
    empty($_POST['quantity']) ||
    $_POST['quantity'] <= 0 ||
    empty($_POST['product_id'])
) {
    header("Location: ../../index.php");
    exit;
} 

$params = $_GET;
$url = '?' . http_build_query($params);

if (check_product($_POST['product_id'])) {
    $product = get_product_by_id($_POST['product_id']);
} else {
    $params['product'] = null;
    header("Location: ../view/seller.php$url");
    exit;
}

if (isset($_SESSION['cart'])){
    $firstItem = (int) reset($_SESSION['cart'])['seller'];
}

if (
    isset($firstItem) &&
    $firstItem !== $product['user_id'] &&
    !empty($_SESSION['cart'])
){
    $_SESSION['msg'] = 'Só é possível comprar em uma loja por vez';
    header("Location: ../view/seller.php$url");
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$id = $product['id'];
$quantity = (int) $_POST['quantity'];

if (isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id]['quantity'] += $quantity;
    $_SESSION['cart'][$id]['total'] =
        $_SESSION['cart'][$id]['quantity'] * $product['price'];
} else {
    $_SESSION['cart'][$id] = [
        'seller' => $product['user_id'],
        'product' => $product['name'],
        'price' => $product['price'],
        'quantity' => $quantity,
        'total' => $quantity * $product['price']
    ];
}

unset($params['product']);
$url = '?' . http_build_query($params);

header("Location: ../view/seller.php$url");
exit;

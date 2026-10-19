<?php session_start();
require_once(__DIR__ . '/../model/products.php');

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

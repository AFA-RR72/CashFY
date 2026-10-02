<?php session_start();
require_once(__DIR__ . '/../model/products.php');

if (
    empty($_GET['id']) ||
    empty($_GET['product']) ||
    empty($_POST['quantity']) ||
    empty($_POST['product_id'])
) {
    header("Location: ../../index.php");
    exit;
}

$params = $_GET;
$params['product'] = $_POST['product_id'];
$url = '?' . http_build_query($params);

$product = get_product_by_id($_POST['product_id']);

$_SESSION['cart'] = ['product', 'price', 'quantity', 'total'];




header("Location: ../view/seller.php$url");
exit;

?>
<?php session_start();
require_once('../model/products.php');

if (!isset($_GET['id'])){
    header('Location: ../../index.php');
    exit;
}

$product = get_product_by_id($_GET['id']);

if ($product && $product['user_id'] == $_SESSION['id']){
    delete_product($product['id']);
    header('Location: ../view/perfil.php?products');
    exit;
} else {
    header('Location: ../../index.php');
    exit;
}

?>
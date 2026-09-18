<?php session_start();
require_once("../model/sales.php");
require_once("../model/products.php");

$product = get_product_by_id($_POST['product']);

if (
    empty($_POST['product']) ||
    empty($_POST['quantity']) ||
    empty($_POST['price']) ||
    empty($_POST['date'])
){
    $_SESSION['msg-form'] = 'Você precisa preencher todos os campos';
    header('Location: ../view/perfil.php?diary#msg');
    exit;
} else{
    register_sale($_SESSION['id'], $product['id'], $_POST['quantity'], $_POST['price'], $_POST['date']);    

    $_SESSION['msg-form'] = 'Venda registrada';
    header('Location: ../view/perfil.php?diary#msg');
    exit;
}

?>
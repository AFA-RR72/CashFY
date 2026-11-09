<?php session_start();
require_once("../model/sales.php");
require_once("../model/products.php");

if (empty($_SESSION['id'])) {
    header('Location: ../view/login.php');
    exit;
}

if (
    empty($_POST['product']) ||
    empty($_POST['quantity']) ||
    empty($_POST['price']) ||
    empty($_POST['date'])
) {
    $_SESSION['msg-form'] = 'Você precisa preencher todos os campos';
    header('Location: ../view/perfil.php?diary#msg');
    exit;
}

$product = get_product_by_id((int) $_POST['product']);
$quantity = (int) $_POST['quantity'];
$price = (float) $_POST['price'];

// O produto precisa existir e pertencer ao vendedor logado
if (!$product || (int) $product['user_id'] !== (int) $_SESSION['id']) {
    $_SESSION['msg-form'] = 'Produto inválido';
    header('Location: ../view/perfil.php?diary#msg');
    exit;
}

if ($quantity <= 0 || $price <= 0) {
    $_SESSION['msg-form'] = 'Quantidade e valor precisam ser maiores que zero';
    header('Location: ../view/perfil.php?diary#msg');
    exit;
}

if (register_sale((int) $_SESSION['id'], (int) $product['id'], $quantity, $price, $_POST['date'])) {
    $_SESSION['msg-form'] = 'Venda registrada';
} else {
    $_SESSION['msg-form'] = 'Não foi possível registrar a venda';
}

header('Location: ../view/perfil.php?diary#msg');
exit;

?>

<?php session_start();
require_once("../model/products.php");
require_once("../model/category.php");
require_once("../model/user.php");
require_once("../config/init.php");

$user = get_user_by_id($_SESSION['id']);

// Estoque: sem o checkbox = produto sem controle de estoque (NULL); com ele, a quantidade é obrigatória
$has_stock = !empty($_POST['has_stock']);
$stock = null;

if ($has_stock) {
    $stock = filter_var($_POST['product_stock'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2147483647]]);
}

if (
    !isset($_FILES['product_photo']) ||
    $_FILES['product_photo']['error'] !== UPLOAD_ERR_OK ||
    empty($_POST['product_name']) ||
    empty($_POST['product_price']) ||
    empty($_POST['product_category']) ||
    empty($_POST['product_description'])
) {
    $_SESSION['msg'] = "Você precisa preencher todos o campos";
    header("Location: ../view/new_product.php#msg");
    exit;
} elseif (strlen($_POST['product_description']) < 10){
    $_SESSION['msg'] = "A descrição precisa conter ao menos 10 caracteres";
    header("Location: ../view/new_product.php#msg");
    exit;
} elseif (!check_category($_POST['product_category'])){
     $_SESSION['msg'] = "Selecione uma categoria válida";
    header("Location: ../view/new_product.php#msg");
    exit;
} elseif ($has_stock && $stock === false){
    $_SESSION['msg'] = "Informe a quantidade em estoque (número inteiro, a partir de 0)";
    header("Location: ../view/new_product.php#msg");
    exit;
} else {
    $name = uniqid() . ".jpg";
    $path = (BASE_PATH . "uploads/products/");
    $db_path = "uploads/products/" . $name;
    move_uploaded_file($_FILES['product_photo']['tmp_name'], $path . $name);
    create_product(
        $user['id'], 
        $_POST['product_name'], 
        $_POST['product_category'], 
        $_POST['product_description'], 
        $_POST['product_price'], 
        $db_path,
        $stock
        );
     $_SESSION['msg'] = "Produto criado com sucesso";
    header("Location: ../view/new_product.php?products#msg");
    exit;
    
}
?>
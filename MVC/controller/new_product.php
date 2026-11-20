<?php
require_once("../config/auth.php");   // session_start + login pelo cookie "lembrar de mim"
require_once("../config/upload.php");
require_once("../model/products.php");
require_once("../model/category.php");
require_once("../model/user.php");
require_once("../config/init.php");

check_login();
check_role(2);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../view/new_product.php");
    exit;
}

$user = get_user_by_id($_SESSION['id']);

// Estoque: sem o checkbox = produto sem controle de estoque (NULL); com ele, a quantidade é obrigatória
$has_stock = !empty($_POST['has_stock']);
$stock = null;

if ($has_stock) {
    $stock = filter_var($_POST['product_stock'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2147483647]]);
}

$photo = $_FILES['product_photo'] ?? null;
$photo_sent = $photo !== null && (int) $photo['error'] !== UPLOAD_ERR_NO_FILE;

if (cashfy_post_too_large()) {
    // Imagem maior que o limite do servidor: o PHP descarta o formulário inteiro
    $_SESSION['msg'] = CASHFY_MAX_IMAGE_TEXT;
    header("Location: ../view/new_product.php#msg");
    exit;
} elseif ($photo_sent && (int) $photo['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['msg'] = cashfy_upload_error_message((int) $photo['error']);
    header("Location: ../view/new_product.php#msg");
    exit;
} elseif (
    !$photo_sent ||
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
    // Valida o conteúdo da imagem e grava com nome único em uploads/products/
    $db_path = cashfy_save_image($photo, 'products', $upload_error);

    if ($db_path === null) {
        $_SESSION['msg'] = $upload_error;
        header("Location: ../view/new_product.php#msg");
        exit;
    }

    try {
        create_product(
            $user['id'],
            $_POST['product_name'],
            $_POST['product_category'],
            $_POST['product_description'],
            $_POST['product_price'],
            $db_path,
            $stock
        );
    } catch (Throwable $e) {
        // Não deixa imagem órfã no disco se o produto não foi criado
        cashfy_delete_upload($db_path, 'products');
        error_log('new_product: ' . $e->getMessage());
        $_SESSION['msg'] = "Não foi possível criar o produto. Tente novamente.";
        header("Location: ../view/new_product.php#msg");
        exit;
    }

    $_SESSION['msg'] = "Produto criado com sucesso";
    header("Location: ../view/new_product.php?products#msg");
    exit;
}
?>

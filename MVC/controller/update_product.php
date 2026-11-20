<?php
require_once("../config/auth.php");   // session_start + login pelo cookie "lembrar de mim"
require_once("../config/upload.php");
require_once("../model/products.php");
require_once("../model/category.php");
require_once("../model/user.php");
require_once("../config/init.php");

if (empty($_SESSION['id'])) {
    header("Location: ../view/login.php");
    exit;
}

$user = get_user_by_id($_SESSION['id']);
$product = get_product_by_id((int) ($_GET['id'] ?? 0));

// Produto inexistente, excluído (get_product_by_id só devolve ativos) ou de outro vendedor
if (!$product || $product['user_id'] != $_SESSION['id']) {
    header("Location: ../view/perfil.php?products");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../view/update_product.php?id=" . $product['id']);
    exit;
}

// Imagem maior que o limite do servidor: o PHP descarta o formulário inteiro
if (cashfy_post_too_large()) {
    $_SESSION['msg'] = CASHFY_MAX_IMAGE_TEXT;
    header("Location: ../view/update_product.php?id=" . $product['id'] . "#msg");
    exit;
}

$old_photo = $product['product_photo'];

$price = preg_replace('/[^0-9.]/', '', $_POST['product_price'] ?? '');
$price = number_format((float) $price, 2, '.', '');

$category = preg_replace('/\D+/', '', $_POST['product_category'] ?? '');

// Estoque: sem o checkbox = produto sem controle de estoque (NULL); com ele, a quantidade é obrigatória
$has_stock = !empty($_POST['has_stock']);
$stock = null;

if ($has_stock) {
    $stock = filter_var($_POST['product_stock'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2147483647]]);
}

$current_stock = ($product['stock'] ?? null) === null ? null : (int) $product['stock'];

// Foto: "enviada" = o usuário escolheu um arquivo (mesmo que o envio tenha dado erro)
$photo_file = $_FILES['product_photo'] ?? null;
$photo_sent = $photo_file !== null && (int) $photo_file['error'] !== UPLOAD_ERR_NO_FILE;


if (
    empty($_POST['product_name']) ||
    empty($_POST['product_price']) ||
    empty($_POST['product_category']) ||
    empty($_POST['product_description'])
) {
    $_SESSION['msg'] = "Você precisa preencher todos os campos.";
    header("Location: ../view/update_product.php?id=" . $product['id'] . "#msg");
    exit;
} elseif (!$price || $price == '') {
    $_SESSION['msg'] = "Insira um número válido para o preço";
    header("Location: ../view/update_product.php?id=" . $product['id'] . "#msg");
    exit;
} elseif (!check_category($category)) {
    $_SESSION['msg'] = "Insira uma categoria válida";
    header("Location: ../view/update_product.php?id=" . $product['id'] . "#msg");
    exit;
} elseif (strlen($_POST['product_description']) < 10) {
    $_SESSION['msg'] = "A descrição precisa conter ao menos 10 caracteres";
    header("Location: ../view/update_product.php?id=" . $product['id'] . "#msg");
    exit;
} elseif ($has_stock && $stock === false) {
    $_SESSION['msg'] = "Informe a quantidade em estoque (número inteiro, a partir de 0)";
    header("Location: ../view/update_product.php?id=" . $product['id'] . "#msg");
    exit;
} elseif ($photo_sent && (int) $photo_file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['msg'] = cashfy_upload_error_message((int) $photo_file['error']);
    header("Location: ../view/update_product.php?id=" . $product['id'] . "#msg");
    exit;
} elseif (
    $product['name'] == $_POST['product_name'] &&
    $product['price'] == $price &&
    $product['category_id'] == $category &&
    $product['description'] == $_POST['product_description'] &&
    $current_stock === $stock &&
    !$photo_sent
) {
    $_SESSION['msg'] = "ATENÇÃO! Não houveram alterações.";
    header("Location: ../view/update_product.php?id=" . $product['id'] . "#msg");
    exit;

}

// Nova foto: valida o conteúdo e grava com nome único (a antiga NÃO é sobrescrita,
// assim navegador/CDN nunca mostram a imagem velha)
$new_photo = null;

if ($photo_sent) {
    $new_photo = cashfy_save_image($photo_file, 'products', $upload_error);

    if ($new_photo === null) {
        $_SESSION['msg'] = $upload_error;
        header("Location: ../view/update_product.php?id=" . $product['id'] . "#msg");
        exit;
    }
}

try {
    update_product(
        $product['id'],
        $_POST['product_name'],
        $price,
        $category,
        $_POST['product_description'],
        $stock
    );

    if ($new_photo !== null) {
        update_product_photo((int) $product['id'], $new_photo);
    }
} catch (Throwable $e) {
    if ($new_photo !== null) {
        cashfy_delete_upload($new_photo, 'products');
    }

    error_log('update_product: ' . $e->getMessage());
    $_SESSION['msg'] = "Não foi possível salvar as alterações. Tente novamente.";
    header("Location: ../view/update_product.php?id=" . $product['id'] . "#msg");
    exit;
}

// A foto antiga só sai do disco depois que a nova já está salva no banco
if ($new_photo !== null) {
    cashfy_delete_upload($old_photo, 'products');
}

$_SESSION['msg'] = 'Produto alterado com sucesso';
header("Location: ../view/update_product.php?id=" . $product['id'] . "#msg");
exit;

?>

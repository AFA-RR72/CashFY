<?php session_start();
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

$photo = $product['product_photo'];

$price = preg_replace('/[^0-9.]/', '', $_POST['product_price']);
$price = number_format((float) $price, 2, '.', '');

$category = preg_replace('/\D+/', '', $_POST['product_category']);

// Estoque: sem o checkbox = produto sem controle de estoque (NULL); com ele, a quantidade é obrigatória
$has_stock = !empty($_POST['has_stock']);
$stock = null;

if ($has_stock) {
    $stock = filter_var($_POST['product_stock'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2147483647]]);
}

$current_stock = ($product['stock'] ?? null) === null ? null : (int) $product['stock'];


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
} elseif (
    $product['name'] == $_POST['product_name'] &&
    $product['price'] == $price &&
    $product['category_id'] == $category &&
    $product['description'] == $_POST['product_description'] &&
    $current_stock === $stock &&
    (
        !isset($_FILES['product_photo']) ||
        $_FILES['product_photo']['error'] !== UPLOAD_ERR_OK
    )
) {
    $_SESSION['msg'] = "ATENÇÃO! Não houveram alterações.";
    header("Location: ../view/update_product.php?id=" . $product['id'] . "#msg");
    exit;

}

if (
    isset($_FILES['product_photo']) &&
    $_FILES['product_photo']['error'] === UPLOAD_ERR_OK
) {
    move_uploaded_file($_FILES['product_photo']['tmp_name'], BASE_PATH . $photo);
}

update_product(
    $product['id'],
    $_POST['product_name'],
    $price,
    $category,
    $_POST['product_description'],
    $stock
);

$_SESSION['msg'] = 'Produto alterado com sucesso';
header("Location: ../view/update_product.php?id=" . $product['id'] . "#msg");
exit;

?>
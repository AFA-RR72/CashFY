<?php session_start();
require_once('../model/products.php');

/*
 * Apagar produto: só via POST (id no corpo), e só o dono do produto.
 *
 * Quem decide entre exclusão definitiva e exclusão lógica é o model (delete_product):
 * produto sem vendas é apagado de vez; produto com histórico de vendas só recebe deleted_at.
 */

if (empty($_SESSION['id'])) {
    header('Location: ../view/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header('Location: ../view/perfil.php?products');
    exit;
}

$product = get_product_by_id((int) $_POST['id']);

if (!$product || $product['user_id'] != $_SESSION['id']) {
    header('Location: ../../index.php');
    exit;
}

// 'deleted' (apagado de vez), 'archived' (exclusão lógica) ou false (não foi possível)
$result = delete_product((int) $product['id'], (int) $_SESSION['id']);

// Para o vendedor o resultado é o mesmo: o produto sai da lista dele
$_SESSION['msg-form'] = $result !== false
    ? 'Produto apagado'
    : 'Não foi possível apagar este produto. Tente novamente.';

header('Location: ../view/perfil.php?products');
exit;

?>
<?php
session_start();
require_once("../model/user.php");

if (!isset($_SESSION['id'])) {
    header("Location: ../view/login.php");
    exit();
}

$user = get_user_by_id($_SESSION['id']);

function check_phone_number($phone_number)
{
    $number = preg_replace('/\D/', '', $phone_number);

    return strlen($number) === 11;
}

if (empty($user['profile_photo'])) {
    $_SESSION['msg-form'] = "É obrigatório que você tenha uma foto de perfil para tornar-se um vendedor";
    header("Location: ../view/perfil.php?vendedor=true#msg-form");
    exit;
} elseif (
    empty($_POST['phone_number']) ||
    empty($_POST['description']) || 
    empty($_POST['password'])
    ) {
    $_SESSION['msg-form'] = "Você precisa preencher todos os campos";
    header("Location: ../view/perfil.php?vendedor=true#msg-form");
    exit;
} elseif (!check_phone_number($_POST['phone_number'])) {
    $_SESSION['msg-form'] = "Insira um número de telefone válido";
    header("Location: ../view/perfil.php?vendedor=true#msg-form");
    exit;
} elseif (strlen($_POST['description']) <= 20) {
    $_SESSION['msg-form'] = "Preencha a descrição com ao menos 20 dígitos";
    header("Location: ../view/perfil.php?vendedor=true#msg-form");
    exit;
} elseif (!password_verify($_POST['password'], $user['password'])) {
    $_SESSION['msg-form'] = "Senha incorreta";
    header("Location: ../view/perfil.php?vendedor=true#msg-form");
    exit;
} else {
    $phone_number = preg_replace('/\D/', '', $_POST['phone_number']);
    update_to_seller($user['id'], $phone_number, $_POST['description']);
    $user = get_user_by_id($_SESSION['id']);
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['msg-form'] = "Perfil atualizado com sucesso";
    header("Location: ../view/perfil.php#msg-form");
    exit;
}

header("Location: ../view/perfil.php");
exit;
?>
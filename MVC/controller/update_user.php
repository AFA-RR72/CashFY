<?php
session_start();
require_once('../model/user.php');

$user = get_user_by_id($_SESSION['id']);

$isSeller = $user['role_id'] == 2;

if (
    empty($_POST['name']) ||
    empty($_POST['inst']) ||
    empty($_POST['email']) ||
    empty($_POST['password'])
) {
    $_SESSION['msg-form'] = 'Você precisa preencher todos os campos';

    if ($isSeller) {
        header("Location: ../view/perfil.php?profile#msg-form");
    } else {
        header("Location: ../view/perfil.php?clientprofile#msg-form");
    }

    exit;
}

if ($isSeller) {

    if (
        empty($_POST['phone']) ||
        empty($_POST['description'])
    ) {
        $_SESSION['msg-form'] = 'Você precisa preencher todos os campos';
        header("Location: ../view/perfil.php?profile#msg-form");
        exit;
    }

    if (strlen($_POST['description']) < 20) {
        $_SESSION['msg-form'] = 'Preencha a descrição com ao menos 20 dígitos';
        header("Location: ../view/perfil.php?profile#msg-form");
        exit;
    }
}

if (!password_verify($_POST['password'], $user['password'])) {

    $_SESSION['msg-form'] = 'Senha incorreta';

    if ($isSeller) {
        header("Location: ../view/perfil.php?profile#msg-form");
    } else {
        header("Location: ../view/perfil.php?clientprofile#msg-form");
    }

    exit;
}

if ($isSeller) {

    update_user(
        $user['id'],
        $_POST['name'],
        $_POST['inst'],
        $_POST['phone'],
        $_POST['description'],
        $_POST['email']
    );

    $_SESSION['msg-form'] = 'Perfil alterado com sucesso';
    header("Location: ../view/perfil.php?profile#msg-form");

} else {

    update_user(
        $user['id'],
        $_POST['name'],
        $_POST['inst'],
        '',
        '',
        $_POST['email']
    );

    $_SESSION['msg-form'] = 'Perfil alterado com sucesso';
    header("Location: ../view/perfil.php?clientprofile#msg-form");
}
exit;
?>


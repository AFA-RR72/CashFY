<?php
session_start();
require_once("../model/user.php");

if (
    empty($_POST['email']) ||
    empty($_POST['password'])
) {
    $_SESSION['msg'] = "Você precisa preencher todos os campos!";
    header("Location: ../view/log-in.php");
    exit;
}
$user = get_user($_POST['email']);
if ($user) {
    if (password_verify($_POST['password'], $user['password'])) {
        $_SESSION['institute'] = $user['institute_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['password'] = $user['password'];
        $_SESSION['remember_token'] = $user['remember_token'];
        $_SESSION['msg'] = "Log-in efetuado com sucesso. <br> Bem vindo, " . $user['name'] . "!";
        header("Location: ../view/log-in.php");
        exit;
    } else {
        $_SESSION['msg'] = "Senha incorreta.";
        header("Location: ../view/log-in.php");
        exit;
    }
} else {
    $_SESSION['msg'] = "Email não encontrado.";
    header("Location: ../view/log-in.php");
    exit;
}
?>
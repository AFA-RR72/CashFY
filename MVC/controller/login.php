<?php session_start();
require_once('../model/user.php');

if (empty($_POST['email']) || empty($_POST['password'])) {
    $_SESSION['msg'] = "Você precisa preencher todos os campos.";
    header("Location: ../view/login.php");
} elseif (check_email($_POST['email'])) {
    $user = get_user_by_email($_POST['email']);
    if (password_verify($_POST['password'], $user['password'])) {
        $_SESSION['id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['institute'] = $user['institute'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['msg'] = "Log-in realizado com sucesso.";
        header("Location: ../view/login.php");
    } else {
        $_SESSION['msg'] = "Senha incorreta.";
        header("Location: ../view/login.php?id=". $user['id']);
    }
} else {
    $_SESSION['msg'] = "Email não encontrado.";
    header("Location: ../view/login.php");
}

?>
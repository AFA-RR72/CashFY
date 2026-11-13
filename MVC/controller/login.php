<?php session_start();
require_once('../model/user.php');
require_once('../config/remember.php');

if (empty($_POST['email']) || empty($_POST['password'])) {
    $_SESSION['msg'] = "Você precisa preencher todos os campos.";
    header("Location: ../view/login.php#msg");
    exit;
} elseif (check_email($_POST['email'])) {
    $user = get_user_by_email($_POST['email']);
    if (password_verify($_POST['password'], $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['id'] = $user['id'];

        if (!empty($_POST['remember'])) {
            remember_user((int) $user['id']);
        } else {
            // login normal: não deixa um cookie antigo de "lembrar de mim" valendo neste navegador
            clear_remember_cookie();
        }

        $_SESSION['msg'] = "Log-in realizado com sucesso.";
        header("Location: ../view/login.php#msg");
        exit;
    } else {
        $_SESSION['msg'] = "Senha incorreta.";
        header("Location: ../view/login.php?id=" . $user['id'] . "#msg");
        exit;
    }
} else {
    $_SESSION['msg'] = "Email não encontrado.";
    header("Location: ../view/login.php#msg");
    exit;
}

?>
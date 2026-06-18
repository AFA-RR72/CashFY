<?php
require_once __DIR__ . "/../config/init.php";
require_once BASE_PATH . "model/user.php";

if (
    empty($_POST['email']) ||
    empty($_POST['password'])
) {
    $_SESSION['msg'] = "Você precisa preencher todos os campos!";
    header("Location: ../view/log-in.php");
    exit;
}
$user = get_user_by_email($_POST['email']);
if ($user) {
    if (password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];

        if (!empty($_POST['remind'])) {
            $token = bin2hex(random_bytes(32));
            save_token($user['id'], $token);
            setcookie("remember_token", $token, time() + (86400 * 7), "/");
        }

        header("Location: ../view/log-in.php?id=" . $user['id']);
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
<?php
ini_set('session.cookie_lifetime', 0);
session_start();

define('BASE_PATH', __DIR__ . '/../');
define('BASE_URL', '/activities/CashFY/MVC/');

require_once BASE_PATH . "model/user.php";

if (!isset($_SESSION['user_id'])){
    if (!empty($_COOKIE['remember_token'])){
        $user = get_user_by_token($_COOKIE['remember_token']);

        if ($user && !empty($user['id'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
        } else{
            setcookie("remember_token", "", time() - 3600, "/");
        }
    }
}

if (!isset($_COOKIE['remember_token']) && isset($_SESSION['user_id'])){
    $limit_time = 1200;
    if (isset($_SESSION['last_access']) && (time() - $_SESSION['last_access']) > $limit_time){
        session_unset();
        session_destroy();

        session_start();
        $_SESSION['msg'] = "Sua sessão expirou por inatividade.";
        header("Location:" . BASE_URL . "view/log-in.php");
        exit;
    }
    $_SESSION['last_access'] = time();
}
?>
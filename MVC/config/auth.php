<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once BASE_PATH . "model/user.php";

if (isset($_GET['id'])) {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $_GET['id']) {
        $_SESSION['msg'] = "Acesso negado! Efetue o log-in corretamente.";
        header("Location:" . BASE_URL . "view/log-in.php");
        exit;
    }
}
?>
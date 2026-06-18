<?php
require_once __DIR__ . "/../config/init.php";
require_once BASE_PATH . "model/user.php";

if (isset($_GET['log-out'])) {
    $id = $_SESSION['user_id'] ?? null;
    if ($id) {
        delete_token($id);
    }

    $_SESSION = [];
    session_unset();
    session_destroy();

    setcookie("remember_token", "", time() - 3600, "/");
    setcookie(session_name(), "", time() - 3600, "/");

} else {
    $_SESSION['log-out_msg'] = "Você tem certeza que deseja encerrar sessão?";
}

header("Location: ../../index.php");
exit;
?>
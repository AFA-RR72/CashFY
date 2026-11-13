<?php
session_start();
require_once('../config/remember.php');

// apaga o "lembrar de mim" (banco + cookie) para não entrar sozinho de novo
forget_user(isset($_SESSION['id']) ? (int) $_SESSION['id'] : null);

session_unset();
session_destroy();

header("Location: ../../index.php");
exit;
?>
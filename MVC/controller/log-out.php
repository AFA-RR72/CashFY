<?php
session_start();
require_once(__DIR__ . '/../config/remember.php');
require_once(__DIR__ . '/../model/push_subscription.php');

if (!empty($_SESSION['push_endpoint']) && !empty($_SESSION['id'])) {
    delete_push_subscription_by_endpoint((string) $_SESSION['push_endpoint'], (int) $_SESSION['id']);
}

forget_user(isset($_SESSION['id']) ? (int) $_SESSION['id'] : null);

session_unset();
session_destroy();

header("Location: ../../index.php");
exit;
?>

<?php session_start();
require_once("../model/user.php");

if (isset($_POST['search']) && trim($_POST['search']) !== '') {
    $result = search_user_by_name($_POST['search']);
} else {
    header("Location: ../../index.php");
    exit;
}

$user = $result[0];

if (!empty($user) > 0) {
    header("Location: ../../index.php#seller-" . $user['id']);
    exit;
} else{
    header("Location: ../../index.php");
    exit;
}
?>
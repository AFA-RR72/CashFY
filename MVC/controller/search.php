<?php session_start();
require_once("../model/user.php");
require_once("../model/category.php");

if (isset($_POST['search']) && trim($_POST['search']) !== '') {
    $result_users = search_user_by_name($_POST['search']);
    $result_categories = search_categories($_POST['search']);
} else {
    header("Location: ../../index.php");
    exit;
}

$user = $result_users[0];

if (!empty($user)) {
    $_SESSION['search-users'] = $result_users;
    header("Location: ../../index.php#seller-" . $user['id']);
    exit;
} else {

    $category = $result_categories[0];

    if (!empty($category)) {
        $_SESSION['search-categories'] = $result_categories;
        header("Location: ../../index.php#category-" . $category['id']);
        exit;
    }
    header("Location: ../../index.php");
    exit;
}
?>
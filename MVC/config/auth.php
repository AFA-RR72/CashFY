<?php session_start();
require_once('init.php');
require_once(__DIR__ . '/../model/user.php');
require_once(__DIR__ . '/../model/products.php');

function check_login()
{
    if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
        header('Location: '. BASE_URL . 'MVC/view/login.php');
        exit;
    }
}

function check_role(){
    $user_test = get_user_by_id($_SESSION['id']);
    if ($user_test['role_id'] != 2){
        header('Location: '. BASE_URL . 'index.php');
        exit;
    }
}

function check_get_id(){
    if (!isset($_GET['id'])){
        header('Location:' . BASE_URL . 'index.php');
        exit;
    }
}
?>
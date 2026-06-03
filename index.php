<?php
session_start();

if (isset($_SESSION['id'])){
    header('Location: MVC/view/index2.php');
} else{
    header('Location: MVC/view/login.php');
}
exit;
?>
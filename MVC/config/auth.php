<?php
session_start();

require_once("../model/usermodel.php");

function auth_required()
{
    $conn = conexao();
    if (isset($_SESSION['id'])) {
        return true;
    }


    if (isset($_COOKIE['remember_token'])) {

        $token = mysqli_real_escape_string($conn, $_COOKIE['remember_token']);

        $sql = "SELECT * FROM users WHERE remember_token = '$token'";
        $result = mysqli_query($conn, $sql);

        if (!$result || mysqli_num_rows($result) === 0) {
            setcookie("remember_token", "", time() - 3600, "/");
            header("Location: ../view/login.php");
            exit;
        }

        $user = mysqli_fetch_assoc($result);

        $_SESSION['id'] = $user['ID'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['profile'] = $user['profile'];

        $_SESSION['msg'] = "Bem-vindo de volta, " . $user['name'] . "!";

        return true;


    }

    header("Location: ../view/login.php");
    exit;
}
?>
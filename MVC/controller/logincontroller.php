<?php
require_once("../model/usermodel.php");
session_start();
$conn = conexao();

if (empty($_POST['email']) || empty($_POST['password'])){
    $_SESSION['erro'] = "Você precisa preencher todos os campos!";
    header("Location: ../view/login.php");
    exit;
}

$email = $_POST['email'];
$password = $_POST['password'];

$user = authenticateuser($email, $password);

if ($user) {
    $_SESSION['id'] = $user['ID'];
    $_SESSION['institute_id'] = $user['institute_id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['profile'] = $user['profile'];

    if (isset($_POST['remember'])) {
        $token = bin2hex(random_bytes(32));
        $token = mysqli_real_escape_string($conn, $token);

        mysqli_query($conn, "UPDATE users SET remember_token = '$token' WHERE ID = {$user['ID']}");
        

        setcookie("remember_token", $token, time() + (86400 * 7), "/");
        
    }

    $_SESSION['msg'] = "Bem-Vindo, ". $user['name'] . "!";
    header("Location: ../view/index2.php");
    exit;
} else {
    $_SESSION['erro'] = "Email ou senha incorretos! <br> <label>Ainda não tem uma conta?<a href='../view/sign-up.php'>Criar agora</a></label>";
    header("Location: ../view/login.php");
    exit;
}

?>
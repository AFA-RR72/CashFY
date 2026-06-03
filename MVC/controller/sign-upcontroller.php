<?php
session_start();
require_once("../model/usermodel.php");
require_once("../model/educational_institutemodel.php");
$conn = conexao();

$name = $_POST['name'];
$institute_id = $_POST['institute_id'];
$email = $_POST['email'];
$password = $_POST['password'];
$pass_verify = $_POST['pass_verify'];

if (
    empty($_POST['name']) ||
    empty($_POST['institute_id']) ||
    empty($_POST['email']) ||
    empty($_POST['password']) ||
    empty($_POST['pass_verify'])
) {
    $_SESSION['erro'] = "Você precisa preencher todos os campos!";
    header("Location: ../view/sign-up.php");
    exit;
}

$sql = "SELECT * FROM educational_institute WHERE ID = '$institute_id'";
$verify = mysqli_query($conn, $sql);
if (mysqli_num_rows($verify) == 0) {
    $_SESSION['erro'] = "Esta instituição de ensino não foi cadastrada.";
    header("Location: ../view/sign-up.php");
    exit;
} else {
    $sqlemail = "SELECT * FROM users WHERE email = '$email'";
    $verifyemail = mysqli_query($conn, $sqlemail);
    if (mysqli_num_rows($verifyemail) > 0) {
        $_SESSION['erro'] = "Este email já está em uso.";
        header("Location: ../view/sign-up.php");
        exit;
    } elseif ($password != $pass_verify) {
        $_SESSION['erro'] = "As senhas não foram preenchidas corretamente!";
        header("Location: ../view/sign-up.php");
        exit;
    } else {
        $hashpass = password_hash($password, PASSWORD_DEFAULT);
    }
}
$sql = "INSERT INTO users (name, institute_id, email, password) VALUES ('$name','$institute_id', '$email', '$hashpass')";

if (mysqli_query($conn, $sql)) {
    $_SESSION['success'] = "Usuário criado com sucesso!";
    header("Location: ../view/login.php");
    exit;
} else {
    $_SESSION['erro'] = "Erro ao criar o usuário.";
    header("Location: ../view/sign-up.php");
    exit;
}
?>
<?php
require_once __DIR__ . "/../config/init.php";
require_once BASE_PATH . "model/institute.php";
require_once BASE_PATH . "model/user.php";

$name = $_POST['name'];
$institute_id = $_POST['institute_id'];
$email = $_POST['email'];
$password = $_POST['password'];
$pass_confirm = $_POST['pass_confirm'];

if (
    empty($_POST['name']) ||
    empty($_POST['institute_id']) ||
    empty($_POST['email']) ||
    empty($_POST['password']) ||
    empty($_POST['pass_confirm'])
) {
    $_SESSION['msg'] = "Você precisa preencher todos os campos";
    header("Location: ../view/sign-up.php");
    exit;
} elseif(
    strlen($_POST['name']) > 255 ||
    strlen($_POST['institute_id']) > 255 ||
    strlen($_POST['email']) > 255 ||
    strlen($_POST['password']) > 255 ||
    strlen($_POST['pass_confirm']) > 255
){
    $_SESSION['msg'] = "O limite de caracteres permitidos é 255.";
    header("Location: ../view/sign-up.php");
    exit;
} elseif(institute_verify($institute_id)){
    $_SESSION['msg'] = "Selecione uma instituição!";
    header("Location: ../view/sign-up.php");
    exit;
} elseif(email_verify($email)){
    $_SESSION['msg'] = "Este email já está em uso.";
    header("Location: ../view/sign-up.php");
    exit;
} elseif(strlen($password) < 8){
    $_SESSION['msg'] = "A senha precisa conter no mínimo 8 caracteres.";
    header("Location: ../view/sign-up.php");
    exit;
} elseif ($password != $pass_confirm) {
    $_SESSION['msg'] = "As senhas precisam ser a mesma.";
    header("Location: ../view/sign-up.php");
    exit;
} else {
    $pass_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    criar($name, $institute_id, $email, $pass_hash);
    $_SESSION['msg'] = "Usuário criado com sucesso. <br> <a href='log-in.php'>Fazer log-in.</a>";
    header("Location: ../view/sign-up.php");
    exit;
}

?>
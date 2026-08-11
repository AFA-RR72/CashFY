<?php session_start();
require_once('../model/user.php');

$email = $_POST['email'];

if(empty($_POST['name']) || empty($_POST['institute']) || empty($_POST['email']) || empty($_POST['password'])){
    $_SESSION['msg'] = "Você precisa preencher todos os campos";
    header("Location: ../view/cadastro.php");
} elseif(check_email($email)){
    $_SESSION['msg'] = "Este email já está em uso";
    header("Location: ../view/cadastro.php");
} elseif (strlen($_POST['password']) < 8){
    $_SESSION['msg'] = "A senha precisa conter no mínimo 8 caracteres.";
    header("Location: ../view/cadastro.php");
} else {
    $name = $_POST['name'];
    $institute_id = $_POST['institute'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    criar($name, $institute_id, $email, $password, 3);

    $_SESSION['msg'] = "Usuário criado com sucesso.";
    header("Location: ../view/cadastro.php");
}

?>
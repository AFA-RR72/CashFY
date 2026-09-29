<?php session_start();
require_once('../model/user.php');
require_once('../config/init.php');

$email = $_POST['email'];
$_SESSION['form'] = $_POST;


if (empty($_POST['name']) || empty($_POST['institute']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['account_type'])) {
    $_SESSION['msg'] = "Você precisa preencher todos os campos";
    header("Location: ../view/cadastro.php#msg");
    exit;
} elseif (check_email($email)) {
    $_SESSION['msg'] = "Este email já está em uso";
    header("Location: ../view/cadastro.php#msg");
    exit;
} elseif (strlen($_POST['password']) < 8) {
    $_SESSION['msg'] = "A senha precisa conter no mínimo 8 caracteres.";
    header("Location: ../view/cadastro.php#msg");
    exit;
} elseif ($_POST['password'] != $_POST['pass_confirm']) {
    $_SESSION['msg'] = "As senhas precisam ser as mesmas.";
    header("Location: ../view/cadastro.php#msg");
    exit;
} elseif (empty($_POST['tos']) || empty($_POST['pp'])) {
    $_SESSION['msg'] = "Você precisa aceitar os <a href='tos.php' class='agreements-msg'>Termos de Uso</a> e a <a href='pp.php' class='agreements-msg'>Política de Privacidade</a>.";
    header("Location: ../view/cadastro.php#msg");
    exit;
} elseif ($_POST['account_type'] === 'client') {
    $name = $_POST['name'];
    $institute_id = $_POST['institute'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    create($name, $institute_id, $email, $password, 3);

    $_SESSION['msg'] = "Usuário criado com sucesso.";
    header("Location: ../view/cadastro.php#msg");
    exit;
} elseif ($_POST['account_type'] === 'seller') {

    if (
        empty($_POST['description']) ||
        empty($_POST['phone'])
    ) {
        $_SESSION['msg'] = "Você precisa preencher todos os campos";
        header("Location: ../view/cadastro.php#msg");
    }

    $name = $_POST['name'];
    $institute_id = $_POST['institute'];
    $email = $_POST['email'];
    $contact = $_POST['phone'];
    $description = $_POST['description'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    create_seller($name, $institute_id, $email, $contact, $description, $password, 2);

    $_SESSION['msg'] = "Usuário criado com sucesso.";
    header("Location: ../view/cadastro.php#msg");
    exit;
}


?>
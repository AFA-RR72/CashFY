<?php session_start();
require_once('../model/user.php');

$email = $_POST['email'];

if (empty($_POST['name']) || empty($_POST['institute']) || empty($_POST['email']) || empty($_POST['password'])) {
    $_SESSION['msg'] = "Você precisa preencher todos os campos";
    header("Location: ../view/cadastro.php");
    exit;
} elseif (check_email($email)) {
    $_SESSION['msg'] = "Este email já está em uso";
    header("Location: ../view/cadastro.php");
    exit;
} elseif (strlen($_POST['password']) < 8) {
    $_SESSION['msg'] = "A senha precisa conter no mínimo 8 caracteres.";
    header("Location: ../view/cadastro.php");
    exit;
} elseif ($_POST['password'] != $_POST['pass_confirm']) {
    $_SESSION['msg'] = "As senhas precisam ser as mesmas.";
    header("Location: ../view/cadastro.php");
    exit;
} elseif (empty($_POST['tos']) || empty($_POST['pp'])) {
    $_SESSION['msg'] = "Você precisa aceitar os <a href='tos.php' class='agreements-msg'>Termos de Uso</a> e a <a href='pp.php' class='agreements-msg'>Política de Privacidade</a>.";
    header("Location: ../view/cadastro.php");
    exit;
} else {
    $name = $_POST['name'];
    $institute_id = $_POST['institute'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    criar($name, $institute_id, $email, $password, 3);

    $_SESSION['msg'] = "Usuário criado com sucesso.";
    header("Location: ../view/cadastro.php");
    exit;
}

?>
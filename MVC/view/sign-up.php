<?php
require_once("../config/auth.php");
require_once("../model/educational_institutemodel.php");
$institutes = getinstitutes();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign-Up</title>
</head>
<body>
    <form action="../controller/sign-upcontroller.php" method="POST">
        <label for="name">Nome: <input type="text" name="name"></label>
        <br>
        <label for="institute_id">Instituição de ensino: <select name="institute_id">
            <option value="">Selecione um instituto</option>
            <?php foreach ($institutes as $inst): ?>
            <option value="<?= $inst['ID']; ?>">
                <?= $inst['name']; ?>
            </option>
            <?php endforeach; ?>
        </select></label>
        <br>
        <label for="email">Email: <input type="text" name="email"></label>
        <br>
        <label for="password">Senha: <input type="password" name="password" id="password"></label>
        <br>
        <label for="pass_verify">Confirme a senha: <input type="password" name="pass_verify" id="pass_verify"></label><input type="checkbox" onclick="togglepass()" title="mostrar senha">
        <br>
        <input type="submit">
    </form>
    <?php
    if (isset($_SESSION['erro'])){
        echo $_SESSION['erro'];
        unset($_SESSION['erro']);
    }
    if (isset($_SESSION['success'])){
        echo $_SESSION['success'];
        unset($_SESSION['success']);
    }
    ?>
    <script>
        function togglepass(){
            const password = document.getElementById("password");
            const pass_verify = document.getElementById("pass_verify");
            if (password.type == "password"){
                password.type = "text";
                pass_verify.type = "text";
            }else{
                password.type = "password";
                pass_verify.type = "password";
            }
        }
        </script>
</body>
</html>
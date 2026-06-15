<?php
session_start();
require_once("../model/institute.php");
$institutes = getinstitutes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CASHFY</title>
</head>
<body>
    <div class="container">
    <a href="login.php">previous</a>

    <div class="card">

    <h2>CASHFY</h2>
    <h4>Criar conta como vendedor</h4>
    
    <form action="../controller/sign-up.php" method="post">

        <label for="nome">Nome:</label>
        <br>
        <input type="text" id="nome" name="name" placeholder="Digite seu nome" required>

        <br><br>

        <label for="institute_id">Sua instituição de ensino:</label>
        <br>
        <select name="institute_id" required>
            <option value="">Selecione uma Instituição:</option>
        <?php foreach ($institutes as $inst): ?>
                <option value="<?= $inst['ID']; ?>">
                    <?= $inst['name']; ?>
            </option>
        <?php endforeach ?>
        </select>

        <br><br>

        <label for="email">e-mail:</label> <br>
        <input type="email" id="email" name="email" placeholder="Digite seu e-mail" required>

        <br><br>

        <label for="password">senha:</label> <br>
        <input type="password" id="password" name="password" placeholder="Digite sua senha" minlength="8" required>

        <br><br>

        <label for="pass_confirm">Confirme a senha:</label>
        <br>
        <input type="password" id="pass_confirm" name="pass_confirm" placeholder="Confirme a senha" minlength="8" required>

        <input type="checkbox" title="Mostrar senha" onchange="togglepass()">

        <br><br>

        <button type="submit">criar conta</button>

        <br>
    </form>
    <?php if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
    } ?>
    </div>
    </div>
    <script>
        function togglepass(){
            const password = document.getElementById("password");
            const pass_confirm = document.getElementById("pass_confirm")
            if (password.type == "password"){
                password.type = "text";
                pass_confirm.type = "text";
            } else{
                password.type = "password";
                pass_confirm.type = "password";
            }
        }
    </script>
</body>
</html>
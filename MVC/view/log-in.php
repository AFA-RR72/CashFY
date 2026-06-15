<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CASHFY</title>
</head>

<body>
    <div class="container">
        <a href="../../index.html">previous</a>

        <div class="card">

            <h2>CASHFY</h2>
            <h4>Entrar na sua conta</h4>

            <form action="../controller/log-in.php" method="post">
                <label for="email">e-mail:</label>
                <br>
                <input type="email" id="email" name="email" placeholder="Digite seu e-mail" required>

                <br><br>

                <label for="password">senha:</label> <br>
                <input type="password" id="password" name="password" placeholder="Digite sua senha" minlength="8" required>
                
                <input type="checkbox" title="Mostrar senha" onchange="togglepass()">

                <br><br>

                <label for="remind">Lembrar de mim</label>
                <input type="checkbox" name="remind"><button type="submit">enviar</button>
                <br>
            </form>
            <a href="sign-up.php">Não tem uma conta? criar</a>
            <br>
            <?php if (isset($_SESSION['msg'])){
                echo $_SESSION['msg'];
                unset($_SESSION['msg']);
            } ?>
        </div>
    </div>
    <script>
        function togglepass(){
            const password = document.getElementById("password");
            if(password.type == "password"){
                password.type = "text";
            } else{
                password.type = "password";
            }
        }
    </script>
</body>

</html>
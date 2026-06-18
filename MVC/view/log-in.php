<?php
require_once __DIR__ . "/../config/init.php";
require_once BASE_PATH . "model/user.php";
require_once BASE_PATH . "config/auth.php";

if (!isset($_GET['id'])) {

    if (isset($_COOKIE['remember_token'])) {
        $_SESSION = [];
        session_unset();
        session_destroy();
    } else {
        $id = $_SESSION['user_id'] ?? null;
        if ($id) {
            delete_token($id);
        }
        setcookie("remember_token", "", time() - 3600, "/");
        setcookie(session_name(), "", time() - 3600, "/");
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar na conta</title>
</head>

<body>
    <div class="container">
        <a href="../../index.php">previous</a>

        <div class="card">

            <h2>CASHFY</h2>
            <h4>Entrar na sua conta</h4>

            <form action="../controller/log-in.php" method="post">
                <label for="email">e-mail:</label>
                <br>
                <input type="email" id="email" name="email" placeholder="Digite seu e-mail" required>

                <br><br>

                <label for="password">senha:</label> <br>
                <input type="password" id="password" name="password" placeholder="Digite sua senha" minlength="8"
                    required>

                <input type="checkbox" title="Mostrar senha" onchange="togglepass()">

                <br><br>

                <label for="remind">Lembrar de mim</label>
                <input type="checkbox" name="remind" value="remind"><button type="submit">enviar</button>
                <br>
            </form>
            <a href="sign-up.php">Não tem uma conta? criar</a>
            <br>
            <?php
            if (isset($_SESSION['msg'])) {
                echo htmlspecialchars($_SESSION['msg']);
                unset($_SESSION['msg']);
            }
            if (isset($_GET['id'])) {
                echo "Log-in efetuado com sucesso. <br> Bem vindo, " . htmlspecialchars($_SESSION['name']) . "!" . "<br>";
                echo "Redirecionando...";
                header("Refresh: 1; url=../../index.php");
                exit;
            }


            ?>
        </div>
    </div>
    <script>
        function togglepass() {
            const password = document.getElementById("password");
            if (password.type == "password") {
                password.type = "text";
            } else {
                password.type = "password";
            }
        }
    </script>
</body>

</html>
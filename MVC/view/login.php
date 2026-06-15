<!DOCTYPE html>
<html lang="en">

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

            <form action="../controller/login.php" method="post">
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
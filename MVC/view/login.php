<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <form action="../controller/logincontroller.php" method="POST">
        <label for="email">Email: <input type="email" name="email" id="email"></label>
        <br>
        <label for="password">Senha: <input type="password" name="password" id="password"></label>
        <label for="togglepass"><input  type="checkbox" onchange="togglepass()" title="mostrar senha"></input></label>
        <br>
        <label for="remember">Lembrar de mim <input type="checkbox" name="remember"></label>
        <br>
        <input type="submit">
    </form>
    <script>
        function togglepass(){
            const password = document.getElementById("password");
            if (password.type == "password"){
                password.type = "text";
            } else{
                password.type = "password";
            }
        }
    </script>
</body>
</html>

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
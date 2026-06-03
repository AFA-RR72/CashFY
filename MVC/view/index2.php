<?php
require_once("../config/auth.php");
auth_required();

if (isset($_SESSION['msg'])) {
    echo $_SESSION['msg'];
    unset($_SESSION['msg']);
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Início</title>
</head>
<body>
<form action="../controller/logout.php">
    <input type="submit" value="Fazer logout">
</form>
</body>
</html>
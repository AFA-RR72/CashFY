<?php
function conexao(){
    $conn = mysqli_connect('localhost', 'root', '', 'cashfy');
    if (!$conn){
        die("Erro de conexão: ". mysqli_connect_error());
    }
    return $conn;
}
function authenticateuser($email, $password){
    $conn = conexao();
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);

    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])){
        return $user;
    }
    return false;
}
?>
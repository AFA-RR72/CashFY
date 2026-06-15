<?php
function conexao(){
    $conn = mysqli_connect("localhost", "root", "", "cashfy");
    if (!$conn){
        die("Erro de conexão!" . mysqli_connect_error());
    }
    return $conn;
}
?>
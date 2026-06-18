<?php
require_once("cashfy.php");

function email_verify($email)
{
    $conn = conexao();

    $stmt = $conn -> prepare("SELECT * FROM users WHERE email = ?");
    $stmt -> bind_param("s", $email);

    $stmt -> execute();

    $result_email = $stmt -> get_result();
    if ($result_email -> num_rows > 0) {
        $stmt -> close();
        return true;
    }

    $stmt -> close();

    return false;
}

function criar($name, $institute_id, $email, $pass_hash)
{
    $conn = conexao();

    $stmt = $conn -> prepare("INSERT INTO users (name, institute_id, email, password) VALUES (?, ?, ?, ?)");
    $stmt -> bind_param("siss", $name, $institute_id, $email, $pass_hash);

    $stmt -> execute();
    $stmt -> close();

    return true;
}

function get_user_by_email($email)
{
    $conn = conexao();

    $stmt = $conn -> prepare("SELECT * FROM users WHERE email = ?");
    $stmt -> bind_param("s", $email);

    $stmt -> execute();

    $result = $stmt -> get_result();
    $user = $result -> fetch_assoc();

    $stmt -> close();

    return $user;
}

function get_user_by_id($id){
    $conn = conexao();
    
    $stmt = $conn -> prepare("SELECT * FROM users WHERE id = ?");
    $stmt -> bind_param("i", $id);

    $stmt -> execute();

    $result = $stmt -> get_result();
    $user = $result -> fetch_assoc();

    $stmt -> close();

    return $user;
}

function save_token($id, $token){
    $conn = conexao();

    $stmt = $conn -> prepare("UPDATE users SET remember_token = ? WHERE id = ?");
    $stmt -> bind_param("si", $token, $id);

    $stmt -> execute();
    $stmt -> close();
}

function get_user_by_token($token){
    $conn = conexao();

    $stmt = $conn -> prepare("SELECT * FROM users WHERE remember_token = ? ");
    $stmt -> bind_param("s", $token);

    $stmt -> execute();

    $result = $stmt ->get_result();
    $user = $result -> fetch_assoc();

    $stmt -> close();

    return $user;
}

function delete_token($id){
    $conn = conexao();

    $stmt = $conn -> prepare("UPDATE users SET remember_token = null WHERE id = ?");
    $stmt -> bind_param("i", $id);

    $stmt -> execute();
    $stmt -> close();
}
?>
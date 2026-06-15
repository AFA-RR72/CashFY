<?php
require_once("cashfy.php");
$conn = conexao();

function email_verify($email)
{
    $conn = conexao();
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result_email = $stmt->get_result();
    if ($result_email->num_rows > 0) {
        return true;
    }
    return false;
}

function criar($name, $institute_id, $email, $pass_hash)
{
    $conn = conexao();
    $stmt = $conn->prepare("INSERT INTO users (name, institute_id, email, password) VALUES (?, ?, ?, ?)");

    $stmt->bind_param("siss", $name, $institute_id, $email, $pass_hash);
    $stmt->execute();
    return true;
}

function get_user($email)
{
    $conn = conexao();
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
?>
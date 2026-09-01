<?php require_once("cashfy.php");

function check_email($email){
    $conn = conn();

    $stmt = $conn -> prepare("SELECT * FROM users WHERE email = ?");
    $stmt -> bind_param("s", $email);

    $stmt -> execute();

    $result = $stmt -> get_result();
    if($result -> num_rows > 0){
        $stmt -> close();
        return true;
    }else{
        $stmt -> close();
        return false;
    }
}

function criar($name, $institute_id, $email, $password, $role){
    $conn = conn();

    $stmt = $conn -> prepare("INSERT INTO users (name, institute_id, email, password, role_id) VALUES (?,?,?,?,?)");
    $stmt -> bind_param("sissi", $name, $institute_id, $email, $password, $role);
    
    $stmt -> execute();
    $stmt -> close();

    return true;
}

function get_user_by_email($email){
    $conn = conn();

    $stmt = $conn -> prepare("SELECT * FROM users WHERE email = ?");
    $stmt ->bind_param("s", $email);

    $stmt -> execute();

    $result = $stmt -> get_result();

    $user = $result -> fetch_assoc();

    $stmt -> close();
    return $user;
}

function get_user_by_id($id){
    $conn = conn();

    $stmt = $conn -> prepare("SELECT * FROM users WHERE id = ?");
    $stmt -> bind_param("i", $id);

    $stmt -> execute();

    $result = $stmt -> get_result();
    $user = $result -> fetch_assoc();

    $stmt -> close();
    return $user;
}

function get_users(){
    $conn = conn();

    $stmt = $conn -> prepare("SELECT * FROM users");
    $stmt -> execute();

    $result = $stmt -> get_result();
    $users = $result -> fetch_all(MYSQLI_ASSOC);

    $stmt ->close();
    return $users;
}

function update_photo($id, $profile_photo){
    $conn = conn();

    $stmt = $conn -> prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
    $stmt -> bind_param("si", $profile_photo, $id);

    $stmt -> execute();
    $stmt -> close();
}

function update_to_seller($id, $contact, $description){
    $conn = conn();

    $stmt  = $conn -> prepare("UPDATE users SET description = ?, phone_number = ?, role_id = 2 WHERE id = ?");
    $stmt -> bind_param("sii", $description, $contact, $id);

    $stmt -> execute();
    $stmt -> close();
}

?>
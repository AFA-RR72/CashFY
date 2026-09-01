<?php require_once("cashfy.php");

function get_categories(){
    $conn = conn();

    $sql = "SELECT * FROM category";
    $categories = mysqli_query($conn, $sql);
    $result = mysqli_fetch_all($categories, MYSQLI_ASSOC);

    return $result;
}

function check_category($category){
    $conn = conn();
    
    $stmt = $conn -> prepare("SELECT * FROM category WHERE id = ?");
    $stmt -> bind_param("i", $category);

    $stmt -> execute();
    if (($stmt -> num_rows()) > 1){
        $stmt -> close();
        return true;
    }
    $stmt -> close();
    return false;
}

?>
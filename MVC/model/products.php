<?php require_once("cashfy.php");

use Dom\Comment;

function get_products(){
    $conn = conn();

    $sql = "SELECT * FROM products";
    $products = mysqli_query($conn, $sql);

    $result = mysqli_fetch_all($products, MYSQLI_ASSOC);
    
    return $result; 
}

function create_product($user, $name, $category, $description, $price, $product_photo){
    $conn = conn();

    $stmt = $conn -> prepare("INSERT INTO products (user_id, name, category_id, description, price, product_photo) VALUE (?, ?, ?, ?, ?, ?)");
    $stmt -> bind_param("isisis", $user, $name, $category, $description, $price, $product_photo);

    $stmt -> execute();
    $stmt -> close();
}

?>
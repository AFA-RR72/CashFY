<?php require_once("cashfy.php");

function create_product($user, $name, $category, $description, $price, $product_photo){
    $conn = conn();

    $stmt = $conn -> prepare("INSERT INTO products (user_id, name, category_id, description, price, product_photo) VALUE (?, ?, ?, ?, ?, ?)");
    $stmt -> bind_param("isisis", $user, $name, $category, $description, $price, $product_photo);

    $stmt -> execute();
    $stmt -> close();
}

function get_products(){
    $conn = conn();

    $sql = "SELECT * FROM products";
    $products = mysqli_query($conn, $sql);

    $result = mysqli_fetch_all($products, MYSQLI_ASSOC);
    
    return $result; 
}

function get_products_by_category($category_id){
    $conn = conn();

    $stmt = $conn -> prepare('SELECT * FROM products WHERE category_id = ?');
    $stmt -> bind_param("i", $category_id);

    $stmt -> execute();

    $result = $stmt -> get_result() -> fetch_all(MYSQLI_ASSOC);

    $stmt -> close();

    return $result;

}


?>
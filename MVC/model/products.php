<?php require_once(__DIR__ . "/../config/cashfy.php");

function create_product($user, $name, $category, $description, $price, $product_photo){
    $conn = conn();

    $stmt = $conn -> prepare("INSERT INTO products (user_id, name, category_id, description, price, product_photo) VALUE (?, ?, ?, ?, ?, ?)");
    $stmt -> bind_param("isisds", $user, $name, $category, $description, $price, $product_photo);

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

function get_product_by_id($id){
    $conn = conn();

    $stmt = $conn -> prepare("SELECT * FROM products WHERE id = ?");
    $stmt -> bind_param('i', $id);

    $stmt -> execute();

    $result = $stmt -> get_result() -> fetch_assoc();

    $stmt -> close();

    return $result;
}

function update_product($id, $name, $price, $category_id, $description){
    $conn = conn();

    $stmt = $conn -> prepare("UPDATE products SET name = ?, price = ?, category_id = ?, description = ? WHERE id = ?");
    $stmt -> bind_param('sdisi', $name, $price, $category_id, $description, $id);

    $stmt -> execute();
    $stmt -> close();
    
    return;
}

function delete_product($id){
    $conn = conn();

    $stmt = $conn -> prepare("DELETE FROM products where id = ?");

    if (!$stmt){
        return false;
    }

    $stmt -> bind_param('i', $id);

    $success = $stmt -> execute();
    $rows_affecteds = $stmt -> affected_rows;

    $stmt ->close();

    return $success && $rows_affecteds > 0;
}


?>
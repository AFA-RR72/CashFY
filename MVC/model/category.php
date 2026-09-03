<?php require_once("cashfy.php");

function get_categories()
{
    $conn = conn();

    $sql = "SELECT * FROM category";
    $categories = mysqli_query($conn, $sql);
    $result = mysqli_fetch_all($categories, MYSQLI_ASSOC);

    return $result;
}

function check_category($category_id)
{
    $conn = conn();

    $stmt = $conn->prepare("SELECT * FROM category WHERE id = ?");
    $stmt->bind_param("i", $category_id);

    $stmt->execute();
    if (($stmt->num_rows()) > 1) {
        $stmt->close();
        return true;
    }
    $stmt->close();
    return false;
}

function get_category_by_slug($slug)
{
    $conn = conn();

    $stmt = $conn->prepare("SELECT * FROM category WHERE slug = ?");
    $stmt->bind_param("s", $slug);

    $stmt->execute();

    $result = $stmt->get_result();
    $category = $result->fetch_assoc();

    $stmt->close();
    return $category;
}

function search_categories($category_name)
{
    $conn = conn();

    $name = trim($category_name);
    $search = "%$category_name%";
    $start = "$category_name%";

    $stmt = $conn->prepare(
        "SELECT * FROM category
        WHERE name LIKE ?
        ORDER BY
           CASE
                WHEN name = ? THEN 1
                WHEN name LIKE ? THEN 2
                ELSE 3
            END,
            name ASC"
    );
    $stmt -> bind_param('sss', $search, $name, $start);

    $stmt -> execute();

    $result = $stmt -> get_result() -> fetch_all(MYSQLI_ASSOC);

    $stmt -> close();

    return $result;
}

?>
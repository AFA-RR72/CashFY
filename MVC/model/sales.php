<?php require_once(__DIR__ . "/../config/cashfy.php");

function get_sales()
{
    $conn = conn();

    $stmt = $conn->prepare("
    SELECT * FROM sales
    ORDER BY date DESC
    LIMIT 10;
    ");
    $stmt->execute();

    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $result;
}

function register_sale($user_id, $product_id, $quantity, $price, $date)
{
    $conn = conn();

    $date = date('y-m-d', strtotime($date));

    $stmt = $conn->prepare("
    INSERT INTO sales (user_id, product_id, quantity, price, date) VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiids", $user_id, $product_id, $quantity, $price, $date);

    $stmt->execute();
    $stmt->close();

    return;
}

function get_total_week($user_id)
{
    $conn = conn();

    $stmt = $conn->prepare("
    SELECT SUM(price) AS 'total_week' FROM sales WHERE user_id = ?
    AND date >= CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY
    AND date < CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY + INTERVAL 7 DAY
    ");
    $stmt->bind_param('i', $user_id);

    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $total = number_format($result['total_week'], 2, ',', '.');

    return $total;
}

function get_total_month($user_id)
{
    $conn = conn();

    $stmt = $conn->prepare("
    SELECT SUM(price) AS 'total_month'
    FROM sales
    WHERE user_id = ?
    AND date >= CURDATE() - INTERVAL (DAY(CURDATE()) - 1) DAY
    AND date < DATE_ADD(
        CURDATE() - INTERVAL (DAY(CURDATE()) - 1) DAY,
        INTERVAL 1 MONTH
    );
    ");
    $stmt->bind_param('i', $user_id);

    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    $total = number_format($result['total_month'], 2, ',', '.');

    return $total;
}

function get_saled_items_month($user_id)
{
    $conn = conn();

    $stmt = $conn->prepare("
    SELECT COUNT(id) as 'saled_items'
    FROM sales
    WHERE user_id = ?
    AND date >= CURDATE() - INTERVAL DAY(CURDATE() - 1) DAY
    AND date < DATE_ADD(
        CURDATE() - INTERVAL (DAY(CURDATE()) - 1) DAY,
        INTERVAL 1 MONTH
    );
    ");
    $stmt->bind_param('i', $user_id);

    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    $stmt->close(); 

    return $result['saled_items'];
}

?>
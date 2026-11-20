<?php require_once(__DIR__ . "/../config/cashfy.php");

function get_institutes()
{
    $conn = conn();

    $stmt = $conn->prepare("SELECT * FROM educational_institute");
    $stmt->execute();

    $result = $stmt->get_result();
    $institutes = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
    return $institutes;
}
?>

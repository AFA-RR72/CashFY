<?php require_once("cashfy.php");

function check_institute($institute_id)
{
    $conn = conn();

    $stmt = $conn->prepare("SELECT * FROM educational_institute WHERE id = ?");
    $stmt->bind_param('i', $institute_id);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $stmt->close();
        return false;
    }
    $stmt ->close();
    return true;
}

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
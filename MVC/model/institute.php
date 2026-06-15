<?php
require_once("cashfy.php");
$conn = conexao();

function institute_verify($institute_id){
    $conn = conexao();

    $stmt = $conn->prepare(
        "SELECT * FROM educational_institute WHERE id = ?"
    );
    $stmt->bind_param(
        'i',
        $institute_id
    );
    $stmt->execute();
    $result_inst = $stmt->get_result();
    if($result_inst->num_rows > 0){
        return false;
    }
    return true;
}

function getinstitutes(){
    $conn = conexao();
    $sql = "SELECT * FROM educational_institute";
    $result = mysqli_query($conn, $sql);

    $institutes = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $institutes[] = $row;
    }
    return $institutes;
}
?>
<?php
function getinstitutes(){
    $conn = conexao();

    $sql = "SELECT * FROM educational_institute";
    $result = mysqli_query($conn, $sql);

    $institutes = [];

    while ($row = mysqli_fetch_assoc($result)){
        $institutes[] = $row;
    }
    return $institutes;
}

?>
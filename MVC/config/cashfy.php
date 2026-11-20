<?php
/* Conexão */
function conn() {
    $conn = mysqli_connect(
        'srv1056.hstgr.io',
        'u359327007_garotes',
        'Grupo02_1234596351',
        'u359327007_cashfy',
        3306
    );

    return $conn;
}
?>
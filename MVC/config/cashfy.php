<?php

function conn() {
    $config = require dirname(__DIR__, 3) . '/cashfy-private/database.php';

    return mysqli_connect(
        $config['host'],
        $config['username'],
        $config['password'],
        $config['database'],
        $config['port']
    );
}
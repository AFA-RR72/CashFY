<?php

function conn() {
    $config = require __DIR__ . '/../../../../cashfy-private/database.php';

    return mysqli_connect(
        $config['host'],
        $config['username'],
        $config['password'],
        $config['database'],
        $config['port']
    );
}
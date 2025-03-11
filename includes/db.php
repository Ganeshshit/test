<?php
require_once 'config.php';

function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    
    if ($conn->connect_error) {
        echo "connection_failed";
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}
?>
<?php
$host = 'localhost';
$db_name = 'ecommerce';
$username = 'root';
$password = '';
    
$conn = new mysqli($host, $username, $password, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0); // Exit for preflight requests
}

?>

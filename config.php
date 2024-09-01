<?php
$host = 'ecommerce.ctsqkye6wkep.eu-north-1.rds.amazonaws.com';
$db_name = 'ecommerce';
$username = 'root';
$password = 'Assem123Assem';
$port= '3306'
    
$conn = new mysqli($host, $username, $password, $db_name,$port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0); // Exit for preflight requests
}

?>

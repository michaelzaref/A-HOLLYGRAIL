<?php
$host = 'ecommerce.ctsqkye6wkep.eu-north-1.rds.amazonaws.com';
$username = 'root'; // Replace with your RDS username
$password = 'Assem123Assem'; // Password provided
$dbname = 'ecommerce'; // Replace with your database name
$port = 3306; // Default MySQL port

// Create connection
$conn = new mysqli($host, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0); // Exit for preflight requests
}

?>

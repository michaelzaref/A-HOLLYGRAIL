<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json"); // Set content type to JSON

include('../config.php');

$sql = "SELECT * FROM products";
$result = $conn->query($sql);
$products = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Decode the JSON-encoded image paths into an array
        $row['image'] = json_decode($row['image'], true);
        // print_r($row['color']);
        // $row['color'] = json_decode($row['color'], true);
        $products[] = $row;
    }
}

echo json_encode($products);

$conn->close();
?>

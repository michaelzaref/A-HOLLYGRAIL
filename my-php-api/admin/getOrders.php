<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include("validate-token.php");
// Handle CORS
header("Access-Control-Allow-Origin: http://localhost:3000"); // Adjust origin as needed
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
include("../config.php");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

$token = $_POST['token'] ?? null; // Use null coalescing operator for better readability
// print_r($token);
if (!$token) {
    echo json_encode(["status" => "error", "message" => "Token is not set"]);
    exit;
}



if (!verifyToken($token)) {
    echo json_encode(["status" => "error", "message" => "Invalid token"]);
    exit;
}

// Get orders
$sql = "SELECT * FROM orders";
$result = $conn->query($sql);

if ($result === false) {
    echo json_encode(["status" => "error", "message" => "Query failed: " . $conn->error]);
    exit;
}

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

echo json_encode(['status' => 'success', 'data' => $orders]);

$conn->close();
?>

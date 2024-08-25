<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle CORS
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
include("validate-token.php");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

include("../config.php");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Get statistics about order statuses
$sql = "SELECT status, COUNT(*) AS count FROM orders GROUP BY status";
$result = $conn->query($sql);

$stats = [];
while ($row = $result->fetch_assoc()) {
    $stats[] = $row;
}

echo json_encode(['status' => 'success', 'data' => $stats]);

$conn->close();
?>
    
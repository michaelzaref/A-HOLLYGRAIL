<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include("validate-token.php");
// Handle CORS
header("Access-Control-Allow-Origin: http://localhost:3000");
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

// Get JSON input from POST request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['order_id']) || !isset($data['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

$order_id = $data['order_id'];
$status = $data['status'];
$token = $data['token'];
if(!isset($token)){
    echo json_encode(["status" => "error", "message" => "token is not set" ]);
    exit;
}
if(!verifyToken($token)){
    echo json_encode(["status" => "error", "message" => "token" ]);
    exit;
}
$sql = "UPDATE orders SET status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param('si', $status, $order_id);
if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Order status updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error]);
}

$conn->close();
?>

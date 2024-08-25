<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle CORS
header("Access-Control-Allow-Origin: *");
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

// Get order_id from query string
$order_id = $_GET['order_id'];

if (!isset($order_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}
if(!isset($_GET['token'])){
    echo json_encode(["status" => "error", "message" => "token is not set" ]);
    exit;
}
if(!verifyToken($_GET['token'])){
    echo json_encode(["status" => "error", "message" => "token" ]);
    exit;
}
// Get order information
$sql = "SELECT o.*, oi.product_id, oi.quantity, oi.price
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param('i', $order_id);
$stmt->execute();
$result = $stmt->get_result();

$order_info = [];
while ($row = $result->fetch_assoc()) {
    $order_info[] = $row;
}

echo json_encode(['status' => 'success', 'data' => $order_info]);

$conn->close();
?>

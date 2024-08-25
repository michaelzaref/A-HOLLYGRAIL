<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust the URL to match your React app's origin
header('Access-Control-Allow-Credentials: true'); // Allow credentials
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
include("../auth/validate-token.php");
include("../config.php");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$user_id = isset($data['user_id']) ? $data['user_id'] : null;
$product_id = isset($data['product_id']) ? $data['product_id'] : null;
$quantity = isset($data['quantity']) ? $data['quantity'] : 1;

if ($user_id === null || $product_id === null || $quantity === null) {
    echo json_encode(["status" => "error", "message" => "Invalid input."]);
    exit;
}

// Check if user_id exists
$user_check_sql = "SELECT id FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_check_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_stmt->store_result();

if ($user_stmt->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "User not found."]);
    $user_stmt->close();
    $conn->close();
    exit;
}
$user_stmt->close();

// Check if product_id exists
$product_check_sql = "SELECT id FROM products WHERE id = ?";
$product_stmt = $conn->prepare($product_check_sql);
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product_stmt->store_result();

if ($product_stmt->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Product not found."]);
    $product_stmt->close();
    $conn->close();
    exit;
}
$product_stmt->close();

// Insert or update cart
$sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
    exit;
}
if(!isset($data['token'])){
    echo json_encode(["status" => "error", "message" => "token not set" ]);
    exit;
}
if(!verifyToken($data['token'])){
    echo json_encode(["status" => "error", "message" => "token not valid" ]);
    exit;
}

$stmt->bind_param("iii", $user_id, $product_id, $quantity);

if ($stmt->execute() )  {
    echo json_encode(["status" => "success", "message" => "Item added to cart."]);
} else {
    echo json_encode(["status" => "error", "message" => "Execute failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

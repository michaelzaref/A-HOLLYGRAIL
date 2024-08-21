<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
include("../auth/validate-token.php");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$user_id = isset($data['user_id']) ? $data['user_id'] : null;
$product_id = isset($data['product_id']) ? $data['product_id'] : null;

if ($user_id === null || $product_id === null) {
    echo json_encode(["status" => "error", "message" => "Invalid input."]);
    exit;
}

include("../config.php");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
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

$sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare statement failed: " . $conn->error]);
    $conn->close();
    exit;
}

$stmt->bind_param("ii", $user_id, $product_id);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Item removed from cart."]);
} else {
    echo json_encode(["status" => "error", "message" => "Execute failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

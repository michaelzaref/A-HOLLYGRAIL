<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
include("../auth/validate-token.php");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

// Corrected the access of $data properties
if (!isset($data->token)) {
    echo json_encode(["status" => "error", "message" => "Token not set"]);
    exit;
}

if (!verifyToken($data->token)) {
    echo json_encode(["status" => "error", "message" => "Token not valid"]);
    exit;
}

$user_id = $data->user_id;
$product_id = $data->product_id;

if ($user_id === null || $product_id === null) {
    echo json_encode(["status" => "error", "message" => "Invalid input."]);
    exit;
}

include("../config.php");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

$sql = "DELETE FROM cart WHERE user_id = ? AND id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare statement failed: " . $conn->error]);
    $conn->close();
    exit;
}

$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(["status" => "success", "message" => "Item removed from cart."]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to remove item from cart."]);
}

$stmt->close();
$conn->close();
?>

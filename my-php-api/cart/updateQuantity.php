<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

$user_id = $data->user_id ?? null;
$product_id = $data->product_id ?? null;
$quantity = $data->quantity ?? null;

if ($user_id === null || $product_id === null || $quantity === null || $quantity < 1) {
    echo json_encode(["status" => "error", "message" => "Invalid input."]);
    exit;
}

include("../config.php");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

$sql = "UPDATE cart SET quantity = ? WHERE user_id = ? AND id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare statement failed: " . $conn->error]);
    $conn->close();
    exit;
}

$stmt->bind_param("iii", $quantity, $user_id, $product_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(["status" => "success", "message" => "Quantity updated successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update quantity."]);
}

$stmt->close();
$conn->close();
?>

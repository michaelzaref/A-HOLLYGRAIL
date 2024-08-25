<?php
include('../config.php');
include('../cors.php');  // Include CORS middleware

header("Content-Type: application/json");

require '../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
    echo json_encode(["success" => false, "message" => "Authorization header not found."]);
    exit();
}

$authHeader = $_SERVER['HTTP_AUTHORIZATION'];
$arr = explode(" ", $authHeader);

if (count($arr) !== 2) {
    echo json_encode(["success" => false, "message" => "Invalid Authorization header format."]);
    exit();
}

$jwt = $arr[1];
$secret_key = "YOUR_SECRET_KEY";

try {
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    $user_id = $decoded->data->id;

    // Fetch user profile from the database
    $sql = "SELECT id, username, email FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    echo json_encode([
        "success" => true,
        "user" => $user
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Access denied.",
        "error" => $e->getMessage()
    ]);
}

$conn->close();
?>

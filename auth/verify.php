<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require '../vendor/autoload.php';

// Fetch and process input data
$data = json_decode(file_get_contents("php://input"), true);
$email = isset($data['email']) ? $data['email'] : null;
$code = isset($data['code']) ? $data['code'] : null;

if ($email === null || $code === null) {
    echo json_encode(["status" => "error", "message" => "Invalid input."]);
    exit;
}

// Database connection
include("../config.php");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Get user_id based on email from unverified_users
$user_check_sql = "SELECT id, name, email, password, address FROM unverified_users WHERE email = ?";
$user_stmt = $conn->prepare($user_check_sql);
$user_stmt->bind_param("s", $email);
$user_stmt->execute();
$user_stmt->bind_result($user_id, $username, $email, $hashed_password, $address);
$user_stmt->fetch();
$user_stmt->close();

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "Email not found."]);
    $conn->close();
    exit;
}

// Check if the code matches
$authcode_check_sql = "SELECT code FROM authcode WHERE user_id = ?";
$authcode_stmt = $conn->prepare($authcode_check_sql);
$authcode_stmt->bind_param("i", $user_id);
$authcode_stmt->execute();
$authcode_stmt->bind_result($stored_code);
$authcode_stmt->fetch();
$authcode_stmt->close();

if ($stored_code && $stored_code == $code) {
    // Move user from unverified_users to users table
    $insert_user_sql = "INSERT INTO users (name, email, password, address) VALUES (?, ?, ?, ?)";
    $insert_user_stmt = $conn->prepare($insert_user_sql);
    $insert_user_stmt->bind_param("ssss", $username, $email, $hashed_password, $address);
    
    if ($insert_user_stmt->execute()) {
        // Delete from unverified_users and authcode
        $delete_unverified_user_sql = "DELETE FROM unverified_users WHERE id = ?";
        $delete_unverified_user_stmt = $conn->prepare($delete_unverified_user_sql);
        $delete_unverified_user_stmt->bind_param("i", $user_id);
        $delete_unverified_user_stmt->execute();
        $delete_unverified_user_stmt->close();

        $delete_authcode_sql = "DELETE FROM authcode WHERE user_id = ?";
        $delete_authcode_stmt = $conn->prepare($delete_authcode_sql);
        $delete_authcode_stmt->bind_param("i", $user_id);
        $delete_authcode_stmt->execute();
        $delete_authcode_stmt->close();

        echo json_encode(["status" => "success", "message" => "Verification successful."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to insert user into users table."]);
    }

    $insert_user_stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid verification code."]);
}

$conn->close();
?>

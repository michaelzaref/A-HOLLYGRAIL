<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require '../vendor/autoload.php';
require 'sendmail.php'; // Assumes sendEmail function is defined in this file

// Fetch and process input data
$data = json_decode(file_get_contents("php://input"), true);
$email = isset($data['email']) ? $data['email'] : null;

if ($email === null) {
    echo json_encode(["status" => "error", "message" => "Invalid input."]);
    exit;
}

// Database connection
include("../config.php");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Check if the email exists
$user_check_sql = "SELECT id, name FROM users WHERE email = ?";
$user_stmt = $conn->prepare($user_check_sql);

if (!$user_stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare statement failed: " . $conn->error]);
    $conn->close();
    exit;
}

$user_stmt->bind_param("s", $email);
$user_stmt->execute();
$user_stmt->store_result();

if ($user_stmt->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "Email does not exist."]);
    $user_stmt->close();
    $conn->close();
    exit;
}

$user_stmt->bind_result($user_id, $username);
$user_stmt->fetch();
$user_stmt->close();

// Generate a new verification code
$verification_code = rand(100000, 999999);

// Update the verification code in the authcode table
$authcode_sql = "INSERT INTO authcode (user_id, code) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE code = VALUES(code), created_at = NOW()";
$authcode_stmt = $conn->prepare($authcode_sql);
$authcode_stmt->bind_param("is", $user_id, $verification_code);
$authcode_stmt->execute();
$authcode_stmt->close();

// Send verification email
$subject = "Email Verification Code";
$htmlBody = "<p>Your new verification code is: <strong>$verification_code</strong></p>";
$altBody = "Your new verification code is: $verification_code";
sendEmail($email, $username, $subject, $htmlBody, $altBody);

echo json_encode(["status" => "success", "message" => "Verification code resent successfully."]);

$conn->close();
?>

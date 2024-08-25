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
require 'sendmail.php'; // Assumes sendEmail function is defined in this file

// Fetch and process input data
$data = json_decode(file_get_contents("php://input"), true);
$username = isset($data['username']) ? $data['username'] : null;
$password = isset($data['password']) ? $data['password'] : null;
$email = isset($data['email']) ? $data['email'] : null;
$address = isset($data['address']) ? $data['address'] : null;

if ($username === null || $password === null || $email === null) {
    echo json_encode(["status" => "error", "message" => "Invalid input."]);
    exit;
}

// Database connection
include("../config.php");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Check if the email already exists in the users or unverified_users table
$user_check_sql = "SELECT id FROM users WHERE email = ? UNION SELECT id FROM unverified_users WHERE email = ?";
$user_stmt = $conn->prepare($user_check_sql);

if (!$user_stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare statement failed: " . $conn->error]);
    $conn->close();
    exit;
}

$user_stmt->bind_param("ss", $email, $email);
$user_stmt->execute();
$user_stmt->store_result();

if ($user_stmt->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Email already exists."]);
    $user_stmt->close();
    $conn->close();
    exit;
}
$user_stmt->close();

// Hash the password
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Insert the new user into the unverified_users table
$sql = "INSERT INTO unverified_users (name, email, password, address) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare statement failed: " . $conn->error]);
    $conn->close();
    exit;
}

$stmt->bind_param("ssss", $username, $email, $hashed_password, $address);

if ($stmt->execute()) {
    $user_id = $stmt->insert_id;

    // Generate a random verification code
    $verification_code = rand(100000, 999999);

    // Store the verification code in the authcode table
    $authcode_sql = "INSERT INTO authcode (user_id, code) VALUES (?, ?)";
    $authcode_stmt = $conn->prepare($authcode_sql);

    if (!$authcode_stmt) {
        echo json_encode(["status" => "error", "message" => "Prepare statement for authcode failed: " . $conn->error]);
        $conn->close();
        exit;
    }

    $authcode_stmt->bind_param("is", $user_id, $verification_code);

    if ($authcode_stmt->execute()) {
        // Send verification email
        $subject = "Email Verification Code";
        $htmlBody = "<p>Your verification code is: <strong>$verification_code</strong></p>";
        $altBody = "Your verification code is: $verification_code";
        sendEmail($email, $username, $subject, $htmlBody, $altBody);

        echo json_encode(["status" => "success", "message" => "User registered successfully. Please check your email for the verification code."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to insert verification code: " . $authcode_stmt->error]);
    }

    $authcode_stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Execute failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

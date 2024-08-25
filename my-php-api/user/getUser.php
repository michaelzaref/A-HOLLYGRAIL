<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
include("../auth/validate-token.php");
include('../config.php');

// Sample database connection

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection error']));
}

$user_id = $_GET['user_id'];
$token = $_GET['token'];

// Verify token (add your token verification logic)
if (!verifyToken($token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

// Fetch user data
$sql = "SELECT id, name, email, role, address, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode(['success' => true, 'user' => $user]);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}

$stmt->close();
$conn->close();
?>

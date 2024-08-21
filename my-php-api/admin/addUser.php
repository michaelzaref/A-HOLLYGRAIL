<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
include("validate-token.php");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Fetch and process input data
$data = json_decode(file_get_contents("php://input"), true);
$name = isset($data['name']) ? $data['name'] : null;
$passwordu = isset($data['password']) ? $data['password'] : null;
$email = isset($data['email']) ? $data['email'] : null;

if ($name === null || $passwordu === null) {
    echo json_encode(["status" => "error", "message" => "Invalid input."]);
    exit;
}
include("../config.php");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}
  

// Check if the name already exists
$user_check_sql = "SELECT id FROM users WHERE email = ?";
$user_stmt = $conn->prepare($user_check_sql);
  
if (!$user_stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare statement failed: " . $conn->error]);
    $conn->close();
    exit;
}
 
$user_stmt->bind_param("s", $email);
$user_stmt->execute();
$user_stmt->store_result();

if ($user_stmt->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "name already exists."]);
    $user_stmt->close();
    $conn->close();
    exit;
}
$user_stmt->close();
 
// Hash the password
$hashed_password = password_hash($passwordu, PASSWORD_BCRYPT);

// Insert the new user into the database
$sql = "INSERT INTO users (name,email, password) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare statement failed: " . $conn->error]);
    $conn->close();
    exit;
}
 
$stmt->bind_param("sss", $name,$email, $hashed_password);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "User registered successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Execute failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

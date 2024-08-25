<?php
header("Access-Control-Allow-Origin: *");
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
$username = isset($data['payload']['username']) ? $data['payload']['username'] : null;

$password = isset($data['payload']['password']) ? $data['payload']['password'] : null;
$email = isset($data['payload']['email']) ? $data['payload']['email'] : null;
$role = isset($data['payload']['role']) ? $data['payload']['role'] : null;
$token = isset($data['token']) ? $data['token'] : null;
if(!isset($token)){
    echo json_encode(["status" => "error", "message" => "token is not set" ]);
    exit;
}
if(!verifyToken($token)){
    echo json_encode(["status" => "error", "message" => "token" ]);
    exit;
}

$table="";
if ($role == "admin"){
    $table = "admins";
}elseif($role == "user"){
    $table = "users";
}

if ($username === null || $password === null) {
    echo json_encode(["status" => "error", "message" => "Invalid input."]);
    exit;
}

include("../config.php");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Check if the username already exists
$user_check_sql = "SELECT id FROM users WHERE email = ?";
$user_stmt = $conn->prepare($user_check_sql);

if (!$user_stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare statement failed: " . $conn->error]);
    $conn->close();
    exit;
}

$user_stmt->bind_param("s", $username);
$user_stmt->execute();
$user_stmt->store_result();

if ($user_stmt->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Username already exists."]);
    $user_stmt->close();
    $conn->close();
    exit;
}
$user_stmt->close();

// Hash the password
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Insert the new user into the database
$sql = "INSERT INTO ".$table." (name,email, password) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare statement failed: " . $conn->error]);
    $conn->close();
    exit;
}

$stmt->bind_param("sss", $username,$email, $hashed_password);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "User registered successfully."]);
} else {
    echo json_encode(["status" => "error", "message" => "Execute failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

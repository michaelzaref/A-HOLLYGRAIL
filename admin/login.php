<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

include("../config.php");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Fetch and process input data
$data = json_decode(file_get_contents("php://input"), true);
$username = isset($data['username']) ? $data['username'] : null;
$password = isset($data['password']) ? $data['password'] : null;

if ($username && $password) {
    // Perform login logic
    $stmt = $conn->prepare("SELECT id, password FROM admins WHERE email = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($userId, $hashedPassword);
        $stmt->fetch();

        if (password_verify($password, $hashedPassword)) {
            // Generate a random session number
            $sessionNumber = rand(100000, 999999);
            
            // Set an expiration time (e.g., 1 hour from now)
            $expirationTime = time() + 3600; // 1 hour = 3600 seconds
            $expirationDatetime = date('Y-m-d H:i:s', $expirationTime);
            
            // Your token data
            $tokenData = [
                "data" => [
                    "userId" => $userId,
                    "email" => $username,
                    "sessionNumber" => $sessionNumber
                ],
                "exp" => $expirationTime
            ];
            
            // Encode the token
            $jsonTokenData = json_encode($tokenData);

            // Encode the JSON string using base64
            $token = base64_encode($jsonTokenData);

            // Store the session info in the database
            $insertStmt = $conn->prepare("INSERT INTO admin_sessions (user_id, session_number, token, expiration_time) VALUES (?, ?, ?, ?)");
            $insertStmt->bind_param("iiss", $userId, $sessionNumber, $token, $expirationDatetime);
            $insertStmt->execute();

            if ($insertStmt->affected_rows > 0) {
                echo json_encode(["status" => "success", "userId" => $userId,"role" => "admin", "token" => $token]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to store session"]);
            }

            $insertStmt->close();
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
    }

    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Invalid input"]);
}

$conn->close();
?>

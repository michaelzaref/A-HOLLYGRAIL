<?php
header("Access-Control-Allow-Origin: *"); // Adjust the URL to match your React app's origin
header("Access-Control-Allow-Credentials: true"); // Allow credentials
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
include("../auth/validate-token.php");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0); // Stop execution for preflight requests
}

// Database connection
include("../config.php");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user_id is provided
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user ID.']);
    exit;
}

// Fetch cart items
$sql = "SELECT c.id,c.product_id, c.quantity, p.name, p.price, p.image
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
while ($row = $result->fetch_assoc()) {
    $row['image'] = json_decode($row['image'], true);
    $cart_items[] = $row;
}
if(!isset($_GET['token'])){
    echo json_encode(["status" => "error", "message" => "token is not set" ]);
    exit;
}
if(!verifyToken($_GET['token'])){
    echo json_encode(["status" => "error", "message" => "token" ]);
    exit;
}

echo json_encode($cart_items);

$stmt->close();
$conn->close();

?>

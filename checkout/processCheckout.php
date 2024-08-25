<?php
include("../auth/validate-token.php");
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle CORS
header("Access-Control-Allow-Origin: *"); // Replace with your frontend URL
header("Access-Control-Allow-Methods: POST, OPTIONS"); // Allow POST and OPTIONS methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allow headers
header("Access-Control-Allow-Credentials: true"); // Allow credentials

// Check for preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No content response
    exit;
}

// Database connection
include("../config.php");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Get JSON input from POST request
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['user_id']) || !isset($data['address'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

$user_id = $data['user_id'];
$address = $data['address'];
$address = implode(" ", $address);
if(!isset($data['token'])){
    echo json_encode(["status" => "error", "message" => "token not set" ]);
    exit;
}
if(!verifyToken($data['token'])){
    echo json_encode(["status" => "error", "message" => "token not valid" ]);
    exit;
}
try {
    // Start a transaction
    $conn->begin_transaction();

    // Fetch cart items
    $sql = "SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Prepare order items array
    $cart_items = [];
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
    }

    if (empty($cart_items)) {
        echo json_encode(['status' => 'error', 'message' => 'Cart is empty']);
        $conn->rollback();
        exit;
    }

    // Calculate the total price
    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    // Insert order into orders table
    $sql = "INSERT INTO orders (user_id, total, address, status) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $status = 'pending'; // Default status

    $stmt->bind_param('iiss', $user_id, $total, $address, $status);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $order_id = $stmt->insert_id;

    // Insert order items into order_items table
    $sql = 'INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)';
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    foreach ($cart_items as $item) {
        $stmt->bind_param('iiid', $order_id, $item['product_id'], $item['quantity'], $item['price']);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
    }

    // Commit transaction
    $conn->commit();

    // Clear the cart
    $sql = 'DELETE FROM cart WHERE user_id = ?';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param('i', $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    echo json_encode(['status' => 'success', 'message' => 'Order placed successfully']);
} catch (Exception $e) {
    // Rollback transaction if something failed
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Failed to process order: ' . $e->getMessage()]);
}

$conn->close();
?>

<?php
header('Content-Type: application/json');

// Include the database and authentication scripts
include("../config.php");
include("../auth/validate-token.php");

$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Check if user_id and token are present
if (!isset($data['user_id']) || !isset($data['token'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing user_id or token."
    ]);
    exit;
}

$user_id = $data['user_id'];
$token = $data['token'];

// Verify the token using the function from validate-token.php
if (!verifyToken($token)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid or expired token."
    ]);
    exit;
}

// Fetch user orders
$sql = "SELECT * FROM orders WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($order = $result->fetch_assoc()) {
    // Fetch order items
    $order_id = $order['id'];
    $item_sql = "SELECT oi.*, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
    $item_stmt = $conn->prepare($item_sql);
    $item_stmt->bind_param("i", $order_id);
    $item_stmt->execute();
    $item_result = $item_stmt->get_result();

    $items = [];
    while ($item = $item_result->fetch_assoc()) {
        $items[] = $item;
    }

    $order['items'] = $items;
    $orders[] = $order;
}

echo json_encode($orders);

$stmt->close();
$conn->close();
?>

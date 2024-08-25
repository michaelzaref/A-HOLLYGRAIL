<?php
include('../config.php');
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"));

$id = $data->user_id;
$token = $data->token;

// Begin transaction
$conn->begin_transaction();

try {
    // Delete related records in the order_items table
    $sqlOrderItems = "DELETE FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE user_id='$id')";
    if (!$conn->query($sqlOrderItems)) {
        throw new Exception($conn->error);
    }

    // Delete related records in the orders table
    $sqlOrders = "DELETE FROM orders WHERE user_id='$id'";
    if (!$conn->query($sqlOrders)) {
        throw new Exception($conn->error);
    }

    // Delete related records in the cart table
    $sqlCart = "DELETE FROM cart WHERE user_id='$id'";
    if (!$conn->query($sqlCart)) {
        throw new Exception($conn->error);
    }

    // Delete related records in the user_sessions table
    $sqlUserSessions = "DELETE FROM user_sessions WHERE user_id='$id'";
    if (!$conn->query($sqlUserSessions)) {
        throw new Exception($conn->error);
    }

    // Delete the user
    $sqlUser = "DELETE FROM users WHERE id='$id'";
    if (!$conn->query($sqlUser)) {
        throw new Exception($conn->error);
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction if an error occurs
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>

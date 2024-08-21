<?php
include('../config.php');
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"));

$id = $data->id;
$username = $data->username;
$email = $data->email;

$sql = "UPDATE users SET username='$username', email='$email' WHERE id='$id'";
if ($conn->query($sql) === TRUE) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}

$conn->close();
?>

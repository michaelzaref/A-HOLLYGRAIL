<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");
include('../config.php');
include("../admin/validate-token.php");

$data = json_decode(file_get_contents("php://input"));
$token = $data->token;
// print_r($data);
if(!isset($token)){
    echo json_encode(["status" => "error", "message" => "token is not set" ]);
    exit;
}
if(!verifyToken($token)){
    echo json_encode(["status" => "error", "message" => "token" ]);
    exit;
}
$id = $data->product_id;
$sql = "UPDATE products SET del='1' WHERE id='$id'";
if ($conn->query($sql) === TRUE) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}

$conn->close();
?>

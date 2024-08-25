<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json; charset=UTF-8");

include("validate-token.php");

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
$conn->close();
?>

<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include('config.php');

// Get the request method
$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Basic routing
switch ($request_method) {
    case 'GET':
        if (preg_match('/\/products/', $request_uri)) {
            include('products/getProducts.php');
        } elseif (preg_match('/\/cart/', $request_uri)) {
            include('cart/getCart.php');
        } elseif (preg_match('/\/user/', $request_uri)) {
            include('user/getUser.php');
        }
        break;

    case 'POST':
        if (preg_match('/\/auth\/login/', $request_uri)) {
            include('auth/login.php');
        } elseif (preg_match('/\/auth\/register/', $request_uri)) {
            include('auth/register.php');
        } elseif (preg_match('/\/products\/add/', $request_uri)) {
            include('products/addProduct.php');
        } elseif (preg_match('/\/cart\/add/', $request_uri)) {
            include('cart/addToCart.php');
        } elseif (preg_match('/\/checkout/', $request_uri)) {
            include('checkout/processCheckout.php');
        }
        break;

    case 'PUT':
        parse_str(file_get_contents("php://input"), $_PUT);
        if (preg_match('/\/products\/update/', $request_uri)) {
            include('products/updateProduct.php');
        } elseif (preg_match('/\/user\/update/', $request_uri)) {
            include('user/updateUser.php');
        }
        break;

    case 'DELETE':
        parse_str(file_get_contents("php://input"), $_DELETE);
        if (preg_match('/\/products\/delete/', $request_uri)) {
            include('products/deleteProduct.php');
        } elseif (preg_match('/\/cart\/remove/', $request_uri)) {
            include('cart/removeFromCart.php');
        } elseif (preg_match('/\/user\/delete/', $request_uri)) {
            include('user/deleteUser.php');
        }
        break;

    default:
        echo json_encode(['message' => 'Invalid Request Method']);
        break;
}
?>

<?php
include("validate-token.php");
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle CORS
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, OPTIONS");
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

$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    
    $sizes = [
        'xs' => $_POST['xs'],
        's' => $_POST['s'],
        'M' => $_POST['M'],
        'L' => $_POST['L'],
        'XL' => $_POST['XL'],
        'XXL' => $_POST['XXL'],
        'XXXL' => $_POST['XXXL']
    ];
    $sizesJson = json_encode($sizes);

    $colorsJson = isset($_POST['colors']) ? json_encode($_POST['colors']) : '[]';
    $tagsJson = isset($_POST['tags']) ? json_encode($_POST['tags']) : '[]';
    $token = $_POST['token'];

    if (!isset($token)) {
        echo json_encode(["status" => "error", "message" => "Token is not set"]);
        exit;
    }
    if (!verifyToken($token)) {
        echo json_encode(["status" => "error", "message" => "Invalid token"]);
        exit;
    }

    // Handle file upload if files are provided
    $imagePaths = [];
    $uploadOk = 1;
    $imagesJson = "";

    if (isset($_FILES['images'])) {
        $targetDir = "../images/";

        foreach ($_FILES['images']['name'] as $key => $imageName) {
            $targetFile = $targetDir . basename($imageName);
            $relativePath = "/images/" . basename($imageName);

            $check = getimagesize($_FILES["images"]["tmp_name"][$key]);
            if ($check === false) {
                echo json_encode(['status' => 'error', 'message' => "File {$imageName} is not an image."]);
                $uploadOk = 0;
                continue;
            }

            if ($_FILES["images"]["size"][$key] > 5000000) {
                echo json_encode(['status' => 'error', 'message' => "File {$imageName} is too large."]);
                $uploadOk = 0;
                continue;
            }

            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                echo json_encode(['status' => 'error', 'message' => "Only JPG, JPEG, PNG & GIF files are allowed for {$imageName}."]);
                $uploadOk = 0;
                continue;
            }

            if (move_uploaded_file($_FILES["images"]["tmp_name"][$key], $targetFile)) {
                $imagePaths[] = $relativePath;
            } else {
                echo json_encode(['status' => 'error', 'message' => "Error uploading file {$imageName}."]);
                $uploadOk = 0;
            }
        }

        if ($uploadOk && !empty($imagePaths)) {
            $imagesJson = json_encode($imagePaths);
        }
    }

    // Prepare the SQL query
    if (empty($imagesJson)) {
        $sql = "UPDATE products SET name = ?, description = ?, price = ?, size = ?, color = ?, tags = ?, stock = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssdssiis', $name, $description, $price, $sizesJson, $colorsJson, $tagsJson, $stock, $id);
    } else {
        $sql = "UPDATE products SET name = ?, description = ?, price = ?, size = ?, color = ?, tags = ?, stock = ?, image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssdssiiss', $name, $description, $price, $sizesJson, $colorsJson, $tagsJson, $stock, $imagesJson, $id);
    }

    // Execute the query
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Product updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error]);
    }

    $stmt->close();
}

$conn->close();
?>

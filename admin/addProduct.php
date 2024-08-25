<?php
include("validate-token.php");
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle CORS
header("Access-Control-Allow-Origin: *");
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

// Check if the form data was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure the necessary POST fields are set
    if (!isset($_POST['name']) || !isset($_POST['description']) || !isset($_POST['price']))  {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
        exit;
    }

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
    $colors = isset($_POST['colors']) ? json_encode($_POST['colors']) : '[]';
    $tags = isset($_POST['tags']) ? json_encode($_POST['tags']) : '[]';
    $token = $_POST['token'];

    if (!isset($token)) {
        echo json_encode(["status" => "error", "message" => "Token is not set"]);
        exit;
    }
    if (!verifyToken($token)) {
        echo json_encode(["status" => "error", "message" =>  "token"]);
        exit;
    }
    
    // Handle file upload
    $targetDir = "../images/"; // Directory to save the uploaded files
    $imagePaths = []; // Array to hold the paths of the uploaded images
    $uploadOk = 1; // Flag to track the overall success of the upload process

    // Loop through each file in the 'images' input field
    foreach ($_FILES['images']['name'] as $key => $imageName) {
        // Define the target file path for the current image
        $targetFile = $targetDir . basename($imageName);
        $relativePath = "/images/" . basename($imageName);

        // Check if the file is an actual image
        $check = getimagesize($_FILES["images"]["tmp_name"][$key]);
        if ($check === false) {
            echo json_encode(['status' => 'error', 'message' => "File {$imageName} is not an image."]);
            $uploadOk = 0;
            continue; // Skip the current iteration
        }

        // Check file size (example: 5MB max)
        if ($_FILES["images"]["size"][$key] > 5000000) {
            echo json_encode(['status' => 'error', 'message' => "Sorry, your file {$imageName} is too large."]);
            $uploadOk = 0;
            continue; // Skip the current iteration
        }

        // Allow certain file formats
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            echo json_encode(['status' => 'error', 'message' => "Sorry, only JPG, JPEG, PNG & GIF files are allowed for {$imageName}."]);
            $uploadOk = 0;
            continue; // Skip the current iteration
        }

        // Move the uploaded file to the target directory
        if (move_uploaded_file($_FILES["images"]["tmp_name"][$key], $targetFile)) {
            // File uploaded successfully
            $imagePaths[] = $relativePath;
        } else {
            echo json_encode(['status' => 'error', 'message' => "Sorry, there was an error uploading your file {$imageName}."]);
            $uploadOk = 0;
        }
    }

    // Check if any uploads were successful
    if ($uploadOk && !empty($imagePaths)) {
        // Convert the array of image paths to a JSON string
        $imagesJson = json_encode($imagePaths);

        $sql = "INSERT INTO products (name, description, price, image, size, color, tags, stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param('ssdsssss', $name, $description, $price, $imagesJson, $sizesJson, $colors, $tags, $stock);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Product added successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No valid images were uploaded.']);
    }
}

$conn->close();

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

// Database connection
include("../config.php");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Check if the form data was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Parse the JSON input
    $data = json_decode(file_get_contents("php://input"), true);

    // Ensure the necessary fields are set
    if (!isset($data['id']) || !isset($data['name']) || !isset($data['description']) || !isset($data['price']) || !isset($data['token'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
        exit;
    }

    $id = $data['id'];
    $name = $data['name'];
    $description = $data['description'];
    $price = $data['price'];
    $token = $data['token'];

    // Token validation
    if (!verifyToken($token)) {
        echo json_encode(["status" => "error", "message" => "Invalid token" ]);
        exit;
    }

    // Handle file upload
    $targetDir = "../images/"; // Directory to save the uploaded files
    $imagePaths = []; // Array to hold the paths of the uploaded images
    $uploadOk = 1; // Flag to track the overall success of the upload process

    if (isset($_FILES['images'])) {
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
            if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
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
    }

    // Update product details in the database
    if ($uploadOk) {
        // Convert the array of image paths to a JSON string if new images were uploaded
        $imagesJson = !empty($imagePaths) ? json_encode($imagePaths) : null;

        // Prepare the SQL query
        if ($imagesJson) {
            $sql = "UPDATE products SET name=?, description=?, price=?, image=? WHERE id=?";
        } else {
            $sql = "UPDATE products SET name=?, description=?, price=? WHERE id=?";
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
            exit;
        }

        if ($imagesJson) {
            $stmt->bind_param('ssdsi', $name, $description, $price, $imagesJson, $id);
        } else {
            $stmt->bind_param('ssdi', $name, $description, $price, $id);
        }

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Product updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No valid images were uploaded.']);
    }
}

$conn->close();
?>

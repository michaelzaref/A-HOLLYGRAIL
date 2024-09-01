<?php
// Assuming you have a connection to your database
require '../config.php'; // Include your DB connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the 'offer' value from the POST request
    if (isset($_POST['offer'])) {
        $offer = intval($_POST['offer']); // Cast the value to an integer

        // Prepare and execute the SQL query to update the offer column for all products
        $sql = "UPDATE products SET offer = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("i", $offer); // Bind the offer value to the query
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Offer updated for all products.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No products were updated.']);
            }

            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare the SQL statement.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Offer value is missing in the request.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>

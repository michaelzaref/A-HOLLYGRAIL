<?php
include_once '../config.php'; // Adjust the path if needed
$rawData = file_get_contents("php://input");

// Decode the JSON data
$data = json_decode($rawData, true);

if (isset($data['user_id'])) {
    $user_id = $data['user_id'];
} else {
    die("ID");
}

function getUserPreferences($user_id, $conn) {
    // Step 1: Get all the product_ids that the user has ordered
    $query = "SELECT oi.product_id 
              FROM orders o 
              JOIN order_items oi ON o.id = oi.order_id 
              WHERE o.user_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ordered_product_ids = [];
    while ($row = $result->fetch_assoc()) {
        $ordered_product_ids[] = $row['product_id'];
    }
    
    // If no orders found, return an empty array
    if (count($ordered_product_ids) === 0) {
        return [];
    }
    
    // Step 2: Get all tags associated with the user's ordered products
    $ordered_product_ids_str = implode(',', array_map('intval', $ordered_product_ids));
    $tagsQuery = "SELECT tags 
                  FROM products 
                  WHERE id IN ($ordered_product_ids_str)";
    
    $tagsResult = $conn->query($tagsQuery);
    
    $tags = [];
    while ($row = $tagsResult->fetch_assoc()) {
        $productTags = json_decode($row['tags'], true);
        if (is_array($productTags)) {
            $tags = array_merge($tags, $productTags);
        }
    }

    // Remove duplicate tags
    $uniqueTags = array_unique($tags);

    // If no tags found, return an empty array
    if (count($uniqueTags) === 0) {
        return [];
    }

    // Step 3: Get all products that have any of the same tags, but exclude already ordered products
    $recommendProducts = [];
    foreach ($uniqueTags as $tag) {
        $json_tag = json_encode($tag); // Encode the tag
        $recommendQuery = "SELECT * 
                           FROM products 
                           WHERE JSON_CONTAINS(tags, ?) 
                           AND id NOT IN ($ordered_product_ids_str)";
        
        $stmt = $conn->prepare($recommendQuery);
        if (!$stmt) {
            die("Query failed: " . $conn->error); // Output the error
        }
        
        $stmt->bind_param('s', $json_tag);
        $stmt->execute();
        $recommendResult = $stmt->get_result();

        while ($row = $recommendResult->fetch_assoc()) {
            $row['id'] = strval($row['id']);
            $row['image'] = json_decode($row['image'], true);
            $recommendProducts[] = $row;
        }
    }

    return $recommendProducts;
}

// Example usage


$recommendedProducts = getUserPreferences($user_id, $conn);

header('Content-Type: application/json');
echo json_encode($recommendedProducts);
?>

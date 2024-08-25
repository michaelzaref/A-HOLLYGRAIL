<?php
function verifyToken($token) {
    // Database connection
    include("../config.php");

    if ($conn->connect_error) {
        return false; // Connection failed
    }

    if ($token) {
        // Decode the base64 token
        $jsonTokenData = base64_decode($token);

        // Convert the JSON string back to an associative array
        $tokenData = json_decode($jsonTokenData, true);

        if ($tokenData && isset($tokenData['data']) && isset($tokenData['exp'])) {
            $userId = $tokenData['data']['userId'];
            $sessionNumber = $tokenData['data']['sessionNumber'];
            $expirationTime = $tokenData['exp'];

            // Check if the token has expired
            if ($expirationTime > time()) {
                // Check the database for the session
                $stmt = $conn->prepare("SELECT expiration_time FROM user_sessions WHERE user_id = ? AND session_number = ? AND token = ?");
                $stmt->bind_param("iis", $userId, $sessionNumber, $token);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($dbExpirationTime);
                    $stmt->fetch();
                    // Check if the session has expired in the database
                    if (strtotime($dbExpirationTime) > time()) {
                        $stmt->close();
                        $conn->close();
                        return true; // Valid token
                    }
                }
                $stmt->close();
            }
        }
    }

    $conn->close();
    return false; // Invalid token
}
?>

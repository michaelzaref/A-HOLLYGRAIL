<?php
function generateToken($userId) {
    // Generate a secure token using a strong algorithm
    return bin2hex(random_bytes(32));
}

// In the User class, add methods to handle token storage and validation

class User {
    // ... existing properties and methods

    // Method to update token in the database
    public function updateToken($token) {
        $query = "UPDATE users SET token = :token WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':id', $this->id);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Method to validate token
    public function validateToken($token) {
        $query = "SELECT id FROM users WHERE token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            return true;
        }
        return false;
    }
}
?>

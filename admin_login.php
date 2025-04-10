<?php
session_start();
include 'db_connection.php';
require_once 'logger.php'; // Include the logging function

// --- Encryption Setup ---
// Hard-coded encryption parameters (for development/testing only)
// For AES-256-CBC, the key must be 32 bytes and the IV must be 16 bytes.
define('ENCRYPTION_KEY', 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6'); // 32 characters for AES-256
define('ENCRYPTION_IV', 'abcdef1234567890');                   // 16 bytes for AES-256-CBC

/**
 * Encrypts plaintext using AES-256-CBC.
 *
 * @param string $plaintext The plain text to encrypt.
 * @param string $key       The encryption key.
 * @param string $iv        The initialization vector.
 * @return string           Base64 encoded encrypted data.
 */
function encryptData($plaintext, $key, $iv) {
    $encrypted = openssl_encrypt((string)$plaintext, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($encrypted);
}
// --- End Encryption Setup ---

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize user inputs
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $sql = "SELECT admin_id, password FROM Admins WHERE username = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($user_id, $hashed_password);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION['admin_id'] = $user_id;
            
            // Build log details:
            // admin_id remains in plain text, username is encrypted
            $logDetails = [
                'admin_id' => $user_id,  // In plain text for easy auditing
                'username' => encryptData($username, ENCRYPTION_KEY, ENCRYPTION_IV)
            ];
            logAction('admin_login', $logDetails);
            
            // Redirect to the dashboard after successful login
            header("Location: admin_dashboard.php");
            exit();
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "User not found.";
    }

    $stmt->close();
    $conn->close();
}
?>

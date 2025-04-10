<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register</title>
</head>
<body>
<p>
<?php
include 'db_connection.php'; // Ensure you have a file for connecting to the database
require_once 'logger.php';   // Include the logging function

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
    // Sanitize and validate inputs
    $username      = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
    $rawPassword   = trim($_POST['password']); // Password is processed as-is for hashing
    $fullName      = trim(filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING));
    $email         = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $contactNumber = trim(filter_input(INPUT_POST, 'contact_number', FILTER_SANITIZE_STRING));

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
        exit();
    }
    
    // Basic check for required fields (contact number is optional)
    if (empty($username) || empty($rawPassword) || empty($fullName) || empty($email)) {
        echo "All fields except contact number are required.";
        exit();
    }
    
    // Hash the password using PASSWORD_BCRYPT
    $password = password_hash($rawPassword, PASSWORD_BCRYPT);

    // Prepare the SQL statement to insert a new user (including contact_number)
    $sql = "INSERT INTO Users (username, password, full_name, email, contact_number) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("sssss", $username, $password, $fullName, $email, $contactNumber);

    // Execute the statement
    if ($stmt->execute()) {
        // Build log details with sensitive data encrypted
        $logDetails = [
            'username'          => encryptData($username, ENCRYPTION_KEY, ENCRYPTION_IV),
            'full_name'         => encryptData($fullName, ENCRYPTION_KEY, ENCRYPTION_IV),
            'email'             => encryptData($email, ENCRYPTION_KEY, ENCRYPTION_IV),
            'contact_number'    => encryptData($contactNumber, ENCRYPTION_KEY, ENCRYPTION_IV),
            'registration_date' => date('Y-m-d H:i:s')
        ];
        logAction('user_register', $logDetails);
        
        // Registration successful; redirect to login page
        header("Location: login.html");
        exit(); // Stop further execution
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
</p>
</body>
</html>

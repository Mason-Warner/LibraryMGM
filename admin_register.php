<?php
include 'db_connection.php';
require_once 'logger.php'; // Include the logging function

// --- Encryption Setup ---
// Hard-coded encryption parameters (for development/testing only)
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate form inputs
    $username       = filter_var(trim($_POST["username"]), FILTER_SANITIZE_STRING);
    $password       = trim($_POST["password"]); // Passwords are not modified for hashing
    $full_name      = filter_var(trim($_POST["full_name"]), FILTER_SANITIZE_STRING);
    $email          = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $contact_number = filter_var(trim($_POST["contact_number"]), FILTER_SANITIZE_STRING);
    $role           = trim($_POST["role"]);

    // Validate email address
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email address.");
    }
    
    // Validate role: only allow "librarian" or "admin"
    if ($role !== "librarian" && $role !== "admin") {
        die("Invalid role selected.");
    }

    // Hash the password using PASSWORD_BCRYPT
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Prepare the SQL statement to insert a new admin
    $stmt = $conn->prepare("INSERT INTO Admins (username, password, full_name, email, contact_number, role) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    
    // Bind the parameters to the SQL query
    $stmt->bind_param("ssssss", $username, $hashed_password, $full_name, $email, $contact_number, $role);
    
    // Execute the statement
    if ($stmt->execute()) {
        // Build log details with encryption for sensitive data
        $logDetails = [
            'username'         => encryptData($username, ENCRYPTION_KEY, ENCRYPTION_IV),
            'full_name'        => encryptData($full_name, ENCRYPTION_KEY, ENCRYPTION_IV),
            'email'            => encryptData($email, ENCRYPTION_KEY, ENCRYPTION_IV),
            'contact_number'   => encryptData($contact_number, ENCRYPTION_KEY, ENCRYPTION_IV),
            'role'             => $role, // Not encrypted so you can quickly see the role
            'registration_date'=> date('Y-m-d H:i:s')
        ];
        // If an admin is already logged in (i.e. registering another admin), log that as well
        if (isset($_SESSION['admin_id'])) {
            $logDetails['registered_by_admin'] = $_SESSION['admin_id']; // Plain text for ease-of-audit
        }
        // Log the registration action in MongoDB
        logAction('admin_register', $logDetails);
        
        // Registration successful; redirect to admin_login.html
        header("Location: admin_login.html");
        exit;
    } else {
        // Display error message if insertion fails
        echo "Error: " . $stmt->error;
    }
    
    // Close the statement and connection
    $stmt->close();
}
$conn->close();
?>

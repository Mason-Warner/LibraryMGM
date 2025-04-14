<?php
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
    // Sanitize and validate inputs
    $username      = trim(filter_input(INPUT_POST, 'username', FILTER_UNSAFE_RAW));
    $password      = trim($_POST['password']); // Process password as-is for hashing
    $full_name     = trim(filter_input(INPUT_POST, 'full_name', FILTER_UNSAFE_RAW));
    $email         = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $contact_number= trim(filter_input(INPUT_POST, 'contact_number', FILTER_UNSAFE_RAW));
    $role          = trim($_POST["role"]);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email address.");
    }
    
    // Validate role: only allow "librarian" or "admin"
    if ($role !== "librarian" && $role !== "admin") {
        die("Invalid role selected.");
    }
    
    // Basic check for required fields (contact number is optional)
    if (empty($username) || empty($password) || empty($full_name) || empty($email)) {
        die("All fields except contact number are required.");
    }
    
    // Hash the password using PASSWORD_BCRYPT
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Encrypt sensitive data for storage in MySQL.
    // The username is stored in plaintext so that login functionality works as expected.
    $encrypted_full_name     = encryptData($full_name, ENCRYPTION_KEY, ENCRYPTION_IV);
    $encrypted_email         = encryptData($email, ENCRYPTION_KEY, ENCRYPTION_IV);
    $encrypted_contact_number= encryptData($contact_number, ENCRYPTION_KEY, ENCRYPTION_IV);

    // Prepare the SQL statement to insert a new admin
    $sql = "INSERT INTO Admins (username, password, full_name, email, contact_number, role) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    
    // Bind the parameters to the SQL query
    // Username and password are stored in plaintext (password is hashed),
    // while full_name, email, and contact_number are stored encrypted.
    $stmt->bind_param("ssssss", $username, $hashed_password, $encrypted_full_name, $encrypted_email, $encrypted_contact_number, $role);
    
    // Execute the statement
    if ($stmt->execute()) {
        // Build log details with sensitive fields encrypted (if desired, you can log them differently)
        $logDetails = [
            'username'          => $username, // Plaintext for ease of audit
            'full_name'         => encryptData($full_name, ENCRYPTION_KEY, ENCRYPTION_IV),
            'email'             => encryptData($email, ENCRYPTION_KEY, ENCRYPTION_IV),
            'contact_number'    => encryptData($contact_number, ENCRYPTION_KEY, ENCRYPTION_IV),
            'role'              => $role,     // Stored in plaintext in the log to easily verify the role
            'registration_date' => date('Y-m-d H:i:s')
        ];
        // If an admin is already logged in (i.e., registering another admin), include their ID in the log in plaintext.
        if (isset($_SESSION['admin_id'])) {
            $logDetails['registered_by_admin'] = $_SESSION['admin_id'];
        }
        logAction('admin_register', $logDetails);
        
        // Registration successful; redirect to admin_login.html
        header("Location: admin_login.html");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
    
    $stmt->close();
}
$conn->close();
?>
</p>
</body>
</html>

<?php
// Start the session and include the database connection
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

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['admin_id'])) {
    echo "You must be logged in to edit user profiles.";
    exit;
}

$admin_id = intval($_SESSION['admin_id']); // Cast to integer for safety

// Check if the logged-in user is an admin
$sql = "SELECT role FROM Admins WHERE admin_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role !== 'admin') {
    echo "You do not have permission to access this page.";
    exit;
}

// If the form has been submitted (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate POST inputs
    $edit_user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    if ($edit_user_id === false || $edit_user_id === null) {
        echo "Invalid user ID.";
        exit;
    }
    
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
    $full_name = trim(filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
        exit;
    }
    $contact_number = trim(filter_input(INPUT_POST, 'contact_number', FILTER_SANITIZE_STRING));
    
    // Check if a new password is provided
    if (!empty($_POST['password'])) {
        $raw_password = trim($_POST['password']);
        $password = password_hash($raw_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET username = ?, full_name = ?, email = ?, contact_number = ?, password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_sql);
        if (!$stmt) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("sssssi", $username, $full_name, $email, $contact_number, $password, $edit_user_id);
    } else {
        $update_sql = "UPDATE users SET username = ?, full_name = ?, email = ?, contact_number = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_sql);
        if (!$stmt) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("ssssi", $username, $full_name, $email, $contact_number, $edit_user_id);
    }
    
    // Execute the update
    if ($stmt->execute()) {
        echo "User profile updated successfully.";
        
        // Build log details for the update action
        // Here we leave the IDs in plaintext, so you can readily see who was edited and by whom.
        // The other fields are encrypted to protect the sensitive content.
        $logDetails = [
            'edited_user_id'     => $edit_user_id,  // Plain text
            'new_username'       => encryptData($username, ENCRYPTION_KEY, ENCRYPTION_IV),
            'new_full_name'      => encryptData($full_name, ENCRYPTION_KEY, ENCRYPTION_IV),
            'new_email'          => encryptData($email, ENCRYPTION_KEY, ENCRYPTION_IV),
            'new_contact_number' => encryptData($contact_number, ENCRYPTION_KEY, ENCRYPTION_IV),
            'changed_by_admin'   => $admin_id,  // Plain text
            'update_time'        => date('Y-m-d H:i:s')
        ];
        logAction('update_user', $logDetails);
        
        // Redirect to the manage_users.php page after the update
        header("Location: manage_users.php");
        exit;  // Always call exit after a header redirect
    } else {
        echo "Error updating user profile: " . $stmt->error;
    }
    $stmt->close();
}

// If the GET request is present, fetch the user details to be edited
if (isset($_GET['id'])) {
    $edit_user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($edit_user_id === false || $edit_user_id === null) {
        echo "Invalid user selected to edit.";
        exit;
    }
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $edit_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Fetch the user details
        $user = $result->fetch_assoc();
    } else {
        echo "User not found.";
        exit;
    }
    $stmt->close();
} else {
    echo "No user selected to edit.";
    exit;
}

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User Profile</title>
</head>
<body>
    <h2>Edit User Profile</h2>
    <form action="update_user.php" method="POST">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>">
        
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>" required><br><br>
        
        <label for="full_name">Full Name:</label>
        <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required><br><br>
        
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" required><br><br>
        
        <label for="contact_number">Contact Number:</label>
        <input type="text" name="contact_number" id="contact_number" value="<?php echo htmlspecialchars($user['contact_number'], ENT_QUOTES, 'UTF-8'); ?>"><br><br>
        
        <label for="password">New Password (Leave blank if not changing):</label>
        <input type="password" name="password" id="password"><br><br>
        
        <button type="submit">Update Profile</button>
    </form>
    <br>
    <p><a href="manage_users.php">Back to User Management</a></p>
</body>
</html>

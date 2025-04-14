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
 * Decrypts encrypted data using AES-256-CBC.
 *
 * @param string $encryptedData Base64 encoded encrypted data.
 * @param string $key           The encryption key.
 * @param string $iv            The initialization vector.
 * @return string               The decrypted plain text.
 */
function decryptData($encryptedData, $key, $iv) {
    // If the value is empty, return an empty string without attempting decryption.
    if(empty($encryptedData)) {
        return "";
    }
    $decoded = base64_decode($encryptedData);
    return openssl_decrypt($decoded, 'AES-256-CBC', $key, 0, $iv);
}
// --- End Encryption Setup ---

if (isset($_SESSION['admin_id'])) {
    // Log that the admin viewed the manage users page
    $logDetails = [
        'admin_id' => $_SESSION['admin_id'],
        'action'   => 'view_manage_users',
        'timestamp'=> date('Y-m-d H:i:s')
    ];
    logAction('view_manage_users', $logDetails);

    echo "<h1>Manage Users</h1>";

    // Display all users
    $sql = "SELECT * FROM users";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        // Updated table headers to include decrypted fields
        echo "<table border='1'>";
        echo "<tr>
                <th>ID</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Contact Number</th>
                <th>Actions</th>
              </tr>";
        while ($row = $result->fetch_assoc()) {
            // Cast user ID to integer for safety
            $userId = intval($row['user_id']);
            // Username is stored in plaintext
            $username = htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8');
            // Decrypt sensitive fields (assuming they were encrypted during registration)
            $fullName = htmlspecialchars(decryptData($row['full_name'], ENCRYPTION_KEY, ENCRYPTION_IV), ENT_QUOTES, 'UTF-8');
            $email = htmlspecialchars(decryptData($row['email'], ENCRYPTION_KEY, ENCRYPTION_IV), ENT_QUOTES, 'UTF-8');
            // Check if contact_number is not empty before decrypting
            if (!empty($row['contact_number'])) {
                $contactNumber = htmlspecialchars(decryptData($row['contact_number'], ENCRYPTION_KEY, ENCRYPTION_IV), ENT_QUOTES, 'UTF-8');
            } else {
                $contactNumber = "";
            }

            echo "<tr>";
            echo "<td>" . $userId . "</td>";
            echo "<td>" . $username . "</td>";
            echo "<td>" . $fullName . "</td>";
            echo "<td>" . $email . "</td>";
            echo "<td>" . $contactNumber . "</td>";
            echo "<td>";
            echo "<a href='update_user.php?id=" . $userId . "'>Update</a> | ";
            echo "<a href='delete_user.php?id=" . $userId . "' onclick=\"return confirm('Are you sure you want to delete this user?');\">Delete</a>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found.</p>";
    }

    // Add link back to the admin dashboard
    echo "<br><p><a href='admin_dashboard.php'>Back to Admin Dashboard</a></p>";

} else {
    echo "Access denied. Admins only.";
}

$conn->close();
?>

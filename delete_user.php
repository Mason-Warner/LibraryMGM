<?php
// Start the session and include the database connection
session_start();
include 'db_connection.php';

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['admin_id'])) {
    echo "You must be logged in to delete users.";
    exit;
}

$user_id = intval($_SESSION['admin_id']); // Get and cast the logged-in user's ID

// Check if the logged-in user is an admin
$sql = "SELECT role FROM Admins WHERE admin_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role !== 'admin') {
    echo "You do not have permission to access this page.";
    exit;
}

// Check if a user ID is provided in the URL and sanitize it
$delete_user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($delete_user_id === false || $delete_user_id === null) {
    echo "Invalid user selected to delete.";
    exit;
}

// Start a transaction to ensure atomicity
$conn->begin_transaction();

try {
    // Delete all borrowed books related to the user
    $delete_borrowed_books_sql = "DELETE FROM BorrowedBooks WHERE user_id = ?";
    $stmt = $conn->prepare($delete_borrowed_books_sql);
    if ($stmt === false) {
        throw new Exception("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $delete_user_id);
    $stmt->execute();
    $stmt->close();

    // Now, delete the user from the users table
    $delete_user_sql = "DELETE FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($delete_user_sql);
    if ($stmt === false) {
        throw new Exception("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $delete_user_id);
    $stmt->execute();
    $stmt->close();

    // Commit the transaction
    $conn->commit();

    // Redirect back to manage_users.php after successful deletion
    header("Location: manage_users.php");
    exit; // Ensure script stops here after redirect

} catch (mysqli_sql_exception $e) {
    // Rollback the transaction in case of an error
    $conn->rollback();
    echo "Error deleting user: " . $e->getMessage();
}

// Close the database connection
$conn->close();
?>

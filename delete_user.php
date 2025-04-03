<?php
// Start the session and include the database connection
session_start();
include 'db_connection.php';

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['admin_id'])) {
    echo "You must be logged in to delete users.";
    exit;
}

$user_id = $_SESSION['admin_id']; // Get the logged-in user's ID

// Check if the logged-in user is an admin
$sql = "SELECT role FROM Admins WHERE admin_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role !== 'admin') {
    echo "You do not have permission to access this page.";
    exit;
}

// Check if a user ID is provided in the URL
if (isset($_GET['id'])) {
    $delete_user_id = $_GET['id'];

    // Start a transaction to ensure atomicity
    $conn->begin_transaction();

    try {
        // Delete all borrowed books related to the user
        $delete_borrowed_books_sql = "DELETE FROM BorrowedBooks WHERE user_id = ?";
        $stmt = $conn->prepare($delete_borrowed_books_sql);
        $stmt->bind_param("i", $delete_user_id);
        $stmt->execute();
        $stmt->close();

        // Now, delete the user from the users table
        $delete_user_sql = "DELETE FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($delete_user_sql);
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

} else {
    echo "No user selected to delete.";
    exit;
}

// Close the database connection
$conn->close();
?>


<?php
session_start();
include 'db_connection.php';
require_once 'logger.php'; // Include the logging function

if (isset($_GET['id'])) {
    // Sanitize and validate the 'id' parameter as an integer
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id === false || $id === null) {
        echo "Invalid book ID.";
        exit();
    }

    // First, delete any entries in BorrowedBooks that reference this book
    $deleteBorrowedBooksSql = "DELETE FROM borrowedbooks WHERE book_id = ?";
    $stmt = $conn->prepare($deleteBorrowedBooksSql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Now delete the book from the Books table
        $deleteBookSql = "DELETE FROM books WHERE book_id = ?";
        $deleteStmt = $conn->prepare($deleteBookSql);
        if ($deleteStmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
        $deleteStmt->bind_param("i", $id);

        if ($deleteStmt->execute()) {
            // Prepare log details with the book ID
            $logDetails = [
                'book_id' => $id,
                'delete_date' => date('Y-m-d H:i:s')
            ];

            // Capture the actor from session (admin, librarian, or user)
            if (isset($_SESSION['admin_id'])) {
                $logDetails['actor'] = 'admin';
                $logDetails['actor_id'] = $_SESSION['admin_id'];
            } elseif (isset($_SESSION['librarian_id'])) {
                $logDetails['actor'] = 'librarian';
                $logDetails['actor_id'] = $_SESSION['librarian_id'];
            } elseif (isset($_SESSION['user_id'])) {
                $logDetails['actor'] = 'user';
                $logDetails['actor_id'] = $_SESSION['user_id'];
            }

            // Log the deletion action
            logAction('delete_book', $logDetails);

            header("Location: manage_inventory.php?success=book_deleted");
            exit();
        } else {
            echo "Error deleting book: " . $deleteStmt->error;
        }

        $deleteStmt->close();
    } else {
        echo "Error deleting borrowed records: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>


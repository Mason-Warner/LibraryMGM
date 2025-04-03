<?php
include 'db_connection.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // First, delete any entries in borrowedbooks that reference this book
    $deleteBorrowedBooksSql = "DELETE FROM borrowedbooks WHERE book_id = ?";
    $stmt = $conn->prepare($deleteBorrowedBooksSql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Now delete the book from the books table
        $deleteBookSql = "DELETE FROM books WHERE book_id = ?";
        $deleteStmt = $conn->prepare($deleteBookSql);
        $deleteStmt->bind_param("i", $id);

        if ($deleteStmt->execute()) {
            echo "Book deleted successfully.";
            header("Location: admin_dashboard.php");
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


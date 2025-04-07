<?php
include 'db_connection.php';

if (isset($_GET['id'])) {
    // Sanitize and validate the 'id' parameter as an integer
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id === false || $id === null) {
        echo "Invalid book ID.";
        exit();
    }

    // First, delete any entries in borrowedbooks that reference this book
    $deleteBorrowedBooksSql = "DELETE FROM borrowedbooks WHERE book_id = ?";
    $stmt = $conn->prepare($deleteBorrowedBooksSql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Now delete the book from the books table
        $deleteBookSql = "DELETE FROM books WHERE book_id = ?";
        $deleteStmt = $conn->prepare($deleteBookSql);
        if ($deleteStmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
        $deleteStmt->bind_param("i", $id);

        if ($deleteStmt->execute()) {
            echo "Book deleted successfully.";
            header("Location: admin_dashboard.php");
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return a Book</title>
</head>
<body>
    <h2>Return a Book</h2>

<?php
session_start();
include 'db_connection.php';

// Ensure the user is logged in before proceeding
if (isset($_SESSION['user_id'])) {
    // Cast user ID to integer for safety
    $userId = intval($_SESSION['user_id']);

    // If the form hasn't been submitted yet, display available borrowed books
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        // Prepare the query to fetch borrowed books without a return date
        $sql = "SELECT transaction_id, Books.book_id, title FROM BorrowedBooks
                JOIN Books ON BorrowedBooks.book_id = Books.book_id 
                WHERE BorrowedBooks.user_id = ? AND return_date IS NULL";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            // Display the form for returning a book
            echo '<form action="return_books.php" method="POST">';
            echo '<label for="transaction_id">Select a book to return:</label>';
            echo '<select name="transaction_id" id="transaction_id" required>';
            
            // Output borrowed books in a dropdown, sanitizing the title output
            while ($row = $result->fetch_assoc()) {
                $transactionId = intval($row['transaction_id']);
                $title = htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8');
                echo '<option value="' . $transactionId . '">' . $title . '</option>';
            }
            echo '</select><br><br>';
            echo '<button type="submit">Return Book</button>';
            echo '</form>';

            $result->free();
        } else {
            echo "<p>You have no books to return.</p>";
        }

        $stmt->close();
    }

    // Handle the return process if the form was submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Sanitize and validate the transaction_id from POST input
        $transactionId = filter_input(INPUT_POST, 'transaction_id', FILTER_VALIDATE_INT);
        if ($transactionId === false || $transactionId === null) {
            echo "<p>Invalid transaction ID provided.</p>";
            exit();
        }

        // Fetch the book_id associated with the given transaction using a prepared statement
        $getBookIdSql = "SELECT book_id FROM BorrowedBooks WHERE transaction_id = ?";
        $stmtGetBookId = $conn->prepare($getBookIdSql);
        if ($stmtGetBookId === false) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmtGetBookId->bind_param("i", $transactionId);
        $stmtGetBookId->execute();
        $stmtGetBookId->bind_result($bookId);
        $stmtGetBookId->fetch();
        $stmtGetBookId->close();

        if ($bookId) {
            // Update the return_date in BorrowedBooks to the current timestamp
            $returnDate = date('Y-m-d H:i:s');
            $updateReturnDateSql = "UPDATE BorrowedBooks SET return_date = ? WHERE transaction_id = ?";
            $updateStmt = $conn->prepare($updateReturnDateSql);
            if ($updateStmt === false) {
                die("Error preparing statement: " . $conn->error);
            }
            $updateStmt->bind_param("si", $returnDate, $transactionId);
            $updateStmt->execute();
            $updateStmt->close();

            // Update the status of the book to 'available' in the Books table
            $updateBookStatusSql = "UPDATE Books SET status = 'available' WHERE book_id = ?";
            $updateBookStmt = $conn->prepare($updateBookStatusSql);
            if ($updateBookStmt === false) {
                die("Error preparing statement: " . $conn->error);
            }
            $updateBookStmt->bind_param("i", $bookId);
            $updateBookStmt->execute();
            $updateBookStmt->close();

            echo "<p>Book returned successfully!</p>";
        } else {
            echo "<p>Error: Invalid transaction ID.</p>";
        }
    }
} else {
    echo "<p>You must be logged in to return books.</p>";
}

// Close the database connection
$conn->close();
?>

    <br>
    <p><a href="dashboard.html">Back to Dashboard</a></p>
</body>
</html>

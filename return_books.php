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
    $userId = $_SESSION['user_id'];

    // Fetch books the user has borrowed (those without a return date)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        // Only fetch books if the form has not been submitted yet
        $sql = "SELECT transaction_id, Books.book_id, title FROM BorrowedBooks
                JOIN Books ON BorrowedBooks.book_id = Books.book_id 
                WHERE BorrowedBooks.user_id = ? AND return_date IS NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Display the form for returning a book
            echo '<form action="return_books.php" method="POST">';
            echo '<label for="transaction_id">Select a book to return:</label>';
            echo '<select name="transaction_id" id="transaction_id" required>';
            
            // Display borrowed books in a dropdown
            while ($row = $result->fetch_assoc()) {
                echo '<option value="' . $row['transaction_id'] . '">' . $row['title'] . '</option>';
            }

            echo '</select><br><br>';
            echo '<button type="submit">Return Book</button>';
            echo '</form>';

            // Free the result set
            $result->free();
        } else {
            echo "<p>You have no books to return.</p>";
        }

        // Close the statement after fetching results
        $stmt->close();
    }

    // Check if form was submitted to return a book
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Get the transaction_id from the form
        $transactionId = $_POST['transaction_id'];

        // Fetch the book_id associated with the transaction
        $getBookIdSql = "SELECT book_id FROM BorrowedBooks WHERE transaction_id = ?";
        $stmtGetBookId = $conn->prepare($getBookIdSql);
        $stmtGetBookId->bind_param("i", $transactionId);
        $stmtGetBookId->execute();
        $stmtGetBookId->bind_result($bookId);
        $stmtGetBookId->fetch(); // Ensure result is fetched

        // Close the prepared statement after fetching the result
        $stmtGetBookId->close();

        if ($bookId) {
            // Update the return_date in BorrowedBooks to the current timestamp
            $returnDate = date('Y-m-d H:i:s'); // Current timestamp
            $updateReturnDateSql = "UPDATE BorrowedBooks SET return_date = ? WHERE transaction_id = ?";
            $updateStmt = $conn->prepare($updateReturnDateSql);
            $updateStmt->bind_param("si", $returnDate, $transactionId);
            $updateStmt->execute();
            $updateStmt->close(); // Close the return update statement

            // Update the status of the book to 'available' in the Books table
            $updateBookStatusSql = "UPDATE Books SET status = 'available' WHERE book_id = ?";
            $updateBookStmt = $conn->prepare($updateBookStatusSql);
            $updateBookStmt->bind_param("i", $bookId);
            $updateBookStmt->execute();
            $updateBookStmt->close(); // Close the book update statement

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


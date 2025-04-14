<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Books</title>
</head>
<body>
    <h2>Borrow a Book</h2>

<?php
session_start();
include 'db_connection.php';
require_once 'logger.php'; // Include logging function

// Ensure the user is logged in before proceeding
if (isset($_SESSION['user_id'])) {
    // Get the user_id from session and cast it to an integer
    $userId = intval($_SESSION['user_id']);

    // Fetch available books from the database (those with status = 'available')
    $sql = "SELECT book_id, title FROM Books WHERE status = 'available'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        // Display the form for borrowing a book
        echo '<form action="borrow_books.php" method="POST">';
        echo '<label for="book_id">Select a book to borrow:</label>';
        echo '<select name="book_id" id="book_id" required>';
        
        // Display available books in a dropdown with escaped output
        while ($row = $result->fetch_assoc()) {
            $bookIdOption = htmlspecialchars($row['book_id'], ENT_QUOTES, 'UTF-8');
            $titleOption = htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8');
            echo '<option value="' . $bookIdOption . '">' . $titleOption . '</option>';
        }
        echo '</select><br><br>';
        echo '<button type="submit">Borrow Book</button>';
        echo '</form>';
    } else {
        echo "<p>No available books to borrow at the moment.</p>";
    }

    // Check if form was submitted to borrow a book
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Sanitize and validate the selected book ID using filter_input
        $bookId = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);
        if ($bookId === false || $bookId === null) {
            echo "<p>Invalid book selection.</p>";
            exit();
        }

        // Set the due date for the borrowed book (e.g., 14 days from now)
        $dueDate = date('Y-m-d H:i:s', strtotime('+14 days'));

        // Insert the borrowing record into the BorrowedBooks table using prepared statements
        $sql = "INSERT INTO BorrowedBooks (user_id, book_id, due_date) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("iis", $userId, $bookId, $dueDate);
        
        if ($stmt->execute()) {
            // Update the Books table to mark the book as "borrowed"
            $updateSql = "UPDATE Books SET status = 'borrowed' WHERE book_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            if ($updateStmt === false) {
                die("Error preparing update statement: " . $conn->error);
            }
            $updateStmt->bind_param("i", $bookId);
            $updateStmt->execute();
            $updateStmt->close();
            
            echo "<p>Book borrowed successfully!</p>";
            
            // Log the borrow action with details about the user and the book
            $logDetails = [
                'user_id'  => $userId,
                'book_id'  => $bookId,
                'due_date' => $dueDate
            ];
            logAction('borrow_book', $logDetails);
            
        } else {
            echo "<p>Error: " . $stmt->error . "</p>";
        }

        // Close the statement
        $stmt->close();
    }

} else {
    echo "<p>You must be logged in to borrow books.</p>";
}

// Close database connection
$conn->close();
?>

    <br>
    <p><a href="dashboard.html">Back to Dashboard</a></p>
</body>
</html>

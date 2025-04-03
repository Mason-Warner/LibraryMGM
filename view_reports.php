<?php
session_start();
include 'db_connection.php';

if (isset($_SESSION['admin_id'])) {
    // Link back to admin dashboard
    echo "<p><a href='admin_dashboard.php'>Back to Admin Dashboard</a></p>";

    echo "<h1>Reports</h1>";

    // Report: Most Borrowed Books
    echo "<h2>Most Borrowed Books</h2>";
    $sql = "SELECT books.title, COUNT(BorrowedBooks.book_id) AS borrow_count 
            FROM BorrowedBooks 
            JOIN books ON BorrowedBooks.book_id = books.book_id 
            GROUP BY BorrowedBooks.book_id 
            ORDER BY borrow_count DESC 
            LIMIT 5";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Title</th><th>Times Borrowed</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . $row['title'] . "</td><td>" . $row['borrow_count'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data available.</p>";
    }

    // Report: Overdue Books
    echo "<h2>Overdue Books</h2>";
    $overdue_sql = "SELECT users.full_name, books.title, BorrowedBooks.due_date 
                    FROM BorrowedBooks 
                    JOIN books ON BorrowedBooks.book_id = books.book_id 
                    JOIN users ON BorrowedBooks.user_id = users.user_id 
                    WHERE Books.status = 'borrowed' AND BorrowedBooks.due_date < CURDATE()";
    $overdue_result = $conn->query($overdue_sql);

    if ($overdue_result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>User</th><th>Title</th><th>Due Date</th></tr>";
        while ($row = $overdue_result->fetch_assoc()) {
            echo "<tr><td>" . $row['full_name'] . "</td><td>" . $row['title'] . "</td><td>" . $row['due_date'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No overdue books at this time.</p>";
    }
} else {
    echo "Access denied. Admins only.";
}

$conn->close();
?>


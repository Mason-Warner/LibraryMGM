<html>
<?php
session_start();
include 'db_connection.php';

if (isset($_SESSION['admin_id'])) {
    echo "<h1>Admin Dashboard</h1>";

    // Links to manage users, view reports, and logout
    echo "<h2>Admin Actions</h2>";
    echo "<a href='manage_users.php'>Manage Users</a> | ";
    echo "<a href='view_reports.php'>View Reports</a> | ";
    echo "<a href='logout.php'>Logout</a>";
    echo "<br><br>";

    // Display all books and provide options to add, update, or delete books
    echo "<h2>Manage Inventory</h2>";

    // Fetch all books from the database
    $sql = "SELECT * FROM books";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Title</th><th>Author</th><th>Genre</th><th>Actions</th></tr>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['book_id'] . "</td>";
            echo "<td>" . $row['title'] . "</td>";
            echo "<td>" . $row['author'] . "</td>";
            echo "<td>" . $row['genre'] . "</td>";
            echo "<td>";
            echo "<a href='update_book.php?id=" . $row['book_id'] . "'>Update</a> | ";
            echo "<a href='delete_book.php?id=" . $row['book_id'] . "' onclick=\"return confirm('Are you sure you want to delete this book?');\">Delete</a>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No books found in the inventory.</p>";
    }

    // Form for adding a new book
    echo "<h2>Add New Book</h2>";
    echo "<form method='post' action='add_book.php'>";
    echo "Title: <input type='text' name='title' required><br>";
    echo "Author: <input type='text' name='author' required><br>";
    echo "Genre: <input type='text' name='genre' required><br>";
    echo "<input type='submit' value='Add Book'>";
    echo "</form>";

    // Form for sending a notification
    echo "<h2>Send Notification to User</h2>";
    echo "<form method='post' action=''>";
    echo "User ID: <input type='number' name='user_id' required><br>";
    echo "Message: <textarea name='message' rows='4' cols='50' required></textarea><br>";
    echo "<input type='submit' name='send_notification' value='Send Notification'>";
    echo "</form>";

    // Handle the notification form submission with input sanitation and prepared statements
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
        // Sanitize inputs
        $user_id = intval($_POST['user_id']);
        $message = trim($_POST['message']);

        // Prepare the SQL statement to insert a new notification
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, status) VALUES (?, ?, 'unread')");
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("is", $user_id, $message);

        if ($stmt->execute()) {
            echo "<p>Notification sent successfully to User ID: $user_id.</p>";
        } else {
            echo "<p>Error sending notification: " . $stmt->error . "</p>";
        }
        $stmt->close();
    }
} else {
    echo "Access denied. Admins only.";
}

$conn->close();
?>
</html>

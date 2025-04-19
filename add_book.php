<?php
session_start();
include 'db_connection.php';
require_once 'logger.php'; // Include the logging function

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input values
    $title  = trim($_POST['title']);
    $author = trim($_POST['author']);
    $genre  = trim($_POST['genre']);

    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO Books (title, author, genre) VALUES (?, ?, ?)");
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    // Bind parameters to the prepared statement
    $stmt->bind_param("sss", $title, $author, $genre);

    // Execute the prepared statement
    if ($stmt->execute()) {
        // Prepare log details
        $logDetails = [
            'title'  => $title,
            'author' => $author,
            'genre'  => $genre
        ];
        
        // Determine the actor from session data
        if (isset($_SESSION['admin_id'])) {
            $logDetails['admin_id'] = $_SESSION['admin_id'];
        } elseif (isset($_SESSION['librarian_id'])) {
            $logDetails['librarian_id'] = $_SESSION['librarian_id'];
        } elseif (isset($_SESSION['user_id'])) {
            $logDetails['user_id'] = $_SESSION['user_id'];
        }
        
        // Log the action in MongoDB
        logAction('add_book', $logDetails);

        // Redirect to inventory page with success flag
        header("Location: manage_inventory.php?added=1");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
}

$conn->close();
?>


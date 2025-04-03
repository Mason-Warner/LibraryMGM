<?php
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $genre = $_POST['genre'];

    $sql = "INSERT INTO books (title, author, genre) VALUES ('$title', '$author', '$genre')";
    if ($conn->query($sql) === TRUE) {
        echo "New book added successfully.";
        header("Location: admin_dashboard.php");
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>

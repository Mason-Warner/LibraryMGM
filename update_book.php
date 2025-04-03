<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Book</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }
        .container {
            width: 80%;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        input[type="text"], input[type="submit"] {
            padding: 10px;
            font-size: 16px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        input[type="text"] {
            width: 100%;
        }
        input[type="submit"] {
            background-color: #007bff;
            color: white;
            cursor: pointer;
            border: none;
            font-weight: bold;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #007bff;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Update Book Details</h1>

    <?php
    include 'db_connection.php';

    if (isset($_GET['id'])) {
        $id = $_GET['id'];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get form data
            $title = $_POST['title'];
            $author = $_POST['author'];
            $genre = $_POST['genre'];

            // Update the book in the database
            $sql = "UPDATE books SET title='$title', author='$author', genre='$genre' WHERE book_id=$id";
            if ($conn->query($sql) === TRUE) {
                echo "<p>Book updated successfully.</p>";
                header("Location: admin_dashboard.php"); // Redirect after update
            } else {
                echo "<p>Error updating record: " . $conn->error . "</p>";
            }
        } else {
            // Fetch the existing book details
            $sql = "SELECT * FROM books WHERE book_id=$id";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $book = $result->fetch_assoc();
            } else {
                echo "<p>Book not found.</p>";
            }
        }
    }

    $conn->close();
    ?>

    <?php if (isset($book)): ?>
        <form action="update_book.php?id=<?php echo $book['book_id']; ?>" method="POST">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" value="<?php echo $book['title']; ?>" required>

            <label for="author">Author</label>
            <input type="text" name="author" id="author" value="<?php echo $book['author']; ?>" required>

            <label for="genre">Genre</label>
            <input type="text" name="genre" id="genre" value="<?php echo $book['genre']; ?>" required>

            <input type="submit" value="Update Book">
        </form>
    <?php endif; ?>

    <div class="back-link">
        <a href="admin_dashboard.php">Back to Admin Dashboard</a>
    </div>
</div>

</body>
</html>


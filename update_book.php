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
    require_once 'logger.php'; // Include the logging function

    // Sanitize and validate the book ID from the GET parameter
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id === false || $id === null) {
        echo "<p>Invalid book ID.</p>";
        exit();
    }

    // If the form is submitted, process the update
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Sanitize input values from POST
        $title  = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING));
        $author = trim(filter_input(INPUT_POST, 'author', FILTER_SANITIZE_STRING));
        $genre  = trim(filter_input(INPUT_POST, 'genre', FILTER_SANITIZE_STRING));

        // Use a prepared statement to update the book record
        $update_sql = "UPDATE Books SET title = ?, author = ?, genre = ? WHERE book_id = ?";
        $stmt = $conn->prepare($update_sql);
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("sssi", $title, $author, $genre, $id);

        if ($stmt->execute()) {
            // Prepare log details for the update action
            $logDetails = [
                'book_id' => $id,
                'new_title' => $title,
                'new_author' => $author,
                'new_genre' => $genre,
                'update_date' => date('Y-m-d H:i:s')
            ];
            // Capture the actor from session if available (admin, librarian, or user)
            if (isset($_SESSION['admin_id'])) {
                $logDetails['actor'] = 'admin';
                $logDetails['actor_id'] = $_SESSION['admin_id'];
            } elseif (isset($_SESSION['librarian_id'])) {
                $logDetails['actor'] = 'librarian';
                $logDetails['actor_id'] = $_SESSION['librarian_id'];
            } elseif (isset($_SESSION['user_id'])) {
                $logDetails['actor'] = 'user';
                $logDetails['actor_id'] = $_SESSION['user_id'];
            }
            // Log the update book action
            logAction('update_book', $logDetails);

            echo "<p>Book updated successfully.</p>";
            header("Location: admin_dashboard.php");
            exit();
        } else {
            echo "<p>Error updating record: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        // Fetch the existing book details using a prepared statement
        $select_sql = "SELECT * FROM Books WHERE book_id = ?";
        $stmt = $conn->prepare($select_sql);
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $book = $result->fetch_assoc();
        } else {
            echo "<p>Book not found.</p>";
            exit();
        }
        $stmt->close();
    }

    $conn->close();
    ?>

    <?php if (isset($book)): ?>
        <form action="update_book.php?id=<?php echo htmlspecialchars($book['book_id'], ENT_QUOTES, 'UTF-8'); ?>" method="POST">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="author">Author</label>
            <input type="text" name="author" id="author" value="<?php echo htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8'); ?>" required>

            <label for="genre">Genre</label>
            <input type="text" name="genre" id="genre" value="<?php echo htmlspecialchars($book['genre'], ENT_QUOTES, 'UTF-8'); ?>" required>

            <input type="submit" value="Update Book">
        </form>
    <?php endif; ?>

    <div class="back-link">
        <a href="admin_dashboard.php">Back to Admin Dashboard</a>
    </div>
</div>

</body>
</html>

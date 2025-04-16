<?php
session_start();
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
    $title  = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING));
    $author = trim(filter_input(INPUT_POST, 'author', FILTER_SANITIZE_STRING));
    $genre  = trim(filter_input(INPUT_POST, 'genre', FILTER_SANITIZE_STRING));

    $update_sql = "UPDATE Books SET title = ?, author = ?, genre = ? WHERE book_id = ?";
    $stmt = $conn->prepare($update_sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("sssi", $title, $author, $genre, $id);

    if ($stmt->execute()) {
        $logDetails = [
            'book_id'    => $id,
            'new_title'  => $title,
            'new_author' => $author,
            'new_genre'  => $genre,
            'update_date' => date('Y-m-d H:i:s')
        ];

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

        logAction('update_book', $logDetails);

        header("Location: admin_dashboard.php");
        exit();
    } else {
        echo "<p>Error updating record: " . $stmt->error . "</p>";
    }
    $stmt->close();
} else {
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Book</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #1e1e1e;
            color: #d4d4d4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background-color: #252526;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
        }

        h2 {
            text-align: center;
            color: #ffffff;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 8px;
            color: #ffffff;
        }

        input[type="text"] {
            padding: 12px;
            margin-bottom: 20px;
            background-color: #333;
            color: #ffffff;
            border: 1px solid #444;
            border-radius: 6px;
            font-size: 1rem;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #007bff;
        }

        input[type="submit"] {
            padding: 12px;
            background-color: #3a3d41;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #505357;
        }

        p {
            text-align: center;
            margin-top: 20px;
            color: #cccccc;
        }

        a {
            color: #007bff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <?php include 'admin_nav.php'; ?>

    <div class="login-container">
        <h2>Update Book</h2>

        <?php if (isset($book)): ?>
            <form action="update_book.php?id=<?= htmlspecialchars($book['book_id'], ENT_QUOTES, 'UTF-8') ?>" method="POST">
                <label for="title">Title:</label>
                <input type="text" name="title" id="title" value="<?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?>" required>

                <label for="author">Author:</label>
                <input type="text" name="author" id="author" value="<?= htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8') ?>" required>

                <label for="genre">Genre:</label>
                <input type="text" name="genre" id="genre" value="<?= htmlspecialchars($book['genre'], ENT_QUOTES, 'UTF-8') ?>" required>

                <input type="submit" value="Update Book">
            </form>
        <?php endif; ?>
    </div>

</body>
</html>


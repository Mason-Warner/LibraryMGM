<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search for Books</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }
        .container {
            width: 80%;
            max-width: 900px;
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
            margin-bottom: 30px;
        }
        input[type="text"] {
            width: 70%;
            padding: 8px;
            font-size: 16px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            padding: 8px 15px;
            font-size: 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .results {
            margin-top: 20px;
        }
        .book-item {
            padding: 10px;
            background-color: #f7f7f7;
            border: 1px solid #ddd;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .book-item h3 {
            margin: 0;
            color: #333;
        }
        .book-item p {
            margin: 5px 0;
            color: #555;
        }
        hr {
            border: 0;
            border-top: 1px solid #ddd;
        }
        .back-to-dashboard {
            display: block;
            text-align: center;
            margin-top: 30px;
            font-size: 18px;
        }
        .back-to-dashboard a {
            color: #007bff;
            text-decoration: none;
        }
        .back-to-dashboard a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Search for Books</h1>

    <!-- Search Form -->
    <form action="search_books.php" method="GET">
        <input type="text" name="query" placeholder="Search by title, author, or genre" required>
        <button type="submit">Search</button>
    </form>

    <!-- Display search results -->
    <div class="results">
        <?php
        // Check if the form was submitted
        if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['query'])) {
            include 'db_connection.php';

            // Get the search query
            $searchTerm = $_GET['query'];
            $sql = "SELECT * FROM Books WHERE title LIKE ? OR author LIKE ? OR genre LIKE ?";
            $stmt = $conn->prepare($sql);
            $searchPattern = "%" . $searchTerm . "%";
            $stmt->bind_param("sss", $searchPattern, $searchPattern, $searchPattern);
            $stmt->execute();
            $result = $stmt->get_result();

            // Display the search results
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="book-item">';
                    echo '<h3>' . $row['title'] . '</h3>';
                    echo '<p><strong>Author:</strong> ' . $row['author'] . '</p>';
                    echo '<p><strong>Genre:</strong> ' . $row['genre'] . '</p>';
                    echo '</div>';
                    echo '<hr>';
                }
            } else {
                echo "<p>No books found matching your search criteria.</p>";
            }

            // Close the database connection
            $stmt->close();
            $conn->close();
        }
        ?>
    </div>

    <!-- Link back to the dashboard -->
    <div class="back-to-dashboard">
        <a href="dashboard.html">Back to Dashboard</a>
    </div>
</div>

</body>
</html>


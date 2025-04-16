<?php
session_start();
include 'db_connection.php';
require_once 'logger.php';

$unreadCount = 0;

if (isset($_SESSION['user_id'])) {
    $userId = intval($_SESSION['user_id']);

    // Get unread notification count
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'unread'");
    if ($countStmt) {
        $countStmt->bind_param("i", $userId);
        $countStmt->execute();
        $countStmt->bind_result($unreadCount);
        $countStmt->fetch();
        $countStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search for Books</title>
    <link rel="stylesheet" href="/css/style.css" />
    <style>
        /* Updated form input and button to match the borrow/return page styles */
        input[type="text"] {
            width: 100%;
            padding: 12px 20px;
            font-size: 16px;
            margin-right: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            background-color: #1e1e1e;
            color: #eee;
        }

        button {
            padding: 12px 20px;
            font-size: 16px;
            background-color: #5a6e8c;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
            margin-top: 10px;  /* Added margin for extra space */
        }

        button:hover {
            background-color: #4a5d78;
        }

        .notifications-link {
            position: relative;
        }

        .notif-badge {
            position: absolute;
            top: -6px;
            right: -10px;
            background-color: #e74c3c;
            color: white;
            padding: 2px 6px;
            border-radius: 50%;
            font-size: 0.75rem;
            font-weight: bold;
            line-height: 1;
            min-width: 18px;
            text-align: center;
            box-shadow: 0 0 4px rgba(0, 0, 0, 0.4);
        }

    </style>
</head>
<body>

  <!-- Include the Navigation Bar at the Top -->
  <?php include 'nav.php'; ?>

  <div class="container">
    <header>
        <h1>Search for Books</h1>
    </header>

    <!-- Search Form -->
    <form action="search_books.php" method="GET">
        <input type="text" name="query" placeholder="Search by title, author, or genre" required>
        <button type="submit">Search</button>
    </form>

    <!-- Display search results -->
    <div class="results">
        <?php
        // Check if the form was submitted and sanitize the input
        if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['query'])) {
            // Sanitize the search query
            $searchTerm = trim(filter_input(INPUT_GET, 'query', FILTER_UNSAFE_RAW));

            // Build log details for the search action.
            $logDetails = ['search_term' => $searchTerm];
            // Optionally capture the actor if a session is set:
            if (isset($_SESSION['admin_id'])) {
                $logDetails['admin_id'] = $_SESSION['admin_id'];
            } elseif (isset($_SESSION['librarian_id'])) {
                $logDetails['librarian_id'] = $_SESSION['librarian_id'];
            } elseif (isset($_SESSION['user_id'])) {
                $logDetails['user_id'] = $_SESSION['user_id'];
            }
            logAction('search_books', $logDetails);

            // Prepare and execute the query using a prepared statement
            $sql = "SELECT * FROM Books WHERE title LIKE ? OR author LIKE ? OR genre LIKE ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                die("Error preparing statement: " . $conn->error);
            }
            $searchPattern = "%" . $searchTerm . "%";
            $stmt->bind_param("sss", $searchPattern, $searchPattern, $searchPattern);
            $stmt->execute();
            $result = $stmt->get_result();

            // Display the search results
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="book-item">';
                    echo '<h3>' . htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') . '</h3>';
                    echo '<p><strong>Author:</strong> ' . htmlspecialchars($row['author'], ENT_QUOTES, 'UTF-8') . '</p>';
                    echo '<p><strong>Genre:</strong> ' . htmlspecialchars($row['genre'], ENT_QUOTES, 'UTF-8') . '</p>';
                    echo '</div>';
                    echo '<hr>';
                }
            } else {
                echo "<p>No books found matching your search criteria.</p>";
            }

            // Close the statement and connection
            $stmt->close();
            $conn->close();
        }
        ?>
    </div>
  </div>

</body>
</html>


<?php
session_start();
include 'db_connection.php';

// Ensure searchQuery is always initialized
$searchQuery = isset($_GET['query']) ? $_GET['query'] : '';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = intval($_SESSION['user_id']);

// Get unread notification count
$unreadCount = 0;
$countStmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'unread'");
if ($countStmt) {
    $countStmt->bind_param("i", $userId);
    $countStmt->execute();
    $countStmt->bind_result($unreadCount);
    $countStmt->fetch();
    $countStmt->close();
}

// Handle borrow action when form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_id'])) {
    $bookId = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);
    if ($bookId === false || $bookId === null) {
        echo "Invalid book ID.";
        exit();
    }

    // Check if the book is available
    $checkSql = "SELECT status, title FROM Books WHERE book_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $bookId);
    $checkStmt->execute();
    $checkStmt->bind_result($status, $title);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($status !== 'available') {
        echo "Book is not available.";
        exit();
    }

    // Process borrow
    $dueDate = date('Y-m-d H:i:s', strtotime('+14 days'));
    $sql = "INSERT INTO BorrowedBooks (user_id, book_id, due_date) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $userId, $bookId, $dueDate);

    if ($stmt->execute()) {
        // Update book status to 'borrowed'
        $updateSql = "UPDATE Books SET status = 'borrowed' WHERE book_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("i", $bookId);
        $updateStmt->execute();
        $updateStmt->close();

        // Set success message
        $successMessage = "$title borrowed successfully!";
    } else {
        echo "Error during borrowing: " . $stmt->error;
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Borrow Books</title>
  <link rel="stylesheet" href="/css/style.css" />
  <style>
    .search-bar {
      margin-bottom: 1rem;
      display: flex;
      justify-content: center;
      flex-direction: column;
      align-items: center;
    }

    input[type="text"] {
      width: 80%;
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
      margin-top: 10px;
    }

    button:hover {
      background-color: #4a5d78;
    }

    .book-item {
      background-color: #252526;
      color: #fff;
      padding: 15px;
      margin: 10px 0;
      border-radius: 6px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .book-item h3 {
      margin: 0;
      font-size: 1.5rem;
    }

    .book-item p {
      margin: 5px 0;
    }

    .book-item button {
      background-color: #5a6e8c;
      color: #fff;
      padding: 10px 20px;
      font-size: 16px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      transition: background-color 0.2s ease-in-out;
    }

    .book-item button:hover {
      background-color: #4a5d78;
    }

    .success-message {
      color: #2ecc71;
      font-size: 1.2rem;
      margin-bottom: 20px;
    }

    /* Notification Badge CSS */
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

    /* Ensure parent of badge has position relative */
    .notifications-link {
      position: relative;
    }
  </style>
</head>
<body>
  <?php include 'nav.php'; ?> <!-- Include the Navigation Bar -->

  <div class="container">
    <header>
      <h1>Borrow a Book</h1>
    </header>

    <div class="search-bar">
      <form action="borrow_books.php" method="GET">
        <input type="text" name="query" placeholder="Search by title, author, or genre" value="<?php echo htmlspecialchars($searchQuery); ?>" />
        <button type="submit">Search</button>
      </form>
    </div>

    <main>
      <?php
      if (isset($successMessage) && $successMessage !== '') {
          echo "<p class='success-message'>$successMessage</p>";
      }

      if (isset($userId)) {
          // Modify the query to filter books based on search
          $sql = "SELECT book_id, title, author, genre FROM Books WHERE status = 'available' AND 
                  (title LIKE ? OR author LIKE ? OR genre LIKE ?)";
          $searchPattern = "%" . $searchQuery . "%";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param("sss", $searchPattern, $searchPattern, $searchPattern);
          $stmt->execute();
          $result = $stmt->get_result();

          if ($result && $result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                  $bookId = htmlspecialchars($row['book_id'], ENT_QUOTES, 'UTF-8');
                  $title = htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8');
                  $author = htmlspecialchars($row['author'], ENT_QUOTES, 'UTF-8');
                  $genre = htmlspecialchars($row['genre'], ENT_QUOTES, 'UTF-8');
                  echo '<div class="book-item" id="book_' . $bookId . '">';
                  echo '<h3>' . $title . '</h3>';
                  echo '<p><strong>Author:</strong> ' . $author . '</p>';
                  echo '<p><strong>Genre:</strong> ' . $genre . '</p>';
                  echo '<form action="borrow_books.php" method="POST">';
                  echo '<input type="hidden" name="book_id" value="' . $bookId . '" />';
                  echo '<button type="submit">Borrow Book</button>';
                  echo '</form>';
                  echo '</div>';
              }
          } else {
              echo "<p>No available books found matching your search criteria.</p>";
          }

          $stmt->close();
      } else {
          echo "<p>You must be logged in to borrow books.</p>";
      }

      $conn->close();
      ?>
    </main>

  </div>
</body>
</html>


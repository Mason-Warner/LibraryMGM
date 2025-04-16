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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Return a Book</title>
  <link rel="stylesheet" href="/css/style.css" />
  <style>
    main form {
      margin-top: 1rem;
    }

    label, select, button {
      font-size: 1rem;
      display: block;
      margin-bottom: 1rem;
    }

    select {
      width: 100%;
      padding: 0.6rem;
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
      <h1>Return a Book</h1>
    </header>

    <main>
      <?php
      if (isset($userId)) {
          // If the form hasn't been submitted yet, display available borrowed books
          if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
              // Prepare the query to fetch borrowed books without a return date
              $sql = "SELECT transaction_id, Books.book_id, title FROM BorrowedBooks
                      JOIN Books ON BorrowedBooks.book_id = Books.book_id 
                      WHERE BorrowedBooks.user_id = ? AND return_date IS NULL";
              $stmt = $conn->prepare($sql);
              if ($stmt === false) {
                  die("Error preparing statement: " . $conn->error);
              }
              $stmt->bind_param("i", $userId);
              $stmt->execute();
              $result = $stmt->get_result();

              if ($result && $result->num_rows > 0) {
                  // Display the form for returning a book
                  echo '<form action="return_books.php" method="POST">';
                  echo '<label for="transaction_id">Select a book to return:</label>';
                  echo '<select name="transaction_id" id="transaction_id" required>';
                  
                  // Output borrowed books in a dropdown, sanitizing the title output
                  while ($row = $result->fetch_assoc()) {
                      $transactionId = intval($row['transaction_id']);
                      $title = htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8');
                      echo '<option value="' . $transactionId . '">' . $title . '</option>';
                  }
                  echo '</select>';
                  echo '<button type="submit">Return Book</button>';
                  echo '</form>';

                  $result->free();
              } else {
                  echo "<p>You have no books to return.</p>";
              }
              $stmt->close();
          }

          // Handle the return process if the form was submitted
          if ($_SERVER['REQUEST_METHOD'] == 'POST') {
              $transactionId = filter_input(INPUT_POST, 'transaction_id', FILTER_VALIDATE_INT);
              if ($transactionId === false || $transactionId === null) {
                  echo "<p>Invalid transaction ID provided.</p>";
                  exit();
              }

              // Fetch the book_id and title associated with the given transaction using a prepared statement
              $getBookDetailsSql = "SELECT Books.book_id, Books.title FROM BorrowedBooks
                                    JOIN Books ON BorrowedBooks.book_id = Books.book_id
                                    WHERE BorrowedBooks.transaction_id = ?";
              $stmtGetBookDetails = $conn->prepare($getBookDetailsSql);
              if ($stmtGetBookDetails === false) {
                  die("Error preparing statement: " . $conn->error);
              }
              $stmtGetBookDetails->bind_param("i", $transactionId);
              $stmtGetBookDetails->execute();
              $stmtGetBookDetails->bind_result($bookId, $bookTitle);
              $stmtGetBookDetails->fetch();
              $stmtGetBookDetails->close();

              if ($bookId) {
                  // Update the return_date in BorrowedBooks to the current timestamp
                  $returnDate = date('Y-m-d H:i:s');
                  $updateReturnDateSql = "UPDATE BorrowedBooks SET return_date = ? WHERE transaction_id = ?";
                  $updateStmt = $conn->prepare($updateReturnDateSql);
                  if ($updateStmt === false) {
                      die("Error preparing statement: " . $conn->error);
                  }
                  $updateStmt->bind_param("si", $returnDate, $transactionId);
                  $updateStmt->execute();
                  $updateStmt->close();

                  // Update the status of the book to 'available' in the Books table
                  $updateBookStatusSql = "UPDATE Books SET status = 'available' WHERE book_id = ?";
                  $updateBookStmt = $conn->prepare($updateBookStatusSql);
                  if ($updateBookStmt === false) {
                      die("Error preparing statement: " . $conn->error);
                  }
                  $updateBookStmt->bind_param("i", $bookId);
                  $updateBookStmt->execute();
                  $updateBookStmt->close();

                  echo "<p>The book \"$bookTitle\" has been returned successfully!</p>";

                  // Log the return action
                  $logDetails = [
                      'user_id'        => $userId,
                      'transaction_id' => $transactionId,
                      'book_id'        => $bookId,
                      'return_date'    => $returnDate
                  ];
                  logAction('return_book', $logDetails);
              } else {
                  echo "<p>Error: Invalid transaction ID.</p>";
              }
          }
      } else {
          echo "<p>You must be logged in to return books.</p>";
      }

      // Close database connection
      $conn->close();
      ?>
    </main>
  </div>
</body>
</html>


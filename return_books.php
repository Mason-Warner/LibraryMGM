<?php
session_start();
include 'db_connection.php';
require_once 'logger.php';

$unreadCount = 0;
$successMessage = "";
$errorMessage = "";
$borrowedBooks = [];

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

    // Handle return submission first so we can show updated list afterward
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $transactionId = filter_input(INPUT_POST, 'transaction_id', FILTER_VALIDATE_INT);
        if ($transactionId) {
            $getBookDetailsSql = "SELECT Books.book_id, Books.title FROM BorrowedBooks
                                  JOIN Books ON BorrowedBooks.book_id = Books.book_id
                                  WHERE BorrowedBooks.transaction_id = ?";
            $stmtGetBookDetails = $conn->prepare($getBookDetailsSql);
            $stmtGetBookDetails->bind_param("i", $transactionId);
            $stmtGetBookDetails->execute();
            $stmtGetBookDetails->bind_result($bookId, $bookTitle);
            $stmtGetBookDetails->fetch();
            $stmtGetBookDetails->close();

            if ($bookId) {
                $returnDate = date('Y-m-d H:i:s');

                $updateStmt = $conn->prepare("UPDATE BorrowedBooks SET return_date = ? WHERE transaction_id = ?");
                $updateStmt->bind_param("si", $returnDate, $transactionId);
                $updateStmt->execute();
                $updateStmt->close();

                $updateBookStmt = $conn->prepare("UPDATE Books SET status = 'available' WHERE book_id = ?");
                $updateBookStmt->bind_param("i", $bookId);
                $updateBookStmt->execute();
                $updateBookStmt->close();

                $successMessage = "✅ The book \"<strong>" . htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8') . "</strong>\" has been returned successfully.";

                // Log the return action
                logAction('return_book', [
                    'user_id'        => $userId,
                    'transaction_id' => $transactionId,
                    'book_id'        => $bookId,
                    'return_date'    => $returnDate
                ]);
            } else {
                $errorMessage = "Error: Invalid transaction ID.";
            }
        } else {
            $errorMessage = "Invalid transaction ID provided.";
        }
    }

    // Fetch updated list of borrowed books (for initial load and after return)
    $stmt = $conn->prepare("SELECT transaction_id, Books.book_id, title FROM BorrowedBooks
                            JOIN Books ON BorrowedBooks.book_id = Books.book_id 
                            WHERE BorrowedBooks.user_id = ? AND return_date IS NULL");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $borrowedBooks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
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
      min-width: 18px;
      text-align: center;
      box-shadow: 0 0 4px rgba(0, 0, 0, 0.4);
    }

    .alert {
      margin-top: 1rem;
      padding: 12px 18px;
      border-radius: 6px;
      background-color: #2a2a2a;
      color: lightgreen;
    }

    .alert-error {
      color: salmon;
    }
  </style>
</head>
<body>
  <?php include 'nav.php'; ?>

  <div class="container">
    <header>
      <h1>Return a Book</h1>
    </header>

    <main>
      <?php if (!empty($successMessage)): ?>
        <div class="alert"><?= $successMessage ?></div>
      <?php elseif (!empty($errorMessage)): ?>
        <div class="alert alert-error"><?= $errorMessage ?></div>
      <?php endif; ?>

      <?php if (isset($userId)): ?>
        <?php if (count($borrowedBooks) > 0): ?>
          <form action="return_books.php" method="POST">
            <label for="transaction_id">Select a book to return:</label>
            <select name="transaction_id" id="transaction_id" required>
              <?php foreach ($borrowedBooks as $book): ?>
                <option value="<?= intval($book['transaction_id']) ?>">
                  <?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button type="submit">Return Book</button>
          </form>
        <?php else: ?>
          <p>You have no books to return.</p>
        <?php endif; ?>
      <?php else: ?>
        <p>You must be logged in to return books.</p>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>


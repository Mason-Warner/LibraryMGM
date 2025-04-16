<?php
session_start();
include 'db_connection.php';
require_once 'logger.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard - LibraryMGM</title>
  <link rel="stylesheet" href="/css/style.css">
  <style>
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background-color: #1e1e1e;
    }

    th, td {
      padding: 12px;
      border: 1px solid #444;
      text-align: left;
    }

    th {
      background-color: #2a2a2a;
    }

    tr:nth-child(even) {
      background-color: #2d2d2d;
    }

    .btn {
      padding: 12px 20px;
      font-size: 16px;
      background-color: #5a6e8c;
      color: #fff;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      text-decoration: none;
      transition: background-color 0.2s ease-in-out;
      display: inline-block;
      margin-right: 10px;
    }

    .btn:hover {
      background-color: #4a5d78;
    }

    .btn-delete {
      background-color: #5c2e2e;
      border: 1px solid #6c3c3c;
    }

    .btn-delete:hover {
      background-color: #783535;
      border-color: #8b4444;
    }

    input[type="text"],
    input[type="number"],
    textarea {
      width: 100%;
      padding: 0.6rem;
      border-radius: 6px;
      border: 1px solid #ccc;
      background-color: #1e1e1e;
      color: #eee;
      margin-bottom: 1rem;
    }

    input[type="submit"] {
      background-color: #5a6e8c;
      color: #fff;
      padding: 12px 20px;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.2s ease-in-out;
      margin-top: 0.5rem;
    }

    input[type="submit"]:hover {
      background-color: #4a5d78;
    }
  </style>
</head>
<body>

  <?php include 'admin_nav.php'; ?>

  <div class="container">
    <header>
      <h1>Admin Dashboard</h1>
    </header>

    <section>
      <h2>Manage Inventory</h2>
      <?php
      $sql = "SELECT * FROM books";
      $result = $conn->query($sql);

      if ($result->num_rows > 0): ?>
        <table>
          <tr>
            <th>ID</th><th>Title</th><th>Author</th><th>Genre</th><th>Actions</th>
          </tr>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $row['book_id'] ?></td>
              <td><?= htmlspecialchars($row['title']) ?></td>
              <td><?= htmlspecialchars($row['author']) ?></td>
              <td><?= htmlspecialchars($row['genre']) ?></td>
              <td>
                <a href="update_book.php?id=<?= $row['book_id'] ?>" class="btn">Update</a>
                <a href="delete_book.php?id=<?= $row['book_id'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this book?');">Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </table>
      <?php else: ?>
        <p>No books found in the inventory.</p>
      <?php endif; ?>
    </section>

    <section>
      <h2>Add New Book</h2>
      <form method="post" action="add_book.php">
        <input type="text" name="title" placeholder="Title" required>
        <input type="text" name="author" placeholder="Author" required>
        <input type="text" name="genre" placeholder="Genre" required>
        <input type="submit" value="Add Book">
      </form>
    </section>

    <section>
      <h2>Send Notification to User</h2>
      <form method="post">
        <input type="number" name="user_id" placeholder="User ID" required>
        <textarea name="message" rows="4" placeholder="Enter message here..." required></textarea>
        <input type="submit" name="send_notification" value="Send Notification">
      </form>

      <?php
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
          $user_id = intval($_POST['user_id']);
          $message = trim($_POST['message']);

          $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, status) VALUES (?, ?, 'unread')");
          if ($stmt) {
              $stmt->bind_param("is", $user_id, $message);
              if ($stmt->execute()) {
                  echo "<p>Notification sent successfully to User ID: $user_id.</p>";
                  logAction('send_notification', [
                      'admin_id'       => $_SESSION['admin_id'],
                      'target_user_id' => $user_id,
                      'message'        => $message
                  ]);
              } else {
                  echo "<p>Error sending notification: " . $stmt->error . "</p>";
              }
              $stmt->close();
          } else {
              echo "<p>Error preparing statement: " . $conn->error . "</p>";
          }
      }

      $conn->close();
      ?>
    </section>
  </div>

</body>
</html>


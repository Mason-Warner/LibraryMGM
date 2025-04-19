<?php
session_start();
include 'db_connection.php';
require_once 'logger.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $message = trim($_POST['message']);
    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;

    if ($userId && $message) {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, status) VALUES (?, ?, 'unread')");
        if ($stmt) {
            $stmt->bind_param("is", $userId, $message);
            if ($stmt->execute()) {
                logAction('send_notification', [
                    'admin_id'       => $_SESSION['admin_id'],
                    'target_user_id' => $userId,
                    'message'        => $message
                ]);
                header("Location: manage_users.php?success=notification_sent");
                exit();
            } else {
                echo "<p>Error sending notification: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            echo "<p>Error preparing statement: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Message and user ID are required.</p>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Send Notification</title>
  <link rel="stylesheet" href="/css/style.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #1e1e1e;
      color: #eee;
      margin: 0;
    }

    .container {
      max-width: 800px;
      margin: 0 auto;
      background-color: #252526;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
    }

    h1 {
      text-align: center;
      font-size: 2rem;
      color: #fff;
    }

    textarea, input[type="submit"] {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border-radius: 6px;
      border: 1px solid #ccc;
      background-color: #1e1e1e;
      color: #eee;
    }

    input[type="submit"] {
      background-color: #5a6e8c;
      color: #fff;
      font-size: 16px;
      cursor: pointer;
      border: none;
      transition: background-color 0.2s ease-in-out;
    }

    input[type="submit"]:hover {
      background-color: #4a5d78;
    }
  </style>
</head>
<body>

  <?php include 'admin_nav.php'; ?>

  <div class="container">
    <h1>Send Notification</h1>
    
    <form method="post">
      <input type="hidden" name="user_id" value="<?= $userId ?>" />
      <textarea name="message" rows="4" placeholder="Enter your notification message here..." required></textarea>
      <input type="submit" name="send_notification" value="Send Notification">
    </form>
  </div>

</body>
</html>


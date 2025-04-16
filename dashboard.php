<?php
session_start();
include 'db_connection.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LibraryMGM</title>
  <link rel="stylesheet" href="/css/style.css">
  <style>
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

  <!-- Include the Navigation Bar -->
  <?php include 'nav.php'; ?>

  <div class="container">
    <header>
      <h1>Dashboard</h1>
    </header>

    <main>
      <!-- Dashboard content can go here -->
    </main>
  </div>

</body>
</html>


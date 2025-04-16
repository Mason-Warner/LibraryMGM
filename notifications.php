<?php
session_start();
include 'db_connection.php';
require_once 'logger.php'; // Include the logging function

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = intval($_SESSION['user_id']);

// Get unread notification count for badge
$countStmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'unread'");
if ($countStmt) {
    $countStmt->bind_param("i", $userId);
    $countStmt->execute();
    $countStmt->bind_result($unreadCount);
    $countStmt->fetch();
    $countStmt->close();
}

// Sanitize and validate the filter parameter (only allow 'unread' or default to 'all')
$filter = filter_input(INPUT_GET, 'filter', FILTER_UNSAFE_RAW);
$filter = ($filter === 'all') ? 'all' : 'unread';

// Log that the user viewed the notifications page
$viewLogDetails = [
    'user_id' => $userId,
    'filter'  => $filter,
    'action'  => 'view_notifications',
    'timestamp' => date('Y-m-d H:i:s')
];
logAction('view_notifications', $viewLogDetails);

// Handle status update using POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate the notification ID from POST data
    $notification_id = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);
    if ($notification_id === false || $notification_id === null) {
        echo "Invalid notification ID.";
        exit();
    }
    // Sanitize the status posted and compute new status
    $posted_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    $new_status = ($posted_status === 'unread') ? 'read' : 'unread';

    // Use a prepared statement for the update
    $updateSql = "UPDATE notifications SET status = ? WHERE notification_id = ? AND user_id = ?";
    $stmt = $conn->prepare($updateSql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("sii", $new_status, $notification_id, $userId);
    if ($stmt->execute()) {
        // Log the update action
        $updateLogDetails = [
            'user_id'         => $userId,
            'notification_id' => $notification_id,
            'new_status'      => $new_status,
            'action'          => 'update_notification'
        ];
        logAction('update_notification', $updateLogDetails);
        
        header("Location: notifications.php?filter=" . urlencode($filter)); // Retain filter on redirect
        exit();
    } else {
        echo "Error updating notification: " . $stmt->error;
    }
    $stmt->close();
}

// Build the SELECT query using a prepared statement
if ($filter === 'unread') {
    $sql = "SELECT * FROM notifications WHERE user_id = ? AND status = 'unread' ORDER BY notification_date DESC";
} else {
    $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY notification_date DESC";
}
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications</title>
  <link rel="stylesheet" href="/css/style.css">
  <style>
    /* General Reset */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    /* Body Styling */
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #1e1e1e;
      color: #d4d4d4;
 /*     margin: 60;
 /*     padding: 60;
    }

    /* Navbar */
 /*   nav {
/*      margin-bottom: 20px; /* Adjust the bottom margin of the navbar */
/*    }

    /* Container */
    .container {
      max-width: 900px;
      margin: 40px auto; /* Increased margin to give more space from the navbar */
      background-color: #252526;
      padding: 60px;
      border-radius: 10px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    }

    /* Header */
    header h1 {
      text-align: center;
      font-size: 2rem;
      color: #ffffff;
      margin-bottom: 30px;
    }

    /* Notification Styling */
    .notification {
      border: 1px solid #444;
      padding: 15px;
      margin: 10px 0;
      border-radius: 5px;
    }

    .notification.unread {
      background-color: #252526;
      color: #ffffff;
      font-weight: bold;
    }

    .notification.read {
      background-color: #333;
      color: #bdbdbd;
    }

    .notification p {
      margin: 5px 0;
    }

    /* Filters */
    .filters {
      margin-bottom: 20px;
    }

    .filters a {
      text-decoration: none;
      margin-right: 10px;
      color: #007bff;
    }

    .filters a.active {
      font-weight: bold;
      color: #0056b3;
    }

    /* Button */
    button {
      background-color: #3a3d41;
      color: #ffffff;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 1rem;
      transition: background-color 0.3s ease;
    }

    button:hover {
      background-color: #505357;
    }

    /* Notification Badge Styling */
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
      <h1>Notifications</h1>
    </header>

    <!-- Filter Options -->
    <div class="filters">
      <a href="notifications.php?filter=all" class="<?= ($filter === 'all') ? 'active' : '' ?>">All</a>
      <a href="notifications.php?filter=unread" class="<?= ($filter === 'unread') ? 'active' : '' ?>">Unread</a>
    </div>

    <!-- Notification List -->
    <?php if ($result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <div class="notification <?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>">
          <p><strong>Message:</strong> <?= htmlspecialchars($row['message'], ENT_QUOTES, 'UTF-8') ?></p>
          <p><small><strong>Date:</strong> <?= htmlspecialchars($row['notification_date'], ENT_QUOTES, 'UTF-8') ?></small></p>
          <form method="POST" action="">
            <input type="hidden" name="notification_id" value="<?= intval($row['notification_id']) ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">
              <?= ($row['status'] === 'unread') ? 'Mark as Read' : 'Mark as Unread' ?>
            </button>
          </form>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>No notifications available.</p>
    <?php endif; ?>
  </div>

</body>
</html>
<?php
$stmt->close();
$conn->close();
?>


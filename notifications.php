<?php
session_start();
include 'db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You need to log in to view notifications.";
    exit();
}

$userId = intval($_SESSION['user_id']);

// Sanitize and validate the filter parameter (only allow 'unread' or default to 'all')
$filter = filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_STRING);
$filter = ($filter === 'unread') ? 'unread' : 'all';

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
    <title>User Notifications</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: auto;
            padding: 20px;
        }
        .notification {
            border: 1px solid #ccc;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .unread {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .read {
            background-color: #e9e9e9;
        }
        .notification p {
            margin: 5px 0;
        }
        form {
            margin-top: 10px;
        }
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
        .back-link {
            margin-top: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <h1>Notifications</h1>

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

    <!-- Back to Dashboard Link -->
    <a href="dashboard.html" class="back-link">Back to Dashboard</a>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>

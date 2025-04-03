<?php
session_start();
include 'db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You need to log in to view notifications.";
    exit();
}

$userId = $_SESSION['user_id'];

// Handle filter selection
$filter = isset($_GET['filter']) && $_GET['filter'] === 'unread' ? 'unread' : 'all';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $notification_id = $_POST['notification_id'];
    $new_status = $_POST['status'] === 'unread' ? 'read' : 'unread';

    $sql = "UPDATE notifications SET status='$new_status' WHERE notification_id=$notification_id AND user_id=$userId";
    if ($conn->query($sql) === TRUE) {
        header("Location: notifications.php?filter=$filter"); // Retain filter on redirect
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Fetch notifications based on the filter
$sql = "SELECT * FROM notifications WHERE user_id = $userId";
if ($filter === 'unread') {
    $sql .= " AND status = 'unread'";
}
$sql .= " ORDER BY notification_date DESC";
$result = $conn->query($sql);
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
        <a href="notifications.php?filter=all" class="<?= $filter === 'all' ? 'active' : '' ?>">All</a>
        <a href="notifications.php?filter=unread" class="<?= $filter === 'unread' ? 'active' : '' ?>">Unread</a>
    </div>

    <!-- Notification List -->
    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="notification <?= $row['status'] ?>">
                <p><strong>Message:</strong> <?= htmlspecialchars($row['message']) ?></p>
                <p><small><strong>Date:</strong> <?= $row['notification_date'] ?></small></p>
                <form method="POST" action="">
                    <input type="hidden" name="notification_id" value="<?= $row['notification_id'] ?>">
                    <input type="hidden" name="status" value="<?= $row['status'] ?>">
                    <button type="submit">
                        <?= $row['status'] === 'unread' ? 'Mark as Read' : 'Mark as Unread' ?>
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
$conn->close();
?>


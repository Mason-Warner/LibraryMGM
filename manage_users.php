<?php
session_start();
include 'db_connection.php';
require_once 'logger.php';

// --- Encryption Setup ---
define('ENCRYPTION_KEY', 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6');
define('ENCRYPTION_IV', 'abcdef1234567890');

function decryptData($encryptedData, $key, $iv) {
    if(empty($encryptedData)) {
        return "";
    }
    $decoded = base64_decode($encryptedData);
    return openssl_decrypt($decoded, 'AES-256-CBC', $key, 0, $iv);
}

if (isset($_SESSION['admin_id'])) {
    $logDetails = [
        'admin_id' => $_SESSION['admin_id'],
        'action'   => 'view_manage_users',
        'timestamp'=> date('Y-m-d H:i:s')
    ];
    logAction('view_manage_users', $logDetails);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
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
            line-height: 1.6;
        }

        /* Container */
        .container {
            max-width: 1100px;
            margin: 40px auto;
            background-color: #252526;
            padding: 30px;
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

        /* Table Styling */
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

        tr:nth-child(odd) {
            background-color: #252526;
        }

        /* Button Styles from Admin Dashboard */
        td a.btn, td a.btn-delete {
            display: inline-block;
            padding: 12px 20px;
            font-size: 16px;
            background-color: #5a6e8c; /* Update button color */
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            border: 1px solid #3f3f46;
            transition: background-color 0.2s ease, border-color 0.2s ease;
            margin-right: 10px;
        }

        td a.btn:hover {
            background-color: #4a5d78;
            border-color: #646464;
        }

        td a.btn.btn-delete {
            background-color: #5c2e2e; /* Delete button color */
            border-color: #6c3c3c;
        }

        td a.btn.btn-delete:hover {
            background-color: #783535;
            border-color: #8b4444;
        }

        /* Spacing for Action Buttons */
        td {
            white-space: nowrap; /* Prevents line breaks */
            width: 200px; /* Increase column width */
        }

        /* Spacing for body content below the navbar */
        body {
            padding-top: 60px;
        }
    </style>
</head>
<body>
    <?php include 'admin_nav.php'; ?>

    <div class="container">
        <h1>Manage Users</h1>

	<?php if (isset($_GET['success']) && $_GET['success'] === 'user_deleted'): ?>
    	<p style="color: lightblue; background-color: #2a2a2a; padding: 10px 15px; border-radius: 6px; margin-top: 1rem;">
        ✅ User deleted successfully!
    	</p>
	<?php endif; ?>

	<?php if (isset($_GET['success']) && $_GET['success'] === 'user_updated'): ?>
    	<p style="color: lightgreen; background-color: #2a2a2a; padding: 10px 15px; border-radius: 6px; margin-top: 1rem;">
        ✅ User updated successfully!
    	</p>
	<?php endif; ?>

	<?php if (isset($_GET['success']) && $_GET['success'] === 'notification_sent'): ?>
    	<p style="color: skyblue; background-color: #2a2a2a; padding: 10px 15px; border-radius: 6px; margin-top: 1rem;">
        ✅ Notification sent successfully!
    	</p>
	<?php endif; ?>

        <?php
        $sql = "SELECT * FROM users";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Contact Number</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()):
                    $userId = intval($row['user_id']);
                    $username = htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8');
                    $fullName = htmlspecialchars(decryptData($row['full_name'], ENCRYPTION_KEY, ENCRYPTION_IV), ENT_QUOTES, 'UTF-8');
                    $email = htmlspecialchars(decryptData($row['email'], ENCRYPTION_KEY, ENCRYPTION_IV), ENT_QUOTES, 'UTF-8');
                    $contactNumber = !empty($row['contact_number']) ? htmlspecialchars(decryptData($row['contact_number'], ENCRYPTION_KEY, ENCRYPTION_IV), ENT_QUOTES, 'UTF-8') : "";
                ?>
                    <tr>
                        <td><?= $userId ?></td>
                        <td><?= $username ?></td>
                        <td><?= $fullName ?></td>
                        <td><?= $email ?></td>
                        <td><?= $contactNumber ?></td>
                        <td>
                            <a href="update_user.php?id=<?= $userId ?>" class="btn">Update</a>
                            <a href="delete_user.php?id=<?= $userId ?>" onclick="return confirm('Are you sure you want to delete this user?');" class="btn btn-delete">Delete</a>
                            <a href="send_notification.php?user_id=<?= $userId ?>" class="btn">Send Notification</a> <!-- New button -->
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No users found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
} else {
    echo "<p style='color: red;'>Access denied. Admins only.</p>";
}
$conn->close();
?>


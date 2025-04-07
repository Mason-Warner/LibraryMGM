<?php
session_start();
include 'db_connection.php';

if (isset($_SESSION['admin_id'])) {
    echo "<h1>Manage Users</h1>";

    // Display all users
    $sql = "SELECT * FROM users";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Contact Details</th><th>Actions</th></tr>";
        while ($row = $result->fetch_assoc()) {
            // Sanitize output by escaping HTML special characters
            $userId = intval($row['user_id']);
            $username = htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8');
            $contact_number = htmlspecialchars($row['contact_number'], ENT_QUOTES, 'UTF-8');

            echo "<tr>";
            echo "<td>" . $userId . "</td>";
            echo "<td>" . $username . "</td>";
            echo "<td>" . $contact_number . "</td>";
            echo "<td>";
            echo "<a href='update_user.php?id=" . $userId . "'>Update</a> | ";
            echo "<a href='delete_user.php?id=" . $userId . "' onclick=\"return confirm('Are you sure you want to delete this user?');\">Delete</a>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found.</p>";
    }

    // Add link back to the admin dashboard
    echo "<br><p><a href='admin_dashboard.php'>Back to Admin Dashboard</a></p>";

} else {
    echo "Access denied. Admins only.";
}

$conn->close();
?>

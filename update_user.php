<?php
// Start the session and include the database connection
session_start();
include 'db_connection.php';

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['admin_id'])) {
    echo "You must be logged in to edit user profiles.";
    exit;
}

$user_id = $_SESSION['admin_id']; // Get the logged-in user's ID

// Check if the logged-in user is an admin
$sql = "SELECT role FROM Admins WHERE admin_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role !== 'admin') {
    echo "You do not have permission to access this page.";
    exit;
}

// If the form has been submitted (POST request)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $edit_user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $contact_number = $_POST['contact_number'];

    // Check if a new password is provided
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET username = ?, full_name = ?, email = ?, contact_number = ?, password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssssi", $username, $full_name, $email, $contact_number, $password, $edit_user_id);
    } else {
        $update_sql = "UPDATE users SET username = ?, full_name = ?, email = ?, contact_number = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssssi", $username, $full_name, $email, $contact_number, $edit_user_id);
    }

    // Execute the update
    if ($stmt->execute()) {
        echo "User profile updated successfully.";
    } else {
        echo "Error updating user profile: " . $stmt->error;
    }
    $stmt->close();
}

// If the GET request is present, fetch the user details to be edited
if (isset($_GET['id'])) {
    $edit_user_id = $_GET['id'];
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $edit_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Fetch the user details
        $user = $result->fetch_assoc();
    } else {
        echo "User not found.";
        exit;
    }
    $stmt->close();
} else {
    echo "No user selected to edit.";
    exit;
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User Profile</title>
</head>
<body>
    <h2>Edit User Profile</h2>

    <form action="edit_user.php" method="POST">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">

        <label for="username">Username:</label>
        <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" required><br><br>

        <label for="full_name">Full Name:</label>
        <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required><br><br>

        <label for="email">Email:</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required><br><br>

        <label for="contact_number">Contact Number:</label>
        <input type="text" name="contact_number" id="contact_number" value="<?php echo htmlspecialchars($user['contact_number']); ?>"><br><br>

        <label for="password">New Password (Leave blank if not changing):</label>
        <input type="password" name="password" id="password"><br><br>

        <button type="submit">Update Profile</button>
    </form>

    <br>
    <p><a href="manage_users.php">Back to User Management</a></p>
</body>
</html>


<?php
// Start the session and include the database connection
session_start();
include 'db_connection.php';
require_once 'logger.php'; // Include the logging function

define('ENCRYPTION_KEY', 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6');
define('ENCRYPTION_IV', 'abcdef1234567890');

function encryptData($plaintext, $key, $iv) {
    $encrypted = openssl_encrypt((string)$plaintext, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($encrypted);
}

function decryptData($encryptedData, $key, $iv) {
    $decoded = base64_decode($encryptedData);
    return openssl_decrypt($decoded, 'AES-256-CBC', $key, 0, $iv);
}

if (!isset($_SESSION['admin_id'])) {
    echo "You must be logged in to edit user profiles.";
    exit;
}

$admin_id = intval($_SESSION['admin_id']);

$sql = "SELECT role FROM Admins WHERE admin_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role !== 'admin') {
    echo "You do not have permission to access this page.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    if ($edit_user_id === false || $edit_user_id === null) {
        echo "Invalid user ID.";
        exit;
    }

    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
    $full_name = trim(filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
        exit;
    }
    $contact_number = trim(filter_input(INPUT_POST, 'contact_number', FILTER_SANITIZE_STRING));

    if (!empty($_POST['password'])) {
        $raw_password = trim($_POST['password']);
        $password = password_hash($raw_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE users SET username = ?, full_name = ?, email = ?, contact_number = ?, password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssssi",
            $username,
            encryptData($full_name, ENCRYPTION_KEY, ENCRYPTION_IV),
            encryptData($email, ENCRYPTION_KEY, ENCRYPTION_IV),
            encryptData($contact_number, ENCRYPTION_KEY, ENCRYPTION_IV),
            $password,
            $edit_user_id
        );
    } else {
        $update_sql = "UPDATE users SET username = ?, full_name = ?, email = ?, contact_number = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssssi",
            $username,
            encryptData($full_name, ENCRYPTION_KEY, ENCRYPTION_IV),
            encryptData($email, ENCRYPTION_KEY, ENCRYPTION_IV),
            encryptData($contact_number, ENCRYPTION_KEY, ENCRYPTION_IV),
            $edit_user_id
        );
    }

    if ($stmt->execute()) {
        $logDetails = [
            'edited_user_id'     => $edit_user_id,
            'new_username'       => $username,
            'new_full_name'      => encryptData($full_name, ENCRYPTION_KEY, ENCRYPTION_IV),
            'new_email'          => encryptData($email, ENCRYPTION_KEY, ENCRYPTION_IV),
            'new_contact_number' => encryptData($contact_number, ENCRYPTION_KEY, ENCRYPTION_IV),
            'changed_by_admin'   => $admin_id,
            'update_time'        => date('Y-m-d H:i:s')
        ];
        logAction('update_user', $logDetails);
        header("Location: manage_users.php");
        exit;
    } else {
        echo "Error updating user profile: " . $stmt->error;
    }
    $stmt->close();
}

if (isset($_GET['id'])) {
    $edit_user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($edit_user_id === false || $edit_user_id === null) {
        echo "Invalid user selected to edit.";
        exit;
    }
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $edit_user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user['full_name'] = decryptData($user['full_name'], ENCRYPTION_KEY, ENCRYPTION_IV);
        $user['email'] = decryptData($user['email'], ENCRYPTION_KEY, ENCRYPTION_IV);
        $user['contact_number'] = decryptData($user['contact_number'], ENCRYPTION_KEY, ENCRYPTION_IV);
    } else {
        echo "User not found.";
        exit;
    }
    $stmt->close();
} else {
    echo "No user selected to edit.";
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User Profile</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #1e1e1e;
            color: #d4d4d4;
            margin: 0;
            padding: 20px;
        }

        h2 {
            color: #ffffff;
            text-align: center;
        }

        nav {
            margin-bottom: 30px;
        }

        nav a {
            background-color: #2d2d2d;
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            margin-right: 10px;
            transition: background-color 0.3s ease;
        }

        nav a:hover {
            background-color: #3a3d41;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background-color: #2a2a2a;
            padding: 30px;
            border-radius: 10px;
        }

        label {
            display: block;
            margin-top: 15px;
            color: #fff;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            background-color: #333;
            color: #fff;
            border: 1px solid #555;
            border-radius: 6px;
        }

        button {
            margin-top: 20px;
            background-color: #3a3d41;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #505357;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #4da3ff;
            text-decoration: none;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<nav>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="manage_users.php">Manage Users</a>
</nav>

<div class="container">
    <h2>Edit User Profile</h2>
    <form action="update_user.php" method="POST">
        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES, 'UTF-8'); ?>">

        <label for="username">Username</label>
        <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>" required>

        <label for="full_name">Full Name</label>
        <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required>

        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" required>

        <label for="contact_number">Contact Number</label>
        <input type="text" name="contact_number" id="contact_number" value="<?php echo htmlspecialchars($user['contact_number'], ENT_QUOTES, 'UTF-8'); ?>">

        <label for="password">New Password (leave blank if not changing)</label>
        <input type="password" name="password" id="password">

        <button type="submit">Update Profile</button>
    </form>

</div>

</body>
</html>


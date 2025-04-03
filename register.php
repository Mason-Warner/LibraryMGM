<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>
<p>
<?php
include 'db_connection.php'; // Ensure you have a file for connecting to the database

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password for security
    $fullName = $_POST['full_name'];
    $email = $_POST['email'];

    $sql = "INSERT INTO Users (username, password, full_name, email) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $username, $password, $fullName, $email);

    if ($stmt->execute()) {
        // Registration successful, redirect to login page
        header("Location: login.html");
        exit(); // Stop further execution
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
</p>
</body>
</html>


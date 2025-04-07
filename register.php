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
    // Sanitize and validate inputs
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
    $rawPassword = trim($_POST['password']); // Password is processed as-is for hashing
    $fullName = trim(filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
        exit();
    }
    
    // Basic check for empty fields (you can expand this as needed)
    if (empty($username) || empty($rawPassword) || empty($fullName) || empty($email)) {
        echo "All fields are required.";
        exit();
    }
    
    // Hash the password using PASSWORD_BCRYPT
    $password = password_hash($rawPassword, PASSWORD_BCRYPT);

    // Prepare the SQL statement to insert a new user
    $sql = "INSERT INTO Users (username, password, full_name, email) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("ssss", $username, $password, $fullName, $email);

    // Execute the statement
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

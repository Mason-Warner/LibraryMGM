<?php
// Include the database connection file
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form inputs
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $contact_number = trim($_POST["contact_number"]);
    $role = trim($_POST["role"]);

    // Hash the password using PASSWORD_BCRYPT
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Prepare the SQL statement to insert a new admin
    $stmt = $conn->prepare("INSERT INTO Admins (username, password, full_name, email, contact_number, role) VALUES (?, ?, ?, ?, ?, ?)");
    
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    
    // Bind the parameters to the SQL query
    $stmt->bind_param("ssssss", $username, $hashed_password, $full_name, $email, $contact_number, $role);
    
    // Execute the statement
    if ($stmt->execute()) {
        // Registration successful; redirect to admin_login.html
        header("Location: admin_login.html");
        exit;
    } else {
        // Display error message if insertion fails
        echo "Error: " . $stmt->error;
    }
    
    // Close the statement and connection
    $stmt->close();
}
$conn->close();
?>

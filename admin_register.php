<?php
// Include the database connection file
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate form inputs
    $username = filter_var(trim($_POST["username"]), FILTER_SANITIZE_STRING);
    $password = trim($_POST["password"]); // Passwords are not modified for hashing
    $full_name = filter_var(trim($_POST["full_name"]), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $contact_number = filter_var(trim($_POST["contact_number"]), FILTER_SANITIZE_STRING);
    $role = trim($_POST["role"]);

    // Validate email address
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email address.");
    }
    
    // Validate role: only allow "librarian" or "admin"
    if ($role !== "librarian" && $role !== "admin") {
        die("Invalid role selected.");
    }

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

<?php

// Database connection settings
$servername = "localhost";
$username = "mwarner"; 
$password = "Atwood13$!"; 
$dbname = "librarydb"; 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>

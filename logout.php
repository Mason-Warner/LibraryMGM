<?php
session_start();
session_destroy(); // Destroy the session to log the user out

// Redirect to the login page after logging out
header("Location: login.html");
exit(); // Ensure no further code is executed after the redirect
?>


<?php
// mongo_config.php
require 'vendor/autoload.php'; // Ensure Composer autoload is included

try {
    // Replace the URI with your actual MongoDB connection string
    $mongoClient = new MongoDB\Client("mongodb+srv://mwarner0:PdUQ1eegJTmGUhj6@cosc641.ohflvet.mongodb.net/?retryWrites=true&w=majority&appName=cosc641");
    // Select your logging database (e.g., "action_logs")
    $mongoDB = $mongoClient->selectDatabase("action_logs");
} catch (Exception $e) {
    die("Error connecting to MongoDB: " . $e->getMessage());
}

return $mongoDB;
?>

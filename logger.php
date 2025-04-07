<?php
// logger.php
function logAction($actionType, $details = [])
{
    // Load the MongoDB database instance from your configuration file
    $mongoDB = include 'mongo_config.php';
    $logsCollection = $mongoDB->logs; // "logs" is the collection for logging actions

    // Prepare a document with the log details
    $logEntry = [
        'action'    => $actionType,         // e.g., 'user_login', 'admin_delete_user'
        'details'   => $details,            // Associative array with any extra data
        'timestamp' => new MongoDB\BSON\UTCDateTime(), // Current time in MongoDB's format
    ];

    try {
        // Insert the log entry into the collection
        $logsCollection->insertOne($logEntry);
    } catch (Exception $e) {
        // Optionally handle errors here (e.g., write to a file if MongoDB logging fails)
        error_log("Logging failed: " . $e->getMessage());
    }
}
?>

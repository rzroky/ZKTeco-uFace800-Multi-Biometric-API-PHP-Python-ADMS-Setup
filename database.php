<?php

// Database connection settings
$host = 'localhost';            // Hostname of the database server
$dbname = 'attendance_db';      // Name of the database
$username = 'attendance_user';             // Database username
$password = 'mypassword';                 // Database password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Handle connection failure: display a generic error message
    // In a production app, do not display raw errors to the user for security
    echo "Connection failed: " . $e->getMessage();
}

// The $conn PDO object is now available for database operations.
?>

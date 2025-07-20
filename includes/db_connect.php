<?php
// File: C:\xampp\htdocs\quiz_platform\includes\db_connect.php

// --- Database Configuration ---
// Define constants for database credentials. Using constants is a good practice
// as it prevents the values from being accidentally changed elsewhere in the code.
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Default XAMPP username
define('DB_PASSWORD', '');     // Default XAMPP password is empty
define('DB_NAME', 'quiz_platform');

// --- Establish Database Connection ---
// We'll use the MySQLi extension (MySQL Improved) to connect.
// The 'new mysqli()' constructor attempts to create a connection object.
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// --- Check Connection ---
// It's crucial to check if the connection was successful.
// The 'connect_error' property of the mysqli object will contain an error message
// if the connection failed.
if ($conn->connect_error) {
    // If the connection fails, we stop the script immediately using die().
    // This prevents any further PHP code from running, which could lead to more errors.
    // We output a descriptive error message to help with debugging.
    die("ERROR: Connection failed. " . $conn->connect_error);
}

// --- Set Character Set ---
// It's good practice to set the character set to utf8mb4 to support a wide
// range of characters, including emojis.
if (!$conn->set_charset("utf8mb4")) {
    // If setting the charset fails, log the error or handle it as needed.
    // For now, we can print an error, but in a production environment,
    // you might log this to a file.
    // printf("Error loading character set utf8mb4: %s\n", $conn->error);
}

// If the script reaches this point, the connection was successful.
// The $conn variable can now be used in other PHP scripts to perform database queries.
?>

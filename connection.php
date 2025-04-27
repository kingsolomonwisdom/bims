<?php
/**
 * Database Connection File
 */

// Database credentials
$server = "localhost";
$username = "root";  // default XAMPP username
$password = "";      // default XAMPP password (blank)
$database = "bbims";  // your database name

// First connect without specifying a database
$con = new mysqli($server, $username, $password);

// Check connection
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Create the database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($con->query($sql) !== TRUE) {
    die("Error creating database: " . $con->error);
}

// Close the initial connection
$con->close();

// Connect to the database
$con = new mysqli($server, $username, $password, $database);

// Check connection again
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Set character set
if (!$con->set_charset("utf8")) {
    printf("Error loading character set utf8: %s\n", $con->error);
    exit();
}

// Create users table if it doesn't exist
$users_table = "
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_photo VARCHAR(255) DEFAULT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    last_login DATETIME DEFAULT NULL
)";

if ($con->query($users_table) !== TRUE) {
    die("Error creating users table: " . $con->error);
}

// Create User Profiles directory if it doesn't exist
$profile_dir = "User Profiles";
if (!file_exists($profile_dir)) {
    mkdir($profile_dir, 0777, true);
}
?> 
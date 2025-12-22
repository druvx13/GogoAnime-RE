<?php
$host = "localhost";
$dbname = "gogoanime1";
$username = "root";
$password = "";
$driver = "mysql"; // Default to mysql

// Load local config if available
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
    if (isset($db_host)) $host = $db_host;
    if (isset($db_name)) $dbname = $db_name;
    if (isset($db_user)) $username = $db_user;
    if (isset($db_pass)) $password = $db_pass;
    if (isset($db_driver)) $driver = $db_driver;
}

try {
    if ($driver === 'sqlite') {
        $conn = new PDO("sqlite:$dbname");
    } else {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    }

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Enable foreign keys for SQLite
    if ($driver === 'sqlite') {
        $conn->exec("PRAGMA foreign_keys = ON;");
    }
} catch(PDOException $e) {
    // Don't echo the error message directly in production as it might leak sensitive info
    error_log("Connection failed: " . $e->getMessage());
    // For CLI/Debugging, we might want to see it
    if (php_sapi_name() === 'cli') {
        die("Database connection failed: " . $e->getMessage() . "\n");
    }
    die("Database connection failed. Please check logs.");
}
?>
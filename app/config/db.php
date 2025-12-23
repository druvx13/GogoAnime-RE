<?php
/**
 * Database Configuration
 *
 * This file establishes the database connection using PDO.
 * It supports both MySQL and SQLite drivers and allows for local configuration overrides.
 *
 * @package    GogoAnime Clone
 * @subpackage Configuration
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

/**
 * @var string $host Database host (default: localhost).
 */
$host = "localhost";

/**
 * @var string $dbname Database name (default: gogoanime1).
 */
$dbname = "gogoanime1";

/**
 * @var string $username Database username (default: root).
 */
$username = "root";

/**
 * @var string $password Database password (default: empty).
 */
$password = "";

/**
 * @var string $driver Database driver, 'mysql' or 'sqlite' (default: mysql).
 */
$driver = "mysql";

// Load local config if available
// Local config can override $host, $dbname, $username, $password, and $driver.
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
        // Ensure path is correct for SQLite
        $conn = new PDO("sqlite:$dbname");
    } else {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    }

    // Set error mode to exception for better error handling
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Enable foreign keys for SQLite
    if ($driver === 'sqlite') {
        $conn->exec("PRAGMA foreign_keys = ON;");
    }
} catch(PDOException $e) {
    // Log error to server logs instead of displaying sensitive info
    error_log("Connection failed: " . $e->getMessage());

    // In CLI mode, display error for debugging
    if (php_sapi_name() === 'cli') {
        die("Database connection failed: " . $e->getMessage() . "\n");
    }

    // In production, show generic message
    die("Database connection failed. Please check logs.");
}
?>

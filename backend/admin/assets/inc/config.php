<?php
// Load environment variables from .env file if it exists
$env_file = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Database configuration
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbuser = $_ENV['DB_USER'] ?? 'root';
$dbpass = $_ENV['DB_PASSWORD'] ?? '';
$db = $_ENV['DB_NAME'] ?? 'hmisphp';

// Create database connection with error handling
$mysqli = new mysqli($host, $dbuser, $dbpass, $db);

// Check connection
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    die("Database connection failed. Please contact the administrator.");
}

// Set charset to utf8
$mysqli->set_charset("utf8mb4");

// Load security functions
require_once dirname(__FILE__) . '/security.php';

// Initialize security
init_secure_session();
set_security_headers();
?>

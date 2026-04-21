<?php

// -----------------------------
// Database configuration (Docker-safe)
// -----------------------------

// Use environment variables first (BEST for Docker)
$host = getenv('DB_HOST');
$dbuser = getenv('DB_USER');
$dbpass = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME');

// Fallbacks (local/dev safety)
$host   = $host ?: 'mysql-db';
$dbuser = $dbuser ?: 'root';
$dbpass = $dbpass ?: 'root';
$dbname = $dbname ?: 'hmisphp';

// -----------------------------
// Create connection
// -----------------------------
$mysqli = new mysqli($host, $dbuser, $dbpass, $dbname);

// Check connection
if ($mysqli->connect_error) {
    error_log("Database connection failed: " . $mysqli->connect_error);
    die("Database connection failed. Please check Docker DB setup.");
}

// -----------------------------
// Charset (important for PHP apps)
// -----------------------------
$mysqli->set_charset("utf8mb4");

?>

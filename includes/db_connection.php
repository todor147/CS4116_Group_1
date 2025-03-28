<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/../.env')) {
    $envVars = parse_ini_file(__DIR__ . '/../.env', false, INI_SCANNER_RAW);
    if ($envVars === false) {
        die("Error loading .env file");
    }
    foreach ($envVars as $key => $value) {
        $_ENV[$key] = trim($value);
    }
}

// Database connection settings from environment variables
$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? 'cs4116_marketplace';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$port = $_ENV['DB_PORT'] ?? '3306';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT           => 3,     // Connection timeout in seconds
    PDO::ATTR_PERSISTENT        => false  // Don't use persistent connections for remote database
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Test the connection
    $pdo->query('SELECT 1');
    
} catch (\PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage(), 3, __DIR__ . '/../logs/db_errors.log');
    throw new \PDOException("Database connection failed. Error: " . $e->getMessage(), (int)$e->getCode());
}

// Set timezone to match your MySQL server
date_default_timezone_set('Europe/Dublin');

// Set character set
$pdo->exec("SET NAMES utf8mb4");
$pdo->exec("SET CHARACTER SET utf8mb4");
$pdo->exec("SET collation_connection = utf8mb4_unicode_ci");
?> 
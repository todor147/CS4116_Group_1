<?php
// Enable error reporting for easier troubleshooting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Log connection attempts
error_log("Starting connection attempt to database on " . date('Y-m-d H:i:s'));

// Detect environment
$server_name = $_SERVER['SERVER_NAME'] ?? '';
$is_infinity_free = (strpos($server_name, 'infinityfree') !== false || 
                     strpos($server_name, 'educoach') !== false);

// Log environment info
error_log("Server name: " . $server_name);
error_log("Environment detected as: " . ($is_infinity_free ? "InfinityFree" : "Local"));

// Track which files have been included
$included_files = [];

// Include essential files
try {
    // Only include ONE database connection file
    $db_connected = false;
    
    // First try MySQLi connection if we're on InfinityFree
    if ($is_infinity_free && file_exists(__DIR__ . '/includes/db_connection_mysqli.php') && !$db_connected) {
        error_log("Attempting MySQLi connection...");
        require_once __DIR__ . '/includes/db_connection_mysqli.php';
        // If we get here, connection was successful
        error_log("Successfully connected using MySQLi");
        $db_connected = true;
    } 
    
    // Fall back to PDO connection if MySQLi failed or we're not on InfinityFree
    if (!$db_connected) {
        error_log("Attempting PDO connection...");
        require_once __DIR__ . '/includes/db_connection.php';
        // If we get here, connection was successful
        error_log("Successfully connected using PDO");
    }
    
    // Include auth_functions.php only once
    if (!isset($included_files['auth_functions'])) {
        require_once __DIR__ . '/includes/auth_functions.php';
        $included_files['auth_functions'] = true;
        error_log("Loaded auth_functions.php");
    }
    
    error_log("Database connection successful");
} catch (Exception $e) {
    error_log("Connection error: " . $e->getMessage());
    // Show a detailed error if there's a problem
    echo '<div style="text-align:center;margin-top:100px;font-family:Arial,sans-serif;">' .
        '<h1>Database Connection Problem</h1>' . 
        '<p>The website is currently unable to connect to the database. Please try again later.</p>' .
        '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>' .
        '<p>Environment: ' . ($is_infinity_free ? 'InfinityFree' : 'Local') . '</p>' .
        '<p>Server: ' . htmlspecialchars($server_name) . '</p>' .
        '</div>';
    exit;
}

// If testing mode is enabled via query parameter
if (isset($_GET['test_db']) && $_GET['test_db'] == '1') {
    echo '<h1>Database Connection Test</h1>';
    echo '<p>If you can see this message, the database connection is working!</p>';
    
    // Display some basic information
    if (isset($mysqli)) {
        echo '<p>Connected via MySQLi</p>';
        echo '<p>MySQL Server: ' . $mysqli->server_info . '</p>';
        echo '<p>Connection character set: ' . $mysqli->character_set_name() . '</p>';
    } else if (isset($pdo)) {
        echo '<p>Connected via PDO</p>';
        echo '<p>PDO Driver: ' . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . '</p>';
        echo '<p>Server Version: ' . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . '</p>';
    }
    
    echo '<p><a href="index.php">Continue to website</a></p>';
    exit;
}

// Redirect to home page
header('Location: pages/home.php');
exit;
?> 

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_errors.log');

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

// Detect environment - check if running on InfinityFree or localhost
$server_name = $_SERVER['SERVER_NAME'] ?? '';
$is_infinity_free = (strpos($server_name, 'infinityfree') !== false || 
                     strpos($server_name, 'educoach') !== false);

// Database connection settings
if ($is_infinity_free) {
    // InfinityFree hosting environment
    $host = 'sql106.infinityfree.com'; // Use the actual remote MySQL server
    $db_name = 'if0_38672207_cs4116_marketplace'; // Correct database name from phpMyAdmin
    $username = 'if0_38672207';
    $password = '7J3ce73nvOIXHMH'; 
    $charset = 'utf8mb4';
    $port = 3306; // Explicitly set the MySQL port
} else {
    // Local development environment
    $host = 'localhost'; 
    $db_name = 'cs4116_marketplace';
    $username = 'root';
    $password = '';
    $charset = 'utf8mb4';
    $port = $_ENV['DB_PORT'] ?? '3306';
}

// Build connection string (DSN)
$dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=$charset";

// Set PDO options - simpler is better
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

// Simple connection approach - no fancy fallbacks
try {
    // Create PDO instance
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Set timezone
    date_default_timezone_set('Europe/Dublin');
    
    // Set character set
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    
    // Only proceed with table creation if connection was successful
    if ($is_infinity_free) {
        // Create database tables if they don't exist
        try {
            // Create Inquiries table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS Inquiries (
                    inquiry_id INT AUTO_INCREMENT PRIMARY KEY,
                    learner_id INT NOT NULL,
                    coach_id INT NOT NULL,
                    message TEXT NOT NULL,
                    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (learner_id) REFERENCES Users(user_id),
                    FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id)
                )
            ");
            
            // Create CoachTimeSlots table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS CoachTimeSlots (
                    slot_id INT AUTO_INCREMENT PRIMARY KEY,
                    coach_id INT NOT NULL,
                    start_time DATETIME NOT NULL,
                    end_time DATETIME NOT NULL,
                    status ENUM('available', 'booked', 'unavailable') DEFAULT 'available',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id)
                )
            ");
        } catch (PDOException $e) {
            // Log table creation errors but don't die - tables might already exist
            error_log("Table creation warning: " . $e->getMessage());
        }
    }
    
} catch (PDOException $e) {
    // Clean error message
    $error_message = $e->getMessage();
    
    // Create user-friendly error page
    echo '<div style="background-color: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px; font-family: Arial, sans-serif;">';
    echo '<h2 style="color: #721c24;">Database Connection Error</h2>';
    echo '<p><strong>Server:</strong> ' . htmlspecialchars($host) . '</p>';
    echo '<p><strong>Database:</strong> ' . htmlspecialchars($db_name) . '</p>';
    echo '<p><strong>Username:</strong> ' . htmlspecialchars($username) . '</p>';
    echo '<p><strong>PDO Error:</strong> ' . htmlspecialchars($error_message) . '</p>';
    echo '<p><strong>Server Name:</strong> ' . htmlspecialchars($server_name) . '</p>';
    echo '<p><strong>PHP Version:</strong> ' . phpversion() . '</p>';
    
    echo '<h3>Possible Solutions:</h3>';
    echo '<ul>';
    echo '<li>Check if the database exists on the server</li>';
    echo '<li>Verify your database username and password</li>';
    echo '<li>Make sure your database host is correct (InfinityFree usually requires "localhost")</li>';
    echo '<li>Check if your hosting account has MySQL privileges</li>';
    echo '</ul>';
    echo '</div>';
    
    // Log error for debugging
    error_log("Database connection failed: " . $error_message);
    exit;
}
?> 
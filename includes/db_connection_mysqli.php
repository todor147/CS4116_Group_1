<?php
// Alternative db_connection.php using MySQLi instead of PDO
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../php_errors.log');

// Detect environment
$server_name = $_SERVER['SERVER_NAME'] ?? '';
$is_infinity_free = (strpos($server_name, 'infinityfree') !== false || 
                     strpos($server_name, 'educoach') !== false);

// Connection settings
if ($is_infinity_free) {
    $host = 'sql106.infinityfree.com';  // Use the actual remote MySQL server
    $db_name = 'if0_38672207_cs4116_marketplace'; // Correct database name from phpMyAdmin
    $username = 'if0_38672207';
    $password = '7J3ce73nvOIXHMH';
} else {
    $host = 'localhost';
    $db_name = 'cs4116_marketplace';
    $username = 'root';
    $password = '';
}

// Create connection using MySQLi
$mysqli = new mysqli($host, $username, $password, $db_name);

// Check connection
if ($mysqli->connect_errno) {
    // Create user-friendly error page
    echo '<div style="background-color: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px; font-family: Arial, sans-serif;">';
    echo '<h2 style="color: #721c24;">Database Connection Error</h2>';
    echo '<p><strong>Server:</strong> ' . htmlspecialchars($host) . '</p>';
    echo '<p><strong>Database:</strong> ' . htmlspecialchars($db_name) . '</p>';
    echo '<p><strong>Username:</strong> ' . htmlspecialchars($username) . '</p>';
    echo '<p><strong>MySQLi Error:</strong> ' . htmlspecialchars($mysqli->connect_error) . '</p>';
    echo '<p><strong>Server Name:</strong> ' . htmlspecialchars($server_name) . '</p>';
    echo '<p><strong>PHP Version:</strong> ' . phpversion() . '</p>';
    
    echo '<h3>Possible Solutions:</h3>';
    echo '<ul>';
    echo '<li>Check if the database exists on the server</li>';
    echo '<li>Verify your database username and password</li>';
    echo '<li>Make sure your hosting account has MySQL privileges</li>';
    echo '</ul>';
    echo '</div>';
    
    // Log error for debugging
    error_log("Database connection failed: " . $mysqli->connect_error);
    exit;
}

// Set UTF-8 character set
$mysqli->set_charset("utf8mb4");

// Set timezone
date_default_timezone_set('Europe/Dublin');

// Function to ensure compatibility with PDO code
function executeQuery($sql, $params = []) {
    global $mysqli;
    
    // If there are no parameters, just execute the query directly
    if (empty($params)) {
        $result = $mysqli->query($sql);
        if ($result === false) {
            throw new Exception("Query failed: " . $mysqli->error);
        }
        return $result;
    }
    
    // Prepare statement
    $stmt = $mysqli->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $mysqli->error);
    }
    
    // Determine parameter types and bind values
    $types = '';
    $bindParams = [];
    
    // First element will be the types string
    $bindParams[] = &$types;
    
    // Add the types and references to the values
    foreach ($params as &$param) {
        if (is_int($param)) {
            $types .= 'i';  // integer
        } elseif (is_float($param)) {
            $types .= 'd';  // double
        } elseif (is_string($param)) {
            $types .= 's';  // string
        } else {
            $types .= 'b';  // blob
        }
        $bindParams[] = &$param;
    }
    
    // Bind parameters if there are any
    if (!empty($types)) {
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
    }
    
    // Execute statement
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    // Get result for select queries
    $result = $stmt->get_result();
    
    // Close statement
    $stmt->close();
    
    return $result;
}

// Create a PDO-compatible fetch system
function fetchAll($result) {
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

function fetch($result) {
    return $result->fetch_assoc();
}

// Try to create tables if we're on InfinityFree
if ($is_infinity_free) {
    try {
        // Create Inquiries table
        $sql = "
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
        ";
        $mysqli->query($sql);
        
        // Create CoachTimeSlots table
        $sql = "
            CREATE TABLE IF NOT EXISTS CoachTimeSlots (
                slot_id INT AUTO_INCREMENT PRIMARY KEY,
                coach_id INT NOT NULL,
                start_time DATETIME NOT NULL,
                end_time DATETIME NOT NULL,
                status ENUM('available', 'booked', 'unavailable') DEFAULT 'available',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (coach_id) REFERENCES Coaches(coach_id)
            )
        ";
        $mysqli->query($sql);
    } catch (Exception $e) {
        // Log table creation errors but don't die
        error_log("Table creation warning: " . $e->getMessage());
    }
}
?> 
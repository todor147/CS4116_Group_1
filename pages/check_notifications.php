<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/notification_functions.php';

header('Content-Type: application/json');

// Return 0 if user is not logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['unread_count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];
$unread_count = 0;

try {
    $unread_count = getUnreadNotificationCount($pdo, $user_id);
} catch (Exception $e) {
    error_log("Error checking notifications: " . $e->getMessage());
}

echo json_encode(['unread_count' => $unread_count]); 
<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$other_user_id = isset($_GET['user']) ? (int)$_GET['user'] : 0;

if ($other_user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid user specified']);
    exit;
}

try {
    // Get new messages (only approved ones)
    $stmt = $pdo->prepare("
        SELECT m.*, u.username 
        FROM Messages m
        JOIN Users u ON u.user_id = m.sender_id
        WHERE ((m.sender_id = ? AND m.receiver_id = ?)
        OR (m.sender_id = ? AND m.receiver_id = ?))
        AND m.status = 'approved'
        AND m.is_read = 0
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$other_user_id, $user_id, $user_id, $other_user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark messages as read if the current user is the receiver
    if (!empty($messages)) {
        $stmt = $pdo->prepare("
            UPDATE Messages 
            SET is_read = 1 
            WHERE sender_id = ? AND receiver_id = ? AND status = 'approved' AND is_read = 0
        ");
        $stmt->execute([$other_user_id, $user_id]);
    }

    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (PDOException $e) {
    error_log("Database error in get_new_messages.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
exit;
?>

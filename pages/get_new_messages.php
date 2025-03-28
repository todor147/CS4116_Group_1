<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$other_user_id = $_GET['user'] ?? null;

if (!$other_user_id) {
    echo json_encode(['error' => 'No user specified']);
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

    // Mark messages as read
    if (!empty($messages)) {
        $stmt = $pdo->prepare("
            UPDATE Messages 
            SET is_read = 1 
            WHERE sender_id = ? AND receiver_id = ? AND status = 'approved'
        ");
        $stmt->execute([$other_user_id, $user_id]);
    }

    echo json_encode(['messages' => $messages]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']);
}
?>

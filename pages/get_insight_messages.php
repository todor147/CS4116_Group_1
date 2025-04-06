<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';

// Set the content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'messages' => []];

// Handle GET request for new messages
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;
    $last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
    
    if ($request_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid request ID']);
        exit;
    }
    
    try {
        // Check if user is part of this conversation
        $stmt = $pdo->prepare("
            SELECT * FROM CustomerInsightRequests
            WHERE id = ? AND (requester_id = ? OR verified_customer_id = ?) AND status = 'accepted'
        ");
        $stmt->execute([$request_id, $user_id, $user_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            echo json_encode(['success' => false, 'error' => 'You are not authorized to view these messages or the request is not active']);
            exit;
        }
        
        // Get new messages since last_id
        $params = [$request_id];
        $sql = "
            SELECT 
                cim.*,
                sender.first_name as sender_first_name,
                sender.last_name as sender_last_name,
                recipient.first_name as recipient_first_name,
                recipient.last_name as recipient_last_name
            FROM CustomerInsightMessages cim
            JOIN users sender ON cim.sender_id = sender.id
            JOIN users recipient ON cim.recipient_id = recipient.id
            WHERE cim.request_id = ?
        ";
        
        if ($last_id > 0) {
            $sql .= " AND cim.id > ?";
            $params[] = $last_id;
        }
        
        $sql .= " ORDER BY cim.created_at ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mark received messages as read
        $stmt = $pdo->prepare("
            UPDATE CustomerInsightMessages
            SET is_read = 1
            WHERE request_id = ? AND recipient_id = ? AND is_read = 0
        ");
        $stmt->execute([$request_id, $user_id]);
        
        // Format messages for response
        $formatted_messages = [];
        foreach ($messages as $msg) {
            $formatted_messages[] = [
                'id' => $msg['id'],
                'sender_id' => $msg['sender_id'],
                'recipient_id' => $msg['recipient_id'],
                'message' => $msg['message'],
                'is_read' => (bool)$msg['is_read'],
                'created_at' => $msg['created_at'],
                'sender_name' => $msg['sender_first_name'] . ' ' . $msg['sender_last_name'],
                'recipient_name' => $msg['recipient_first_name'] . ' ' . $msg['recipient_last_name'],
                'is_self' => ($msg['sender_id'] == $user_id)
            ];
        }
        
        $response = [
            'success' => true,
            'messages' => $formatted_messages,
            'last_id' => !empty($messages) ? end($messages)['id'] : $last_id
        ];
    } catch (PDOException $e) {
        $response = ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

echo json_encode($response);
?> 
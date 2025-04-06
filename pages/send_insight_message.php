<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and validate request parameters
    $request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
    $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    
    if (!$request_id) {
        $response['message'] = 'Missing request ID';
    } elseif (!$receiver_id) {
        $response['message'] = 'Missing receiver ID';
    } elseif (empty($content)) {
        $response['message'] = 'Message cannot be empty';
    } else {
        try {
            // Verify permission to send message
            $stmt = $pdo->prepare("
                SELECT * FROM CustomerInsightRequests 
                WHERE request_id = ? 
                AND status = 'accepted'
                AND (requester_id = ? OR verified_customer_id = ?)
            ");
            $stmt->execute([$request_id, $user_id, $user_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                $response['message'] = 'You do not have permission to send messages for this request';
            } else {
                // Verify receiver is the other party in this request
                $other_user_id = ($user_id == $request['requester_id']) ? $request['verified_customer_id'] : $request['requester_id'];
                
                if ($receiver_id != $other_user_id) {
                    $response['message'] = 'Invalid receiver';
                } else {
                    // Insert the message
                    $stmt = $pdo->prepare("
                        INSERT INTO CustomerInsightMessages 
                        (request_id, sender_id, receiver_id, content, created_at, is_read)
                        VALUES (?, ?, ?, ?, NOW(), 0)
                    ");
                    $stmt->execute([$request_id, $user_id, $receiver_id, $content]);
                    
                    // Get the inserted message with additional info
                    $message_id = $pdo->lastInsertId();
                    $stmt = $pdo->prepare("
                        SELECT cim.*, u.username
                        FROM CustomerInsightMessages cim
                        JOIN Users u ON cim.sender_id = u.user_id
                        WHERE cim.message_id = ?
                    ");
                    $stmt->execute([$message_id]);
                    $message = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $response = [
                        'success' => true,
                        'message' => 'Message sent successfully',
                        'message' => $message
                    ];
                }
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }
}

echo json_encode($response);
?> 
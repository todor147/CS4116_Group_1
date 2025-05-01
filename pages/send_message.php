<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';
require __DIR__ . '/../includes/notification_functions.php';

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Check if the request is AJAX - Accept any capitalization of the header
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

// Set content type to JSON for all responses
header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => '',
    'needs_moderation' => false
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';

    // Validate inputs
    if ($receiver_id <= 0) {
        $response['message'] = "Invalid receiver.";
        echo json_encode($response);
        exit;
    }

    if (empty($content)) {
        $response['message'] = "Message cannot be empty.";
        echo json_encode($response);
        exit;
    }

    try {
        // Fetch banned words
        $stmt = $pdo->prepare("SELECT word FROM BannedWords");
        $stmt->execute();
        $bannedWords = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Function to check for banned words
        function containsBannedWords($text, $bannedWords) {
            foreach ($bannedWords as $word) {
                if (stripos($text, $word) !== false) {
                    return true;
                }
            }
            return false;
        }

        // Check if message contains banned words
        $needs_moderation = containsBannedWords($content, $bannedWords);
        $status = $needs_moderation ? 'pending' : 'approved';

        // Insert message with appropriate status
        $stmt = $pdo->prepare("
            INSERT INTO Messages (sender_id, receiver_id, content, status, created_at, is_read)
            VALUES (?, ?, ?, ?, NOW(), 0)
        ");
        $stmt->execute([$sender_id, $receiver_id, $content, $status]);
        
        $response['success'] = true;
        
        if ($needs_moderation) {
            $response['needs_moderation'] = true;
            $response['message'] = "Your message contains flagged content and will be reviewed by a moderator.";
        } else {
            $response['message'] = "Message sent successfully.";
            
            // Include the message_id for immediate display
            $message_id = $pdo->lastInsertId();
            $response['message_id'] = $message_id;
            
            // Get sender username for the notification
            $stmt = $pdo->prepare("SELECT username FROM Users WHERE user_id = ?");
            $stmt->execute([$sender_id]);
            $sender = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Create notification for the receiver
            if ($sender) {
                notifyNewMessage($pdo, $receiver_id, $sender_id, $sender['username'], $sender_id);
            }
        }
    } catch (PDOException $e) {
        error_log("Database error sending message: " . $e->getMessage());
        $response['message'] = "Error sending message. Please try again.";
    }
}

// Always return JSON
echo json_encode($response);
exit;
?>

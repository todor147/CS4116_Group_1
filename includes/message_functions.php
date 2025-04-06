<?php
/**
 * Message helper functions for the EduCoach Platform
 */

/**
 * Get the number of unread messages for a user
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id The user ID to check for
 * @return int The number of unread messages
 */
function getUnreadMessageCount($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM Messages 
            WHERE receiver_id = ? 
            AND is_read = 0
            AND status = 'approved'
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) {
        error_log("Error getting unread message count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get conversations for a user
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id The user ID to get conversations for
 * @return array List of conversations with unread counts
 */
function getUserConversations($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT 
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id
                    ELSE m.sender_id
                END as other_user_id,
                u.username,
                u.profile_image,
                (SELECT COUNT(*) FROM Messages 
                 WHERE receiver_id = ? 
                 AND sender_id = other_user_id 
                 AND is_read = 0
                 AND status = 'approved') as unread_count,
                (SELECT content FROM Messages 
                 WHERE (sender_id = ? AND receiver_id = other_user_id) 
                 OR (sender_id = other_user_id AND receiver_id = ?)
                 AND status = 'approved'
                 ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM Messages 
                 WHERE (sender_id = ? AND receiver_id = other_user_id) 
                 OR (sender_id = other_user_id AND receiver_id = ?)
                 AND status = 'approved'
                 ORDER BY created_at DESC LIMIT 1) as last_message_time
            FROM Messages m
            JOIN Users u ON u.user_id = 
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id
                    ELSE m.sender_id
                END
            WHERE (m.sender_id = ? OR m.receiver_id = ?)
            AND m.status = 'approved'
            ORDER BY last_message_time DESC
        ");
        $stmt->execute([
            $user_id, $user_id, $user_id, $user_id, 
            $user_id, $user_id, $user_id, $user_id, $user_id
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user conversations: " . $e->getMessage());
        return [];
    }
}

/**
 * Get messages between two users
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id The current user ID
 * @param int $other_user_id The other user ID
 * @return array List of messages between the users
 */
function getConversationMessages($pdo, $user_id, $other_user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT m.*, u.username 
            FROM Messages m
            JOIN Users u ON u.user_id = m.sender_id
            WHERE ((m.sender_id = ? AND m.receiver_id = ?)
            OR (m.sender_id = ? AND m.receiver_id = ?))
            AND m.status = 'approved'
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting conversation messages: " . $e->getMessage());
        return [];
    }
}

/**
 * Mark messages as read
 * 
 * @param PDO $pdo Database connection
 * @param int $receiver_id The receiver user ID
 * @param int $sender_id The sender user ID
 * @return bool True if successful, false otherwise
 */
function markMessagesAsRead($pdo, $receiver_id, $sender_id) {
    try {
        $stmt = $pdo->prepare("
            UPDATE Messages 
            SET is_read = 1 
            WHERE sender_id = ? AND receiver_id = ? AND status = 'approved'
        ");
        $stmt->execute([$sender_id, $receiver_id]);
        return true;
    } catch (PDOException $e) {
        error_log("Error marking messages as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Send a message between users
 * 
 * @param PDO $pdo Database connection
 * @param int $sender_id The sender user ID
 * @param int $receiver_id The receiver user ID
 * @param string $content The message content
 * @param int|null $inquiry_id Optional inquiry ID if related to an inquiry
 * @return array Response with success status and any messages
 */
function sendMessage($pdo, $sender_id, $receiver_id, $content, $inquiry_id = null) {
    $response = [
        'success' => false,
        'message' => '',
        'needs_moderation' => false
    ];

    try {
        // Fetch banned words
        $stmt = $pdo->prepare("SELECT word FROM BannedWords");
        $stmt->execute();
        $bannedWords = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Check if message contains banned words
        $needs_moderation = false;
        foreach ($bannedWords as $word) {
            if (stripos($content, $word) !== false) {
                $needs_moderation = true;
                break;
            }
        }
        
        $status = $needs_moderation ? 'pending' : 'approved';

        // Insert message with appropriate status
        $stmt = $pdo->prepare("
            INSERT INTO Messages (sender_id, receiver_id, inquiry_id, content, status, created_at, is_read)
            VALUES (?, ?, ?, ?, ?, NOW(), 0)
        ");
        $stmt->execute([$sender_id, $receiver_id, $inquiry_id, $content, $status]);
        
        $response['success'] = true;
        
        if ($needs_moderation) {
            $response['needs_moderation'] = true;
            $response['message'] = "Your message contains flagged content and will be reviewed by a moderator.";
        } else {
            $response['message'] = "Message sent successfully.";
        }
    } catch (PDOException $e) {
        $response['message'] = "Error sending message: " . $e->getMessage();
    }

    return $response;
} 
<?php
/**
 * Notification utility functions for EduCoach
 */

/**
 * Get the count of unread notifications for a user
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @return int Count of unread notifications
 */
function getUnreadNotificationCount($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM Notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return isset($result['count']) ? (int)$result['count'] : 0;
    } catch (PDOException $e) {
        // Log the error but don't disrupt the page
        error_log("Error getting notification count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Create a new notification for a user
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $link Optional link for more details
 * @param string $notification_type Optional notification type
 * @return bool Success status
 */
function createNotification($pdo, $user_id, $title, $message, $link = '', $notification_type = 'general') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO Notifications
            (user_id, title, message, link, notification_type, created_at, is_read)
            VALUES (?, ?, ?, ?, ?, NOW(), 0)
        ");
        return $stmt->execute([$user_id, $title, $message, $link, $notification_type]);
    } catch (PDOException $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark a notification as read
 * 
 * @param PDO $pdo Database connection
 * @param int $notification_id Notification ID
 * @param int $user_id User ID for security check
 * @return bool Success status
 */
function markNotificationAsRead($pdo, $notification_id, $user_id) {
    try {
        $stmt = $pdo->prepare("
            UPDATE Notifications 
            SET is_read = 1 
            WHERE notification_id = ? AND user_id = ?
        ");
        return $stmt->execute([$notification_id, $user_id]);
    } catch (PDOException $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Notify a user about an inquiry status change
 * 
 * @param PDO $pdo Database connection
 * @param int $inquiry_id Inquiry ID
 * @param string $status New status
 * @return bool Success status
 */
function notifyInquiryStatusChange($pdo, $inquiry_id, $status) {
    try {
        // Get inquiry details
        $stmt = $pdo->prepare("
            SELECT si.*, 
                   u.username as student_name,
                   c.name as coach_name,
                   c.user_id as coach_user_id
            FROM ServiceInquiries si
            JOIN Users u ON si.user_id = u.user_id
            JOIN Coaches c ON si.coach_id = c.coach_id
            WHERE si.inquiry_id = ?
        ");
        $stmt->execute([$inquiry_id]);
        $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$inquiry) {
            return false;
        }
        
        // Format notification details based on status
        switch ($status) {
            case 'accepted':
                // Notify student
                $title = "Your inquiry has been accepted";
                $message = "Coach {$inquiry['coach_name']} has accepted your inquiry";
                $link = "inquiry-detail.php?id={$inquiry_id}";
                createNotification($pdo, $inquiry['user_id'], $title, $message, $link, 'inquiry');
                break;
                
            case 'rejected':
                // Notify student
                $title = "Your inquiry has been declined";
                $message = "Coach {$inquiry['coach_name']} is unable to accept your inquiry at this time";
                $link = "inquiry-detail.php?id={$inquiry_id}";
                createNotification($pdo, $inquiry['user_id'], $title, $message, $link, 'inquiry');
                break;
                
            case 'cancelled':
                // Notify coach
                $title = "Inquiry cancelled";
                $message = "{$inquiry['student_name']} has cancelled their inquiry";
                $link = "coach-inquiries.php";
                createNotification($pdo, $inquiry['coach_user_id'], $title, $message, $link, 'inquiry');
                break;
                
            case 'completed':
                // Notify student
                $title = "Inquiry marked as completed";
                $message = "Your inquiry with {$inquiry['coach_name']} has been marked as completed";
                $link = "inquiry-detail.php?id={$inquiry_id}";
                createNotification($pdo, $inquiry['user_id'], $title, $message, $link, 'inquiry');
                break;
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error creating inquiry notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Notify a user about a new message
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID to notify
 * @param int $from_user_id User ID who sent the message
 * @param string $from_username Username who sent the message
 * @param int $conversation_id Conversation ID
 * @return bool Success status
 */
function notifyNewMessage($pdo, $user_id, $from_user_id, $from_username, $conversation_id) {
    $title = "New message";
    $message = "You have received a new message from {$from_username}";
    $link = "messages.php?conversation={$conversation_id}";
    
    return createNotification($pdo, $user_id, $title, $message, $link, 'message');
}

/**
 * Notify a user about a session update
 * 
 * @param PDO $pdo Database connection
 * @param int $session_id Session ID
 * @param string $action Action type (scheduled, rescheduled, cancelled)
 * @param int $notify_user_id User ID to notify
 * @return bool Success status
 */
function notifySessionUpdate($pdo, $session_id, $action, $notify_user_id) {
    try {
        // Get session details
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   c.name as coach_name,
                   u.username as student_name
            FROM Sessions s
            JOIN Coaches c ON s.coach_id = c.coach_id
            JOIN Users u ON s.user_id = u.user_id
            WHERE s.session_id = ?
        ");
        $stmt->execute([$session_id]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$session) {
            return false;
        }
        
        $session_date = date('l, F j, Y', strtotime($session['session_date']));
        $session_time = date('g:i A', strtotime($session['start_time']));
        
        // Format notification based on action
        switch ($action) {
            case 'scheduled':
                $title = "New Session Scheduled";
                $message = "Session with " . 
                           ($notify_user_id == $session['user_id'] ? $session['coach_name'] : $session['student_name']) . 
                           " scheduled for {$session_date} at {$session_time}";
                break;
                
            case 'rescheduled':
                $title = "Session Rescheduled";
                $message = "Session with " . 
                           ($notify_user_id == $session['user_id'] ? $session['coach_name'] : $session['student_name']) . 
                           " rescheduled for {$session_date} at {$session_time}";
                break;
                
            case 'cancelled':
                $title = "Session Cancelled";
                $message = "Session with " . 
                           ($notify_user_id == $session['user_id'] ? $session['coach_name'] : $session['student_name']) . 
                           " on {$session_date} at {$session_time} has been cancelled";
                break;
                
            default:
                $title = "Session Update";
                $message = "Your session details have been updated";
                break;
        }
        
        $link = "session-detail.php?id={$session_id}";
        
        return createNotification($pdo, $notify_user_id, $title, $message, $link, 'session');
    } catch (PDOException $e) {
        error_log("Error creating session notification: " . $e->getMessage());
        return false;
    }
}
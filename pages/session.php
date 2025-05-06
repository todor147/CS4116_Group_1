<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/notification_functions.php';  // Add notification functions

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login with return URL
    $returnUrl = urlencode($_SERVER['REQUEST_URI']);
    header('Location: login.php?redirect=' . $returnUrl);
    exit;
}

// Get user type and ID
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT user_type FROM Users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$is_coach = ($user['user_type'] === 'business');

// Get coach_id if user is a coach
$coach_id = null;
if ($is_coach) {
    $stmt = $pdo->prepare("SELECT coach_id FROM Coaches WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $coach = $stmt->fetch();
    if ($coach) {
        $coach_id = $coach['coach_id'];
    }
}

// Get coach_id and tier_id from URL parameters
$selected_coach_id = isset($_GET['coach_id']) ? (int)$_GET['coach_id'] : null;
$selected_tier_id = isset($_GET['tier_id']) ? (int)$_GET['tier_id'] : null;

// Handle session status updates via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($_POST['action']) {
            case 'update_status':
                if (!isset($_POST['session_id'], $_POST['status'])) {
                    throw new Exception('Missing required parameters');
                }

                try {
                    // First verify the session exists and get its details
                    $stmt = $pdo->prepare("
                        SELECT s.*, c.user_id as coach_user_id, u.username as learner_name, u2.username as coach_name
                        FROM Sessions s 
                        JOIN Coaches c ON s.coach_id = c.coach_id
                        JOIN Users u ON s.learner_id = u.user_id
                        JOIN Users u2 ON c.user_id = u2.user_id
                        WHERE s.session_id = ?
                    ");
                    if (!$stmt->execute([$_POST['session_id']])) {
                        throw new Exception('Failed to fetch session details');
                    }
                    $session = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$session) {
                        throw new Exception('Session not found');
                    }
                    
                    // Convert IDs to integers for comparison
                    $learner_id = (int)$session['learner_id'];
                    $coach_user_id = (int)$session['coach_user_id'];
                    $current_user_id = (int)$user_id;
                    
                    // Check if user has permission (either learner or coach)
                    if ($learner_id !== $current_user_id && $coach_user_id !== $current_user_id) {
                        throw new Exception(
                            'Permission denied. You must be either the learner or coach for this session.'
                        );
                    }

                    // For completion, check if the session time has passed
                    if ($_POST['status'] === 'completed') {
                        $scheduled_time = new DateTime($session['scheduled_time']);
                        $now = new DateTime();
                        
                        if ($scheduled_time > $now) {
                            throw new Exception('Sessions can only be marked as completed after their scheduled time');
                        }
                    }
                    
                    // Update session status
                    $stmt = $pdo->prepare("UPDATE Sessions SET status = ? WHERE session_id = ?");
                    if (!$stmt->execute([$_POST['status'], $_POST['session_id']])) {
                        throw new Exception('Failed to update session status');
                    }

                    // If status is completed, free up the corresponding time slot
                    if ($_POST['status'] === 'completed' || $_POST['status'] === 'cancelled') {
                        // Find and update the time slot if it exists
                        $stmt = $pdo->prepare("
                            UPDATE CoachTimeSlots
                            SET status = 'available'
                            WHERE coach_id = ? AND start_time = ? AND status = 'booked'
                        ");
                        $stmt->execute([$session['coach_id'], $session['scheduled_time']]);
                    }
                    
                    // If completing session and rating provided, save the rating
                    if ($_POST['status'] === 'completed' && isset($_POST['rating'])) {
                        // Check if a review already exists for this session
                        $stmt = $pdo->prepare("SELECT review_id FROM Reviews WHERE session_id = ?");
                        if (!$stmt->execute([$_POST['session_id']])) {
                            throw new Exception('Failed to check for existing review');
                        }
                        $existingReview = $stmt->fetch();
                        
                        if ($existingReview) {
                            // Update existing review
                            $stmt = $pdo->prepare("
                                UPDATE Reviews 
                                SET rating = ?, comment = ?, created_at = NOW() 
                                WHERE session_id = ?
                            ");
                            if (!$stmt->execute([
                                $_POST['rating'],
                                $_POST['feedback'] ?? null,
                                $_POST['session_id']
                            ])) {
                                throw new Exception('Failed to update review');
                            }
                        } else {
                            // Insert new review
                            $stmt = $pdo->prepare("
                                INSERT INTO Reviews (session_id, user_id, coach_id, rating, comment, created_at) 
                                VALUES (?, ?, ?, ?, ?, NOW())
                            ");
                            if (!$stmt->execute([
                                $_POST['session_id'],
                                $user_id,
                                $session['coach_id'],
                                $_POST['rating'],
                                $_POST['feedback'] ?? null
                            ])) {
                                throw new Exception('Failed to insert review');
                            }
                        }
                    }
                    
                    $response['success'] = true;
                    $response['message'] = 'Session status updated successfully';
                } catch (Exception $e) {
                    $response['success'] = false;
                    $response['message'] = $e->getMessage();
                    error_log('Error updating session status: ' . $e->getMessage());
                }
                break;

            case 'request_reschedule':
                if (!isset($_POST['session_id'], $_POST['new_time'], $_POST['reason'])) {
                    throw new Exception('Missing required parameters');
                }

                try {
                    // Verify session exists and get details
                    $stmt = $pdo->prepare("
                        SELECT s.*, c.user_id as coach_user_id, u.username as learner_name, u2.username as coach_name,
                               u.email as learner_email, u2.email as coach_email
                        FROM Sessions s 
                        JOIN Coaches c ON s.coach_id = c.coach_id
                        JOIN Users u ON s.learner_id = u.user_id
                        JOIN Users u2 ON c.user_id = u2.user_id
                        WHERE s.session_id = ?
                    ");
                    if (!$stmt->execute([$_POST['session_id']])) {
                        throw new Exception('Failed to fetch session details');
                    }
                    $session = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$session) {
                        throw new Exception('Session not found');
                    }

                    // Check if session is already completed or cancelled
                    if ($session['status'] === 'completed' || $session['status'] === 'cancelled') {
                        throw new Exception('Cannot reschedule a completed or cancelled session');
                    }
                    
                    // Convert IDs to integers for comparison
                    $learner_id = (int)$session['learner_id'];
                    $coach_user_id = (int)$session['coach_user_id'];
                    $current_user_id = (int)$user_id;
                    
                    // Check if user has permission (either learner or coach)
                    if ($learner_id !== $current_user_id && $coach_user_id !== $current_user_id) {
                        throw new Exception(
                            'Permission denied. You must be either the learner or coach for this session.'
                        );
                    }

                    // Determine requester role and recipient
                    $is_learner_request = ($current_user_id === $learner_id);
                    $recipient_id = $is_learner_request ? $coach_user_id : $learner_id;
                    
                    // Check if the requested time is valid
                    $new_time = new DateTime($_POST['new_time']);
                    $now = new DateTime();
                    if ($new_time <= $now) {
                        throw new Exception('The requested time must be in the future');
                    }
                    
                    // Check if a reschedule request already exists for this session
                    $stmt = $pdo->prepare("
                        SELECT * FROM RescheduleRequests
                        WHERE session_id = ? AND status = 'pending'
                    ");
                    $stmt->execute([$_POST['session_id']]);
                    $existing_request = $stmt->fetch();
                    
                    if ($existing_request) {
                        throw new Exception('A rescheduling request is already pending for this session');
                    }
                    
                    // Check if time slot is available
                    $new_time_formatted = $new_time->format('Y-m-d H:i:s');
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as slot_count
                        FROM CoachTimeSlots
                        WHERE coach_id = ? 
                        AND start_time = ?
                        AND status = 'available'
                    ");
                    $stmt->execute([$session['coach_id'], $new_time_formatted]);
                    $slot_available = ($stmt->fetch()['slot_count'] > 0);
                    
                    // Insert the reschedule request
                    $pdo->beginTransaction();
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO RescheduleRequests
                        (session_id, requester_id, new_time, reason, status, created_at)
                        VALUES (?, ?, ?, ?, 'pending', NOW())
                    ");
                    $stmt->execute([
                        $_POST['session_id'],
                        $current_user_id,
                        $new_time_formatted,
                        $_POST['reason']
                    ]);
                    
                    $request_id = $pdo->lastInsertId();
                    
                    // Create a notification for the other party
                    $notification_text = sprintf(
                        "%s has requested to reschedule your session on %s to %s.",
                        $is_learner_request ? $session['learner_name'] : $session['coach_name'],
                        date('M j, Y g:i A', strtotime($session['scheduled_time'])),
                        date('M j, Y g:i A', strtotime($new_time_formatted))
                    );
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO Notifications
                        (user_id, title, message, link, is_read, created_at)
                        VALUES (?, 'Session Reschedule Request', ?, ?, 0, NOW())
                    ");
                    $stmt->execute([
                        $recipient_id,
                        $notification_text,
                        "/pages/view-session.php?id={$_POST['session_id']}"
                    ]);
                    
                    $pdo->commit();
                    
                    $response['success'] = true;
                    $response['message'] = 'Reschedule request submitted successfully';
                    $response['slot_available'] = $slot_available;
                    
                    if (!$slot_available) {
                        $response['message'] .= ' Note: The requested time is not currently available in the coach\'s schedule. The coach will need to create this time slot if they approve.';
                    }
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $response['success'] = false;
                    $response['message'] = $e->getMessage();
                    error_log('Error requesting reschedule: ' . $e->getMessage());
                }
                break;
                
            case 'respond_reschedule':
                if (!isset($_POST['request_id'], $_POST['response'])) {
                    throw new Exception('Missing required parameters');
                }

                try {
                    // Start transaction
                    $pdo->beginTransaction();
                    
                    // Get the reschedule request
                    $stmt = $pdo->prepare("
                        SELECT r.*, s.coach_id, s.learner_id, s.scheduled_time as original_time,
                               c.user_id as coach_user_id,
                               u1.username as requester_name,
                               u2.username as recipient_name
                        FROM RescheduleRequests r
                        JOIN Sessions s ON r.session_id = s.session_id
                        JOIN Coaches c ON s.coach_id = c.coach_id
                        JOIN Users u1 ON r.requester_id = u1.user_id
                        JOIN Users u2 ON (
                            CASE WHEN r.requester_id = s.learner_id THEN c.user_id
                                 ELSE s.learner_id
                            END
                        ) = u2.user_id
                        WHERE r.request_id = ?
                    ");
                    $stmt->execute([$_POST['request_id']]);
                    $request = $stmt->fetch();
                    
                    if (!$request) {
                        throw new Exception('Reschedule request not found');
                    }
                    
                    // Check if the request is still pending
                    if ($request['status'] !== 'pending') {
                        throw new Exception('This request has already been processed');
                    }
                    
                    // Check if current user is authorized to respond
                    $recipient_id = ($request['requester_id'] == $request['learner_id'])
                        ? $request['coach_user_id']
                        : $request['learner_id'];
                        
                    if ($user_id != $recipient_id) {
                        throw new Exception('You are not authorized to respond to this request');
                    }
                    
                    // Update the request status
                    $new_status = ($_POST['response'] === 'approve') ? 'approved' : 'rejected';
                    $stmt = $pdo->prepare("
                        UPDATE RescheduleRequests
                        SET status = ?, responded_at = NOW()
                        WHERE request_id = ?
                    ");
                    $stmt->execute([$new_status, $_POST['request_id']]);
                    
                    // If approved, update the session
                    if ($new_status === 'approved') {
                        // Handle the time slot - free up the old slot
                        $stmt = $pdo->prepare("
                            UPDATE CoachTimeSlots
                            SET status = 'available'
                            WHERE coach_id = ? AND start_time = ? AND status = 'booked'
                        ");
                        $stmt->execute([$request['coach_id'], $request['original_time']]);
                        
                        // Check if the new slot exists and is available
                        $stmt = $pdo->prepare("
                            SELECT slot_id FROM CoachTimeSlots
                            WHERE coach_id = ? AND start_time = ? AND status = 'available'
                            LIMIT 1
                        ");
                        $stmt->execute([$request['coach_id'], $request['new_time']]);
                        $new_slot = $stmt->fetch();
                        
                        // If a slot exists, mark it as booked
                        if ($new_slot) {
                            $stmt = $pdo->prepare("
                                UPDATE CoachTimeSlots
                                SET status = 'booked'
                                WHERE slot_id = ?
                            ");
                            $stmt->execute([$new_slot['slot_id']]);
                        } else {
                            // If no slot exists, create one and mark it as booked
                            // Calculate end time (1 hour later by default)
                            $start_time = new DateTime($request['new_time']);
                            $end_time = clone $start_time;
                            $end_time->modify('+1 hour');
                            
                            $stmt = $pdo->prepare("
                                INSERT INTO CoachTimeSlots 
                                (coach_id, start_time, end_time, status)
                                VALUES (?, ?, ?, 'booked')
                            ");
                            $stmt->execute([
                                $request['coach_id'],
                                $start_time->format('Y-m-d H:i:s'),
                                $end_time->format('Y-m-d H:i:s')
                            ]);
                        }
                        
                        // Update the session scheduled time
                        $stmt = $pdo->prepare("
                            UPDATE Sessions
                            SET scheduled_time = ?, last_updated = NOW()
                            WHERE session_id = ?
                        ");
                        $stmt->execute([$request['new_time'], $request['session_id']]);
                    }
                    
                    // Create notification for the requester
                    $notification_text = sprintf(
                        "%s has %s your request to reschedule the session to %s.",
                        $request['recipient_name'],
                        ($new_status === 'approved') ? 'approved' : 'declined',
                        date('M j, Y g:i A', strtotime($request['new_time']))
                    );
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO Notifications
                        (user_id, title, message, link, is_read, created_at)
                        VALUES (?, ?, ?, ?, 0, NOW())
                    ");
                    $stmt->execute([
                        $request['requester_id'],
                        'Reschedule Request ' . ucfirst($new_status),
                        $notification_text,
                        "/pages/view-session.php?id={$request['session_id']}"
                    ]);
                    
                    $pdo->commit();
                    
                    $response['success'] = true;
                    $response['message'] = 'Reschedule request ' . ($new_status === 'approved' ? 'approved' : 'rejected') . ' successfully';
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $response['success'] = false;
                    $response['message'] = $e->getMessage();
                    error_log('Error responding to reschedule request: ' . $e->getMessage());
                }
                break;
                
            case 'schedule_session':
                if (isset($_POST['coach_id'], $_POST['scheduled_time'])) {
                    try {
                        // Add validation
                        if (empty($_POST['coach_id']) || empty($_POST['scheduled_time'])) {
                            throw new Exception('All fields are required');
                        }
                        
                        // Check if scheduled time is in the future
                        $scheduled_time = new DateTime($_POST['scheduled_time']);
                        $now = new DateTime();
                        if ($scheduled_time <= $now) {
                            throw new Exception('Scheduled time must be in the future');
                        }
                        
                        // Verify coach exists
                        $stmt = $pdo->prepare("SELECT coach_id FROM Coaches WHERE coach_id = ?");
                        $stmt->execute([$_POST['coach_id']]);
                        if (!$stmt->fetch()) {
                            throw new Exception('Invalid coach selected');
                        }
                        
                        // Check for existing sessions within 1 hour
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) as session_count 
                            FROM Sessions 
                            WHERE coach_id = ? 
                            AND scheduled_time BETWEEN DATE_SUB(?, INTERVAL 1 HOUR) AND DATE_ADD(?, INTERVAL 1 HOUR)
                        ");
                        $stmt->execute([
                            $_POST['coach_id'],
                            $_POST['scheduled_time'],
                            $_POST['scheduled_time']
                        ]);
                        $session_count = $stmt->fetch()['session_count'];

                        if ($session_count > 0) {
                            throw new Exception('Cannot schedule session within 1 hour of another session with this coach');
                        }
                        
                        // Start transaction
                        $pdo->beginTransaction();
                        
                        // Create service inquiry first
                        $stmt = $pdo->prepare("INSERT INTO ServiceInquiries (user_id, coach_id, tier_id, status) VALUES (?, ?, ?, 'pending')");
                        if (!$stmt->execute([$user_id, $_POST['coach_id'], $_POST['tier_id']])) {
                            throw new Exception('Failed to create service inquiry');
                        }
                        $inquiry_id = $pdo->lastInsertId();
                        
                        // Debug log
                        error_log("Creating session for inquiry ID: $inquiry_id, user ID: $user_id, coach ID: {$_POST['coach_id']}, time: {$_POST['scheduled_time']}");
                        
                        // Create the session
                        $stmt = $pdo->prepare("INSERT INTO Sessions (inquiry_id, learner_id, coach_id, tier_id, scheduled_time, status) VALUES (?, ?, ?, ?, ?, 'scheduled')");
                        if (!$stmt->execute([$inquiry_id, $user_id, $_POST['coach_id'], $_POST['tier_id'], $_POST['scheduled_time']])) {
                            throw new Exception('Failed to create session');
                        }
                        
                        // Commit transaction
                        $pdo->commit();
                        
                        $response['success'] = true;
                        $response['message'] = 'Session scheduled successfully';
                    } catch (PDOException $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        error_log('PDO Error scheduling session: ' . $e->getMessage());
                        error_log('SQL State: ' . $e->errorInfo[0]);
                        error_log('Driver Error Code: ' . $e->errorInfo[1]);
                        error_log('Driver Error Message: ' . $e->errorInfo[2]);
                        $response['message'] = 'Database error scheduling session. Please try again.';
                    } catch (Exception $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        error_log('Error scheduling session: ' . $e->getMessage());
                        $response['message'] = $e->getMessage();
                    }
                } else {
                    $response['message'] = 'Missing required parameters';
                }
                break;
                
            case 'submit_inquiry':
                try {
                    // Validate input
                    if (empty($_POST['coach_id']) || empty($_POST['message'])) {
                        throw new Exception('All fields are required');
                    }
                    
                    // Insert inquiry
                    $stmt = $pdo->prepare("
                        INSERT INTO ServiceInquiries 
                        (user_id, coach_id, message, status) 
                        VALUES (?, ?, ?, 'pending')
                    ");
                    if (!$stmt->execute([$user_id, $_POST['coach_id'], $_POST['message']])) {
                        throw new Exception('Failed to submit inquiry');
                    }
                    
                    $response['success'] = true;
                    $response['message'] = 'Inquiry submitted successfully';
                } catch (Exception $e) {
                    $response['message'] = $e->getMessage();
                }
                break;
                
            case 'convert_inquiry':
                try {
                    // Validate input
                    if (empty($_POST['inquiry_id']) || empty($_POST['scheduled_time'])) {
                        throw new Exception('All fields are required');
                    }
                    
                    // Get inquiry details
                    $stmt = $pdo->prepare("SELECT * FROM ServiceInquiries WHERE inquiry_id = ?");
                    $stmt->execute([$_POST['inquiry_id']]);
                    $inquiry = $stmt->fetch();
                    
                    if (!$inquiry) {
                        throw new Exception('Inquiry not found');
                    }
                    
                    // Create session
                    $stmt = $pdo->prepare("
                        INSERT INTO Sessions 
                        (inquiry_id, learner_id, coach_id, scheduled_time, status) 
                        VALUES (?, ?, ?, ?, 'scheduled')
                    ");
                    if (!$stmt->execute([
                        $_POST['inquiry_id'],
                        $inquiry['user_id'],
                        $inquiry['coach_id'],
                        $_POST['scheduled_time']
                    ])) {
                        throw new Exception('Failed to create session');
                    }
                    
                    // Update inquiry status
                    $stmt = $pdo->prepare("
                        UPDATE ServiceInquiries 
                        SET status = 'completed' 
                        WHERE inquiry_id = ?
                    ");
                    if (!$stmt->execute([$_POST['inquiry_id']])) {
                        throw new Exception('Failed to update inquiry status');
                    }
                    
                    // Get session ID
                    $session_id = $pdo->lastInsertId();
                    
                    // Create notifications
                    $stmt = $pdo->prepare("
                        SELECT s.scheduled_time, s.learner_id, c.user_id as coach_user_id,
                               learner.username as learner_name, coach.username as coach_name,
                               t.name as tier_name
                        FROM Sessions s
                        JOIN Users learner ON s.learner_id = learner.user_id
                        JOIN Coaches c ON s.coach_id = c.coach_id
                        JOIN Users coach ON c.user_id = coach.user_id
                        LEFT JOIN ServiceTiers t ON s.tier_id = t.tier_id
                        WHERE s.session_id = ?
                    ");
                    $stmt->execute([$session_id]);
                    $session_info = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($session_info) {
                        // Create formatted date/time for notifications
                        $session_date = date('l, F j, Y', strtotime($session_info['scheduled_time']));
                        $session_time = date('g:i A', strtotime($session_info['scheduled_time']));
                        $service_info = !empty($session_info['tier_name']) ? " ({$session_info['tier_name']})" : "";
                        
                        // Notify the learner
                        $title = "Session Scheduled from Inquiry";
                        $message = "Your inquiry has been converted to a session with {$session_info['coach_name']}{$service_info} scheduled for {$session_date} at {$session_time}";
                        $link = "view-session.php?id={$session_id}";
                        createNotification($pdo, $session_info['learner_id'], $title, $message, $link, 'session');
                    }
                    
                    $response['success'] = true;
                    $response['message'] = 'Session created successfully';
                } catch (Exception $e) {
                    $response['message'] = $e->getMessage();
                }
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $response['success'] = false;
        $response['message'] = $e->getMessage();
        error_log('Error in sessions.php: ' . $e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}

// Get user's sessions
$query = $is_coach 
    ? "SELECT s.*, u.username as learner_name, st.name as tier_name, st.price 
       FROM Sessions s 
       JOIN Users u ON s.learner_id = u.user_id 
       LEFT JOIN ServiceTiers st ON s.tier_id = st.tier_id 
       WHERE s.coach_id = ?"
    : "SELECT s.*, u.username as coach_name, st.name as tier_name, st.price 
       FROM Sessions s 
       JOIN Coaches c ON s.coach_id = c.coach_id 
       JOIN Users u ON c.user_id = u.user_id 
       LEFT JOIN ServiceTiers st ON s.tier_id = st.tier_id 
       WHERE s.learner_id = ?";

// Debug - log the IDs being used
error_log("User ID: $user_id, Coach ID: " . ($coach_id ?? 'null') . ", Is Coach: " . ($is_coach ? 'yes' : 'no'));

$stmt = $pdo->prepare($query);
$stmt->execute([$is_coach ? $coach_id : $user_id]);
$sessions = $stmt->fetchAll();

// Get user's inquiries
try {
    if ($is_coach) {
        // Get inquiries for coach
        $stmt = $pdo->prepare("
            SELECT i.*, u.username 
            FROM ServiceInquiries i
            JOIN Users u ON i.user_id = u.user_id
            WHERE i.coach_id = (
                SELECT coach_id FROM Coaches WHERE user_id = ?
            )
            ORDER BY i.created_at DESC
        ");
        $stmt->execute([$user_id]);
    } else {
        // Get inquiries for learner
        $stmt = $pdo->prepare("
            SELECT i.*, u.username 
            FROM ServiceInquiries i
            JOIN Coaches c ON i.coach_id = c.coach_id
            JOIN Users u ON c.user_id = u.user_id
            WHERE i.user_id = ?
            ORDER BY i.created_at DESC
        ");
        $stmt->execute([$user_id]);
    }
    $inquiries = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Error fetching inquiries: ' . $e->getMessage());
    $inquiries = [];
}

// Get available coaches and their service tiers for booking
try {
    // Debug: Check if the database connection is working
    error_log("Fetching coaches and tiers from database...");

    // Fetch coaches and their service tiers in a single query
    $stmt = $pdo->prepare("
        SELECT c.coach_id, u.username, c.headline as expertise, st.tier_id, st.name as tier_name, st.price
        FROM Coaches c
        JOIN Users u ON c.user_id = u.user_id
        LEFT JOIN ServiceTiers st ON c.coach_id = st.coach_id
        ORDER BY c.coach_id, st.tier_id
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: Log the fetched results
    error_log("Fetched results: " . print_r($results, true));

    // If no results, log a warning
    if (empty($results)) {
        error_log("No coaches or tiers found in the database.");
    }

    // Group the results by coach
    $coaches = [];
    foreach ($results as $row) {
        $coachId = $row['coach_id'];
        
        // If this coach hasn't been added yet, add them
        if (!isset($coaches[$coachId])) {
            $coaches[$coachId] = [
                'coach_id' => $row['coach_id'],
                'username' => $row['username'],
                'expertise' => $row['expertise'],
                'tiers' => []
            ];
        }

        // If this row has a tier, add it to the coach's tiers
        if ($row['tier_id'] !== null) {
            $coaches[$coachId]['tiers'][] = [
                'tier_id' => $row['tier_id'],
                'tier_name' => $row['tier_name'],
                'price' => $row['price']
            ];
        }
    }

    // Convert the associative array to a numerically indexed array
    $coaches = array_values($coaches);

    // Debug: Log the final coaches array
    error_log("Final coaches array: " . print_r($coaches, true));

} catch (PDOException $e) {
    error_log('Error fetching coaches and tiers: ' . $e->getMessage());
    $coaches = [];
}

// Create calendar events array from sessions
$calendarEvents = [];
foreach ($sessions as $session) {
    $calendarEvents[] = [
        'id' => $session['session_id'],
        'title' => $is_coach ? $session['learner_name'] : $session['coach_name'],
        'start' => $session['scheduled_time'],
        'end' => date('Y-m-d H:i:s', strtotime($session['scheduled_time'] . ' +' . $session['duration'] . ' minutes')),
        'color' => $session['status'] === 'completed' ? '#28a745' : 
                  ($session['status'] === 'cancelled' ? '#dc3545' : '#007bff')
    ];
}

// Include the header
include __DIR__ . '/../includes/header.php';
?>

<!-- Main Content -->
<div class="container mt-4">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= htmlspecialchars($_SESSION['success_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= htmlspecialchars($_SESSION['error_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <h1 class="mb-4">My Sessions</h1>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fs-2 fw-bold"><?= $is_coach ? 'My Teaching Sessions' : 'My Learning Sessions' ?></h1>
    <?php if (!$is_coach): ?>
        <a href="coach-search.php" class="btn btn-primary d-flex align-items-center">
            <i class="bi bi-calendar-plus me-2"></i> Schedule New Session
        </a>
        <?php endif; ?>
        </div>
    
    <!-- Quick Actions Dashboard -->
    <div class="row g-4 mb-4">
        <!-- Upcoming Sessions Card -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
        <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                            <i class="bi bi-calendar-event text-primary fs-4"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">Upcoming Sessions</h5>
                            <?php
                            $upcoming_count = 0;
                            foreach ($sessions as $session) {
                                if ($session['status'] === 'scheduled' && strtotime($session['scheduled_time']) > time()) {
                                    $upcoming_count++;
                                }
                            }
                            // Add debug log
                            error_log("Total sessions: " . count($sessions) . ", Upcoming: " . $upcoming_count);
                            ?>
                            <span class="text-muted"><?= $upcoming_count ?> scheduled</span>
                        </div>
                    </div>
                    <?php
                    // Get next upcoming session
                    $next_session = null;
                    $current_time = time();
                    foreach ($sessions as $session) {
                        if ($session['status'] === 'scheduled' && strtotime($session['scheduled_time']) > $current_time) {
                            if (!$next_session || strtotime($session['scheduled_time']) < strtotime($next_session['scheduled_time'])) {
                                $next_session = $session;
                            }
                        }
                    }
                    
                    if ($next_session):
                    ?>
                    <div class="border-start border-4 border-primary ps-3">
                        <div class="small text-muted">Next Session</div>
                        <div class="fw-bold"><?= htmlspecialchars($is_coach ? $next_session['learner_name'] : $next_session['coach_name']) ?></div>
                        <div><?= date('l, M j', strtotime($next_session['scheduled_time'])) ?></div>
                        <div><?= date('g:i A', strtotime($next_session['scheduled_time'])) ?></div>
                    </div>
                                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i> No upcoming sessions scheduled.
                        <?php if (!$is_coach): ?>
                        <a href="coach-search.php" class="alert-link d-block mt-2">Find a coach to schedule one</a>
                                <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    </div>
                        </div>
                    </div>
        
        <!-- Completed Sessions Card -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                            <i class="bi bi-check-circle text-success fs-4"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">Completed</h5>
                            <?php
                            $completed_count = 0;
                            foreach ($sessions as $session) {
                                if ($session['status'] === 'completed') {
                                    $completed_count++;
                                }
                            }
                            ?>
                            <span class="text-muted"><?= $completed_count ?> sessions</span>
                        </div>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <?php
                        $total_sessions = count($sessions);
                        $completion_percentage = $total_sessions > 0 ? ($completed_count / $total_sessions) * 100 : 0;
                        ?>
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $completion_percentage ?>%"></div>
                    </div>
                    <div class="small text-muted mt-2">
                        <?= $completion_percentage > 0 ? number_format($completion_percentage) . '% completion rate' : 'No sessions completed yet' ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Links Card -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                            <i class="bi bi-lightning-charge text-info fs-4"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">Quick Actions</h5>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <?php if (!$is_coach): ?>
                        <a href="coach-search.php" class="btn btn-outline-primary">
                            <i class="bi bi-search me-2"></i> Find a Coach
                        </a>
                        <a href="book.php" class="btn btn-outline-success">
                            <i class="bi bi-calendar-plus me-2"></i> Book a Session
                        </a>
                                <?php else: ?>
                        <a href="edit-coach-availability.php" class="btn btn-outline-primary">
                            <i class="bi bi-clock me-2"></i> Update Availability
                        </a>
                        <a href="edit-coach-profile.php" class="btn btn-outline-success">
                            <i class="bi bi-person-gear me-2"></i> Edit Profile
                        </a>
                                <?php endif; ?>
                        </div>
                    </div>
                </div>
        </div>
    </div>

    <!-- Session Calendar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white py-3">
            <h5 class="card-title mb-0"><i class="bi bi-calendar3 me-2"></i>Session Calendar</h5>
        </div>
        <div class="card-body p-0">
            <!-- Add calendar container div -->
            <div id="calendar" class="p-3"></div>
        </div>
    </div>

    <!-- Sessions List -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="bi bi-clock-history me-2"></i>Session History</h5>
            <div class="btn-group">
                <button type="button" class="btn btn-light filter-btn active" data-filter="all">All</button>
                <button type="button" class="btn btn-light filter-btn" data-filter="scheduled">Scheduled</button>
                <button type="button" class="btn btn-light filter-btn" data-filter="completed">Completed</button>
                <button type="button" class="btn btn-light filter-btn" data-filter="cancelled">Cancelled</button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><?= $is_coach ? 'Learner' : 'Coach' ?></th>
                            <th>Service Tier</th>
                            <th>Date & Time</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sessions)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
                                    <p>No sessions found.</p>
                                    <?php if (!$is_coach): ?>
                                    <a href="coach-search.php" class="btn btn-primary mt-2">
                                        <i class="bi bi-search me-2"></i> Find a Coach
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        
                        <?php 
                        // Get pending reschedule requests for these sessions
                        $session_ids = array_column($sessions, 'session_id');
                        $pending_requests = [];
                        
                        if (!empty($session_ids)) {
                            $placeholders = implode(',', array_fill(0, count($session_ids), '?'));
                            $stmt = $pdo->prepare("
                                SELECT r.*, u.username as requester_name
                                FROM RescheduleRequests r
                                JOIN Users u ON r.requester_id = u.user_id
                                WHERE r.session_id IN ($placeholders)
                                AND r.status = 'pending'
                            ");
                            $stmt->execute($session_ids);
                            
                            while ($request = $stmt->fetch()) {
                                $pending_requests[$request['session_id']] = $request;
                            }
                        }
                        ?>
                        
                        <?php foreach ($sessions as $session): 
                            $has_pending_request = isset($pending_requests[$session['session_id']]);
                            $is_requester = $has_pending_request && $pending_requests[$session['session_id']]['requester_id'] == $user_id;
                            $requested_time = $has_pending_request ? date('M j, Y g:i A', strtotime($pending_requests[$session['session_id']]['new_time'])) : '';
                            $original_time = $has_pending_request ? date('M j, Y g:i A', strtotime($session['scheduled_time'])) : '';
                        ?>
                        <tr data-status="<?= strtolower($session['status']) ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php
                                    $name = $is_coach ? $session['learner_name'] : $session['coach_name'];
                                    $initial = strtoupper(substr($name, 0, 1));
                                    ?>
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                        <?= $initial ?>
                                    </div>
                                    <div>
                                        <?= htmlspecialchars($name) ?>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($session['tier_name']) ?></td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span><?= date('M j, Y', strtotime($session['scheduled_time'])) ?></span>
                                    <small class="text-muted"><?= date('g:i A', strtotime($session['scheduled_time'])) ?></small>
                                    
                                    <?php if ($has_pending_request): ?>
                                    <div class="mt-1">
                                        <?php if ($is_requester): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-clock-history me-1"></i> Reschedule Pending
                                        </span>
                                        <?php else: ?>
                                        <button class="btn btn-sm btn-warning view-reschedule-request" 
                                                data-request-id="<?= $pending_requests[$session['session_id']]['request_id'] ?>"
                                                data-requester="<?= htmlspecialchars($pending_requests[$session['session_id']]['requester_name']) ?>"
                                                data-original-time="<?= $original_time ?>"
                                                data-new-time="<?= $requested_time ?>"
                                                data-reason="<?= htmlspecialchars($pending_requests[$session['session_id']]['reason']) ?>">
                                            <i class="bi bi-clock-history me-1"></i> Reschedule Request
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><strong>$<?= number_format($session['price'], 2) ?></strong></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $session['status'] === 'completed' ? 'success' : 
                                    ($session['status'] === 'cancelled' ? 'danger' : 'primary') 
                                ?>">
                                    <?= ucfirst($session['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <?php 
                                    // Get session time and current time for comparison
                                    $session_time = new DateTime($session['scheduled_time']);
                                    $current_time = new DateTime();
                                    $session_passed = $current_time > $session_time;
                                    
                                    if (strtolower($session['status']) === 'scheduled'): 
                                    ?>
                                        
                                        <?php if ($session_passed): // Only show complete button if session time has passed ?>
                                        <button class="btn btn-sm btn-outline-success complete-session" data-session-id="<?= $session['session_id'] ?>">
                                            <i class="bi bi-check-circle me-1"></i> Complete
                                </button>
                                        <?php endif; ?>
                                        
                                        <?php if (!$has_pending_request): // Only show reschedule if no pending request ?>
                                        <button class="btn btn-sm btn-outline-primary reschedule-session" data-session-id="<?= $session['session_id'] ?>">
                                            <i class="bi bi-calendar-week me-1"></i> Reschedule
                                </button>
                                <?php endif; ?>
                                        
                                        <button class="btn btn-sm btn-outline-danger cancel-session" data-session-id="<?= $session['session_id'] ?>">
                                            <i class="bi bi-x-circle me-1"></i> Cancel
                                        </button>
                                        
                                    <?php elseif (strtolower($session['status']) === 'completed' && !$is_coach): ?>
                                        <a href="review.php?coach_id=<?= $session['coach_id'] ?>&session_id=<?= $session['session_id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-star me-1"></i> Review
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="view-session.php?id=<?= $session['session_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye me-1"></i> Details
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Service Inquiries -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white py-3">
            <h5 class="card-title mb-0"><i class="bi bi-chat-left-dots me-2"></i>Service Inquiries</h5>
        </div>
        <div class="card-body">
            <?php if (!$is_coach): ?>
            <!-- Inquiry Form -->
            <div class="card mb-4 bg-light border">
                <div class="card-body">
                    <h5 class="card-title">New Inquiry</h5>
            <form id="inquiryForm">
                <div class="mb-3">
                    <label for="coach" class="form-label">Select Coach</label>
                    <select class="form-select" id="coach" name="coach_id" required>
                        <option value="">Choose a coach...</option>
                        <?php if (!empty($coaches)): ?>
                            <?php foreach ($coaches as $coach): ?>
                                <option value="<?= $coach['coach_id'] ?>">
                                    <?= htmlspecialchars($coach['username']) ?> - <?= htmlspecialchars($coach['expertise']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">No coaches available</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i> Submit Inquiry
                        </button>
            </form>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Inquiry List -->
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><?= $is_coach ? 'Learner' : 'Coach' ?></th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inquiries)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="bi bi-chat-square-text fs-1 d-block mb-3"></i>
                                    <p>No inquiries found.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($inquiries as $inquiry): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php
                                    $initial = strtoupper(substr($inquiry['username'], 0, 1));
                                    ?>
                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                        <?= $initial ?>
                                    </div>
                                    <div>
                                        <?= htmlspecialchars($inquiry['username']) ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 250px;">
                                    <?= htmlspecialchars($inquiry['message']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?= 
                                    $inquiry['status'] === 'pending' ? 'warning' : 
                                    ($inquiry['status'] === 'accepted' ? 'success' : 
                                    ($inquiry['status'] === 'rejected' ? 'danger' : 'primary')) 
                                ?>">
                                    <?= ucfirst($inquiry['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span><?= date('M j, Y', strtotime($inquiry['created_at'])) ?></span>
                                    <small class="text-muted"><?= date('g:i A', strtotime($inquiry['created_at'])) ?></small>
                                </div>
                            </td>
                            <td>
                                <?php if ($inquiry['status'] === 'accepted' && !$is_coach): ?>
                                <button class="btn btn-sm btn-primary convert-inquiry" 
                                        data-inquiry-id="<?= $inquiry['inquiry_id'] ?>">
                                    <i class="bi bi-calendar-plus me-1"></i> Schedule
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Rating Modal -->
<div class="modal fade" id="ratingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rate Your Session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="ratingForm">
                    <input type="hidden" id="session_id_rating" name="session_id">
                    <div class="mb-3">
                        <label class="form-label">Rating</label>
                        <div class="rating-stars mb-3">
                            <i class="bi bi-star-fill fs-3 text-warning" data-rating="1"></i>
                            <i class="bi bi-star-fill fs-3 text-warning" data-rating="2"></i>
                            <i class="bi bi-star-fill fs-3 text-warning" data-rating="3"></i>
                            <i class="bi bi-star-fill fs-3 text-warning" data-rating="4"></i>
                            <i class="bi bi-star-fill fs-3 text-warning" data-rating="5"></i>
                        </div>
                        <input type="hidden" id="rating_value" name="rating" required>
                    </div>
                    <div class="mb-3">
                        <label for="feedback" class="form-label">Feedback (Optional)</label>
                        <textarea class="form-control" id="feedback" name="feedback" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitRating">Submit Rating</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Bootstrap Icons CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">

<!-- Add Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Add custom styles to ensure proper z-index hierarchy -->
<style>
    /* Ensure proper z-index for navbar elements */
    .navbar-nav .nav-link, 
    .navbar-brand, 
    .navbar .dropdown-toggle,
    .navbar .user-dropdown,
    .navbar .btn-outline-primary {
        position: relative;
        z-index: 1050 !important; /* Higher than any other elements */
    }
    
    /* Ensure no invisible overlays block interaction */
    body::before {
        content: none !important;
        display: none !important;
    }
    
    /* Fix for calendar overlapping header */
    .fc-view-harness {
        z-index: 1 !important;
    }
    
    /* Fix for any full page overlays */
    .modal-backdrop {
        z-index: 1040 !important;
    }
    
    /* Ensure calendar doesn't capture clicks meant for the navbar */
    #calendar {
        pointer-events: auto;
        z-index: 1 !important;
    }
    
    /* Ensure buttons and interactive elements work properly */
    .fc-button, .fc-event, .fc-daygrid-day {
        position: relative;
        z-index: 2 !important;
    }
</style>

<!-- Add this right before the closing body tag -->
<script>
// Wait for the DOM and all resources to be fully loaded
window.addEventListener('load', function() {
    console.log('Page fully loaded, initializing session management');
    
    // Fix for filter buttons
    function setupFilterButtons() {
        // Target the buttons with data-filter attributes
        const filterButtons = document.querySelectorAll('button[data-filter]');
        console.log('Found filter buttons:', filterButtons.length, Array.from(filterButtons).map(btn => btn.dataset.filter));
        
        if (filterButtons.length > 0) {
            filterButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const filterValue = this.dataset.filter;
                    console.log('Filter button clicked:', filterValue);
                    
                    // Find all filter buttons
                    const allFilterButtons = document.querySelectorAll('button[data-filter]');
                    
                    // Remove active class from all buttons
                    allFilterButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Get the filter value from the data-filter attribute
                    console.log('Filtering by:', filterValue);
                    
                    // Get all rows in the session table
                    const sessionTable = document.querySelector('.table');
                    if (!sessionTable) {
                        console.error('Session table not found');
                        return;
                    }
                    
                    const rows = sessionTable.querySelectorAll('tbody tr[data-status]');
                    console.log('Found rows to filter:', rows.length, 'with data-status attributes');
                    
                    // Debug: List all rows and their status
                    console.log('All rows data-status values:');
                    Array.from(rows).forEach((row, i) => {
                        console.log(`Row ${i+1}: status='${row.dataset.status}'`);
                    });
                    
                    // Filter the rows based on status
                    let visibleCount = 0;
                    rows.forEach(row => {
                        const rowStatus = row.dataset.status;
                        console.log('Row status:', rowStatus, 'comparing with filter:', filterValue);
                        
                        // Show/hide based on filter
                        if (filterValue === 'all' || rowStatus === filterValue) {
                            row.style.display = '';
                            visibleCount++;
                            console.log('✓ SHOWING row with status:', rowStatus);
                        } else {
                            row.style.display = 'none';
                            console.log('✗ HIDING row with status:', rowStatus);
                        }
                    });
                    console.log(`Filtering complete: ${visibleCount} rows visible out of ${rows.length}`);
                });
            });
        } else {
            console.error('No filter buttons found with data-filter attributes');
        }
    }
    
    function setupFilterButtonHandler(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Filter button clicked:', this.textContent.trim());
            
            // Find all filter buttons in the same group
            const buttonGroup = this.closest('.btn-group') || document.querySelector('.btn-group');
            const allFilterButtons = buttonGroup ? 
                buttonGroup.querySelectorAll('.btn') : 
                document.querySelectorAll('.btn-group .btn');
            
            // Remove active class from all buttons
            allFilterButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Get the filter value from the button text
            const filterValue = this.textContent.trim().toLowerCase();
            console.log('Filtering by:', filterValue);
            
            // Get all rows in the session table
            const sessionTable = document.querySelector('.table');
            if (!sessionTable) {
                console.error('Session table not found');
                return;
            }
            
            const rows = sessionTable.querySelectorAll('tbody tr');
            console.log('Found rows to filter:', rows.length);
            
            // Filter the rows based on status
            rows.forEach(row => {
                // Get the status from the badge text in the status column (5th column)
                const statusCell = row.querySelector('td:nth-child(5)');
                if (!statusCell) {
                    console.warn('Status cell not found in row', row);
                    return;
                }
                
                const statusText = statusCell.textContent.trim().toLowerCase();
                console.log('Row status text:', statusText);
                
                // Show/hide based on filter
                if (filterValue === 'all' || statusText.includes(filterValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Fix for action buttons
    function setupActionButtons() {
        // Find all Complete buttons with the correct class
        const completeButtons = document.querySelectorAll('.complete-session');
        console.log('Found complete buttons:', completeButtons.length);
        
        // If no buttons found with the class, try alternative selectors
        if (completeButtons.length === 0) {
            // Try to find buttons by text content
            document.querySelectorAll('.btn').forEach(btn => {
                if (btn.textContent.trim().toLowerCase() === 'complete') {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        console.log('Complete button clicked');
                        
                        // Get session ID directly from the button's data attribute
                        let sessionId = this.dataset.sessionId;
                        console.log('Button session ID attribute:', sessionId);
                        
                        if (!sessionId) {
                            // Try other methods to find session ID
                            sessionId = findSessionIdFromRow(this.closest('tr'));
                        }
                        
                        if (!sessionId) {
                            alert('Could not determine session ID. Please try again.');
                            return;
                        }
                        
                        handleComplete(sessionId, this.closest('tr'));
                    });
                }
            });
        } else {
            completeButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    let sessionId = this.dataset.sessionId;
                    console.log('Complete button clicked, session ID:', sessionId);
                    
                    if (!sessionId) {
                        sessionId = findSessionIdFromRow(this.closest('tr'));
                    }
                    
                    if (!sessionId) {
                        alert('Could not determine session ID. Please try again.');
                        return;
                    }
                    
                    handleComplete(sessionId, this.closest('tr'));
                });
            });
        }
        
        // Find all Cancel buttons with the correct class
        const cancelButtons = document.querySelectorAll('.cancel-session');
        console.log('Found cancel buttons:', cancelButtons.length);
        
        // If no buttons found with the class, try alternative selectors
        if (cancelButtons.length === 0) {
            // Try to find buttons by text content
            document.querySelectorAll('.btn').forEach(btn => {
                if (btn.textContent.trim().toLowerCase() === 'cancel') {
                    btn.addEventListener('click', function(e) {
            e.preventDefault();
                        console.log('Cancel button clicked');
                        
                        // Get session ID directly from the button's data attribute
                        let sessionId = this.dataset.sessionId;
                        console.log('Button session ID attribute:', sessionId);
                        
                        if (!sessionId) {
                            // Try other methods to find session ID
                            sessionId = findSessionIdFromRow(this.closest('tr'));
                        }
                        
                        if (!sessionId) {
                            alert('Could not determine session ID. Please try again.');
                            return;
                        }
                        
                        handleCancel(sessionId);
                    });
                }
            });
        } else {
            cancelButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    let sessionId = this.dataset.sessionId;
                    console.log('Cancel button clicked, session ID:', sessionId);
                    
                    if (!sessionId) {
                        sessionId = findSessionIdFromRow(this.closest('tr'));
                    }
                    
                    if (!sessionId) {
                        alert('Could not determine session ID. Please try again.');
                        return;
                    }
                    
                    handleCancel(sessionId);
                });
            });
        }
    }
    
    // Helper function to find session ID from different sources in a row
    function findSessionIdFromRow(row) {
        if (!row) return null;
        console.log('Trying to find session ID from row:', row);
        
        // Try to find session ID in links within the row
        const links = row.querySelectorAll('a[href*="id="]');
        if (links.length > 0) {
            const href = links[0].getAttribute('href');
            const match = href.match(/id=(\d+)/);
            if (match && match[1]) {
                console.log('Found session ID from link:', match[1]);
                return match[1];
            }
        }
        
        // Try to find it from a hidden input
        const hiddenInput = row.querySelector('input[name="session_id"]');
        if (hiddenInput && hiddenInput.value) {
            console.log('Found session ID from hidden input:', hiddenInput.value);
            return hiddenInput.value;
        }
        
        // Last resort: try to get it from the current URL
        const urlParams = new URLSearchParams(window.location.search);
        const urlSessionId = urlParams.get('id');
        if (urlSessionId) {
            console.log('Found session ID from URL:', urlSessionId);
            return urlSessionId;
        }
        
        console.error('Could not find session ID from any source');
        return null;
    }
    
    function handleComplete(sessionId, row) {
        console.log('Handling complete for session:', sessionId);
        
        // Get the session time from the row
        let sessionTimeStr = '';
        if (row) {
            const dateCell = row.querySelector('td:nth-child(3)');
            if (dateCell) {
                sessionTimeStr = dateCell.textContent.trim();
                console.log('Found session time:', sessionTimeStr);
            }
        }
        
        // Check if the session time has passed
        if (sessionTimeStr) {
            try {
                const sessionTime = new Date(sessionTimeStr);
                const now = new Date();
                
                console.log('Parsed time:', sessionTime);
                console.log('Current time:', now);
                
                if (!isNaN(sessionTime.getTime()) && sessionTime > now) {
                    alert('This session cannot be marked as completed until after the scheduled time.');
                    return;
                }
            } catch (error) {
                console.error('Error checking session time:', error);
                // Continue anyway if there was an error in time checking
            }
        }
        
        // Show the rating modal
        const ratingModal = document.getElementById('ratingModal');
        if (ratingModal) {
            const sessionIdInput = document.getElementById('session_id_rating');
            if (sessionIdInput) {
                sessionIdInput.value = sessionId;
            }
            
            // Use Bootstrap modal if available
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = new bootstrap.Modal(ratingModal);
                modal.show();
                } else {
                // Fallback to basic display
                ratingModal.style.display = 'block';
            }
        } else {
            // If modal not found, just submit directly
            submitCompletionRequest(sessionId);
        }
    }
    
    function handleCancel(sessionId) {
        console.log('Handling cancel for session:', sessionId);
        
                    if (!confirm('Are you sure you want to cancel this session?')) {
                        return;
                    }
                    
        submitCancellationRequest(sessionId);
    }
    
    function submitCompletionRequest(sessionId, rating, feedback) {
                    const formData = new FormData();
                    formData.append('action', 'update_status');
                    formData.append('session_id', sessionId);
        formData.append('status', 'completed');
        
        if (rating) {
            formData.append('rating', rating);
        }
        
        if (feedback) {
            formData.append('feedback', feedback);
        }
        
        fetch(window.location.pathname, {
                            method: 'POST',
                            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(result => {
                        if (result.success) {
                alert('Session marked as completed!');
                            location.reload();
                        } else {
                            alert(result.message || 'Error updating session status');
                        }
        })
        .catch(error => {
            console.error('Error completing session:', error);
            alert('Error updating session status: ' + error.message);
        });
    }
    
    function submitCancellationRequest(sessionId) {
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('session_id', sessionId);
        formData.append('status', 'cancelled');
        
        fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(result => {
            if (result.success) {
                alert('Session cancelled successfully!');
                location.reload();
                } else {
                alert(result.message || 'Error updating session status');
            }
        })
        .catch(error => {
            console.error('Error canceling session:', error);
            alert('Error updating session status: ' + error.message);
        });
    }
    
    // Handle rating submission
    function setupRatingSubmission() {
        // Set up the star rating functionality
    const ratingStars = document.querySelectorAll('.rating-stars i');
        if (ratingStars.length > 0) {
    ratingStars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.dataset.rating;
                    document.getElementById('rating_value').value = rating;
                    
                    // Update visual state of stars
                    ratingStars.forEach(s => {
                        if (s.dataset.rating <= rating) {
                            s.classList.add('text-warning');
                        } else {
                            s.classList.remove('text-warning');
                            s.classList.add('text-muted');
                        }
        });
    });

                // Add hover effects
                star.addEventListener('mouseenter', function() {
            const rating = this.dataset.rating;
                    ratingStars.forEach(s => {
                        if (s.dataset.rating <= rating) {
                            s.classList.add('text-warning');
                        } else {
                            s.classList.remove('text-warning');
                            s.classList.add('text-muted');
                        }
        });

                star.addEventListener('mouseleave', function() {
                    const rating = document.getElementById('rating_value').value;
                    ratingStars.forEach(s => {
                        if (rating && s.dataset.rating <= rating) {
                            s.classList.add('text-warning');
                        } else {
                            s.classList.remove('text-warning');
                            s.classList.add('text-muted');
                        }
                    });
                });
            });
        }

        const submitButton = document.getElementById('submitRating');
        if (submitButton) {
            submitButton.addEventListener('click', function() {
                const ratingValue = document.getElementById('rating_value');
                if (!ratingValue || !ratingValue.value) {
            alert('Please select a rating before submitting');
            return;
        }

        const sessionId = document.getElementById('session_id_rating').value;
        if (!sessionId) {
            alert('Session ID is missing');
            return;
        }

                const feedback = document.getElementById('feedback')?.value || '';
                
                submitCompletionRequest(sessionId, ratingValue.value, feedback);
            });
        }
    }
    
    // Initialize everything
    setupFilterButtons();
    setupActionButtons();
    setupRatingSubmission();
    
    console.log('Session management initialization complete');
});

// Initialize calendar
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            fixedWeekCount: false, // Don't show extra weeks from next month
            showNonCurrentDates: false, // Don't show dates from adjacent months
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: <?= json_encode($calendarEvents) ?>,
            eventClick: function(info) {
                // Navigate to session details when clicking on an event
                window.location.href = 'view-session.php?id=' + info.event.id;
            }
        });
        calendar.render();
        console.log('Calendar initialized');
            } else {
        console.warn('Calendar element not found');
    }
});

// ... existing code ...
    function showAlert(type, message) {
        const alertElement = document.createElement('div');
        alertElement.className = `alert alert-${type} alert-dismissible fade show fixed-top mx-auto mt-3`;
        alertElement.style.maxWidth = '600px';
        alertElement.style.zIndex = '9999';
        alertElement.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.body.appendChild(alertElement);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            alertElement.classList.remove('show');
            setTimeout(function() {
                document.body.removeChild(alertElement);
            }, 300);
        }, 5000);
    }
    
    // Session filtering functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    const sessionRows = document.querySelectorAll('tr[data-status]');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Get the filter value
            const filter = this.getAttribute('data-filter');
            
            // Show/hide rows based on filter
            sessionRows.forEach(row => {
                const status = row.getAttribute('data-status');
                if (filter === 'all' || status === filter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Check if any sessions are visible
            const visibleSessions = Array.from(sessionRows).filter(row => row.style.display !== 'none');
            const noSessionsRow = document.querySelector('.no-sessions-row');
            
            // Remove existing no-sessions-row if it exists
            if (noSessionsRow) {
                noSessionsRow.remove();
            }
            
            // Add "No sessions found" message if no sessions are visible
            if (visibleSessions.length === 0) {
                const tbody = sessionRows[0]?.parentNode;
                if (tbody) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.className = 'no-sessions-row';
                    emptyRow.innerHTML = `
                        <td colspan="6" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
                                <p>No ${filter === 'all' ? '' : filter} sessions found.</p>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(emptyRow);
                }
            }
            
            // Update URL with filter parameter for bookmarking
            const url = new URL(window.location.href);
            if (filter === 'all') {
                url.searchParams.delete('status');
            } else {
                url.searchParams.set('status', filter);
            }
            window.history.replaceState({}, '', url);
        });
    });
    
    // Initialize the filter based on URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const statusParam = urlParams.get('status');
    
    if (statusParam) {
        const statusButton = document.querySelector(`.filter-btn[data-filter="${statusParam.toLowerCase()}"]`);
        if (statusButton) {
            statusButton.click();
        }
    }
    
    // Add return_filter parameter to detail links
    const detailLinks = document.querySelectorAll('a[href*="view-session.php"]');
    detailLinks.forEach(link => {
        link.addEventListener('click', function() {
            const activeFilter = document.querySelector('.filter-btn.active').getAttribute('data-filter');
            if (activeFilter !== 'all') {
                const url = new URL(this.href, window.location.origin);
                url.searchParams.set('return_filter', activeFilter);
                this.href = url.toString();
            }
        });
    });
});
// ... existing code ...
</script>

<!-- Add this HTML for the toast notification -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <strong class="me-auto">Success</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            Session successfully scheduled!
        </div>
    </div>
</div>

<!-- Add UI components after the sessions list -->

<!-- Reschedule Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Reschedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="rescheduleForm">
                    <input type="hidden" id="reschedule_session_id" name="session_id">
                    
                    <div class="mb-3">
                        <label for="new_date" class="form-label">New Date</label>
                        <input type="date" class="form-control" id="new_date" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_time" class="form-label">New Time</label>
                        <input type="time" class="form-control" id="new_time" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reschedule_reason" class="form-label">Reason for Rescheduling</label>
                        <textarea class="form-control" id="reschedule_reason" rows="3" required 
                                  placeholder="Please provide a reason for rescheduling this session"></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Your request will be sent to the other party for approval. 
                        The session will only be rescheduled if they accept your request.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitRescheduleBtn">Submit Request</button>
            </div>
        </div>
    </div>
</div>

<!-- Reschedule Request Modal -->
<div class="modal fade" id="rescheduleRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reschedule Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="rescheduleRequestDetails">
                    <!-- Will be populated dynamically -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="rejectRescheduleBtn">Reject</button>
                <button type="button" class="btn btn-success" id="approveRescheduleBtn">Approve</button>
            </div>
        </div>
    </div>
</div>

<!-- Add reschedule JavaScript functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the sessions UI
    initSessionsUI();

    // Handle reschedule request form submission
    const rescheduleForm = document.getElementById('rescheduleForm');
    const submitRescheduleBtn = document.getElementById('submitRescheduleBtn');
    
    // Also look for Submit Request button (alternative ID)
    const submitRequestBtn = document.getElementById('submitRequest');

    // Handle the standard submit button
    if (submitRescheduleBtn) {
        submitRescheduleBtn.addEventListener('click', function() {
            submitRescheduleRequest();
        });
    }
    
    // Handle the alternative submit button (from the UI screenshot)
    if (submitRequestBtn) {
        submitRequestBtn.addEventListener('click', function() {
            submitRescheduleRequest();
        });
    }
    
    // Extract the form submission logic to a reusable function
    function submitRescheduleRequest() {
        // Validate the form
        if (!rescheduleForm.checkValidity()) {
            rescheduleForm.reportValidity();
            return;
        }

        // Get form values
        const sessionId = document.getElementById('reschedule_session_id').value;
        const newDate = document.getElementById('new_date').value;
        const newTime = document.getElementById('new_time').value;
        const reason = document.getElementById('reschedule_reason').value;
        
        // Combine date and time
        const newDateTime = `${newDate}T${newTime}:00`;
        
        console.log('Submitting reschedule request:', {
            sessionId,
            newDateTime,
            reason
        });
        
        // Submit the request
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: {
                action: 'request_reschedule',
                session_id: sessionId,
                new_time: newDateTime,
                reason: reason
            },
            success: function(response) {
                if (response.success) {
                    // Close the modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('rescheduleModal'));
                    modal.hide();
                    
                    // Show success message
                    showAlert('success', response.message);
                    
                    // Reload the page after a delay
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'An error occurred while submitting your request.');
            }
        });
    }

    // Handle reschedule request response
    const approveRescheduleBtn = document.getElementById('approveRescheduleBtn');
    const rejectRescheduleBtn = document.getElementById('rejectRescheduleBtn');
    
    if (approveRescheduleBtn && rejectRescheduleBtn) {
        approveRescheduleBtn.addEventListener('click', function() {
            respondToRescheduleRequest('approve');
        });
        
        rejectRescheduleBtn.addEventListener('click', function() {
            respondToRescheduleRequest('reject');
        });
    }
    
    function respondToRescheduleRequest(response) {
        const requestId = document.getElementById('approveRescheduleBtn').getAttribute('data-request-id');
        
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: {
                action: 'respond_reschedule',
                request_id: requestId,
                response: response
            },
            success: function(response) {
                if (response.success) {
                    // Close the modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('rescheduleRequestModal'));
                    modal.hide();
                    
                    // Show success message
                    showAlert('success', response.message);
                    
                    // Reload the page after a delay
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
            } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'An error occurred while processing your response.');
            }
        });
    }
    
    // Initialize sessions UI
    function initSessionsUI() {
        // Initialize reschedule buttons
        const rescheduleButtons = document.querySelectorAll('.reschedule-session');
        rescheduleButtons.forEach(button => {
        button.addEventListener('click', function() {
                const sessionId = this.getAttribute('data-session-id');
                document.getElementById('reschedule_session_id').value = sessionId;
                
                // Reset the form
                document.getElementById('new_date').value = '';
                document.getElementById('new_time').value = '';
                document.getElementById('reschedule_reason').value = '';
                
                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('rescheduleModal'));
                modal.show();
        });
    });
        
        // Initialize view reschedule request buttons
        const viewRequestButtons = document.querySelectorAll('.view-reschedule-request');
        viewRequestButtons.forEach(button => {
            button.addEventListener('click', function() {
                const requestId = this.getAttribute('data-request-id');
                const requesterName = this.getAttribute('data-requester');
                const originalTime = this.getAttribute('data-original-time');
                const newTime = this.getAttribute('data-new-time');
                const reason = this.getAttribute('data-reason');
                
                // Update modal content
                document.getElementById('rescheduleRequestDetails').innerHTML = `
                    <p><strong>${requesterName}</strong> has requested to reschedule a session:</p>
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="bg-light p-2 rounded me-3">
                                <i class="bi bi-calendar-x text-danger"></i>
        </div>
                            <div>
                                <div class="small text-muted">Original Time</div>
                                <div class="fw-bold">${originalTime}</div>
        </div>
    </div>
                        <div class="d-flex align-items-center">
                            <div class="bg-light p-2 rounded me-3">
                                <i class="bi bi-calendar-check text-success"></i>
</div>
                            <div>
                                <div class="small text-muted">Requested New Time</div>
                                <div class="fw-bold">${newTime}</div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="fw-bold mb-1">Reason:</div>
                        <div class="p-3 bg-light rounded">${reason}</div>
                    </div>
                `;
                
                // Set the request ID for response buttons
                document.getElementById('approveRescheduleBtn').setAttribute('data-request-id', requestId);
                document.getElementById('rejectRescheduleBtn').setAttribute('data-request-id', requestId);
                
                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('rescheduleRequestModal'));
                modal.show();
            });
        });
    }
    
    // Helper function to show alerts
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Add to the top of the page
        document.querySelector('.container').prepend(alertDiv);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }, 5000);
    }
});
</script>

<!-- Add script to ensure proper Bootstrap dropdown functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Reinitialize Bootstrap dropdowns
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    dropdownElementList.forEach(function(dropdownToggleEl) {
        dropdownToggleEl.addEventListener('click', function(e) {
            e.stopPropagation();
            var dropdown = this.closest('.dropdown');
            var menu = dropdown.querySelector('.dropdown-menu');
            
            if (menu) {
                // Toggle the dropdown menu
                if (menu.classList.contains('show')) {
                    menu.classList.remove('show');
                } else {
                    // Close all other menus first
                    document.querySelectorAll('.dropdown-menu.show').forEach(function(openMenu) {
                        openMenu.classList.remove('show');
                    });
                    menu.classList.add('show');
                }
            }
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(function(openMenu) {
                openMenu.classList.remove('show');
            });
        }
    });
});
</script>

<!-- Add FullCalendar JS and its dependencies -->
</script>

<!-- Add session actions script -->
<script src="../assets/js/session-actions.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
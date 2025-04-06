<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../includes/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if session ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>Invalid session ID</div>";
    header('Location: session.php');
    exit;
}

$session_id = (int)$_GET['id'];

// Determine if the user is a coach
$stmt = $pdo->prepare("SELECT user_type FROM Users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$is_coach = ($user['user_type'] === 'business');

// Get session details with security check (make sure the user is either the coach or the learner of this session)
try {
    if ($is_coach) {
        $stmt = $pdo->prepare("
            SELECT s.*, u.username as learner_name, u.email as learner_email, 
                   st.name as tier_name, st.price,
                   c.coach_id
            FROM sessions s
            JOIN Users u ON s.learner_id = u.user_id
            JOIN ServiceTiers st ON s.tier_id = st.tier_id
            JOIN Coaches c ON s.coach_id = c.coach_id
            WHERE s.session_id = ? AND c.user_id = ?
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT s.*, u.username as coach_name, u.email as coach_email, 
                   st.name as tier_name, st.price,
                   c.coach_id
            FROM sessions s
            JOIN Coaches c ON s.coach_id = c.coach_id
            JOIN Users u ON c.user_id = u.user_id
            JOIN ServiceTiers st ON s.tier_id = st.tier_id
            WHERE s.session_id = ? AND s.learner_id = ?
        ");
    }
    
    $stmt->execute([$session_id, $user_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        echo "<div class='alert alert-danger'>You don't have permission to view this session or it doesn't exist.</div>";
        header('Location: session.php');
        exit;
    }
    
    // Check if the user has already rated this session
    $hasRated = false;
    if (!$is_coach && $session['status'] === 'completed') {
        $stmt = $pdo->prepare("
            SELECT * FROM Reviews 
            WHERE user_id = ? AND coach_id = ? AND session_id = ?
        ");
        $stmt->execute([$user_id, $session['coach_id'], $session_id]);
        $hasRated = $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    }
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error retrieving session: " . $e->getMessage() . "</div>";
    exit;
}

// Handle AJAX requests first, before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Always set JSON content type for all responses
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'An error occurred'];
    
    try {
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            $user_id = $_SESSION['user_id'];
            
            if ($action === 'update_status') {
                if (!isset($_POST['status'])) {
                    throw new Exception('Missing status parameter');
                }
                
                $newStatus = $_POST['status'];
                $validStatuses = ['scheduled', 'completed', 'cancelled'];
                
                if (!in_array($newStatus, $validStatuses)) {
                    throw new Exception('Invalid status value');
                }
                
                // For completion, check if session time has passed
                if ($newStatus === 'completed') {
                    $scheduled_time = new DateTime($session['scheduled_time']);
                    $now = new DateTime();
                    
                    if ($scheduled_time > $now) {
                        throw new Exception('Sessions can only be marked as completed after their scheduled time');
                    }
                }
                
                // Update the session status
                $stmt = $pdo->prepare("UPDATE sessions SET status = ? WHERE session_id = ?");
                if (!$stmt->execute([$newStatus, $session_id])) {
                    throw new Exception('Failed to update session status');
                }
                
                // If completing and rating provided, save the rating
                if ($newStatus === 'completed' && isset($_POST['rating']) && !$is_coach) {
                    $rating = (int)$_POST['rating'];
                    $feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';
                    
                    if ($rating < 1 || $rating > 5) {
                        throw new Exception('Rating must be between 1 and 5');
                    }
                    
                    // Insert the review
                    $stmt = $pdo->prepare("
                        INSERT INTO Reviews (user_id, coach_id, session_id, rating, comment, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    if (!$stmt->execute([$user_id, $session['coach_id'], $session_id, $rating, $feedback])) {
                        throw new Exception('Failed to save rating');
                    }
                    
                    // Update coach's average rating
                    $stmt = $pdo->prepare("
                        UPDATE Coaches 
                        SET rating = (
                            SELECT AVG(rating) 
                            FROM Reviews 
                            WHERE coach_id = ?
                        )
                        WHERE coach_id = ?
                    ");
                    $stmt->execute([$session['coach_id'], $session['coach_id']]);
                }
                
                $response['success'] = true;
                $response['message'] = 'Session status updated successfully';
            } elseif ($action === 'request_reschedule') {
                // Increase execution time limit for this operation
                set_time_limit(120); // 2 minutes
                
                // Disable error output that might interfere with JSON
                ini_set('display_errors', 0);
                error_reporting(0);
                
                if (!isset($_POST['session_id'], $_POST['new_time'], $_POST['reason'])) {
                    throw new Exception('Missing required parameters');
                }
                
                $session_id = (int)$_POST['session_id'];
                
                // Log the starting point for debugging
                error_log("Processing reschedule request for session ID: $session_id");
                
                try {
                    // Get session details first to verify permissions
                    $stmt = $pdo->prepare("
                        SELECT s.*, c.user_id as coach_user_id 
                        FROM sessions s
                        JOIN Coaches c ON s.coach_id = c.coach_id
                        WHERE s.session_id = ?
                    ");
                    $stmt->execute([$session_id]);
                    $session = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$session) {
                        throw new Exception('Session not found');
                    }
                    
                    // Check if user has permission (either learner or coach)
                    if ($session['learner_id'] != $user_id && $session['coach_user_id'] != $user_id) {
                        throw new Exception('Permission denied');
                    }
                    
                    // Check if session is eligible for rescheduling
                    if ($session['status'] !== 'scheduled') {
                        throw new Exception('Only scheduled sessions can be rescheduled');
                    }
                    
                    // Parse the new time with proper error handling
                    try {
                        $new_time = new DateTime($_POST['new_time']);
                        $now = new DateTime();
                    } catch (Exception $e) {
                        throw new Exception('Invalid date format provided');
                    }
                    
                    if ($new_time <= $now) {
                        throw new Exception('The requested time must be in the future');
                    }
                    
                    // Format the new time for database insertion
                    $new_time_formatted = $new_time->format('Y-m-d H:i:s');
                    
                    // Check if a reschedule request already exists for this session
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as request_count 
                        FROM RescheduleRequests
                        WHERE session_id = ? AND status = 'pending'
                    ");
                    $stmt->execute([$session_id]);
                    $request_count = $stmt->fetch(PDO::FETCH_ASSOC)['request_count'];
                    
                    if ($request_count > 0) {
                        throw new Exception('A rescheduling request is already pending for this session');
                    }
                    
                    // Check if time slot is available - simplified query
                    $coach_id = $session['coach_id'];
                    $slot_available = false;
                    
                    try {
                        $stmt = $pdo->prepare("
                            SELECT COUNT(*) as slot_count
                            FROM CoachTimeSlots
                            WHERE coach_id = ? 
                            AND start_time = ?
                            AND status = 'available'
                        ");
                        $stmt->execute([$coach_id, $new_time_formatted]);
                        $slot_available = ($stmt->fetch(PDO::FETCH_ASSOC)['slot_count'] > 0);
                    } catch (PDOException $slotError) {
                        // Log the error but continue
                        error_log("Error checking slot availability: " . $slotError->getMessage());
                        // Default to not available
                        $slot_available = false;
                    }
                    
                    // Begin transaction
                    $pdo->beginTransaction();
                    
                    // Insert the reschedule request
                    $stmt = $pdo->prepare("
                        INSERT INTO RescheduleRequests
                        (session_id, requester_id, new_time, reason, status, created_at)
                        VALUES (?, ?, ?, ?, 'pending', NOW())
                    ");
                    $stmt->execute([
                        $session_id,
                        $user_id,
                        $new_time_formatted,
                        $_POST['reason']
                    ]);
                    
                    $request_id = $pdo->lastInsertId();
                    error_log("Created reschedule request ID: $request_id");
                    
                    // Determine recipient ID (the other party)
                    $recipient_id = ($session['learner_id'] == $user_id) ? $session['coach_user_id'] : $session['learner_id'];
                    
                    // Get names for notification - simplified approach
                    $stmt = $pdo->prepare("SELECT username FROM Users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $requester = $stmt->fetch(PDO::FETCH_ASSOC);
                    $requester_name = $requester ? $requester['username'] : 'User';
                    
                    // Create a notification for the other party
                    $notification_text = sprintf(
                        "%s has requested to reschedule your session on %s to %s.",
                        $requester_name,
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
                        "/pages/view-session.php?id={$session_id}"
                    ]);
                    
                    // Commit the transaction
                    $pdo->commit();
                    error_log("Reschedule request completed successfully");
                    
                    $response['success'] = true;
                    $response['message'] = 'Reschedule request submitted successfully';
                    $response['slot_available'] = $slot_available;
                    
                    if (!$slot_available) {
                        $response['message'] .= ' Note: The requested time is not currently available in the coach\'s schedule. The coach will need to create this time slot if they approve.';
                    }
                } catch (Exception $e) {
                    // If a transaction is in progress, roll it back
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    
                    // Log the error
                    error_log("Error in reschedule request: " . $e->getMessage());
                    
                    // Rethrow to be caught by outer catch block
                    throw $e;
                }
            } elseif ($action === 'respond_reschedule') {
                if (!isset($_POST['request_id'], $_POST['response'])) {
                    throw new Exception('Missing required parameters');
                }
                
                // Start transaction
                $pdo->beginTransaction();
                
                // Get the reschedule request
                $stmt = $pdo->prepare("
                    SELECT r.*, s.coach_id, s.learner_id, s.scheduled_time as original_time,
                           c.user_id as coach_user_id,
                           u1.username as requester_name,
                           u2.username as recipient_name
                    FROM RescheduleRequests r
                    JOIN sessions s ON r.session_id = s.session_id
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
                        UPDATE sessions
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
            } else {
                throw new Exception('Invalid action');
            }
        } else {
            throw new Exception('Missing action parameter');
        }
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $response['success'] = false;
        $response['message'] = $e->getMessage();
        error_log('Error in view-session.php: ' . $e->getMessage());
    }
    
    // Return JSON response and exit
    echo json_encode($response);
    exit;
}

// Check for pending reschedule requests
$pending_reschedule = null;
try {
    $stmt = $pdo->prepare("
        SELECT r.*, u.username as requester_name
        FROM RescheduleRequests r
        JOIN Users u ON r.requester_id = u.user_id
        WHERE r.session_id = ? AND r.status = 'pending'
    ");
    $stmt->execute([$session_id]);
    $pending_reschedule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Determine if current user is the requester
    if ($pending_reschedule) {
        $is_requester = ($pending_reschedule['requester_id'] == $user_id);
    }
} catch (PDOException $e) {
    error_log("Error checking reschedule requests: " . $e->getMessage());
}

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Session Details</h4>
                    <a href="session.php" class="btn btn-light btn-sm">Back to Sessions</a>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5><?= $is_coach ? 'Learner' : 'Coach' ?> Information</h5>
                            <p><strong>Name:</strong> <?= htmlspecialchars($is_coach ? $session['learner_name'] : $session['coach_name']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($is_coach ? $session['learner_email'] : $session['coach_email']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Session Information</h5>
                            <p><strong>Service:</strong> <?= htmlspecialchars($session['tier_name']) ?></p>
                            <p><strong>Price:</strong> $<?= number_format($session['price'], 2) ?></p>
                            <p><strong>Duration:</strong> <?= $session['duration'] ?? '60' ?> minutes</p>
                            <p><strong>Scheduled Time:</strong> <?= date('F j, Y g:i A', strtotime($session['scheduled_time'])) ?></p>
                            <p>
                                <strong>Status:</strong>
                                <span class="badge bg-<?= 
                                    $session['status'] === 'completed' ? 'success' : 
                                    ($session['status'] === 'cancelled' ? 'danger' : 'primary') 
                                ?>">
                                    <?= ucfirst($session['status']) ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <?php if (!empty($session['notes'])): ?>
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5>Session Notes</h5>
                                <div class="p-3 bg-light rounded">
                                    <?= nl2br(htmlspecialchars($session['notes'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($session['status'] === 'scheduled'): ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <?php if (strtotime($session['scheduled_time']) < time()): ?>
                                        <button class="btn btn-success complete-session" data-session-id="<?= $session['session_id'] ?>">
                                            Mark as Completed
                                        </button>
                                    <?php endif; ?>

                                    <?php if (!$pending_reschedule): ?>
                                        <button class="btn btn-warning reschedule-session" data-session-id="<?= $session['session_id'] ?>">
                                            Reschedule
                                        </button>
                                    <?php else: ?>
                                        <?php if ($is_requester): ?>
                                            <button class="btn btn-warning" disabled>
                                                Reschedule Pending
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-warning respond-reschedule" data-request-id="<?= $pending_reschedule['request_id'] ?>" data-response="approve">
                                                Approve Reschedule
                                            </button>
                                            <button class="btn btn-outline-danger respond-reschedule" data-request-id="<?= $pending_reschedule['request_id'] ?>" data-response="reject">
                                                Reject Reschedule
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-danger cancel-session" data-session-id="<?= $session['session_id'] ?>">
                                        Cancel Session
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!$is_coach && $session['status'] === 'completed' && !$hasRated): ?>
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5>Rate This Session</h5>
                                        <form id="ratingForm">
                                            <input type="hidden" id="session_id_rating" name="session_id" value="<?= $session_id ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Rating</label>
                                                <div class="rating-stars mb-3">
                                                    <i class="bi bi-star fs-3 rating-star" data-rating="1"></i>
                                                    <i class="bi bi-star fs-3 rating-star" data-rating="2"></i>
                                                    <i class="bi bi-star fs-3 rating-star" data-rating="3"></i>
                                                    <i class="bi bi-star fs-3 rating-star" data-rating="4"></i>
                                                    <i class="bi bi-star fs-3 rating-star" data-rating="5"></i>
                                                </div>
                                                <input type="hidden" id="rating_value" name="rating" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="feedback" class="form-label">Feedback (Optional)</label>
                                                <textarea class="form-control" id="feedback" name="feedback" rows="3"></textarea>
                                            </div>
                                            <button type="button" class="btn btn-primary" id="submitRating">Submit Rating</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rating Modal (for session completion) -->
<div class="modal fade" id="ratingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rate Your Session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="modalRatingForm">
                    <input type="hidden" id="modal_session_id" name="session_id">
                    <div class="mb-3">
                        <label class="form-label">Rating</label>
                        <div class="rating-stars mb-3">
                            <i class="bi bi-star fs-3 modal-rating-star" data-rating="1"></i>
                            <i class="bi bi-star fs-3 modal-rating-star" data-rating="2"></i>
                            <i class="bi bi-star fs-3 modal-rating-star" data-rating="3"></i>
                            <i class="bi bi-star fs-3 modal-rating-star" data-rating="4"></i>
                            <i class="bi bi-star fs-3 modal-rating-star" data-rating="5"></i>
                        </div>
                        <input type="hidden" id="modal_rating_value" name="rating" required>
                    </div>
                    <div class="mb-3">
                        <label for="modal_feedback" class="form-label">Feedback (Optional)</label>
                        <textarea class="form-control" id="modal_feedback" name="feedback" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitModalRating">Submit Rating</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 11000;">
    <div id="rescheduleToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <strong class="me-auto">Success</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <i class="bi bi-check-circle-fill me-2"></i>
            <span id="toastMessage">Your reschedule request has been submitted successfully.</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle complete button
    const completeButtons = document.querySelectorAll('.complete-session');
    completeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const sessionId = this.dataset.sessionId;
            
            // Check if the session time has passed
            const sessionTimeStr = '<?= $session['scheduled_time'] ?>';
            const sessionTime = new Date(sessionTimeStr);
            const now = new Date();
            
            if (sessionTime > now) {
                alert('This session cannot be marked as completed until after the scheduled time.');
                return;
            }
            
            // Show rating modal for learners, otherwise just complete
            if (<?= $is_coach ? 'true' : 'false' ?>) {
                // For coaches, just mark as completed
                if (confirm('Are you sure you want to mark this session as completed?')) {
                    updateSessionStatus(sessionId, 'completed');
                }
            } else {
                // For learners, show rating modal
                const modalEl = document.getElementById('ratingModal');
                document.getElementById('modal_session_id').value = sessionId;
                
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
        });
    });
    
    // Handle cancel button
    const cancelButtons = document.querySelectorAll('.cancel-session');
    cancelButtons.forEach(button => {
        button.addEventListener('click', function() {
            const sessionId = this.dataset.sessionId;
            if (confirm('Are you sure you want to cancel this session?')) {
                updateSessionStatus(sessionId, 'cancelled');
            }
        });
    });
    
    // Setup star rating in modal
    const modalRatingStars = document.querySelectorAll('.modal-rating-star');
    setupStarRating(modalRatingStars, 'modal_rating_value');
    
    // Setup star rating in page form
    const pageRatingStars = document.querySelectorAll('.rating-star');
    setupStarRating(pageRatingStars, 'rating_value');
    
    // Handle rating submission from modal
    document.getElementById('submitModalRating')?.addEventListener('click', function() {
        const sessionId = document.getElementById('modal_session_id').value;
        const rating = document.getElementById('modal_rating_value').value;
        const feedback = document.getElementById('modal_feedback').value;
        
        if (!rating) {
            alert('Please select a rating');
            return;
        }
        
        updateSessionStatus(sessionId, 'completed', rating, feedback);
    });
    
    // Handle rating submission from page form
    document.getElementById('submitRating')?.addEventListener('click', function() {
        const sessionId = document.getElementById('session_id_rating').value;
        const rating = document.getElementById('rating_value').value;
        const feedback = document.getElementById('feedback').value;
        
        if (!rating) {
            alert('Please select a rating');
            return;
        }
        
        updateSessionStatus(sessionId, 'completed', rating, feedback);
    });
    
    // Function to handle star rating UI
    function setupStarRating(stars, valueFieldId) {
        if (!stars.length) return;
        
        stars.forEach(star => {
            // Click handler
            star.addEventListener('click', function() {
                const rating = this.dataset.rating;
                document.getElementById(valueFieldId).value = rating;
                
                // Update visual state
                stars.forEach(s => {
                    if (s.dataset.rating <= rating) {
                        s.classList.remove('bi-star');
                        s.classList.add('bi-star-fill', 'text-warning');
                    } else {
                        s.classList.remove('bi-star-fill', 'text-warning');
                        s.classList.add('bi-star');
                    }
                });
            });
            
            // Hover effects
            star.addEventListener('mouseenter', function() {
                const hoverRating = this.dataset.rating;
                
                stars.forEach(s => {
                    if (s.dataset.rating <= hoverRating) {
                        s.classList.remove('bi-star');
                        s.classList.add('bi-star-fill', 'text-warning');
                    } else {
                        s.classList.remove('bi-star-fill', 'text-warning');
                        s.classList.add('bi-star');
                    }
                });
            });
            
            star.addEventListener('mouseleave', function() {
                const selectedRating = document.getElementById(valueFieldId).value;
                
                stars.forEach(s => {
                    if (selectedRating && s.dataset.rating <= selectedRating) {
                        s.classList.remove('bi-star');
                        s.classList.add('bi-star-fill', 'text-warning');
                    } else {
                        s.classList.remove('bi-star-fill', 'text-warning');
                        s.classList.add('bi-star');
                    }
                });
            });
        });
    }
    
    // Function to update session status
    function updateSessionStatus(sessionId, status, rating, feedback) {
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('session_id', sessionId);
        formData.append('status', status);
        
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
                alert(status === 'completed' ? 'Session marked as completed!' : 'Session cancelled successfully!');
                location.reload();
            } else {
                alert(result.message || 'Error updating session status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating session status: ' + error.message);
        });
    }

    // Handle reschedule button
    const rescheduleButtons = document.querySelectorAll('.reschedule-session');
    rescheduleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const rescheduleModal = new bootstrap.Modal(document.getElementById('rescheduleModal'));
            rescheduleModal.show();
        });
    });
    
    // Handle reschedule submission
    document.getElementById('submitReschedule')?.addEventListener('click', function() {
        const form = document.getElementById('rescheduleForm');
        const newTime = document.getElementById('new_time').value;
        const reason = document.getElementById('reason').value;
        const sessionId = <?= $session_id ?>; // Get the session ID from PHP
        
        // Hide previous error message if shown
        const errorAlert = document.getElementById('rescheduleErrorAlert');
        const errorMessage = document.getElementById('rescheduleErrorMessage');
        errorAlert.classList.add('d-none');
        
        // Validate inputs
        if (!newTime) {
            errorMessage.textContent = 'Please select a new time';
            errorAlert.classList.remove('d-none');
            return;
        }
        
        if (!reason) {
            errorMessage.textContent = 'Please provide a reason for rescheduling';
            errorAlert.classList.remove('d-none');
            return;
        }

        // Show loading state
        const submitButton = document.getElementById('submitReschedule');
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';
        
        // Set a timeout to prevent endless submission state
        const requestTimeout = setTimeout(function() {
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
            errorMessage.textContent = 'Request timed out. Please try again.';
            errorAlert.classList.remove('d-none');
        }, 20000); // 20 second timeout
        
        // Use native fetch instead of jQuery AJAX
        const formData = new FormData(form);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            clearTimeout(requestTimeout);
            
            if (!response.ok) {
                throw new Error(`Server returned ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(result => {
            // Reset button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
            
            if (result.success) {
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('rescheduleModal'));
                if (modal) {
                    modal.hide();
                }
                
                // Show toast notification
                const toastElement = document.getElementById('rescheduleToast');
                const toastMessage = document.getElementById('toastMessage');
                toastMessage.textContent = result.message || 'Your reschedule request has been submitted successfully.';
                
                const toast = new bootstrap.Toast(toastElement, { 
                    autohide: true,
                    delay: 5000
                });
                toast.show();
                
                console.log('Reschedule request successful, reloading page in 2 seconds...');
                
                // Reload the page after a delay to show changes
                setTimeout(function() {
                    window.location.href = window.location.pathname + '?id=' + sessionId + '&success=1';
                }, 2000);
            } else {
                // Show error message in the alert
                errorMessage.textContent = result.message || 'Error requesting reschedule';
                errorAlert.classList.remove('d-none');
            }
        })
        .catch(error => {
            clearTimeout(requestTimeout);
            console.error('Fetch Error:', error);
            
            // Reset button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
            
            // Show error in the alert
            if (error.message.includes('timeout') || error.name === 'TimeoutError') {
                errorMessage.textContent = 'Request timed out. Please try again.';
            } else if (error.message.includes('NetworkError')) {
                errorMessage.textContent = 'Could not connect to the server. Please check your internet connection.';
            } else {
                errorMessage.textContent = 'Error requesting reschedule: ' + error.message;
            }
            errorAlert.classList.remove('d-none');
        });
    });
    
    // Handle respond to reschedule request
    const respondRescheduleButtons = document.querySelectorAll('.respond-reschedule');
    respondRescheduleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const requestId = this.dataset.requestId;
            const response = this.dataset.response;
            
            if (confirm(`Are you sure you want to ${response} this reschedule request?`)) {
                const formData = new FormData();
                formData.append('action', 'respond_reschedule');
                formData.append('request_id', requestId);
                formData.append('response', response);
                
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
                        alert(result.message);
                        location.reload();
                    } else {
                        alert(result.message || 'Error responding to reschedule request');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error responding to reschedule request: ' + error.message);
                });
            }
        });
    });
});
</script>

<!-- Reschedule Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reschedule Session</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="rescheduleForm" method="post" onsubmit="return false;">
                    <input type="hidden" name="action" value="request_reschedule">
                    <input type="hidden" name="session_id" value="<?= $session_id ?>">
                    <div class="mb-3">
                        <label for="new_time" class="form-label">New Date & Time</label>
                        <input type="datetime-local" class="form-control" id="new_time" name="new_time" required>
                        <div class="form-text">Please select a time that works for you</div>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Rescheduling</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                        <div class="form-text">Please provide a brief reason for requesting to reschedule</div>
                    </div>
                </form>
                
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <small>Rescheduling requires approval from the other party. They will be notified of your request.</small>
                </div>
                
                <!-- Add error message display area -->
                <div id="rescheduleErrorAlert" class="alert alert-danger mt-3 d-none">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <span id="rescheduleErrorMessage">An error occurred. Please try again.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitReschedule">Submit Request</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user type and ID
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT user_type FROM Users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$is_coach = ($user['user_type'] === 'business');

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

                // Remove transaction handling
                try {
                    // First verify the session exists and get its details
                    $stmt = $pdo->prepare("
                        SELECT s.*, c.user_id as coach_user_id, u.username as learner_name, u2.username as coach_name
                        FROM sessions s 
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
                            'Permission denied. You must be either the learner or coach for this session. ' .
                            'Current user: ' . $current_user_id
                        );
                    }
                    
                    // Update session status
                    $stmt = $pdo->prepare("UPDATE sessions SET status = ? WHERE session_id = ?");
                    if (!$stmt->execute([$_POST['status'], $_POST['session_id']])) {
                        throw new Exception('Failed to update session status');
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
                            FROM sessions 
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
                        $stmt = $pdo->prepare("INSERT INTO sessions (inquiry_id, learner_id, coach_id, tier_id, scheduled_time, status) VALUES (?, ?, ?, ?, ?, 'scheduled')");
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
                        INSERT INTO sessions 
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
       FROM sessions s 
       JOIN Users u ON s.learner_id = u.user_id 
       JOIN ServiceTiers st ON s.tier_id = st.tier_id 
       WHERE s.coach_id = ?"
    : "SELECT s.*, u.username as coach_name, st.name as tier_name, st.price 
       FROM sessions s 
       JOIN Coaches c ON s.coach_id = c.coach_id 
       JOIN Users u ON c.user_id = u.user_id 
       JOIN ServiceTiers st ON s.tier_id = st.tier_id 
       WHERE s.learner_id = ?";

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
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

include __DIR__ . '/../includes/header.php';
?>

<!-- Add FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />

<div class="container mt-4">
    <h1 class="mb-4"><?= $is_coach ? 'My Teaching Sessions' : 'My Learning Sessions' ?></h1>
    
    <?php if (!$is_coach): ?>
    <!-- Session Booking Form -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Schedule New Session</h5>
        </div>
        <div class="card-body">
            <form id="scheduleForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="coach" class="form-label">Select Coach</label>
                            <select class="form-select" id="coach" name="coach_id" required>
                                <option value="">Choose a coach...</option>
                                <?php if (!empty($coaches)): ?>
                                    <?php foreach ($coaches as $coach): ?>
                                        <option value="<?= $coach['coach_id'] ?>" 
                                            <?= ($selected_coach_id === $coach['coach_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($coach['username']) ?> - <?= htmlspecialchars($coach['expertise']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">No coaches available</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="scheduled_time" class="form-label">Session Time</label>
                            <input type="datetime-local" class="form-control" id="scheduled_time" name="scheduled_time" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tier" class="form-label">Select Service Tier</label>
                            <select class="form-select" id="tier" name="tier_id" required>
                                <option value="">Choose a service tier...</option>
                                <?php if (!empty($coaches)): ?>
                                    <?php foreach ($coaches as $coach): ?>
                                        <?php if ($selected_coach_id === $coach['coach_id']): ?>
                                            <?php foreach ($coach['tiers'] as $tier): ?>
                                                <option value="<?= $tier['tier_id'] ?>" 
                                                    <?= ($selected_tier_id === $tier['tier_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($tier['tier_name']) ?> - $<?= number_format($tier['price'], 2) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">No tiers available</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Schedule Session</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Calendar View -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Session Calendar</h5>
        </div>
        <div class="card-body">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Sessions List -->
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Session History</h5>
            <div class="btn-group">
                <button class="btn btn-light btn-sm active" data-filter="all">All</button>
                <button class="btn btn-light btn-sm" data-filter="scheduled">Scheduled</button>
                <button class="btn btn-light btn-sm" data-filter="completed">Completed</button>
                <button class="btn btn-light btn-sm" data-filter="cancelled">Cancelled</button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
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
                        <?php foreach ($sessions as $session): ?>
                        <tr data-status="<?= strtolower($session['status']) ?>">
                            <td><?= htmlspecialchars($is_coach ? $session['learner_name'] : $session['coach_name']) ?></td>
                            <td><?= htmlspecialchars($session['tier_name']) ?></td>
                            <td><?= date('M j, Y g:i A', strtotime($session['scheduled_time'])) ?></td>
                            <td>$<?= number_format($session['price'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $session['status'] === 'completed' ? 'success' : 
                                    ($session['status'] === 'cancelled' ? 'danger' : 'primary') 
                                ?>">
                                    <?= ucfirst($session['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (strtolower($session['status']) === 'scheduled'): ?>
                                <button class="btn btn-sm btn-success complete-session" data-session-id="<?= $session['session_id'] ?>">
                                    Complete
                                </button>
                                <button class="btn btn-sm btn-danger cancel-session" data-session-id="<?= $session['session_id'] ?>">
                                    Cancel
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add new section for inquiries -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Service Inquiries</h5>
        </div>
        <div class="card-body">
            <?php if (!$is_coach): ?>
            <!-- Inquiry Form -->
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
                    <textarea class="form-control" id="message" name="message" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit Inquiry</button>
            </form>
            <?php endif; ?>
            
            <!-- Inquiry List -->
            <div class="table-responsive mt-4">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><?= $is_coach ? 'Learner' : 'Coach' ?></th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inquiries as $inquiry): ?>
                        <tr>
                            <td><?= htmlspecialchars($inquiry['username']) ?></td>
                            <td><?= htmlspecialchars($inquiry['message']) ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $inquiry['status'] === 'pending' ? 'warning' : 
                                    ($inquiry['status'] === 'accepted' ? 'success' : 
                                    ($inquiry['status'] === 'rejected' ? 'danger' : 'primary')) 
                                ?>">
                                    <?= ucfirst($inquiry['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y g:i A', strtotime($inquiry['created_at'])) ?></td>
                            <td>
                                <?php if ($inquiry['status'] === 'accepted' && !$is_coach): ?>
                                <button class="btn btn-sm btn-primary convert-inquiry" 
                                        data-inquiry-id="<?= $inquiry['inquiry_id'] ?>">
                                    Schedule Session
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
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

<!-- Add FullCalendar JS and its dependencies -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<!-- Add Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize calendar
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: <?= json_encode(array_map(function($session) use ($is_coach) {
            return [
                'title' => ($is_coach ? $session['learner_name'] : $session['coach_name']) . ' - ' . $session['tier_name'],
                'start' => $session['scheduled_time'],
                'backgroundColor' => $session['status'] === 'completed' ? '#198754' : 
                                   ($session['status'] === 'cancelled' ? '#dc3545' : '#0d6efd'),
                'borderColor' => 'transparent'
            ];
        }, $sessions)) ?>,
        selectable: true,
        select: function(info) {
            if (document.getElementById('scheduled_time')) {
                document.getElementById('scheduled_time').value = info.startStr.slice(0, 16);
            }
        }
    });
    calendar.render();

    const coachSelect = document.getElementById('coach');
    const tierSelect = document.getElementById('tier');

    // Function to populate service tiers based on the selected coach
    function populateTiers(coachId) {
        tierSelect.innerHTML = '<option value="">Choose a service tier...</option>';
        if (!coachId) return;

        // Find the selected coach
        const selectedCoach = <?= json_encode($coaches) ?>.find(coach => coach.coach_id == coachId);

        if (selectedCoach && selectedCoach.tiers.length > 0) {
            selectedCoach.tiers.forEach(tier => {
                const option = document.createElement('option');
                option.value = tier.tier_id;
                option.text = `${tier.tier_name} - $${tier.price}`;
                if (tier.tier_id == <?= json_encode($selected_tier_id) ?>) {
                    option.selected = true;
                }
                tierSelect.appendChild(option);
            });
        } else {
            const option = document.createElement('option');
            option.value = '';
            option.text = 'No tiers available';
            tierSelect.appendChild(option);
        }
    }

    // Populate tiers when the page loads (if a coach is pre-selected)
    const selectedCoachId = <?= json_encode($selected_coach_id) ?>;
    if (selectedCoachId) {
        populateTiers(selectedCoachId);
    }

    // Populate tiers when the coach selection changes
    coachSelect.addEventListener('change', function() {
        const coachId = this.value;
        populateTiers(coachId);
    });

    // Handle session scheduling
    const scheduleForm = document.getElementById('scheduleForm');
    if (scheduleForm) {
        scheduleForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'schedule_session');
            
            try {
                const response = await fetch('session.php', {
                    method: 'POST',
                    body: formData
                });
                
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Invalid server response');
                }
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success message using Bootstrap toast
                    const toast = new bootstrap.Toast(document.getElementById('successToast'));
                    toast.show();
                    setTimeout(() => location.reload(), 1500); // Reload after 1.5 seconds
                } else {
                    alert(result.message || 'Error scheduling session');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error scheduling session: ' + error.message);
            }
        });
    }

    // Handle session status updates
    function bindSessionActions() {
        document.querySelectorAll('.complete-session, .cancel-session').forEach(button => {
            button.addEventListener('click', async function(e) {
                e.preventDefault();
                const sessionId = this.dataset.sessionId;
                const action = this.classList.contains('complete-session') ? 'complete' : 'cancel';
                
                if (action === 'complete') {
                    // Show rating modal for complete action
                    document.getElementById('session_id_rating').value = sessionId;
                    const ratingModal = new bootstrap.Modal(document.getElementById('ratingModal'));
                    ratingModal.show();
                } else {
                    // Handle cancel action
                    if (!confirm('Are you sure you want to cancel this session?')) {
                        return;
                    }
                    
                    const formData = new FormData();
                    formData.append('action', 'update_status');
                    formData.append('session_id', sessionId);
                    formData.append('status', 'cancelled');
                    
                    try {
                        const response = await fetch('session.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();
                        
                        if (result.success) {
                            location.reload();
                        } else {
                            alert(result.message || 'Error updating session status');
                        }
                    } catch (error) {
                        alert('Error updating session status');
                    }
                }
            });
        });
    }

    // Bind session actions on page load
    bindSessionActions();

    // Re-bind session actions after filtering
    document.querySelectorAll('[data-filter]').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelector('[data-filter].active').classList.remove('active');
            this.classList.add('active');
            
            const filter = this.dataset.filter;
            document.querySelectorAll('tbody tr').forEach(row => {
                const status = row.getAttribute('data-status');
                if (filter === 'all' || status === filter) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Re-bind session actions after filtering
            bindSessionActions();
        });
    });

    // Rating Modal Functionality
    const ratingStars = document.querySelectorAll('.rating-stars i');
    const ratingValue = document.getElementById('rating_value');
    let currentSessionId = null;

    // Star rating functionality
    ratingStars.forEach(star => {
        star.addEventListener('mouseover', function() {
            const rating = this.dataset.rating;
            highlightStars(rating);
        });

        star.addEventListener('click', function() {
            const rating = this.dataset.rating;
            ratingValue.value = rating;
            highlightStars(rating);
        });
    });

    document.querySelector('.rating-stars').addEventListener('mouseout', function() {
        highlightStars(ratingValue.value);
    });

    function highlightStars(rating) {
        ratingStars.forEach(star => {
            const starRating = star.dataset.rating;
            star.style.opacity = starRating <= rating ? '1' : '0.5';
        });
    }

    // Handle rating submission
    document.getElementById('submitRating').addEventListener('click', async function() {
        if (!ratingValue.value) {
            alert('Please select a rating before submitting');
            return;
        }

        const sessionId = document.getElementById('session_id_rating').value;
        if (!sessionId) {
            alert('Session ID is missing');
            return;
        }

        const submitButton = this;
        submitButton.disabled = true;
        submitButton.textContent = 'Submitting...';

        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('status', 'completed');
        formData.append('session_id', sessionId);
        formData.append('rating', ratingValue.value);
        formData.append('feedback', document.getElementById('feedback').value || '');
        
        try {
            const response = await fetch('session.php', {
                method: 'POST',
                body: formData
            });
            
            let result;
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                result = await response.json();
            } else {
                throw new Error('Server returned non-JSON response');
            }
            
            if (result.success) {
                alert('Rating submitted successfully!');
                const modal = bootstrap.Modal.getInstance(document.getElementById('ratingModal'));
                if (modal) {
                    modal.hide();
                }
                location.reload();
            } else {
                throw new Error(result.message || 'Error updating session status');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error submitting rating: ' + error.message);
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = 'Submit Rating';
        }
    });

    // Add JavaScript for inquiry handling
    document.getElementById('inquiryForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'submit_inquiry');
        
        try {
            const response = await fetch('session.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                location.reload();
            } else {
                alert(result.message);
            }
        } catch (error) {
            alert('Error submitting inquiry');
        }
    });

    document.querySelectorAll('.convert-inquiry').forEach(button => {
        button.addEventListener('click', function() {
            const inquiryId = this.dataset.inquiryId;
            // Show scheduling modal or redirect to scheduling page
        });
    });
});
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

<?php include __DIR__ . '/../includes/footer.php'; ?> 
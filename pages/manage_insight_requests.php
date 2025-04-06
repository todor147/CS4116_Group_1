<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$requests = [];

// Check if the user is a verified customer (has completed sessions with coaches)
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as session_count 
        FROM sessions 
        WHERE learner_id = ? AND status = 'completed'
    ");
    $stmt->execute([$user_id]);
    $session_count = $stmt->fetch(PDO::FETCH_ASSOC)['session_count'];
    $is_verified_customer = ($session_count > 0);
    
    if (!$is_verified_customer) {
        $error = "You need to complete at least one coaching session to become a verified customer.";
    } else {
        // Get user's privacy settings
        $stmt = $pdo->prepare("
            SELECT * FROM UserPrivacySettings WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        $privacy_settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$privacy_settings) {
            // Create default privacy settings if none exist
            $stmt = $pdo->prepare("
                INSERT INTO UserPrivacySettings 
                (user_id, allow_insight_requests, share_session_history, share_coach_ratings, public_profile) 
                VALUES (?, 1, 1, 1, 1)
            ");
            $stmt->execute([$user_id]);
            
            $privacy_settings = [
                'allow_insight_requests' => 1,
                'share_session_history' => 1,
                'share_coach_ratings' => 1,
                'public_profile' => 1
            ];
        }
        
        // Fetch insight requests for this user
        $stmt = $pdo->prepare("
            SELECT 
                cir.id, 
                cir.status, 
                cir.message,
                cir.created_at,
                requester.id as requester_id,
                requester.first_name as requester_first_name,
                requester.last_name as requester_last_name,
                requester.username as requester_username,
                coach.id as coach_id,
                coach.first_name as coach_first_name,
                coach.last_name as coach_last_name,
                (SELECT COUNT(*) FROM CustomerInsightMessages WHERE request_id = cir.id) as message_count
            FROM CustomerInsightRequests cir
            JOIN users requester ON cir.requester_id = requester.id
            JOIN users coach ON cir.coach_id = coach.id
            WHERE cir.verified_customer_id = ?
            ORDER BY 
                CASE 
                    WHEN cir.status = 'pending' THEN 0
                    WHEN cir.status = 'accepted' THEN 1
                    ELSE 2
                END,
                cir.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle request actions (accept/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($request_id <= 0) {
        $error = "Invalid request selected.";
    } elseif (!in_array($action, ['accept', 'reject'])) {
        $error = "Invalid action specified.";
    } else {
        try {
            // Verify the request belongs to this user and is in pending state
            $stmt = $pdo->prepare("
                SELECT * FROM CustomerInsightRequests
                WHERE id = ? AND verified_customer_id = ? AND status = 'pending'
            ");
            $stmt->execute([$request_id, $user_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                $error = "The request could not be found or has already been processed.";
            } else {
                // Update the request status
                $new_status = ($action === 'accept') ? 'accepted' : 'rejected';
                $stmt = $pdo->prepare("
                    UPDATE CustomerInsightRequests
                    SET status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$new_status, $request_id]);
                
                // If accepting, create initial message
                if ($action === 'accept') {
                    $requester_id = $request['requester_id'];
                    $welcome_message = "Thank you for your request. I'm happy to share my experience with this coach. What would you like to know specifically?";
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO CustomerInsightMessages 
                        (request_id, sender_id, recipient_id, message)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$request_id, $user_id, $requester_id, $welcome_message]);
                }
                
                $success = ($action === 'accept') 
                    ? "You have accepted the request. You can now message with the customer."
                    : "You have rejected the request.";
                
                // Refresh the requests list
                $stmt = $pdo->prepare("
                    SELECT 
                        cir.id, 
                        cir.status, 
                        cir.message,
                        cir.created_at,
                        requester.id as requester_id,
                        requester.first_name as requester_first_name,
                        requester.last_name as requester_last_name,
                        requester.username as requester_username,
                        coach.id as coach_id,
                        coach.first_name as coach_first_name,
                        coach.last_name as coach_last_name,
                        (SELECT COUNT(*) FROM CustomerInsightMessages WHERE request_id = cir.id) as message_count
                    FROM CustomerInsightRequests cir
                    JOIN users requester ON cir.requester_id = requester.id
                    JOIN users coach ON cir.coach_id = coach.id
                    WHERE cir.verified_customer_id = ?
                    ORDER BY 
                        CASE 
                            WHEN cir.status = 'pending' THEN 0
                            WHEN cir.status = 'accepted' THEN 1
                            ELSE 2
                        END,
                        cir.created_at DESC
                ");
                $stmt->execute([$user_id]);
                $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Manage Customer Insight Requests</h5>
                    <div>
                        <a href="user_privacy_settings.php" class="btn btn-light btn-sm me-2">Privacy Settings</a>
                        <a href="profile.php" class="btn btn-light btn-sm">Back to Profile</a>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <?php if ($is_verified_customer): ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h6 class="mb-1">Your Insight Requests</h6>
                                <p class="text-muted small mb-0">Manage requests from potential customers seeking insights about coaches</p>
                            </div>
                            <div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="allow_insights" 
                                           <?= isset($privacy_settings['allow_insight_requests']) && $privacy_settings['allow_insight_requests'] ? 'checked' : '' ?>
                                           onchange="window.location.href='user_privacy_settings.php'">
                                    <label class="form-check-label" for="allow_insights">Allow Insight Requests</label>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (isset($privacy_settings['allow_insight_requests']) && !$privacy_settings['allow_insight_requests']): ?>
                            <div class="alert alert-warning mb-4">
                                <i class="bi bi-exclamation-triangle"></i> You are not currently accepting new insight requests. 
                                <a href="user_privacy_settings.php" class="alert-link">Update your privacy settings</a> to allow requests.
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($requests)): ?>
                            <div class="text-center p-4 border rounded bg-light">
                                <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                                <p class="mt-2 mb-0">No insight requests found. When potential customers request insights about coaches you've worked with, they'll appear here.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Requester</th>
                                            <th>Coach</th>
                                            <th>Request Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($requests as $request): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="assets/profile/default-avatar.png" alt="User" class="rounded-circle me-2" width="40">
                                                        <div>
                                                            <div><?= htmlspecialchars($request['requester_first_name'] . ' ' . $request['requester_last_name']) ?></div>
                                                            <small class="text-muted">@<?= htmlspecialchars($request['requester_username']) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <img src="assets/profile/default-avatar.png" alt="Coach" class="rounded-circle me-2" width="40">
                                                        <div>
                                                            <div><?= htmlspecialchars($request['coach_first_name'] . ' ' . $request['coach_last_name']) ?></div>
                                                            <small class="text-muted">Coach</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($request['created_at'])) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= 
                                                        $request['status'] === 'pending' ? 'warning' : 
                                                        ($request['status'] === 'accepted' ? 'success' : 'secondary')
                                                    ?>">
                                                        <?= ucfirst($request['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($request['status'] === 'pending'): ?>
                                                        <button type="button" class="btn btn-sm btn-primary" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#requestModal<?= $request['id'] ?>">
                                                            View Details
                                                        </button>
                                                    <?php elseif ($request['status'] === 'accepted'): ?>
                                                        <a href="insight_messages.php?request_id=<?= $request['id'] ?>" class="btn btn-sm btn-success">
                                                            Messages
                                                            <?php if ($request['message_count'] > 0): ?>
                                                                <span class="badge bg-light text-dark ms-1"><?= $request['message_count'] ?></span>
                                                            <?php endif; ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-sm btn-secondary disabled">Rejected</button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Request Modals -->
                            <?php foreach ($requests as $request): ?>
                                <?php if ($request['status'] === 'pending'): ?>
                                    <div class="modal fade" id="requestModal<?= $request['id'] ?>" tabindex="-1" aria-labelledby="requestModalLabel<?= $request['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="requestModalLabel<?= $request['id'] ?>">Insight Request Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <img src="assets/profile/default-avatar.png" alt="User" class="rounded-circle me-3" width="60">
                                                        <div>
                                                            <h6 class="mb-1"><?= htmlspecialchars($request['requester_first_name'] . ' ' . $request['requester_last_name']) ?></h6>
                                                            <p class="text-muted mb-0">@<?= htmlspecialchars($request['requester_username']) ?></p>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Requesting insights about:</label>
                                                        <div class="d-flex align-items-center p-2 border rounded">
                                                            <img src="assets/profile/default-avatar.png" alt="Coach" class="rounded-circle me-2" width="40">
                                                            <div>
                                                                <div><?= htmlspecialchars($request['coach_first_name'] . ' ' . $request['coach_last_name']) ?></div>
                                                                <small class="text-muted">Coach</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Message:</label>
                                                        <div class="p-3 bg-light rounded">
                                                            <?= nl2br(htmlspecialchars($request['message'])) ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="alert alert-info">
                                                        <i class="bi bi-info-circle"></i> By accepting this request, you agree to share your insights about this coach with the requester.
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                                        <button type="submit" name="action" value="reject" class="btn btn-outline-secondary">Reject</button>
                                                        <button type="submit" name="action" value="accept" class="btn btn-primary">Accept</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
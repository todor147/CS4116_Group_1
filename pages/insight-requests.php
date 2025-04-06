<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';

if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle request approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if (!$request_id) {
        $error = "Invalid request";
    } elseif (!in_array($action, ['approve', 'reject'])) {
        $error = "Invalid action";
    } else {
        try {
            // Verify the request belongs to the current user
            $stmt = $pdo->prepare("
                SELECT request_id FROM CustomerInsightRequests 
                WHERE request_id = ? AND verified_customer_id = ?
            ");
            $stmt->execute([$request_id, $user_id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                $error = "Request not found or you don't have permission to manage it";
            } else {
                // Update request status
                $status = ($action === 'approve') ? 'accepted' : 'rejected';
                $stmt = $pdo->prepare("
                    UPDATE CustomerInsightRequests 
                    SET status = ? 
                    WHERE request_id = ?
                ");
                $stmt->execute([$status, $request_id]);
                
                if ($action === 'approve') {
                    // Get requester information to start a conversation
                    $stmt = $pdo->prepare("
                        SELECT cir.requester_id, cir.message, u.username 
                        FROM CustomerInsightRequests cir
                        JOIN Users u ON cir.requester_id = u.user_id
                        WHERE cir.request_id = ?
                    ");
                    $stmt->execute([$request_id]);
                    $requester = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Add the initial message to CustomerInsightMessages
                    $stmt = $pdo->prepare("
                        INSERT INTO CustomerInsightMessages
                        (request_id, sender_id, receiver_id, content, created_at, is_read)
                        VALUES (?, ?, ?, ?, NOW(), 0)
                    ");
                    $stmt->execute([
                        $request_id, 
                        $requester['requester_id'], 
                        $user_id, 
                        $requester['message']
                    ]);
                    
                    $success = "You have approved the insight request from " . htmlspecialchars($requester['username']) . ". You can now communicate with them.";
                } else {
                    $success = "You have rejected the insight request.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Get pending insight requests
$pending_requests = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            cir.request_id, cir.requester_id, cir.coach_id, cir.message, cir.created_at,
            requester.username as requester_username, requester.profile_image as requester_image,
            coach_user.username as coach_username, coach_user.profile_image as coach_image
        FROM CustomerInsightRequests cir
        JOIN Users requester ON cir.requester_id = requester.user_id
        JOIN Coaches coach ON cir.coach_id = coach.coach_id
        JOIN Users coach_user ON coach.user_id = coach_user.user_id
        WHERE cir.verified_customer_id = ? AND cir.status = 'pending'
        ORDER BY cir.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Get active insight conversations (accepted requests)
$active_insights = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            cir.request_id, cir.requester_id, cir.coach_id, cir.created_at,
            requester.username as requester_username, requester.profile_image as requester_image,
            coach_user.username as coach_username, coach_user.profile_image as coach_image,
            (SELECT COUNT(*) FROM CustomerInsightMessages cim 
             WHERE cim.request_id = cir.request_id 
             AND cim.receiver_id = ? 
             AND cim.is_read = 0) as unread_count,
            (SELECT content FROM CustomerInsightMessages cim 
             WHERE cim.request_id = cir.request_id 
             ORDER BY cim.created_at DESC LIMIT 1) as last_message,
            (SELECT created_at FROM CustomerInsightMessages cim 
             WHERE cim.request_id = cir.request_id 
             ORDER BY cim.created_at DESC LIMIT 1) as last_message_time
        FROM CustomerInsightRequests cir
        JOIN Users requester ON cir.requester_id = requester.user_id
        JOIN Coaches coach ON cir.coach_id = coach.coach_id
        JOIN Users coach_user ON coach.user_id = coach_user.user_id
        WHERE cir.verified_customer_id = ? AND cir.status = 'accepted'
        ORDER BY last_message_time DESC
    ");
    $stmt->execute([$user_id, $user_id]);
    $active_insights = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Get requests I've sent to other verified customers
$my_requests = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            cir.request_id, cir.verified_customer_id, cir.coach_id, cir.status, cir.created_at,
            customer.username as customer_username, customer.profile_image as customer_image,
            coach_user.username as coach_username, coach_user.profile_image as coach_image,
            (SELECT COUNT(*) FROM CustomerInsightMessages cim 
             WHERE cim.request_id = cir.request_id 
             AND cim.receiver_id = ? 
             AND cim.is_read = 0) as unread_count
        FROM CustomerInsightRequests cir
        JOIN Users customer ON cir.verified_customer_id = customer.user_id
        JOIN Coaches coach ON cir.coach_id = coach.coach_id
        JOIN Users coach_user ON coach.user_id = coach_user.user_id
        WHERE cir.requester_id = ?
        ORDER BY cir.created_at DESC
    ");
    $stmt->execute([$user_id, $user_id]);
    $my_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Customer Insights</h2>
            <p class="text-muted">Manage your customer insight requests and communications</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="profile.php" class="btn btn-outline-primary">
                <i class="bi bi-gear"></i> Manage Privacy Settings
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Pending Requests Section -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Pending Insight Requests</h5>
                    <?php if (!empty($pending_requests)): ?>
                        <span class="badge bg-light text-primary rounded-pill"><?= count($pending_requests) ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($pending_requests)): ?>
                        <div class="p-4 text-center text-muted">
                            <div class="mb-3">
                                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            </div>
                            <p>No pending requests</p>
                            <p class="small">When other users request insights about coaches you've worked with, they'll appear here.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($pending_requests as $request): ?>
                                <div class="list-group-item p-3">
                                    <div class="d-flex mb-2">
                                        <img src="../assets/images/profiles/<?= !empty($request['requester_image']) ? $request['requester_image'] : 'default.jpg' ?>" 
                                             alt="<?= htmlspecialchars($request['requester_username']) ?>" 
                                             class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($request['requester_username']) ?></h6>
                                            <p class="text-muted small mb-0">
                                                Requesting insights about 
                                                <strong><?= htmlspecialchars($request['coach_username']) ?></strong>
                                            </p>
                                            <small class="text-muted"><?= date('M j, Y g:i a', strtotime($request['created_at'])) ?></small>
                                        </div>
                                    </div>
                                    <div class="mb-3 p-3 bg-light rounded">
                                        <?= nl2br(htmlspecialchars($request['message'])) ?>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <form method="POST" class="me-2">
                                            <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Reject</button>
                                        </form>
                                        <form method="POST">
                                            <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm">Approve & Start Conversation</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Active Insight Conversations -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Active Insight Conversations</h5>
                    <?php if (!empty($active_insights)): ?>
                        <span class="badge bg-light text-primary rounded-pill"><?= count($active_insights) ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($active_insights)): ?>
                        <div class="p-4 text-center text-muted">
                            <div class="mb-3">
                                <i class="bi bi-chat-quote" style="font-size: 2rem;"></i>
                            </div>
                            <p>No active conversations</p>
                            <p class="small">When you approve insight requests, your conversations will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($active_insights as $insight): ?>
                                <a href="insight-conversation.php?request_id=<?= $insight['request_id'] ?>" 
                                   class="list-group-item list-group-item-action p-3 <?= $insight['unread_count'] > 0 ? 'bg-light' : '' ?>">
                                    <div class="d-flex">
                                        <div class="position-relative me-3">
                                            <img src="../assets/images/profiles/<?= !empty($insight['requester_image']) ? $insight['requester_image'] : 'default.jpg' ?>" 
                                                 alt="<?= htmlspecialchars($insight['requester_username']) ?>" 
                                                 class="rounded-circle" style="width: 48px; height: 48px; object-fit: cover;">
                                            <?php if ($insight['unread_count'] > 0): ?>
                                                <span class="position-absolute top-0 end-0 translate-middle p-1 bg-danger rounded-circle">
                                                    <span class="visually-hidden">New alerts</span>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="d-flex justify-content-between">
                                                <h6 class="mb-0"><?= htmlspecialchars($insight['requester_username']) ?></h6>
                                                <small class="text-muted"><?= get_time_ago(strtotime($insight['last_message_time'])) ?></small>
                                            </div>
                                            <p class="mb-0 small text-muted">
                                                Re: <?= htmlspecialchars($insight['coach_username']) ?>
                                            </p>
                                            <p class="mb-0 small text-truncate <?= $insight['unread_count'] > 0 ? 'fw-bold' : 'text-muted' ?>">
                                                <?= !empty($insight['last_message']) ? htmlspecialchars($insight['last_message']) : 'No messages yet' ?>
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- My Requests Section -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">My Insight Requests</h5>
                    <?php if (!empty($my_requests)): ?>
                        <span class="badge bg-light text-primary rounded-pill"><?= count($my_requests) ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($my_requests)): ?>
                        <div class="p-4 text-center text-muted">
                            <div class="mb-3">
                                <i class="bi bi-send" style="font-size: 2rem;"></i>
                            </div>
                            <p>You haven't sent any insight requests yet</p>
                            <a href="coach-search.php" class="btn btn-primary btn-sm mt-2">Find a Coach</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Coach</th>
                                        <th>Status</th>
                                        <th>Date Requested</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_requests as $req): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../assets/images/profiles/<?= !empty($req['customer_image']) ? $req['customer_image'] : 'default.jpg' ?>" 
                                                         alt="<?= htmlspecialchars($req['customer_username']) ?>" 
                                                         class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                                    <?= htmlspecialchars($req['customer_username']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../assets/images/profiles/<?= !empty($req['coach_image']) ? $req['coach_image'] : 'default.jpg' ?>" 
                                                         alt="<?= htmlspecialchars($req['coach_username']) ?>" 
                                                         class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                                    <?= htmlspecialchars($req['coach_username']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($req['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php elseif ($req['status'] === 'accepted'): ?>
                                                    <span class="badge bg-success">Accepted</span>
                                                    <?php if ($req['unread_count'] > 0): ?>
                                                        <span class="badge bg-danger ms-1"><?= $req['unread_count'] ?> new</span>
                                                    <?php endif; ?>
                                                <?php elseif ($req['status'] === 'rejected'): ?>
                                                    <span class="badge bg-danger">Rejected</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($req['created_at'])) ?></td>
                                            <td>
                                                <?php if ($req['status'] === 'accepted'): ?>
                                                    <a href="insight-conversation.php?request_id=<?= $req['request_id'] ?>" class="btn btn-primary btn-sm">
                                                        View Conversation
                                                    </a>
                                                <?php elseif ($req['status'] === 'rejected'): ?>
                                                    <button class="btn btn-outline-secondary btn-sm" disabled>Request Declined</button>
                                                <?php else: ?>
                                                    <button class="btn btn-outline-secondary btn-sm" disabled>Awaiting Response</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function for time formatting
function get_time_ago($timestamp) {
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 172800) return 'Yesterday';
    
    return date('M j', $timestamp);
}

include __DIR__ . '/../includes/footer.php';
?> 
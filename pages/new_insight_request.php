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
$coach_id = isset($_GET['coach_id']) ? intval($_GET['coach_id']) : 0;
$error = '';
$success = '';
$verified_customers = [];

// Validate coach_id
if ($coach_id <= 0) {
    $error = "Invalid coach selected. Please go back and select a valid coach.";
} else {
    try {
        // Check if the coach exists and is a coach
        $stmt = $pdo->prepare("
            SELECT id, first_name, last_name, role 
            FROM users 
            WHERE id = ? AND role = 'coach'
        ");
        $stmt->execute([$coach_id]);
        $coach = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$coach) {
            $error = "The selected coach does not exist or is not a coach.";
        } else {
            // Get verified customers who have worked with this coach
            $stmt = $pdo->prepare("
                SELECT u.id, u.first_name, u.last_name, u.username, COUNT(s.id) as session_count
                FROM users u
                JOIN Sessions s ON u.id = s.learner_id
                JOIN UserPrivacySettings ps ON u.id = ps.user_id
                WHERE s.coach_id = ? 
                AND s.status = 'completed'
                AND ps.allow_insight_requests = 1
                AND u.id != ?
                GROUP BY u.id
                HAVING COUNT(s.id) > 0
            ");
            $stmt->execute([$coach_id, $user_id]);
            $verified_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Check if user already has pending or accepted requests for this coach
            $stmt = $pdo->prepare("
                SELECT cir.id, cir.status, u.first_name, u.last_name
                FROM CustomerInsightRequests cir
                JOIN users u ON cir.verified_customer_id = u.id
                WHERE cir.requester_id = ? 
                AND cir.coach_id = ?
                AND cir.status IN ('pending', 'accepted')
            ");
            $stmt->execute([$user_id, $coach_id]);
            $existing_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $verified_customer_id = isset($_POST['verified_customer_id']) ? intval($_POST['verified_customer_id']) : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    if ($verified_customer_id <= 0) {
        $error = "Please select a verified customer to request insights from.";
    } elseif (empty($message)) {
        $error = "Please enter a message explaining why you're requesting insights.";
    } else {
        try {
            // Check if this customer is valid and allows insight requests
            $stmt = $pdo->prepare("
                SELECT u.id
                FROM users u
                JOIN UserPrivacySettings ps ON u.id = ps.user_id
                WHERE u.id = ? AND ps.allow_insight_requests = 1
            ");
            $stmt->execute([$verified_customer_id]);
            $valid_customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$valid_customer) {
                $error = "This customer does not allow insight requests.";
            } else {
                // Check if a request already exists
                $stmt = $pdo->prepare("
                    SELECT id FROM CustomerInsightRequests
                    WHERE requester_id = ? AND verified_customer_id = ? AND coach_id = ?
                ");
                $stmt->execute([$user_id, $verified_customer_id, $coach_id]);
                $existing_request = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing_request) {
                    $error = "You have already sent a request to this customer about this coach.";
                } else {
                    // Insert the new request
                    $stmt = $pdo->prepare("
                        INSERT INTO CustomerInsightRequests 
                        (requester_id, verified_customer_id, coach_id, message)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$user_id, $verified_customer_id, $coach_id, $message]);
                    
                    $success = "Your insight request has been sent successfully. You will be notified if the customer accepts your request.";
                }
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
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Request Customer Insights</h5>
                    <a href="view_coach.php?id=<?= $coach_id ?>" class="btn btn-light btn-sm">Back to Coach Profile</a>
                </div>
                
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php else: ?>
                        <?php if (!empty($coach) && empty($error)): ?>
                            <div class="mb-4">
                                <h6>Request insights about:</h6>
                                <div class="d-flex align-items-center p-3 bg-light rounded">
                                    <div class="flex-shrink-0">
                                        <img src="assets/profile/default-avatar.png" alt="Coach" class="rounded-circle" width="60">
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-1"><?= htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']) ?></h6>
                                        <p class="text-muted mb-0">Coach</p>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($existing_requests)): ?>
                                <div class="alert alert-info mb-4">
                                    <h6><i class="bi bi-info-circle"></i> Your Existing Requests</h6>
                                    <p>You already have insight requests for this coach:</p>
                                    <ul class="mb-0">
                                        <?php foreach ($existing_requests as $request): ?>
                                            <li>
                                                <?= htmlspecialchars($request['first_name'] . ' ' . $request['last_name']) ?> 
                                                - <span class="badge bg-<?= $request['status'] === 'pending' ? 'warning' : 'success' ?>">
                                                    <?= ucfirst($request['status']) ?>
                                                </span>
                                                <?php if ($request['status'] === 'accepted'): ?>
                                                    <a href="insight_messages.php?request_id=<?= $request['id'] ?>" class="btn btn-sm btn-primary ms-2">
                                                        View Messages
                                                    </a>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (count($verified_customers) > 0): ?>
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="verified_customer_id" class="form-label">Select a Verified Customer:</label>
                                        <select class="form-select" id="verified_customer_id" name="verified_customer_id" required>
                                            <option value="">-- Select a verified customer --</option>
                                            <?php foreach ($verified_customers as $customer): ?>
                                                <option value="<?= $customer['id'] ?>">
                                                    <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?> 
                                                    (<?= $customer['session_count'] ?> completed sessions)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">These are verified customers who have completed sessions with this coach</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="message" class="form-label">Your Message:</label>
                                        <textarea class="form-control" id="message" name="message" rows="4" placeholder="Explain why you're interested in learning about their experience with this coach..." required></textarea>
                                        <div class="form-text">Be specific about what you'd like to know. This helps the customer decide whether to accept your request.</div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">Send Request</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i> There are no verified customers available who allow insight requests for this coach.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
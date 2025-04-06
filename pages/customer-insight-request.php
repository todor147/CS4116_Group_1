<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';

if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$coach_id = isset($_GET['coach_id']) ? (int)$_GET['coach_id'] : null;
$error = '';
$success = '';

// Get coach information
$coach = null;
if ($coach_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.coach_id, u.user_id, u.username, u.profile_image 
            FROM Coaches c
            JOIN Users u ON c.user_id = u.user_id
            WHERE c.coach_id = ?
        ");
        $stmt->execute([$coach_id]);
        $coach = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$coach) {
            $error = "Coach not found";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get verified customers of this coach (users who have completed sessions and left reviews)
$verified_customers = [];
if ($coach) {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.user_id, u.username, u.profile_image, 
                   (SELECT COUNT(*) FROM Reviews r WHERE r.user_id = u.user_id AND r.coach_id = ?) as review_count,
                   (SELECT COUNT(*) FROM sessions s WHERE s.learner_id = u.user_id AND s.coach_id = ? AND s.status = 'completed') as session_count
            FROM Users u
            JOIN sessions s ON s.learner_id = u.user_id
            WHERE s.coach_id = ? 
            AND s.status = 'completed'
            AND u.user_id != ?
            AND EXISTS (
                SELECT 1 FROM UserPrivacySettings ups 
                WHERE ups.user_id = u.user_id 
                AND ups.allow_insight_requests = 1
            )
            ORDER BY session_count DESC, review_count DESC
        ");
        $stmt->execute([$coach_id, $coach_id, $coach_id, $user_id]);
        $verified_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verified_customer_id = isset($_POST['verified_customer_id']) ? (int)$_POST['verified_customer_id'] : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    if (!$verified_customer_id) {
        $error = "Please select a verified customer to request insights from";
    } elseif (empty($message)) {
        $error = "Please provide a message with your request";
    } else {
        try {
            // Check if a request already exists
            $stmt = $pdo->prepare("
                SELECT request_id FROM CustomerInsightRequests 
                WHERE requester_id = ? AND verified_customer_id = ? AND coach_id = ?
                AND status != 'rejected'
            ");
            $stmt->execute([$user_id, $verified_customer_id, $coach_id]);
            $existing_request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_request) {
                $error = "You already have a pending or accepted request with this customer for this coach";
            } else {
                // Create a new insight request
                $stmt = $pdo->prepare("
                    INSERT INTO CustomerInsightRequests 
                    (requester_id, verified_customer_id, coach_id, status, message, created_at)
                    VALUES (?, ?, ?, 'pending', ?, NOW())
                ");
                $stmt->execute([$user_id, $verified_customer_id, $coach_id, $message]);
                
                $success = "Your insight request has been sent successfully! You'll be notified when the customer responds.";
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
                    <a href="coach-profile.php?id=<?= $coach_id ?>" class="btn btn-light btn-sm">Back to Coach Profile</a>
                </div>
                
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <?php if ($coach): ?>
                        <div class="mb-4">
                            <h5>Request insights about:</h5>
                            <div class="d-flex align-items-center mt-3">
                                <?php 
                                $profile_image = !empty($coach['profile_image']) ? $coach['profile_image'] : 'default.jpg';
                                $image_path = "../assets/images/profiles/{$profile_image}";
                                ?>
                                <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($coach['username']) ?>" class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($coach['username']) ?></h6>
                                    <span class="badge bg-success">Coach</span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (empty($verified_customers)): ?>
                            <div class="alert alert-info">
                                <p class="mb-0">There are no verified customers available for this coach who allow insight requests.</p>
                                <p class="small mb-0 mt-2">Verified customers are those who have completed sessions with this coach and have enabled insight sharing in their privacy settings.</p>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="verified_customer_id" class="form-label">Select a verified customer to contact:</label>
                                    <select class="form-select" id="verified_customer_id" name="verified_customer_id" required>
                                        <option value="">-- Select a verified customer --</option>
                                        <?php foreach ($verified_customers as $customer): ?>
                                            <option value="<?= $customer['user_id'] ?>">
                                                <?= htmlspecialchars($customer['username']) ?> 
                                                (<?= $customer['session_count'] ?> sessions, <?= $customer['review_count'] ?> reviews)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">These customers have completed sessions with this coach and allow insight requests.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="message" class="form-label">Your message:</label>
                                    <textarea class="form-control" id="message" name="message" rows="4" placeholder="Introduce yourself and explain what you'd like to know about this coach..." required></textarea>
                                    <div class="form-text">Be specific about what aspects of the coach you'd like insights on (teaching style, communication, expertise, etc).</div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Submit Request</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <p>No coach was specified or the coach could not be found.</p>
                            <a href="coach-search.php" class="btn btn-primary mt-2">Find a Coach</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';

// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';
$session = null;
$review = null;

// Get session ID from URL parameter
$session_id = $_GET['id'] ?? null;

if (!$session_id) {
    header('Location: dashboard.php');
    exit;
}

try {
    // Get session details
    $stmt = $pdo->prepare("
        SELECT s.*, 
               c.coach_id,
               u_coach.username as coach_name,
               u_learner.username as learner_name,
               st.name as service_name,
               st.price
        FROM Sessions s
        JOIN Coaches c ON s.coach_id = c.coach_id
        JOIN Users u_coach ON c.user_id = u_coach.user_id
        JOIN Users u_learner ON s.learner_id = u_learner.user_id
        JOIN ServiceTiers st ON s.tier_id = st.tier_id
        WHERE s.session_id = ?
    ");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if session exists and user has access
    if (!$session || ($session['learner_id'] != $_SESSION['user_id'] && 
        $session['coach_id'] != (isset($coach['coach_id']) ? $coach['coach_id'] : null))) {
        header('Location: dashboard.php');
        exit;
    }

    // Check if there's already a review for this session
    $stmt = $pdo->prepare("SELECT * FROM Reviews WHERE session_id = ?");
    $stmt->execute([$session_id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);

    // Handle session status updates (for coaches)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'complete' && $session['status'] === 'scheduled') {
            $stmt = $pdo->prepare("UPDATE Sessions SET status = 'completed' WHERE session_id = ?");
            $stmt->execute([$session_id]);
            $success = 'Session marked as completed!';
            $session['status'] = 'completed';
        } elseif ($_POST['action'] === 'cancel' && $session['status'] === 'scheduled') {
            $stmt = $pdo->prepare("UPDATE Sessions SET status = 'cancelled' WHERE session_id = ?");
            $stmt->execute([$session_id]);
            $success = 'Session cancelled successfully.';
            $session['status'] = 'cancelled';
        }
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Session Details</h4>
                    <span class="badge <?= 
                        $session['status'] === 'scheduled' ? 'bg-warning' : 
                        ($session['status'] === 'completed' ? 'bg-success' : 'bg-danger') 
                    ?>">
                        <?= ucfirst($session['status']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Coach</h5>
                            <p><?= htmlspecialchars($session['coach_name']) ?></p>
                            
                            <h5>Learner</h5>
                            <p><?= htmlspecialchars($session['learner_name']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Service</h5>
                            <p><?= htmlspecialchars($session['service_name']) ?></p>
                            
                            <h5>Price</h5>
                            <p>$<?= number_format($session['price'], 2) ?></p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Date & Time</h5>
                            <p><?= date('F j, Y g:i A', strtotime($session['scheduled_time'])) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Duration</h5>
                            <p><?= $session['duration'] ?> minutes</p>
                        </div>
                    </div>

                    <?php if ($session['status'] === 'scheduled'): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            This session is scheduled to take place on <?= date('F j, Y', strtotime($session['scheduled_time'])) ?> 
                            at <?= date('g:i A', strtotime($session['scheduled_time'])) ?>.
                        </div>
                        
                        <?php if ($session['coach_id'] == ($_SESSION['coach_id'] ?? null)): ?>
                            <form method="POST" class="d-flex gap-2">
                                <button type="submit" name="action" value="complete" class="btn btn-success flex-grow-1">
                                    Mark as Completed
                                </button>
                                <button type="submit" name="action" value="cancel" class="btn btn-danger flex-grow-1">
                                    Cancel Session
                                </button>
                            </form>
                        <?php endif; ?>

                    <?php elseif ($session['status'] === 'completed'): ?>
                        <?php if ($session['learner_id'] == $_SESSION['user_id']): ?>
                            <?php if (!$review): ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>
                                    This session has been completed. Would you like to leave a review?
                                    <a href="review.php?session_id=<?= $session_id ?>" class="btn btn-primary ms-3">
                                        Leave a Review
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Your Review</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star<?= ($i <= $review['rating']) ? '-fill' : '' ?> text-warning"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <?php if ($review['comment']): ?>
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-x-circle me-2"></i>
                            This session has been cancelled.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
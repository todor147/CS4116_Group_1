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

// Get session ID from URL parameter
$session_id = $_GET['session_id'] ?? null;

if (!$session_id) {
    header('Location: dashboard.php');
    exit;
}

try {
    // Get session details and check if user is authorized to review
    $stmt = $pdo->prepare("
        SELECT s.*, c.coach_id, u.username as coach_name, st.name as service_name
        FROM Sessions s
        JOIN Coaches c ON s.coach_id = c.coach_id
        JOIN Users u ON c.user_id = u.user_id
        JOIN ServiceTiers st ON s.tier_id = st.tier_id
        WHERE s.session_id = ? AND s.learner_id = ? AND s.status = 'completed'
    ");
    $stmt->execute([$session_id, $_SESSION['user_id']]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if session exists and belongs to the current user
    if (!$session) {
        header('Location: dashboard.php');
        exit;
    }

    // Check if review already exists
    $stmt = $pdo->prepare("SELECT * FROM Reviews WHERE session_id = ?");
    $stmt->execute([$session_id]);
    if ($stmt->fetch()) {
        header('Location: dashboard.php?message=already_reviewed');
        exit;
    }

    // Handle review submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rating = $_POST['rating'] ?? null;
        $comment = $_POST['comment'] ?? '';

        if (!$rating || $rating < 1 || $rating > 5) {
            $error = 'Please provide a valid rating (1-5 stars)';
        } else {
            // Insert review
            $stmt = $pdo->prepare("
                INSERT INTO Reviews (session_id, user_id, coach_id, rating, comment, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $session_id,
                $_SESSION['user_id'],
                $session['coach_id'],
                $rating,
                $comment
            ]);

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

            $success = 'Thank you for your review!';
            header('Refresh: 2; URL=dashboard.php');
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
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">Review Your Session</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php else: ?>
                        <div class="mb-4">
                            <h5>Session Details</h5>
                            <p class="mb-1"><strong>Coach:</strong> <?= htmlspecialchars($session['coach_name']) ?></p>
                            <p class="mb-1"><strong>Service:</strong> <?= htmlspecialchars($session['service_name']) ?></p>
                            <p><strong>Date:</strong> <?= date('F j, Y g:i A', strtotime($session['scheduled_time'])) ?></p>
                        </div>

                        <form method="POST" action="">
                            <div class="mb-4">
                                <label class="form-label">Rating</label>
                                <div class="star-rating">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required>
                                        <label for="star<?= $i ?>" title="<?= $i ?> stars">
                                            <i class="bi bi-star-fill"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="comment" class="form-label">Your Review (Optional)</label>
                                <textarea class="form-control" id="comment" name="comment" rows="4" 
                                    placeholder="Share your experience with this coach..."></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Submit Review</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 0.25rem;
}

.star-rating input {
    display: none;
}

.star-rating label {
    cursor: pointer;
    font-size: 1.5rem;
    color: #ddd;
}

.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
    color: #ffc107;
}

.star-rating label i {
    transition: color 0.2s ease;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
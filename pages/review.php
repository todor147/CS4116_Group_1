<?php
// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../includes/db_connection.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';
$session = null;

// Get session ID and coach ID from URL parameters
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : null;
$coach_id = isset($_GET['coach_id']) ? (int)$_GET['coach_id'] : null;

// Check for valid session ID
if (!$session_id || !$coach_id) {
    $_SESSION['error_message'] = "Invalid request. Missing session or coach information.";
    header('Location: session.php');
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
        $_SESSION['error_message'] = "You don't have permission to review this session or it doesn't exist.";
        header('Location: session.php');
        exit;
    }

    // Check if review already exists
    $stmt = $pdo->prepare("SELECT * FROM Reviews WHERE session_id = ? AND user_id = ?");
    $stmt->execute([$session_id, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $_SESSION['info_message'] = "You have already reviewed this session.";
        header('Location: session.php');
        exit;
    }

    // Fetch banned words
    $stmt = $pdo->prepare("SELECT word FROM BannedWords");
    $stmt->execute();
    $bannedWords = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Function to check for banned words
    function containsBannedWords($text, $bannedWords) {
        foreach ($bannedWords as $word) {
            if (stripos($text, $word) !== false) {
                return true;
            }
        }
        return false;
    }

    // Handle review submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

        if (!$rating || $rating < 1 || $rating > 5) {
            $error = 'Please provide a valid rating (1-5 stars)';
        } elseif (containsBannedWords($comment, $bannedWords)) {
            $error = 'Your review contains inappropriate language. Please revise your comment.';
        } else {
            try {
                // Begin transaction
                $pdo->beginTransaction();
                
                // Insert review with pending status for moderation
                $stmt = $pdo->prepare("
                    INSERT INTO Reviews (session_id, user_id, coach_id, rating, comment, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'pending', NOW())
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
                        WHERE coach_id = ? AND status != 'rejected'
                    )
                    WHERE coach_id = ?
                ");
                $stmt->execute([$session['coach_id'], $session['coach_id']]);
                
                // Commit the transaction
                $pdo->commit();

                // Notify user of pending review
                $success = 'Your review has been submitted and is pending approval.';
            } catch (PDOException $innerException) {
                // Rollback on error
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                
                $error = 'Error saving review: ' . $innerException->getMessage();
            }
        }
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Review Your Session</h4>
                    <a href="session.php" class="btn btn-light btn-sm">Back to Sessions</a>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <div class="text-center mt-3">
                            <a href="session.php" class="btn btn-primary">Back to Sessions</a>
                        </div>
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
                                <div class="rating-stars mb-3">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required>
                                        <label for="star<?= $i ?>" title="<?= $i ?> stars">
                                            <i class="bi bi-star"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                                <div class="selected-rating mb-2">
                                    <span id="rating-value">No rating selected</span>
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
.rating-stars {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 0.5rem;
}

.rating-stars input {
    display: none;
}

.rating-stars label {
    cursor: pointer;
    font-size: 2rem;
    color: #dddddd;
}

.rating-stars label:hover,
.rating-stars label:hover ~ label,
.rating-stars input:checked ~ label {
    color: #ffc107;
}

.rating-stars label i {
    transition: color 0.2s ease;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ratingInputs = document.querySelectorAll('input[name="rating"]');
    const ratingLabels = document.querySelectorAll('.rating-stars label i');
    const ratingValue = document.getElementById('rating-value');
    
    // Update stars when rating is selected
    ratingInputs.forEach(input => {
        input.addEventListener('change', function() {
            const value = this.value;
            ratingValue.textContent = `${value} star${value > 1 ? 's' : ''}`;
            
            // Update all stars
            ratingLabels.forEach((label, index) => {
                // Convert from 5-star display order to 1-5 value
                const starValue = 5 - index;
                
                if (starValue <= value) {
                    label.classList.remove('bi-star');
                    label.classList.add('bi-star-fill', 'text-warning');
                } else {
                    label.classList.remove('bi-star-fill', 'text-warning');
                    label.classList.add('bi-star');
                }
            });
        });
    });
    
    // Handle hover effects
    ratingLabels.forEach((label, index) => {
        const starElem = label.parentElement;
        
        starElem.addEventListener('mouseenter', function() {
            // Convert from display order to value (5-index gives the star value)
            const hoverValue = 5 - index;
            
            ratingLabels.forEach((starLabel, i) => {
                const starValue = 5 - i;
                
                if (starValue <= hoverValue) {
                    starLabel.classList.remove('bi-star');
                    starLabel.classList.add('bi-star-fill', 'text-warning');
                } else {
                    starLabel.classList.remove('bi-star-fill', 'text-warning');
                    starLabel.classList.add('bi-star');
                }
            });
        });
    });
    
    // When mouse leaves the container, restore the selected state
    const container = document.querySelector('.rating-stars');
    container.addEventListener('mouseleave', function() {
        const selectedRating = document.querySelector('input[name="rating"]:checked');
        
        if (selectedRating) {
            const value = selectedRating.value;
            
            ratingLabels.forEach((label, index) => {
                const starValue = 5 - index;
                
                if (starValue <= value) {
                    label.classList.remove('bi-star');
                    label.classList.add('bi-star-fill', 'text-warning');
                } else {
                    label.classList.remove('bi-star-fill', 'text-warning');
                    label.classList.add('bi-star');
                }
            });
        } else {
            // No rating selected, reset all stars
            ratingLabels.forEach(label => {
                label.classList.remove('bi-star-fill', 'text-warning');
                label.classList.add('bi-star');
            });
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?> 

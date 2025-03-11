<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';

// Initialize variables
$coach = null;
$error_message = '';
$average_rating = 0;
$review_count = 0;
$coach_services = [];
$reviews = [];

// Check if coach_id is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $coach_id = $_GET['id'];

    try {
        // Get coach information
        $stmt = $pdo->prepare("
            SELECT c.*, u.username, u.email, u.profile_image, u.bio
            FROM Coaches c
            JOIN Users u ON c.user_id = u.user_id
            WHERE c.coach_id = ?
        ");
        $stmt->execute([$coach_id]);
        $coach = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$coach) {
            // Coach not found, set error message
            $error_message = "Coach not found";
        } else {
            // Get service tiers
            $stmt = $pdo->prepare("
                SELECT * FROM ServiceTiers
                WHERE coach_id = ?
                ORDER BY price ASC
            ");
            $stmt->execute([$coach_id]);
            $coach_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate average rating and review count
            try {
                $stmt = $pdo->prepare("
                    SELECT AVG(rating) as avg_rating, COUNT(*) as review_count
                    FROM Reviews
                    WHERE coach_id = ?
                ");
                $stmt->execute([$coach_id]);
                $rating_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($rating_data) {
                    // Round to nearest 0.5
                    $average_rating = $rating_data['avg_rating'] ? round($rating_data['avg_rating'] * 2) / 2 : 0;
                    $review_count = $rating_data['review_count'];
                }
            } catch (PDOException $e) {
                // Just log the error, don't stop page from loading
                error_log("Error getting ratings: " . $e->getMessage());
            }

            // Get reviews
            $stmt = $pdo->prepare("
                SELECT r.*, u.username, u.profile_image
                FROM Reviews r
                JOIN Users u ON r.user_id = u.user_id
                WHERE r.coach_id = ?
                ORDER BY r.created_at DESC
                LIMIT 5
            ");
            $stmt->execute([$coach_id]);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $error_message = "Error fetching coach data: " . $e->getMessage();
    }
} else {
    $error_message = "No coach ID provided";
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="home.php">Home</a></li>
            <li class="breadcrumb-item"><a href="search.php">Coaches</a></li>
            <li class="breadcrumb-item active" aria-current="page">
                <?= $coach ? htmlspecialchars($coach['username']) : 'Coach Profile' ?>
            </li>
        </ol>
    </nav>

    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error_message) ?>
            <p class="mt-3"><a href="search.php" class="btn btn-primary">Back to Search</a></p>
        </div>
    <?php elseif ($coach): ?>
        <div class="row">
            <!-- Coach Information -->
            <div class="col-md-4 mb-4">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <?php 
                        $profile_image = $coach['profile_image'] ?? 'default.jpg';
                        $image_path = "/assets/images/profiles/{$profile_image}";
                        $default_image = "/assets/images/profiles/default.jpg";
                        
                        // Check if file exists and is readable
                        $full_image_path = $_SERVER['DOCUMENT_ROOT'] . $image_path;
                        $full_default_path = $_SERVER['DOCUMENT_ROOT'] . $default_image;
                        
                        // If user image doesn't exist or fallback doesn't exist, use an external default
                        if (file_exists($full_image_path) && is_readable($full_image_path)) {
                            $display_image = $image_path;
                        } elseif (file_exists($full_default_path) && is_readable($full_default_path)) {
                            $display_image = $default_image;
                        } else {
                            // Fallback to a reliable external avatar generator
                            $display_image = "https://ui-avatars.com/api/?name=" . urlencode($coach['username']) . "&background=random&size=150";
                        }
                        ?>
                        
                        <img src="<?= $display_image ?>" alt="<?= htmlspecialchars($coach['username']) ?>" 
                             class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                        
                        <h3 class="card-title"><?= htmlspecialchars($coach['username']) ?></h3>
                        
                        <!-- Rating display -->
                        <div class="mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= floor($average_rating)): ?>
                                    <i class="bi bi-star-fill text-warning"></i>
                                <?php elseif ($i - 0.5 == $average_rating): ?>
                                    <i class="bi bi-star-half text-warning"></i>
                                <?php else: ?>
                                    <i class="bi bi-star text-warning"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <span class="ms-1">(<?= $review_count ?> reviews)</span>
                        </div>
                        
                        <div class="mb-3">
                            <span class="badge bg-primary mb-1"><?= htmlspecialchars($coach['expertise']) ?></span>
                        </div>
                        
                        <p class="card-text"><?= nl2br(htmlspecialchars($coach['bio'] ?? '')) ?></p>
                        
                        <?php if (isset($_SESSION['logged_in']) && $_SESSION['user_id'] != $coach['user_id']): ?>
                            <a href="messages.php?coach_id=<?= $coach_id ?>" class="btn btn-outline-primary mb-2">
                                <i class="bi bi-chat-dots"></i> Message
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Availability Section -->
                <div class="card shadow mt-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Availability</h5>
                    </div>
                    <div class="card-body">
                        <p><?= nl2br(htmlspecialchars($coach['availability'])) ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Service Tiers -->
            <div class="col-md-8">
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Service Tiers</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($coach_services)): ?>
                            <p class="text-muted">No service tiers available at the moment.</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($coach_services as $service): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><?= htmlspecialchars($service['name']) ?></h5>
                                                <h6 class="card-subtitle mb-2 text-muted">
                                                    $<?= number_format($service['price'], 2) ?> / <?= htmlspecialchars($service['duration']) ?>
                                                </h6>
                                                <p class="card-text"><?= nl2br(htmlspecialchars($service['description'])) ?></p>
                                            </div>
                                            <div class="card-footer bg-transparent border-0">
                                                <?php if (isset($_SESSION['logged_in'])): ?>
                                                    <a href="book-session.php?tier_id=<?= $service['tier_id'] ?>" 
                                                       class="btn btn-primary btn-sm">Book Session</a>
                                                <?php else: ?>
                                                    <a href="login.php" class="btn btn-outline-primary btn-sm">
                                                        Login to Book
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Reviews Section -->
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Reviews</h5>
                        <?php if (isset($_SESSION['logged_in'])): ?>
                            <a href="add-review.php?coach_id=<?= $coach_id ?>" class="btn btn-light btn-sm">
                                <i class="bi bi-plus-circle"></i> Add Review
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($reviews)): ?>
                            <p class="text-muted">No reviews yet. Be the first to leave a review!</p>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="mb-4 pb-3 border-bottom">
                                    <div class="d-flex align-items-center mb-2">
                                        <?php 
                                        // Handle profile image for reviewer
                                        $reviewer_image = $review['profile_image'] ?? 'default.jpg';
                                        $reviewer_path = "/assets/images/profiles/{$reviewer_image}";
                                        
                                        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $reviewer_path)) {
                                            $reviewer_display = $reviewer_path;
                                        } else {
                                            $reviewer_display = "https://ui-avatars.com/api/?name=" . urlencode($review['username']) . "&size=32&background=random";
                                        }
                                        ?>
                                        <img src="<?= $reviewer_display ?>" alt="<?= htmlspecialchars($review['username']) ?>" 
                                             class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($review['username']) ?></h6>
                                            <small class="text-muted">
                                                <?= date('F j, Y', strtotime($review['created_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?= ($i <= $review['rating']) ? '-fill' : '' ?> text-warning"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <p><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                            
                            <a href="all-reviews.php?coach_id=<?= $coach_id ?>" class="btn btn-outline-primary">
                                View All Reviews
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
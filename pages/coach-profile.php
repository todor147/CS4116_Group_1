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
$coach_skills = [];
$coach_availability = [];

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

        // Handle case where custom_category column doesn't exist
        if (!array_key_exists('custom_category', $coach) && $coach) {
            try {
                // Check if column exists first to avoid errors
                $result = $pdo->query("SHOW COLUMNS FROM Coaches LIKE 'custom_category'");
                if ($result->rowCount() === 0) {
                    // Add column if it doesn't exist
                    $pdo->exec("ALTER TABLE Coaches ADD COLUMN custom_category VARCHAR(100) NULL AFTER video_url");
                    error_log("Added missing custom_category column to Coaches table.");
                    $coach['custom_category'] = null; // Set default value
                }
            } catch (PDOException $e) {
                error_log("Error checking/adding custom_category column: " . $e->getMessage());
                // Fail silently and just set default value
                $coach['custom_category'] = null;
            }
        }

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
                
                // Also get rating value directly from the coach table for comparison
                // This ensures consistency with the values shown in search results
                if ($coach && isset($coach['rating']) && $coach['rating'] > 0) {
                    // If coach table has a rating but reviews don't, use coach table value
                    if ($average_rating == 0) {
                        $average_rating = $coach['rating'];
                        // Default to at least 1 review if we have a rating but no review count
                        $review_count = $review_count > 0 ? $review_count : 1;
                    }
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
            
            // Get coach's skills
            try {
                $stmt = $pdo->prepare("
                    SELECT s.*, cs.proficiency_level, ec.category_name, ec.category_id
                    FROM Coach_Skills cs
                    JOIN Skills s ON cs.skill_id = s.skill_id
                    JOIN Expertise_Categories ec ON s.category_id = ec.category_id
                    WHERE cs.coach_id = ?
                ");
                $stmt->execute([$coach_id]);
                $coach_skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Also try to get coach's custom skills if the table exists
                try {
                    $stmt = $pdo->prepare("
                        SELECT ccs.*, ec.category_name
                        FROM Coach_Custom_Skills ccs
                        JOIN Expertise_Categories ec ON ccs.category_id = ec.category_id
                        WHERE ccs.coach_id = ?
                    ");
                    $stmt->execute([$coach_id]);
                    $custom_skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Combine regular and custom skills
                    foreach ($custom_skills as &$skill) {
                        $skill['is_custom'] = true;
                        $coach_skills[] = $skill;
                    }
                } catch (PDOException $e) {
                    // Table might not exist yet - that's fine
                }
            } catch (PDOException $e) {
                $coach_skills = [];
            }
            
            // Get coach availability
            $stmt = $pdo->prepare("
                SELECT day_of_week, start_time, end_time
                FROM Coach_Availability
                WHERE coach_id = ? AND is_available = 1
                ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
            ");
            $stmt->execute([$coach_id]);
            $coach_availability = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $error_message = "Error fetching coach data: " . $e->getMessage();
    }
} else {
    $error_message = "No coach ID provided";
}

include __DIR__ . '/../includes/header.php';
?>

<style>
/* Service Tier Cards Styling */
.service-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
    overflow: hidden;
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}

.service-card .card-header {
    position: relative;
    z-index: 2;
}

.service-card .service-description {
    font-size: 0.95rem;
}

.service-card .service-description ul,
.service-card .service-description ol {
    padding-left: 1.5rem;
    margin-bottom: 1rem;
}

/* Improved Ribbon styling */
.ribbon {
    position: absolute;
    top: -3px;
    right: -3px;
    z-index: 5;
    overflow: hidden;
    width: 100px;
    height: 100px;
}

.ribbon span {
    position: absolute;
    display: block;
    width: 120px;
    padding: 5px 0;
    background-color: #3d6bfd;
    color: #fff;
    text-align: center;
    font-size: 0.7rem;
    font-weight: bold;
    box-shadow: 0 3px 5px rgba(0,0,0,0.15);
    transform: rotate(45deg);
    right: -25px;
    top: 20px;
    text-transform: uppercase;
}

/* Price display */
.display-6 {
    font-size: 2.5rem;
    font-weight: 500;
}
</style>

<div class="container mt-4 mb-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="home.php">Home</a></li>
            <li class="breadcrumb-item"><a href="coach-search.php">Coaches</a></li>
            <li class="breadcrumb-item active" aria-current="page">
                <?= $coach ? htmlspecialchars($coach['username']) : 'Coach Profile' ?>
            </li>
        </ol>
    </nav>

    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error_message) ?>
            <p class="mt-3"><a href="coach-search.php" class="btn btn-primary">Back to Search</a></p>
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
                        <h5 class="text-muted mb-3"><?= htmlspecialchars($coach['headline']) ?></h5>
                        
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
                        
                        <div class="d-flex justify-content-center mb-3">
                            <span class="badge bg-primary me-1">€<?= number_format($coach['hourly_rate'], 2) ?>/hr</span>
                            <span class="badge bg-secondary"><?= htmlspecialchars($coach['experience']) ?> Experience</span>
                        </div>
                        
                        <!-- Display custom category if it exists, otherwise show standard category -->
                        <?php if (!empty($coach['custom_category'])): ?>
                            <div class="mb-3">
                                <span class="badge bg-info me-2">Custom Category:</span>
                                <span class="badge bg-secondary"><?= htmlspecialchars($coach['custom_category']) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <p class="card-text"><?= nl2br(htmlspecialchars($coach['bio'] ?? '')) ?></p>
                        
                        <?php if (isset($_SESSION['logged_in']) && $_SESSION['user_id'] != $coach['user_id']): ?>
                            <div class="d-grid gap-2">
                                <a href="messages.php?new_conversation=true&recipient_id=<?= $coach['user_id'] ?>" class="btn btn-outline-primary mb-2">
                                    <i class="bi bi-chat-dots"></i> Message
                                </a>
                                <a href="customer-insight-request.php?coach_id=<?= $coach_id ?>" class="btn btn-outline-info mb-2">
                                    <i class="bi bi-info-circle"></i> Request Customer Insight
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Availability Section -->
                <div class="card shadow mt-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Availability</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($coach_availability)): ?>
                            <p class="text-muted">No availability information provided.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($coach_availability as $slot): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?= htmlspecialchars($slot['day_of_week']) ?></strong>
                                                <span class="text-muted ms-2">
                                                    <?= date('g:i A', strtotime($slot['start_time'])) ?> - 
                                                    <?= date('g:i A', strtotime($slot['end_time'])) ?>
                                                </span>
                                            </div>
                                            <span class="badge bg-success">Available</span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Skills Section -->
                <div class="card shadow mt-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Skills & Expertise</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($coach_skills)): ?>
                            <p class="text-muted">No skills information provided.</p>
                        <?php else: ?>
                            <?php 
                            // Group skills by category
                            $skills_by_category = [];
                            foreach ($coach_skills as $skill) {
                                $category = $skill['category_name'];
                                if (!isset($skills_by_category[$category])) {
                                    $skills_by_category[$category] = [];
                                }
                                $skills_by_category[$category][] = $skill;
                            }
                            ?>
                            
                            <div class="accordion" id="skillsAccordion">
                                <?php $index = 0; ?>
                                <?php foreach ($skills_by_category as $category => $skills): ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading<?= $index ?>">
                                            <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button" 
                                                    data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" 
                                                    aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" 
                                                    aria-controls="collapse<?= $index ?>">
                                                <?= htmlspecialchars($category) ?>
                                            </button>
                                        </h2>
                                        <div id="collapse<?= $index ?>" 
                                             class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" 
                                             aria-labelledby="heading<?= $index ?>" 
                                             data-bs-parent="#skillsAccordion">
                                            <div class="accordion-body">
                                                <ul class="list-group list-group-flush">
                                                    <?php foreach ($skills as $skill): ?>
                                                        <li class="list-group-item">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <span><?= htmlspecialchars($skill['skill_name']) ?></span>
                                                                <div>
                                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                        <?php if ($i <= $skill['proficiency_level']): ?>
                                                                            <i class="bi bi-circle-fill text-primary small"></i>
                                                                        <?php else: ?>
                                                                            <i class="bi bi-circle text-primary small"></i>
                                                                        <?php endif; ?>
                                                                    <?php endfor; ?>
                                                                </div>
                                                            </div>
                                                            <?php if (!empty($skill['description'])): ?>
                                                                <small class="text-muted"><?= htmlspecialchars($skill['description']) ?></small>
                                                            <?php endif; ?>
                                                            <?php if (isset($skill['is_custom']) && $skill['is_custom']): ?>
                                                                <span class="badge bg-success mt-1">Custom</span>
                                                            <?php endif; ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <?php $index++; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <!-- About Me Section -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">About Me</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($coach['about_me'])): ?>
                            <div class="mb-4">
                                <?= nl2br(htmlspecialchars($coach['about_me'])) ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No detailed information provided.</p>
                        <?php endif; ?>
                        
                        <?php if (!empty($coach['video_url'])): ?>
                            <?php
                            // Function to convert YouTube URL to embed format
                            function getYoutubeEmbedUrl($url) {
                                // Regular expressions to match YouTube URLs
                                $pattern = '/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
                                
                                // Extract video ID
                                if (preg_match($pattern, $url, $matches)) {
                                    $videoId = $matches[1];
                                    return "https://www.youtube.com/embed/{$videoId}";
                                }
                                
                                // If URL already looks like an embed URL or is something else entirely, return as is
                                return $url;
                            }
                            
                            $embedUrl = getYoutubeEmbedUrl($coach['video_url']);
                            ?>
                            <div class="ratio ratio-16x9 mt-4">
                                <iframe src="<?= htmlspecialchars($embedUrl) ?>" 
                                        title="Coach Introduction" 
                                        allowfullscreen
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        referrerpolicy="strict-origin-when-cross-origin">
                                </iframe>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Service Tiers -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Service Tiers</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($coach_services)): ?>
                            <p class="text-muted">No service tiers available at the moment.</p>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($coach_services as $index => $service): ?>
                                    <div class="col-md-4 mb-4">
                                        <div class="card h-100 service-card<?= (isset($service['is_popular']) && $service['is_popular']) ? ' border-primary' : '' ?>">
                                            <?php if (isset($service['is_popular']) && $service['is_popular']): ?>
                                                <div class="ribbon">
                                                    <span>POPULAR</span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="card-header text-center bg-light py-3">
                                                <h5 class="card-title mb-0"><?= htmlspecialchars($service['name']) ?></h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="text-center mb-4">
                                                    <h4 class="display-6 mb-0">€<?= number_format($service['price'], 2) ?></h4>
                                                    <p class="text-muted">per session</p>
                                                </div>
                                                <div class="service-description">
                                                    <?= nl2br(htmlspecialchars($service['description'])) ?>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-transparent text-center py-3">
                                                <?php if (isset($_SESSION['logged_in'])): ?>
                                                    <?php
                                                    // Assuming you have the coach ID and service tier ID available
                                                    $tier_id = $service['tier_id'];
                                                    $book_session_url = "book.php?coach_id=$coach_id&tier_id=$tier_id";
                                                    ?>
                                                    <a href="<?= $book_session_url ?>" class="btn btn-primary btn-lg w-100">Book Session</a>
                                                <?php else: ?>
                                                    <a href="login.php?redirect=coach-profile.php?id=<?= $coach_id ?>" 
                                                       class="btn btn-outline-primary btn-lg w-100">Login to Book</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="alert alert-info mb-0">
                                        <div class="d-flex">
                                            <div class="me-3">
                                                <i class="bi bi-info-circle-fill fs-4"></i>
                                            </div>
                                            <div>
                                                <h6 class="alert-heading">Not sure which service is right for you?</h6>
                                                <p class="mb-0">You can message <?= htmlspecialchars($coach['username']) ?> to discuss your specific needs before booking a session.</p>
                                                <?php if (isset($_SESSION['logged_in'])): ?>
                                                    <a href="messages.php?new_conversation=true&recipient_id=<?= $coach['user_id'] ?>" class="btn btn-sm btn-info mt-2">
                                                        <i class="bi bi-chat-dots"></i> Send Message
                                                    </a>
                                                <?php else: ?>
                                                    <a href="login.php?redirect=coach-profile.php?id=<?= $coach_id ?>" class="btn btn-sm btn-info mt-2">
                                                        <i class="bi bi-chat-dots"></i> Login to Message
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Reviews Section -->
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Reviews</h5>
                        <?php if (isset($_SESSION['logged_in'])): ?>
                            <?php
                            // Check if user has completed a session with this coach
                            $stmt = $pdo->prepare("
                                SELECT COUNT(*) as session_count 
                                FROM Sessions 
                                WHERE learner_id = ? AND coach_id = ? AND status = 'completed'
                            ");
                            $stmt->execute([$_SESSION['user_id'], $coach_id]);
                            $has_completed_session = ($stmt->fetch(PDO::FETCH_ASSOC)['session_count'] > 0);
                            
                            if ($has_completed_session): 
                            ?>
                                <a href="review.php?coach_id=<?= $coach_id ?>" class="btn btn-light btn-sm">
                                    <i class="bi bi-plus-circle"></i> Add Review
                                </a>
                            <?php else: ?>
                                <button class="btn btn-light btn-sm" disabled data-bs-toggle="tooltip" 
                                        title="Complete a session with this coach to leave a review">
                                    <i class="bi bi-plus-circle"></i> Add Review
                                </button>
                            <?php endif; ?>
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
                            
                            <?php if ($review_count > 5): ?>
                                <div class="text-center">
                                    <a href="coach-reviews.php?id=<?= $coach_id ?>" class="btn btn-outline-primary">
                                        View All <?= $review_count ?> Reviews
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Booking Button Section -->
        <div class="mt-4 text-center">
            <?php if (isset($_SESSION['logged_in']) && isset($_SESSION['user_id'])): ?>
                <!-- User is logged in, show normal booking button -->
                <?php if (!empty($coach_services)): ?>
                    <a href="book.php?coach_id=<?= $coach_id ?>&tier_id=<?= $coach_services[0]['tier_id'] ?>" class="btn btn-lg btn-primary px-5 py-3 mb-3">
                        <i class="bi bi-calendar-plus me-2"></i> Book a Session
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <!-- User is not logged in, show login prompt -->
                <div class="alert alert-info p-4">
                    <h5><i class="bi bi-info-circle me-2"></i> Want to book a session with this coach?</h5>
                    <p class="mb-3">You'll need to login or create an account first.</p>
                    <a href="login.php?redirect=coach-profile.php?id=<?= $coach_id ?>" class="btn btn-primary me-2">Login</a>
                    <a href="register.php" class="btn btn-outline-primary">Create Account</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Contact coach button only, no duplicate booking button -->
        <?php if ($coach && $coach['coach_id'] && isset($_SESSION['user_id']) && $_SESSION['user_id'] != $coach['user_id']): ?>
            <div class="d-flex justify-content-center mt-3">
                <a href="messages.php?receiver_id=<?= $coach['user_id'] ?>" class="btn btn-outline-primary">
                    <i class="bi bi-chat-dots"></i> Contact Coach
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
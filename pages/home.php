<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
session_start();
}

// Include database connection
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

// Check if the user is a student and has booked sessions
$has_booked_sessions = false;
if (!$is_coach) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as session_count 
        FROM session 
        WHERE learner_id = ? AND status = 'scheduled'
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    $has_booked_sessions = ($result['session_count'] > 0);
}

// Get user's upcoming sessions if logged in
$upcoming_sessions = [];
if (isset($_SESSION['logged_in']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['user_type'] ?? 'regular';
    $is_coach = ($user_type === 'business');
    
    try {
        if ($is_coach) {
            // Get coach's upcoming sessions
            $stmt = $pdo->prepare("
                SELECT s.*, u.username as learner_name, st.name as tier_name, s.scheduled_time
                FROM session s
                JOIN Users u ON s.learner_id = u.user_id
                JOIN ServiceTiers st ON s.tier_id = st.tier_id
                WHERE s.coach_id = (SELECT coach_id FROM Coaches WHERE user_id = ?) 
                AND s.status = 'scheduled'
                AND s.scheduled_time > NOW()
                ORDER BY s.scheduled_time ASC
                LIMIT 3
            ");
            $stmt->execute([$user_id]);
        } else {
            // Get learner's upcoming sessions
            $stmt = $pdo->prepare("
                SELECT s.*, u.username as coach_name, st.name as tier_name, s.scheduled_time
                FROM session s
                JOIN Coaches c ON s.coach_id = c.coach_id
                JOIN Users u ON c.user_id = u.user_id
                JOIN ServiceTiers st ON s.tier_id = st.tier_id
                WHERE s.learner_id = ? 
                AND s.status = 'scheduled'
                AND s.scheduled_time > NOW()
                ORDER BY s.scheduled_time ASC
                LIMIT 3
            ");
            $stmt->execute([$user_id]);
        }
        $upcoming_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Log the results
        error_log("Upcoming sessions: " . print_r($upcoming_sessions, true));
    } catch (PDOException $e) {
        // Log error but continue
        error_log("Error fetching upcoming sessions: " . $e->getMessage());
    }
}

// Get popular categories
try {
    $stmt = $pdo->prepare("
        SELECT c.category_id, c.name, c.description, COUNT(cc.coach_id) as coach_count
        FROM Categories c
        LEFT JOIN CoachCategories cc ON c.category_id = cc.category_id
        GROUP BY c.category_id
        ORDER BY coach_count DESC
        LIMIT 12
    ");
    $stmt->execute();
    $popular_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $popular_categories = [];
}

// Get top-rated coaches
try {
    $stmt = $pdo->prepare("
        SELECT c.coach_id, c.expertise, c.availability, c.rating, 
               u.username, u.profile_image, u.bio,
               COUNT(r.review_id) as review_count
        FROM Coaches c
        JOIN Users u ON c.user_id = u.user_id
        LEFT JOIN Reviews r ON c.coach_id = r.coach_id
        WHERE u.is_banned = 0
        GROUP BY c.coach_id
        ORDER BY c.rating DESC, review_count DESC
        LIMIT 6
    ");
    $stmt->execute();
    $featured_coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $featured_coaches = [];
}

// Get recent testimonials
try {
    $stmt = $pdo->prepare("
        SELECT r.review_id, r.rating, r.comment, r.created_at,
               u.username as learner_name,
               u2.username as coach_name,
               c.expertise
        FROM Reviews r
        JOIN Users u ON r.user_id = u.user_id
        JOIN Coaches c ON r.coach_id = c.coach_id
        JOIN Users u2 ON c.user_id = u2.user_id
        WHERE r.rating >= 4
        ORDER BY r.created_at DESC
        LIMIT 3
    ");
    $stmt->execute();
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $testimonials = [];
}

// Include header AFTER session and database operations
include __DIR__ . '/../includes/header.php';
?>

<?php if (isset($_SESSION['logged_in']) && !empty($upcoming_sessions)): ?>
<!-- Upcoming Sessions (Only if logged in and has sessions) -->
<section class="py-4 bg-light border-bottom">
    <div class="container">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-calendar-check text-primary me-2"></i> Your Upcoming Sessions</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($upcoming_sessions as $session): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 fw-bold"><?= isset($session['coach_name']) ? htmlspecialchars($session['coach_name']) : htmlspecialchars($session['learner_name']) ?></p>
                                <p class="mb-0 small text-muted"><?= htmlspecialchars($session['tier_name']) ?></p>
                            </div>
                            <div class="text-center">
                                <span class="badge bg-primary"><?= date('D, M j', strtotime($session['scheduled_time'])) ?></span>
                                <span class="d-block small"><?= date('g:i A', strtotime($session['scheduled_time'])) ?></span>
                            </div>
                            <a href="session-details.php?id=<?= $session['session_id'] ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card-footer bg-white text-center">
                <a href="session.php" class="text-decoration-none">View All Sessions</a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Hero Search Section -->
<section class="py-5 <?php echo isset($_SESSION['logged_in']) ? '' : 'bg-light'; ?>">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h1 class="display-4 fw-bold mb-3">Find the <span class="text-primary">perfect coach</span></h1>
                <h2 class="h4 text-muted mb-5">Online or face to face, make your choice from thousands of expert coaches</h2>
                
                <form action="coach-search.php" method="get" class="mb-4">
                    <div class="input-group input-group-lg">
                        <input type="text" class="form-control" name="query" placeholder="Maths, Guitar, Spanish, Yoga..." aria-label="Search for a subject">
                        <select class="form-select" name="location" style="max-width: 150px;">
                            <option value="online">Online</option>
                            <option value="in-person">Around me</option>
                        </select>
                        <button class="btn btn-primary px-4" type="submit">Search</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Popular Subjects Section -->
<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="text-center mb-4">Popular Subjects</h2>
            </div>
        </div>
        
        <div class="row g-4 justify-content-center">
            <?php 
            // If no categories found, show placeholders
            if (empty($popular_categories)) {
                $placeholder_subjects = ['Maths', 'English', 'Piano', 'Spanish', 'Guitar', 'French', 'Science', 'Yoga', 'Computer Programming', 'Physics', 'Chemistry', 'Art'];
                foreach ($placeholder_subjects as $index => $subject) {
                    if ($index < 12) {
            ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="coach-search.php?query=<?= urlencode($subject) ?>" class="text-decoration-none">
                        <div class="card h-100 border-0 text-center shadow-sm hover-card">
                            <div class="card-body">
                                <div class="mb-3">
                                    <i class="bi bi-book text-primary" style="font-size: 2rem;"></i>
                                </div>
                                <h5 class="card-title"><?= htmlspecialchars($subject) ?></h5>
                            </div>
                        </div>
                    </a>
                </div>
            <?php
                    }
                }
            } else {
                // Show actual categories from database
                foreach ($popular_categories as $category) {
            ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="coach-search.php?category=<?= urlencode($category['category_id']) ?>" class="text-decoration-none">
                        <div class="card h-100 border-0 text-center shadow-sm hover-card">
                            <div class="card-body">
                                <div class="mb-3">
                                    <i class="bi bi-book text-primary" style="font-size: 2rem;"></i>
                                </div>
                                <h5 class="card-title"><?= htmlspecialchars($category['name']) ?></h5>
                            </div>
                    </div>
                </a>
                </div>
            <?php
                }
            }
            ?>
        </div>
    </div>
</section>

<!-- Featured Coaches Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>Featured Coaches</h2>
                <p class="lead">Connect with our top-rated coaches and start your learning journey today.</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="coach-search.php" class="btn btn-primary">View All Coaches</a>
            </div>
        </div>
        
        <div class="row">
            <?php
            // Get top 4 coaches by rating
            try {
                $stmt = $pdo->prepare("
                    SELECT c.*, u.username, u.profile_image, u.bio 
                    FROM Coaches c
                    JOIN Users u ON c.user_id = u.user_id
                    ORDER BY c.rating DESC
                    LIMIT 4
                ");
                $stmt->execute();
                $featured_coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($featured_coaches as $coach) {
                    // Get coach's top skills
                    $stmt = $pdo->prepare("
                        SELECT s.skill_name
                        FROM Coach_Skills cs
                        JOIN Skills s ON cs.skill_id = s.skill_id
                        WHERE cs.coach_id = ?
                        ORDER BY cs.proficiency_level DESC
                        LIMIT 3
                    ");
                    $stmt->execute([$coach['coach_id']]);
                    $top_skills = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // Handle profile image
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
                        $display_image = "https://ui-avatars.com/api/?name=" . urlencode($coach['username']) . "&background=random&size=100";
                    }
                    ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body text-center">
                                <img src="<?= $display_image ?>" alt="<?= htmlspecialchars($coach['username']) ?>" 
                                     class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;">
                                
                                <h5 class="card-title"><?= htmlspecialchars($coach['username']) ?></h5>
                                <p class="text-muted small"><?= htmlspecialchars($coach['headline']) ?></p>
                                
                                <!-- Rating display -->
                                <div class="mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= floor($coach['rating'])): ?>
                                            <i class="bi bi-star-fill text-warning"></i>
                                        <?php elseif ($i - 0.5 == $coach['rating']): ?>
                                            <i class="bi bi-star-half text-warning"></i>
                                        <?php else: ?>
                                            <i class="bi bi-star text-warning"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    <span class="ms-1"><?= number_format($coach['rating'], 1) ?></span>
                                </div>
                                
                                <?php if (!empty($top_skills)): ?>
                                    <div class="mb-3">
                                        <?php foreach ($top_skills as $skill): ?>
                                            <span class="badge bg-light text-dark me-1 mb-1"><?= htmlspecialchars($skill) ?></span>
            <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <a href="coach-profile.php?id=<?= $coach['coach_id'] ?>" class="btn btn-outline-primary btn-sm">View Profile</a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } catch (PDOException $e) {
                echo '<div class="col-12"><div class="alert alert-danger">Error loading featured coaches.</div></div>';
            }
            ?>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-5">
        <div class="container">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2>The Perfect Match</h2>
                <p class="text-muted">Over thousands of students have given a 5 star review to their coach</p>
            </div>
        </div>
        
            <div class="row g-4">
                <?php
            if (empty($testimonials)) {
                // Sample testimonials if none in database
                $sample_testimonials = [
                    [
                        'comment' => 'My coach has been incredibly helpful in advancing my skills. The personalized approach to teaching made all the difference in my learning journey.',
                        'learner_name' => 'Sarah',
                        'coach_name' => 'Michael',
                        'expertise' => 'Mathematics'
                    ],
                    [
                        'comment' => 'I\'ve tried many online tutors before, but none have been as effective as my EduCoach tutor. The sessions are well-structured and tailored to my needs.',
                        'learner_name' => 'James',
                        'coach_name' => 'Emily',
                        'expertise' => 'Spanish'
                    ],
                    [
                        'comment' => 'As someone who struggled with learning guitar for years, finding the right teacher made all the difference. Now I can play my favorite songs with confidence!',
                        'learner_name' => 'David',
                        'coach_name' => 'Laura',
                        'expertise' => 'Guitar'
                    ]
                ];
                
                foreach ($sample_testimonials as $testimonial):
                ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="mb-3 text-warning">
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                </div>
                            <p class="card-text">"<?= htmlspecialchars($testimonial['comment']) ?>"</p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <p class="mb-0 fw-bold"><?= htmlspecialchars($testimonial['learner_name']) ?></p>
                                    <p class="text-muted small">Student</p>
                                </div>
                                <div class="text-end">
                                    <p class="mb-0 fw-bold"><?= htmlspecialchars($testimonial['coach_name']) ?></p>
                                    <p class="text-muted small"><?= htmlspecialchars($testimonial['expertise']) ?> Coach</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
                endforeach;
            } else {
                // Show actual testimonials from database
                foreach ($testimonials as $testimonial):
            ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="mb-3 text-warning">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $testimonial['rating']): ?>
                                        <i class="bi bi-star-fill"></i>
                                    <?php else: ?>
                                        <i class="bi bi-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <p class="card-text">"<?= htmlspecialchars($testimonial['comment']) ?>"</p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <p class="mb-0 fw-bold"><?= htmlspecialchars($testimonial['learner_name']) ?></p>
                                    <p class="text-muted small">Student</p>
                                </div>
                                <div class="text-end">
                                    <p class="mb-0 fw-bold"><?= htmlspecialchars($testimonial['coach_name']) ?></p>
                                    <p class="text-muted small"><?= htmlspecialchars($testimonial['expertise']) ?> Coach</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
                endforeach;
            }
            ?>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mb-4 mb-lg-0">
                <h2>Ready to start learning?</h2>
                <p class="lead mb-0">Connect with expert coaches and take your skills to the next level.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <?php if (isset($_SESSION['logged_in'])): ?>
                    <a href="coach-search.php" class="btn btn-light btn-lg">Find a Coach</a>
                <?php else: ?>
                    <a href="register.php" class="btn btn-light btn-lg me-2">Register Now</a>
                    <a href="login.php" class="btn btn-outline-light btn-lg">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section (simplified) -->
<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h2>How It Works</h2>
                <p class="text-muted">Three simple steps to start your learning journey</p>
        </div>
    </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="text-center">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-search fs-4"></i>
                    </div>
                    <h3>Find a Coach</h3>
                    <p class="text-muted">Browse through our extensive list of qualified coaches and tutors.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-calendar-check fs-4"></i>
                    </div>
                    <h3>Book a Session</h3>
                    <p class="text-muted">Schedule a session at a time that works best for you.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="text-center">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="bi bi-mortarboard fs-4"></i>
                    </div>
                    <h3>Start Learning</h3>
                    <p class="text-muted">Connect with your coach and begin your learning journey.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .hover-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .hover-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
</style> 

<?php if (!$is_coach && $has_booked_sessions): ?>
<!-- Display a notification if the student has booked sessions -->
<div class="container mt-4">
    <div class="alert alert-info" role="alert">
        You have a session booked! <a href="session.php">View your sessions</a>.
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
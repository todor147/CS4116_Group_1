<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Redirect to login if not logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php?error=' . urlencode('You must be logged in to access this page.'));
    exit;
}

// Check if user is a coach
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'regular';

if ($user_type !== 'business') {
    header('Location: dashboard.php?error=' . urlencode('You must be a business user to access coach settings.'));
    exit;
}

// Get coach information
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.email, u.profile_image 
        FROM Coaches c
        JOIN Users u ON c.user_id = u.user_id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $coach = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$coach) {
        // User is a business user but doesn't have a coach profile yet
        header('Location: become-coach.php');
        exit;
    }
    
    // Get current coach stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Coach_Skills WHERE coach_id = ?");
    $stmt->execute([$coach['coach_id']]);
    $skillCount = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Coach_Availability WHERE coach_id = ? AND is_available = 1");
    $stmt->execute([$coach['coach_id']]);
    $availabilityCount = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ServiceTiers WHERE coach_id = ?");
    $stmt->execute([$coach['coach_id']]);
    $serviceTierCount = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Reviews WHERE coach_id = ?");
    $stmt->execute([$coach['coach_id']]);
    $reviewCount = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Coach Settings</h1>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_GET['success']) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>
            
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <?php
                            // Display profile image
                            $profile_image = !empty($coach['profile_image']) ? $coach['profile_image'] : 'default.jpg';
                            $image_path = "../assets/images/profiles/" . $profile_image;
                            $default_path = "../assets/images/profiles/default.jpg";
                            
                            if (file_exists($image_path)) {
                                $display_image = $image_path;
                            } else {
                                $display_image = $default_path;
                            }
                            ?>
                            <img src="<?= $display_image ?>" alt="Profile" class="img-fluid rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;">
                            <h5><?= htmlspecialchars($coach['username']) ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($coach['headline']) ?></p>
                            <a href="coach-profile.php?id=<?= $coach['coach_id'] ?>" class="btn btn-outline-primary btn-sm">View Public Profile</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Profile Information</h5>
                                    <p class="card-text">Update your coach profile information, headline, about section, and more.</p>
                                    <a href="edit-coach-profile.php" class="btn btn-primary">Edit Profile</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Skills & Expertise</h5>
                                    <p class="card-text">Manage your expertise areas and specific skills. <span class="badge bg-info"><?= $skillCount ?> skills</span></p>
                                    <a href="edit-coach-skills.php" class="btn btn-primary">Manage Skills</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Availability</h5>
                                    <p class="card-text">Set your weekly availability schedule. <span class="badge bg-info"><?= $availabilityCount ?> time slots</span></p>
                                    <a href="edit-coach-availability.php" class="btn btn-primary">Manage Availability</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Service Tiers</h5>
                                    <p class="card-text">Create and manage your service offerings and pricing. <span class="badge bg-info"><?= $serviceTierCount ?> services</span></p>
                                    <a href="edit-coach-services.php" class="btn btn-primary">Manage Services</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Service Analytics</h5>
                                    <p class="card-text">Track performance and metrics for your service tiers and pricing.</p>
                                    <a href="service-analytics.php" class="btn btn-primary">View Analytics</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Advanced Analytics Dashboard</h5>
                                    <p class="card-text">Access comprehensive performance metrics, charts, and insights for your coaching business.</p>
                                    <a href="analytics/dashboard.php" class="btn btn-primary">Open Dashboard</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Your Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="stats-item">
                                <h3><?= number_format($coach['rating'], 1) ?></h3>
                                <p>Rating</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-item">
                                <h3><?= $reviewCount ?></h3>
                                <p>Reviews</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-item">
                                <h3><?= $skillCount ?></h3>
                                <p>Skills</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-item">
                                <h3><?= $serviceTierCount ?></h3>
                                <p>Services</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
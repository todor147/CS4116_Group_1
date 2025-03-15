<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';

// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'regular';
$is_coach = ($user_type === 'business');

// Get additional data based on user type
try {
    if ($is_coach) {
        // Get coach information
        $stmt = $pdo->prepare("
            SELECT c.*, u.username, u.email, u.profile_image, u.bio
            FROM Coaches c
            JOIN Users u ON c.user_id = u.user_id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $coach = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if coach profile exists
        if ($coach) {
            // Get coach's service tiers
            $stmt = $pdo->prepare("
                SELECT * FROM ServiceTiers 
                WHERE coach_id = ?
            ");
            $stmt->execute([$coach['coach_id']]);
            $serviceTiers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get recent inquiries
            $stmt = $pdo->prepare("
                SELECT si.*, u.username as learner_name, st.name as tier_name
                FROM ServiceInquiries si
                JOIN Users u ON si.user_id = u.user_id
                JOIN ServiceTiers st ON si.tier_id = st.tier_id
                WHERE si.coach_id = ?
                ORDER BY si.created_at DESC
                LIMIT 5
            ");
            $stmt->execute([$coach['coach_id']]);
            $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get upcoming sessions
            $stmt = $pdo->prepare("
                SELECT s.*, u.username as learner_name, st.name as tier_name
                FROM Sessions s
                JOIN Users u ON s.learner_id = u.user_id
                JOIN ServiceTiers st ON s.tier_id = st.tier_id
                WHERE s.coach_id = ? AND s.status = 'scheduled'
                ORDER BY s.scheduled_time ASC
                LIMIT 5
            ");
            $stmt->execute([$coach['coach_id']]);
            $upcomingSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Initialize empty arrays if no coach profile exists
            $serviceTiers = [];
            $inquiries = [];
            $upcomingSessions = [];
        }
    } else {
        // Get learner's inquiries
        $stmt = $pdo->prepare("
            SELECT si.*, u.username as coach_name, st.name as tier_name
            FROM ServiceInquiries si
            JOIN Coaches c ON si.coach_id = c.coach_id
            JOIN Users u ON c.user_id = u.user_id
            JOIN ServiceTiers st ON si.tier_id = st.tier_id
            WHERE si.user_id = ?
            ORDER BY si.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get upcoming sessions
        $stmt = $pdo->prepare("
            SELECT s.*, u.username as coach_name, st.name as tier_name
            FROM Sessions s
            JOIN Coaches c ON s.coach_id = c.coach_id
            JOIN Users u ON c.user_id = u.user_id
            JOIN ServiceTiers st ON s.tier_id = st.tier_id
            WHERE s.learner_id = ? AND s.status = 'scheduled'
            ORDER BY s.scheduled_time ASC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $upcomingSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">
                <?php echo ($is_coach) ? 'Coach Dashboard' : 'Learner Dashboard'; ?>
            </h1>
            
            <?php if (isset($_GET['welcome']) && $_GET['welcome'] == 1): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Welcome to EduCoach!</strong> Your account has been created successfully and you're now logged in.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            </div>
        </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Profile Overview</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <?php 
                        // Improved profile image handling with debug output
                        $profile_image = isset($_SESSION['profile_image']) && !empty($_SESSION['profile_image']) ? $_SESSION['profile_image'] : 'default.jpg';
                        $image_path = "../assets/images/profiles/{$profile_image}";
                        $default_image = "../assets/images/profiles/default.jpg";
                        
                        // Check if file exists and is readable
                        $full_image_path = __DIR__ . "/../assets/images/profiles/{$profile_image}";
                        $full_default_path = __DIR__ . "/../assets/images/profiles/default.jpg";
                        
                        // Add debugging information (only visible in HTML source)
                        echo "<!-- Debug: profile_image='{$profile_image}', full_path='{$full_image_path}', exists=" . (file_exists($full_image_path) ? 'true' : 'false') . " -->";
                        
                        // If user image doesn't exist or fallback doesn't exist, use an external default
                        if (file_exists($full_image_path) && is_readable($full_image_path)) {
                            $display_image = $image_path;
                        } elseif (file_exists($full_default_path) && is_readable($full_default_path)) {
                            $display_image = $default_image;
                        } else {
                            // Fallback to a reliable external default avatar
                            $display_image = "https://ui-avatars.com/api/?name=" . urlencode($_SESSION['username']) . "&background=random&size=100";
                        }
                        ?>
                        <img src="<?= $display_image ?>" alt="Profile" class="rounded-circle img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                </div>
                    <h5 class="text-center"><?= htmlspecialchars($_SESSION['username']) ?></h5>
                    <p class="text-center text-muted"><?= htmlspecialchars($_SESSION['email']) ?></p>
                    
                    <?php if ($is_coach && isset($coach)): ?>
                        <hr>
                        <?php if (isset($coach) && $coach): ?>
                            <div class="mb-2">
                                <strong>Expertise:</strong>
                                <p><?= isset($coach['headline']) ? htmlspecialchars($coach['headline']) : 'Not specified' ?></p>
                            </div>
                            <div class="mb-2">
                                <strong>Availability:</strong>
                                <p>
                                    <?php
                                    if (isset($coach['coach_id'])) {
                                        try {
                                            $stmt = $pdo->prepare("
                                                SELECT COUNT(*) FROM Coach_Availability 
                                                WHERE coach_id = ? AND is_available = 1
                                            ");
                                            $stmt->execute([$coach['coach_id']]);
                                            $availCount = $stmt->fetchColumn();
                                            echo $availCount > 0 ? 'Schedule set' : 'Not specified';
                                        } catch (PDOException $e) {
                                            echo 'Not specified';
                                        }
                                    } else {
                                        echo 'Not specified';
                                    }
                                    ?>
                                </p>
                                </div>
                            <div class="mb-2">
                                <strong>Rating:</strong>
                                <p><?= isset($coach['rating']) && $coach['rating'] ? number_format($coach['rating'], 1) . ' / 5.0' : 'No ratings yet' ?></p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <p>You haven't set up your coach profile yet.</p>
                                <a href="become-coach.php" class="btn btn-primary btn-sm">Set Up Coach Profile</a>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2">
                        <a href="profile.php" class="btn btn-outline-primary">Edit Profile</a>
                                </div>
                            </div>
                        </div>
                    </div>

        <div class="col-md-8">
            <?php if ($is_coach): ?>
                <!-- Coach-specific content -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Your Service Tiers</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($serviceTiers) && count($serviceTiers) > 0): ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Price</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($serviceTiers as $tier): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($tier['name']) ?></td>
                                            <td><?= htmlspecialchars(substr($tier['description'], 0, 50)) ?>...</td>
                                            <td>$<?= number_format($tier['price'], 2) ?></td>
                                            <td>
                                                <a href="edit-coach-services.php" class="btn btn-sm btn-outline-primary">Edit</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <a href="edit-coach-services.php" class="btn btn-primary">Add New Tier</a>
                        <?php else: ?>
                            <p class="text-muted">You haven't created any service tiers yet.</p>
                            <a href="edit-coach-services.php" class="btn btn-primary">Create Your First Tier</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Inquiries - Both user types -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <?= $is_coach ? 'Recent Inquiries' : 'Your Service Inquiries' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (isset($inquiries) && count($inquiries) > 0): ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th><?= $is_coach ? 'From' : 'To' ?></th>
                                    <th>Service</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inquiries as $inquiry): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($inquiry['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($is_coach ? $inquiry['learner_name'] : $inquiry['coach_name']) ?></td>
                                    <td><?= htmlspecialchars($inquiry['tier_name']) ?></td>
                                    <td>
                                        <span class="badge <?= 
                                            $inquiry['status'] === 'pending' ? 'bg-warning' : 
                                            ($inquiry['status'] === 'accepted' ? 'bg-success' : 
                                            ($inquiry['status'] === 'completed' ? 'bg-info' : 'bg-danger')) 
                                        ?>">
                                            <?= ucfirst($inquiry['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="inquiry-details.php?id=<?= $inquiry['inquiry_id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <a href="inquiries.php" class="btn btn-outline-primary">View All Inquiries</a>
                    <?php else: ?>
                        <p>No inquiries found.</p>
                        <?php if (!$is_coach): ?>
                            <a href="search.php" class="btn btn-primary">Find a Coach</a>
                    <?php endif; ?>
                    <?php endif; ?>
                        </div>
                    </div>

            <!-- Upcoming Sessions - Both user types -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Upcoming Sessions</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($upcomingSessions) && count($upcomingSessions) > 0): ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th><?= $is_coach ? 'Learner' : 'Coach' ?></th>
                                    <th>Service</th>
                                    <th>Duration</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingSessions as $session): ?>
                                <tr>
                                    <td><?= date('M d, Y h:i A', strtotime($session['scheduled_time'])) ?></td>
                                    <td><?= htmlspecialchars($is_coach ? $session['learner_name'] : $session['coach_name']) ?></td>
                                    <td><?= htmlspecialchars($session['tier_name']) ?></td>
                                    <td><?= $session['duration'] ?> min</td>
                                    <td>
                                        <a href="session-details.php?id=<?= $session['session_id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <a href="sessions.php" class="btn btn-outline-primary">View All Sessions</a>
                    <?php else: ?>
                        <p>No upcoming sessions found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
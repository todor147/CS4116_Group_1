<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Redirect to login if not logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php?redirect=service-analytics.php");
    exit();
}

// Check if user is a coach
$userId = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT * FROM Coaches WHERE user_id = ?");
    $stmt->execute([$userId]);
    $coach = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$coach) {
        // User is not a coach, redirect to become-coach page
        $_SESSION['error_message'] = "You must be a coach to access this page";
        header("Location: become-coach.php");
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$errors = [];
$success = false;

// Get coach's service tiers
try {
    $stmt = $pdo->prepare("SELECT * FROM ServiceTiers WHERE coach_id = ? ORDER BY price ASC");
    $stmt->execute([$coach['coach_id']]);
    $serviceTiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get analytics data for each tier
    $tierData = [];
    foreach ($serviceTiers as $tier) {
        $tierId = $tier['tier_id'];
        
        // Get total inquiries for this tier
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as inquiry_count,
                   SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                   SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_count,
                   SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count
            FROM ServiceInquiries
            WHERE coach_id = ? AND tier_id = ?
        ");
        $stmt->execute([$coach['coach_id'], $tierId]);
        $inquiryStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get total sessions for this tier
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as session_count,
                   SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_count,
                   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                   SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
            FROM Sessions
            WHERE coach_id = ? AND tier_id = ?
        ");
        $stmt->execute([$coach['coach_id'], $tierId]);
        $sessionStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get average rating for this tier
        $stmt = $pdo->prepare("
            SELECT AVG(r.rating) as avg_rating, COUNT(r.review_id) as review_count
            FROM Reviews r
            JOIN Sessions s ON r.session_id = s.session_id
            WHERE s.coach_id = ? AND s.tier_id = ?
        ");
        $stmt->execute([$coach['coach_id'], $tierId]);
        $ratingStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate revenue from completed sessions
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as completed_sessions, SUM(st.price) as total_revenue
            FROM Sessions s
            JOIN ServiceTiers st ON s.tier_id = st.tier_id
            WHERE s.coach_id = ? AND s.tier_id = ? AND s.status = 'completed'
        ");
        $stmt->execute([$coach['coach_id'], $tierId]);
        $revenueStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Store all stats together
        $tierData[$tierId] = [
            'tier' => $tier,
            'inquiry_stats' => $inquiryStats,
            'session_stats' => $sessionStats,
            'rating_stats' => $ratingStats,
            'revenue_stats' => $revenueStats
        ];
    }
    
    // Calculate conversion rates and other derived metrics
    foreach ($tierData as $tierId => &$data) {
        $inquiries = max(1, $data['inquiry_stats']['inquiry_count']);
        $sessions = $data['session_stats']['session_count'];
        
        $data['conversion_rate'] = round(($sessions / $inquiries) * 100, 1);
        $data['avg_rating'] = $data['rating_stats']['avg_rating'] ? round($data['rating_stats']['avg_rating'], 1) : 'N/A';
        $data['completed_sessions'] = $data['revenue_stats']['completed_sessions'];
        $data['total_revenue'] = $data['revenue_stats']['total_revenue'] ? $data['revenue_stats']['total_revenue'] : 0;
    }
    
    // Get the best performing tier based on total revenue
    $bestTier = null;
    $maxRevenue = 0;
    foreach ($tierData as $tierId => $data) {
        if ($data['total_revenue'] > $maxRevenue) {
            $maxRevenue = $data['total_revenue'];
            $bestTier = $tierId;
        }
    }
    
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Coach Dashboard</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="edit-coach-profile.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-person-badge"></i> Profile
                    </a>
                    <a href="edit-coach-skills.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-stars"></i> Skills & Expertise
                    </a>
                    <a href="edit-coach-availability.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-calendar-check"></i> Availability
                    </a>
                    <a href="edit-coach-services.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-list-check"></i> Service Tiers
                    </a>
                    <a href="service-analytics.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-graph-up"></i> Service Analytics
                    </a>
                    <a href="coach-profile.php?id=<?= $coach['coach_id'] ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-eye"></i> View Public Profile
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h2 class="h4 mb-0">Service Tier Performance</h2>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($serviceTiers)): ?>
                        <div class="alert alert-info">
                            <p>You haven't created any service tiers yet. Please create service tiers to view analytics.</p>
                            <a href="edit-coach-services.php" class="btn btn-primary">Create Service Tiers</a>
                        </div>
                    <?php else: ?>
                        <!-- Performance Summary -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Performance Summary</h5>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="metric-card text-center p-3">
                                                    <h6 class="text-muted">Total Inquiries</h6>
                                                    <h3><?= array_sum(array_column(array_column($tierData, 'inquiry_stats'), 'inquiry_count')) ?></h3>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="metric-card text-center p-3">
                                                    <h6 class="text-muted">Total Sessions</h6>
                                                    <h3><?= array_sum(array_column(array_column($tierData, 'session_stats'), 'session_count')) ?></h3>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="metric-card text-center p-3">
                                                    <h6 class="text-muted">Completed Sessions</h6>
                                                    <h3><?= array_sum(array_column($tierData, 'completed_sessions')) ?></h3>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="metric-card text-center p-3">
                                                    <h6 class="text-muted">Total Revenue</h6>
                                                    <h3>$<?= number_format(array_sum(array_column($tierData, 'total_revenue')), 2) ?></h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Best Performing Tier -->
                        <?php if ($bestTier): ?>
                            <div class="alert alert-success mb-4">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="bi bi-trophy fs-2"></i>
                                    </div>
                                    <div>
                                        <h5 class="alert-heading">Best Performing Service Tier</h5>
                                        <p class="mb-0">
                                            <strong><?= htmlspecialchars($tierData[$bestTier]['tier']['name']) ?></strong> 
                                            is your best performing tier with 
                                            <strong>$<?= number_format($tierData[$bestTier]['total_revenue'], 2) ?></strong> 
                                            in revenue from 
                                            <strong><?= $tierData[$bestTier]['completed_sessions'] ?></strong> 
                                            completed sessions.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Service Tier Comparison -->
                        <h5 class="mb-3">Service Tier Comparison</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Service Tier</th>
                                        <th>Price</th>
                                        <th>Inquiries</th>
                                        <th>Conversion Rate</th>
                                        <th>Sessions</th>
                                        <th>Average Rating</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($serviceTiers as $tier): ?>
                                        <?php $tierId = $tier['tier_id']; ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($tier['name']) ?></strong>
                                            </td>
                                            <td>$<?= number_format($tier['price'], 2) ?></td>
                                            <td><?= $tierData[$tierId]['inquiry_stats']['inquiry_count'] ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                        <div class="progress-bar" role="progressbar" 
                                                             style="width: <?= $tierData[$tierId]['conversion_rate'] ?>%;" 
                                                             aria-valuenow="<?= $tierData[$tierId]['conversion_rate'] ?>" 
                                                             aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <span><?= $tierData[$tierId]['conversion_rate'] ?>%</span>
                                                </div>
                                            </td>
                                            <td><?= $tierData[$tierId]['session_stats']['session_count'] ?></td>
                                            <td>
                                                <?php if ($tierData[$tierId]['avg_rating'] !== 'N/A'): ?>
                                                    <div class="d-flex align-items-center">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <?php if ($i <= floor($tierData[$tierId]['avg_rating'])): ?>
                                                                <i class="bi bi-star-fill text-warning"></i>
                                                            <?php elseif ($i - 0.5 <= $tierData[$tierId]['avg_rating']): ?>
                                                                <i class="bi bi-star-half text-warning"></i>
                                                            <?php else: ?>
                                                                <i class="bi bi-star text-warning"></i>
                                                            <?php endif; ?>
                                                        <?php endfor; ?>
                                                        <span class="ms-2"><?= $tierData[$tierId]['avg_rating'] ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">No ratings</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong>$<?= number_format($tierData[$tierId]['total_revenue'], 2) ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Recommendations -->
                        <div class="card mt-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Recommendations</h5>
                            </div>
                            <div class="card-body">
                                <ul class="recommendation-list">
                                    <?php if (count($serviceTiers) < 3): ?>
                                        <li>
                                            <strong>Consider adding more service tiers:</strong> 
                                            Offering multiple tiers (basic, standard, premium) gives customers options at 
                                            different price points and can increase overall revenue.
                                            <a href="edit-coach-services.php" class="btn btn-sm btn-outline-primary mt-2">Add New Tier</a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php foreach ($tierData as $tierId => $data): ?>
                                        <?php if ($data['conversion_rate'] < 50 && $data['inquiry_stats']['inquiry_count'] > 5): ?>
                                            <li>
                                                <strong>Improve conversion for "<?= htmlspecialchars($data['tier']['name']) ?>":</strong> 
                                                This tier has <?= $data['inquiry_stats']['inquiry_count'] ?> inquiries but only a <?= $data['conversion_rate'] ?>% 
                                                conversion rate. Consider improving the description or adjusting the price.
                                                <a href="edit-coach-services.php" class="btn btn-sm btn-outline-primary mt-2">Edit This Tier</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php if ($data['avg_rating'] !== 'N/A' && $data['avg_rating'] < 4 && $data['rating_stats']['review_count'] > 2): ?>
                                            <li>
                                                <strong>Address satisfaction issues with "<?= htmlspecialchars($data['tier']['name']) ?>":</strong> 
                                                This tier has an average rating of <?= $data['avg_rating'] ?>/5. Consider reviewing 
                                                customer feedback and improving the service quality.
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    
                                    <?php if ($bestTier): ?>
                                        <li>
                                            <strong>Focus on your best performer:</strong> 
                                            "<?= htmlspecialchars($tierData[$bestTier]['tier']['name']) ?>" is generating the most revenue. 
                                            Consider promoting this tier more prominently on your profile.
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.metric-card {
    border-radius: 10px;
    border: 1px solid #e0e0e0;
    background-color: white;
}

.recommendation-list {
    list-style-type: none;
    padding-left: 0;
}

.recommendation-list li {
    border-left: 4px solid #3d6bfd;
    padding: 15px;
    margin-bottom: 15px;
    background-color: #f8f9fa;
    border-radius: 0 5px 5px 0;
}
</style>

<?php include '../includes/footer.php'; ?> 
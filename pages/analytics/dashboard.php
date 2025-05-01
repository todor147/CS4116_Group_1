<?php
session_start();
require_once __DIR__ . '/../../includes/db_connection.php';
require_once __DIR__ . '/../../includes/auth_functions.php';

// Redirect to login if not logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: ../login.php?redirect=analytics/dashboard.php");
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
        $_SESSION['error_message'] = "You must be a coach to access analytics";
        header("Location: ../become-coach.php");
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Set time period filter
$timeFilter = $_GET['time_period'] ?? 'last_30_days';
$dateFormat = '%Y-%m-%d';

switch ($timeFilter) {
    case 'last_7_days':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $dateTitle = 'Last 7 Days';
        break;
    case 'last_30_days':
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $dateTitle = 'Last 30 Days';
        break;
    case 'last_90_days':
        $startDate = date('Y-m-d', strtotime('-90 days'));
        $dateTitle = 'Last 90 Days';
        break;
    case 'this_year':
        $startDate = date('Y-01-01');
        $dateTitle = 'This Year (' . date('Y') . ')';
        break;
    case 'all_time':
        $startDate = '2000-01-01'; // Very old date to include all data
        $dateTitle = 'All Time';
        break;
    default:
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $dateTitle = 'Last 30 Days';
}

$endDate = date('Y-m-d'); // Today

$errors = [];
$analytics = [];

try {
    // 1. Overall stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT s.session_id) as total_sessions,
            COUNT(DISTINCT CASE WHEN s.status = 'completed' THEN s.session_id END) as completed_sessions,
            COUNT(DISTINCT CASE WHEN s.status = 'cancelled' THEN s.session_id END) as cancelled_sessions,
            COUNT(DISTINCT si.inquiry_id) as total_inquiries,
            COUNT(DISTINCT CASE WHEN s.status = 'completed' THEN u.user_id END) as total_students,
            IFNULL(SUM(CASE WHEN s.status = 'completed' THEN st.price ELSE 0 END), 0) as total_revenue,
            IFNULL(AVG(CASE WHEN r.review_id IS NOT NULL THEN r.rating ELSE NULL END), 0) as avg_rating,
            COUNT(DISTINCT r.review_id) as total_reviews
        FROM Coaches c
        LEFT JOIN serviceinquiries si ON c.coach_id = si.coach_id AND si.created_at BETWEEN ? AND ?
        LEFT JOIN Sessions s ON c.coach_id = s.coach_id AND s.scheduled_time BETWEEN ? AND ?
        LEFT JOIN ServiceTiers st ON s.tier_id = st.tier_id
        LEFT JOIN Users u ON s.learner_id = u.user_id
        LEFT JOIN Reviews r ON s.coach_id = r.coach_id AND r.created_at BETWEEN ? AND ?
        WHERE c.coach_id = ?
    ");
    $stmt->execute([$startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $coach['coach_id']]);
    $analytics['overall'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Time series data for sessions, revenue and inquiries (daily data)
    $stmt = $pdo->prepare("
        SELECT 
            DATE(s.scheduled_time) as date,
            COUNT(DISTINCT s.session_id) as session_count,
            IFNULL(SUM(CASE WHEN s.status = 'completed' THEN st.price ELSE 0 END), 0) as daily_revenue
        FROM Sessions s
        JOIN ServiceTiers st ON s.tier_id = st.tier_id
        WHERE s.coach_id = ? AND s.scheduled_time BETWEEN ? AND ?
        GROUP BY DATE(s.scheduled_time)
        ORDER BY date
    ");
    $stmt->execute([$coach['coach_id'], $startDate, $endDate]);
    $analytics['daily_sessions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Time series data for inquiries
    $stmt = $pdo->prepare("
        SELECT 
            DATE(si.created_at) as date,
            COUNT(DISTINCT si.inquiry_id) as inquiry_count
        FROM serviceinquiries si
        WHERE si.coach_id = ? AND si.created_at BETWEEN ? AND ?
        GROUP BY DATE(si.created_at)
        ORDER BY date
    ");
    $stmt->execute([$coach['coach_id'], $startDate, $endDate]);
    $analytics['daily_inquiries'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Rating distribution
    $stmt = $pdo->prepare("
        SELECT 
            r.rating,
            COUNT(r.review_id) as count
        FROM Reviews r
        WHERE r.coach_id = ? AND r.created_at BETWEEN ? AND ?
        GROUP BY r.rating
        ORDER BY r.rating
    ");
    $stmt->execute([$coach['coach_id'], $startDate, $endDate]);
    $analytics['rating_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Service tier performance
    $stmt = $pdo->prepare("
        SELECT 
            st.tier_id,
            st.name,
            st.price,
            COUNT(DISTINCT s.session_id) as session_count,
            COUNT(DISTINCT CASE WHEN s.status = 'completed' THEN s.session_id END) as completed_count,
            IFNULL(SUM(CASE WHEN s.status = 'completed' THEN st.price ELSE 0 END), 0) as tier_revenue,
            COUNT(DISTINCT si.inquiry_id) as inquiry_count
        FROM ServiceTiers st
        LEFT JOIN Sessions s ON st.tier_id = s.tier_id AND s.scheduled_time BETWEEN ? AND ?
        LEFT JOIN serviceinquiries si ON st.tier_id = si.tier_id AND si.created_at BETWEEN ? AND ?
        WHERE st.coach_id = ?
        GROUP BY st.tier_id, st.name, st.price
        ORDER BY tier_revenue DESC
    ");
    $stmt->execute([$startDate, $endDate, $startDate, $endDate, $coach['coach_id']]);
    $analytics['tier_performance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Student retention (returning vs new students)
    $stmt = $pdo->prepare("
        SELECT
            u.user_id,
            u.username,
            COUNT(s.session_id) as session_count,
            MIN(s.scheduled_time) as first_session,
            MAX(s.scheduled_time) as last_session,
            IFNULL(SUM(CASE WHEN s.status = 'completed' THEN st.price ELSE 0 END), 0) as student_revenue
        FROM Users u
        JOIN Sessions s ON u.user_id = s.learner_id
        JOIN ServiceTiers st ON s.tier_id = st.tier_id
        WHERE s.coach_id = ? AND s.scheduled_time BETWEEN ? AND ?
        GROUP BY u.user_id, u.username
        ORDER BY session_count DESC
        LIMIT 10
    ");
    $stmt->execute([$coach['coach_id'], $startDate, $endDate]);
    $analytics['top_students'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 7. Conversion rates (inquiries to sessions)
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT si.inquiry_id) as total_inquiries,
            COUNT(DISTINCT s.session_id) as converted_to_sessions
        FROM serviceinquiries si
        LEFT JOIN Sessions s ON si.inquiry_id = s.inquiry_id
        WHERE si.coach_id = ? AND si.created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$coach['coach_id'], $startDate, $endDate]);
    $analytics['conversion'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// Include header
include __DIR__ . '/../../includes/header.php';
?>

<!-- Add Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<div class="container-fluid mt-4 mb-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Coach Dashboard</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="../edit-coach-profile.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-person-badge"></i> Profile
                    </a>
                    <a href="../edit-coach-skills.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-stars"></i> Skills & Expertise
                    </a>
                    <a href="../edit-coach-availability.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-calendar-check"></i> Availability
                    </a>
                    <a href="../edit-coach-services.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-list-check"></i> Service Tiers
                    </a>
                    <a href="../service-analytics.php" class="list-group-item list-group-item-action">
                        <i class="bi bi-graph-up"></i> Service Analytics
                    </a>
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="bi bi-speedometer2"></i> Analytics Dashboard
                    </a>
                    <a href="../coach-profile.php?id=<?= $coach['coach_id'] ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-eye"></i> View Public Profile
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Coach Analytics Dashboard</h1>
                <div class="d-flex">
                    <div class="btn-group">
                        <a href="?time_period=last_7_days" class="btn btn-outline-primary <?= $timeFilter === 'last_7_days' ? 'active' : '' ?>">7 Days</a>
                        <a href="?time_period=last_30_days" class="btn btn-outline-primary <?= $timeFilter === 'last_30_days' ? 'active' : '' ?>">30 Days</a>
                        <a href="?time_period=last_90_days" class="btn btn-outline-primary <?= $timeFilter === 'last_90_days' ? 'active' : '' ?>">90 Days</a>
                        <a href="?time_period=this_year" class="btn btn-outline-primary <?= $timeFilter === 'this_year' ? 'active' : '' ?>">This Year</a>
                        <a href="?time_period=all_time" class="btn btn-outline-primary <?= $timeFilter === 'all_time' ? 'active' : '' ?>">All Time</a>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Key Performance Indicators -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card h-100 border-primary">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-2">Total Revenue</h6>
                            <h3 class="mb-0">$<?= number_format($analytics['overall']['total_revenue'], 2) ?></h3>
                            <p class="small text-muted mt-2 mb-0"><?= $dateTitle ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 border-success">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-2">Completed Sessions</h6>
                            <h3 class="mb-0"><?= $analytics['overall']['completed_sessions'] ?></h3>
                            <p class="small text-muted mt-2 mb-0">of <?= $analytics['overall']['total_sessions'] ?> total</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 border-info">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-2">Average Rating</h6>
                            <h3 class="mb-0"><?= number_format($analytics['overall']['avg_rating'], 1) ?> / 5.0</h3>
                            <p class="small text-muted mt-2 mb-0">from <?= $analytics['overall']['total_reviews'] ?> reviews</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 border-warning">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-2">Unique Students</h6>
                            <h3 class="mb-0"><?= $analytics['overall']['total_students'] ?></h3>
                            <p class="small text-muted mt-2 mb-0"><?= $dateTitle ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card shadow h-100">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Sessions & Revenue Trend</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="sessionsRevenueChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow h-100">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Ratings Distribution</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="ratingsChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Conversion Rate & Service Tiers -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card shadow h-100">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Inquiry to Session Conversion</h5>
                        </div>
                        <div class="card-body text-center">
                            <?php
                            $conversionRate = 0;
                            if ($analytics['conversion']['total_inquiries'] > 0) {
                                $conversionRate = ($analytics['conversion']['converted_to_sessions'] / $analytics['conversion']['total_inquiries']) * 100;
                            }
                            ?>
                            <div class="conversion-gauge mb-3">
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?= $conversionRate ?>%;" 
                                         aria-valuenow="<?= $conversionRate ?>" 
                                         aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <h2 class="mb-1"><?= number_format($conversionRate, 1) ?>%</h2>
                            <p class="text-muted">
                                <?= $analytics['conversion']['converted_to_sessions'] ?> sessions from 
                                <?= $analytics['conversion']['total_inquiries'] ?> inquiries
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card shadow h-100">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Service Tier Performance</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-borderless table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Service Tier</th>
                                            <th>Price</th>
                                            <th>Sessions</th>
                                            <th>Revenue</th>
                                            <th>% of Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $totalRevenue = $analytics['overall']['total_revenue'];
                                        foreach ($analytics['tier_performance'] as $tier): 
                                            $percentage = $totalRevenue > 0 ? ($tier['tier_revenue'] / $totalRevenue) * 100 : 0;
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($tier['name']) ?></td>
                                                <td>$<?= number_format($tier['price'], 2) ?></td>
                                                <td><?= $tier['completed_count'] ?></td>
                                                <td>$<?= number_format($tier['tier_revenue'], 2) ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                            <div class="progress-bar bg-primary" style="width: <?= $percentage ?>%"></div>
                                                        </div>
                                                        <span><?= number_format($percentage, 1) ?>%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Students -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Top Students</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Student</th>
                                            <th>Sessions</th>
                                            <th>First Session</th>
                                            <th>Last Session</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($analytics['top_students'] as $student): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($student['username']) ?></td>
                                                <td><?= $student['session_count'] ?></td>
                                                <td><?= date('M d, Y', strtotime($student['first_session'])) ?></td>
                                                <td><?= date('M d, Y', strtotime($student['last_session'])) ?></td>
                                                <td>$<?= number_format($student['student_revenue'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($analytics['top_students'])): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No student data available for the selected time period.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Prepare chart data
const sessionsData = <?= json_encode(array_column($analytics['daily_sessions'], 'session_count')) ?>;
const revenueData = <?= json_encode(array_column($analytics['daily_sessions'], 'daily_revenue')) ?>;
const dates = <?= json_encode(array_column($analytics['daily_sessions'], 'date')) ?>;

// Sessions & Revenue Chart
const ctx1 = document.getElementById('sessionsRevenueChart').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: dates,
        datasets: [
            {
                label: 'Sessions',
                data: sessionsData,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderWidth: 2,
                tension: 0.1,
                yAxisID: 'y'
            },
            {
                label: 'Revenue ($)',
                data: revenueData,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderWidth: 2,
                tension: 0.1,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        stacked: false,
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Sessions'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false,
                },
                title: {
                    display: true,
                    text: 'Revenue ($)'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Date'
                }
            }
        }
    }
});

// Ratings Distribution Chart
const ratingsData = [];
const ratingsLabels = ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'];
const ratingsColors = [
    'rgba(255, 99, 132, 0.7)',
    'rgba(255, 159, 64, 0.7)',
    'rgba(255, 205, 86, 0.7)',
    'rgba(75, 192, 192, 0.7)',
    'rgba(54, 162, 235, 0.7)'
];

// Initialize with zeros
for (let i = 0; i < 5; i++) {
    ratingsData[i] = 0;
}

// Fill in actual data
<?php foreach ($analytics['rating_distribution'] as $rating): ?>
    ratingsData[<?= $rating['rating'] - 1 ?>] = <?= $rating['count'] ?>;
<?php endforeach; ?>

const ctx2 = document.getElementById('ratingsChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ratingsLabels,
        datasets: [{
            data: ratingsData,
            backgroundColor: ratingsColors,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const index = context.dataIndex;
                        const value = context.dataset.data[index];
                        const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                        return `${value} reviews (${percentage}%)`;
                    }
                }
            }
        }
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?> 
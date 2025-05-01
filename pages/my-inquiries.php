<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';

// Helper function to get appropriate badge class for status
function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'bg-warning';
        case 'accepted':
            return 'bg-success';
        case 'completed':
            return 'bg-info';
        case 'cancelled':
            return 'bg-secondary';
        case 'rejected':
            return 'bg-danger';
        default:
            return 'bg-light';
    }
}

// Helper function to check if a table exists
function tableExists($pdo, $table) {
    try {
        $result = $pdo->query("SHOW TABLES LIKE '{$table}'");
        return $result->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'regular';
$is_coach = ($user_type === 'business');

// Get inquiries
try {
    if ($is_coach) {
        // Get coach ID 
        $stmt = $pdo->prepare("SELECT coach_id FROM Coaches WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $coach = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$coach) {
            throw new Exception("Coach profile not found");
        }
        
        $coach_id = $coach['coach_id'];
        
        // Get inquiries for coach
        $stmt = $pdo->prepare("
            SELECT si.*, u.username as learner_name, st.name as tier_name
            FROM ServiceInquiries si
            JOIN Users u ON si.user_id = u.user_id
            JOIN ServiceTiers st ON si.tier_id = st.tier_id
            WHERE si.coach_id = ?
            ORDER BY si.created_at DESC
        ");
        $stmt->execute([$coach_id]);
    } else {
        // Get inquiries for learner
        $stmt = $pdo->prepare("
            SELECT si.*, u.username as coach_name, st.name as tier_name
            FROM ServiceInquiries si
            JOIN Coaches c ON si.coach_id = c.coach_id
            JOIN Users u ON c.user_id = u.user_id
            JOIN ServiceTiers st ON si.tier_id = st.tier_id
            WHERE si.user_id = ?
            ORDER BY si.created_at DESC
        ");
        $stmt->execute([$user_id]);
    }
    
    $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = $e->getMessage();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fs-2 fw-bold"><?= $is_coach ? 'Service Inquiries From Learners' : 'My Service Inquiries' ?></h1>
        <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">All Inquiries</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($inquiries)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
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
                                    <td><?= date('M j, Y', strtotime($inquiry['created_at'])) ?></td>
                                    <td>
                                        <?php if ($is_coach): ?>
                                            <?= htmlspecialchars($inquiry['learner_name']) ?>
                                        <?php else: ?>
                                            <?= htmlspecialchars($inquiry['coach_name']) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($inquiry['tier_name']) ?></td>
                                    <td>
                                        <span class="badge <?= getStatusBadgeClass($inquiry['status']) ?>">
                                            <?= ucfirst($inquiry['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="inquiry-details.php?id=<?= $inquiry['inquiry_id'] ?>" class="btn btn-sm btn-outline-primary" onclick="window.location.href='inquiry-details.php?id=<?= $inquiry['inquiry_id'] ?>'; return false;">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center py-4">No service inquiries found.</p>
                <?php if (!$is_coach): ?>
                    <div class="text-center">
                        <a href="coach-search.php" class="btn btn-primary">Find a Coach</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Mark notification as read if ID is provided
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notification_id = (int)$_GET['mark_read'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE Notifications 
            SET is_read = 1 
            WHERE notification_id = ? AND user_id = ?
        ");
        $stmt->execute([$notification_id, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            $success = "Notification marked as read.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Mark all notifications as read
if (isset($_GET['mark_all_read'])) {
    try {
        $stmt = $pdo->prepare("
            UPDATE Notifications 
            SET is_read = 1 
            WHERE user_id = ?
        ");
        $stmt->execute([$user_id]);
        
        if ($stmt->rowCount() > 0) {
            $success = "All notifications marked as read.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Delete notification if ID is provided
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notification_id = (int)$_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("
            DELETE FROM Notifications 
            WHERE notification_id = ? AND user_id = ?
        ");
        $stmt->execute([$notification_id, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            $success = "Notification deleted.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get notifications for the user
$notifications = [];
$unread_count = 0;

try {
    // Get total unread count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM Notifications 
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$user_id]);
    $unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get notifications with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 10;
    $offset = ($page - 1) * $per_page;
    
    $stmt = $pdo->prepare("
        SELECT * FROM Notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$user_id, $per_page, $offset]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM Notifications 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $total_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $total_pages = ceil($total_count / $per_page);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-bell me-2"></i>Notifications</h5>
                    <?php if (count($notifications) > 0): ?>
                    <a href="?mark_all_read=1" class="btn btn-light btn-sm">Mark All as Read</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <?php if (count($notifications) > 0): ?>
                        <div class="list-group">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-start <?= $notification['is_read'] ? '' : 'list-group-item-light' ?>">
                                    <div class="ms-2 me-auto">
                                        <?php if (!$notification['is_read']): ?>
                                            <span class="badge bg-primary rounded-pill float-end">New</span>
                                        <?php endif; ?>
                                        
                                        <div class="fw-bold"><?= htmlspecialchars($notification['title']) ?></div>
                                        <p class="mb-1"><?= htmlspecialchars($notification['message']) ?></p>
                                        <small class="text-muted">
                                            <?= date('F j, Y g:i a', strtotime($notification['created_at'])) ?>
                                        </small>
                                        <div class="mt-2">
                                            <?php if (!empty($notification['link'])): ?>
                                                <a href="<?= htmlspecialchars($notification['link']) ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                            <?php endif; ?>
                                            
                                            <?php if (!$notification['is_read']): ?>
                                                <a href="?mark_read=<?= $notification['notification_id'] ?>" class="btn btn-sm btn-outline-secondary">Mark as Read</a>
                                            <?php endif; ?>
                                            
                                            <a href="?delete=<?= $notification['notification_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this notification?')">Delete</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Notifications pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-bell-slash text-muted" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">No Notifications</h5>
                            <p class="text-muted">You don't have any notifications at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
<?php
session_start();

// Log the session state
error_log("Admin page accessed by: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'not logged in'));

// Check if user is admin
if ((!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) && 
    (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin')) {
    // Log the failure
    error_log("Admin access denied - redirecting to login");
    
    // Clear the session to ensure a clean state
    session_unset();
    session_destroy();
    
    header('Location: login.php');
    exit;
}

require __DIR__ . '/../includes/db_connection.php';

$error = '';
$success = '';

try {
    // Get statistics
    $total_users = $pdo->query("SELECT COUNT(*) FROM Users")->fetchColumn();
    $total_coaches = $pdo->query("SELECT COUNT(*) FROM Coaches")->fetchColumn();
    $total_sessions = $pdo->query("SELECT COUNT(*) FROM Sessions")->fetchColumn();
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Admin Dashboard</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Users</h5>
                            <p class="card-text display-4"><?= $total_users ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Coaches</h5>
                            <p class="card-text display-4"><?= $total_coaches ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Sessions</h5>
                            <p class="card-text display-4"><?= $total_sessions ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <a href="manage-users.php" class="btn btn-primary btn-lg">Manage Users</a>
                        <a href="review-moderation.php" class="btn btn-info btn-lg">Review Moderation</a>
                        <a href="message-moderation.php" class="btn btn-warning btn-lg">Message Moderation</a>
                        <a href="banned-words.php" class="btn btn-danger btn-lg">Banned Words</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
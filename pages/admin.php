<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
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

include __DIR__ . '/../includes/header.php';
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
                    <div class="d-grid gap-4">
                        <a href="manage-users.php" class="btn btn-primary">Manage Users</a>
                        <a href="manage-coaches.php" class="btn btn-success">Manage Coaches</a>
                        <a href="content-restrictions.php" class="btn btn-info">Content Moderation</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
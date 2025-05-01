<?php
session_start();

require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';

// Check if user is admin (same check as in admin.php)
if ((!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) && 
    (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin')) {
    // Clear the session to ensure a clean state
    session_unset();
    session_destroy();
    
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

try {
    // Check if status column exists in Reviews table
    $columnsQuery = $pdo->query("SHOW COLUMNS FROM Reviews LIKE 'status'");
    $statusColumnExists = $columnsQuery->rowCount() > 0;
    
    // Create SQL query based on whether status column exists
    if ($statusColumnExists) {
        // Fetch pending reviews if status column exists
        $stmt = $pdo->prepare("SELECT r.review_id, r.user_id, r.coach_id, r.comment, r.rating, r.created_at, 
                                u.username as reviewer_name, c.user_id as coach_user_id
                              FROM Reviews r
                              JOIN Users u ON r.user_id = u.user_id
                              JOIN Coaches c ON r.coach_id = c.coach_id
                              WHERE r.status = 'pending' OR r.status IS NULL
                              ORDER BY r.created_at DESC");
    } else {
        // Fetch all reviews if status column doesn't exist
        $stmt = $pdo->prepare("SELECT r.review_id, r.user_id, r.coach_id, r.comment, r.rating, r.created_at,
                                u.username as reviewer_name, c.user_id as coach_user_id
                              FROM Reviews r
                              JOIN Users u ON r.user_id = u.user_id
                              JOIN Coaches c ON r.coach_id = c.coach_id
                              ORDER BY r.created_at DESC");
    }
    
    $stmt->execute();
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add status column if it doesn't exist
    if (!$statusColumnExists) {
        try {
            $pdo->exec("ALTER TABLE Reviews ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
            $success = "Added status column to Reviews table for moderation functionality";
        } catch (PDOException $e) {
            $error = "Failed to add status column: " . $e->getMessage();
        }
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Review Moderation</h1>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Pending Reviews</h4>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <table class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Reviewer</th>
                                <th>Date</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (count($reviews) > 0) {
                                foreach ($reviews as $review) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($review['reviewer_name']) . " (ID: " . $review['user_id'] . ")</td>";
                                    echo "<td>" . date('M j, Y', strtotime($review['created_at'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($review['rating']) . " ★</td>";
                                    echo "<td>" . htmlspecialchars($review['comment']) . "</td>";
                                    echo "<td>
                                        <form method='post' action='moderationlogic.php'>
                                            <input type='hidden' name='review_id' value='" . $review['review_id'] . "'>
                                            <button type='submit' name='action' value='approve' class='btn btn-sm btn-success'>Approve</button>
                                            <button type='submit' name='action' value='reject' class='btn btn-sm btn-danger'>Reject</button>
                                        </form>
                                    </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5'>No pending reviews found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>


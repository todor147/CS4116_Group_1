<?php
session_start();

require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

try {
    // Fetch pending reviews
    $stmt = $pdo->prepare("SELECT review_id, user_id, coach_id, comment, rating FROM Reviews WHERE status = 'pending'");
    $stmt->execute();
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <table class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>User ID</th>
                                <th>Coach ID</th>
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
                                    echo "<td>" . htmlspecialchars($review['user_id']) . "</td>";
                                    echo "<td>" . htmlspecialchars($review['coach_id']) . "</td>";
                                    echo "<td>" . htmlspecialchars($review['rating']) . "</td>";
                                    echo "<td>" . htmlspecialchars($review['comment']) . "</td>";
                                    echo "<td>
                                        <form method='post' action='moderate_review.php'>
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


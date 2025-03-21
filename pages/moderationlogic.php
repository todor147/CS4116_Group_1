<?php
session_start();

require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $review_id = $_POST['review_id'];
    $action = $_POST['action'];

    if ($action === 'approve' || $action === 'reject') {
        $new_status = $action === 'approve' ? 'approved' : 'rejected';

        $stmt = $pdo->prepare("UPDATE Reviews SET status = ? WHERE review_id = ?");
        $stmt->execute([$new_status, $review_id]);
    }
}

header("Location: review-moderation.php");
exit();
?>

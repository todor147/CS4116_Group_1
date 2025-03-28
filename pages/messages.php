<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';

if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch all conversations for the current user
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            CASE 
                WHEN m.sender_id = ? THEN m.receiver_id
                ELSE m.sender_id
            END as other_user_id,
            u.username,
            (SELECT COUNT(*) FROM Messages 
             WHERE receiver_id = ? 
             AND sender_id = other_user_id 
             AND is_read = 0) as unread_count,
            (SELECT created_at FROM Messages 
             WHERE (sender_id = ? AND receiver_id = other_user_id) 
             OR (sender_id = other_user_id AND receiver_id = ?)
             ORDER BY created_at DESC LIMIT 1) as last_message_time
        FROM Messages m
        JOIN Users u ON u.user_id = 
            CASE 
                WHEN m.sender_id = ? THEN m.receiver_id
                ELSE m.sender_id
            END
        WHERE m.sender_id = ? OR m.receiver_id = ?
        ORDER BY last_message_time DESC
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Conversations</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($conversations as $conv): ?>
                            <a href="conversation.php?user=<?= $conv['other_user_id'] ?>" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($conv['username']) ?>
                                <?php if ($conv['unread_count'] > 0): ?>
                                    <span class="badge badge-primary badge-pill"><?= $conv['unread_count'] ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Select a conversation</h5>
                </div>
                <div class="card-body">
                    <p class="text-center text-muted">Select a conversation from the list to view messages</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

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

// Get any session messages
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

try {
    // Fetch pending messages
    $stmt = $pdo->prepare("
        SELECT m.*, 
               sender.username as sender_name,
               receiver.username as receiver_name
        FROM Messages m
        JOIN Users sender ON m.sender_id = sender.user_id
        JOIN Users receiver ON m.receiver_id = receiver.user_id
        WHERE m.status = 'pending'
        ORDER BY m.created_at DESC
    ");
    $stmt->execute();
    $pending_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Message Moderation</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Pending Messages</h4>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>From</th>
                                <th>To</th>
                                <th>Message</th>
                                <th>Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pending_messages) > 0): ?>
                                <?php foreach ($pending_messages as $message): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($message['sender_name']) ?></td>
                                        <td><?= htmlspecialchars($message['receiver_name']) ?></td>
                                        <td><?= htmlspecialchars($message['content']) ?></td>
                                        <td><?= date('M j, Y g:i a', strtotime($message['created_at'])) ?></td>
                                        <td>
                                            <form method="post" action="moderate-message.php" class="d-inline">
                                                <input type="hidden" name="message_id" value="<?= $message['message_id'] ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No pending messages</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

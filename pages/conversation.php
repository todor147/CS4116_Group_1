<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';

if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$other_user_id = $_GET['user'] ?? null;
$error = '';
$success = '';

if (!$other_user_id) {
    header('Location: messages.php');
    exit;
}

try {
    // Get other user's details first
    $stmt = $pdo->prepare("
        SELECT u.username, c.coach_id 
        FROM Users u 
        LEFT JOIN Coaches c ON u.user_id = c.user_id 
        WHERE u.user_id = ?
    ");
    $stmt->execute([$other_user_id]);
    $other_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$other_user) {
        header('Location: messages.php');
        exit;
    }

    // Check for any pending messages from the current user
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as pending_count
        FROM Messages
        WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'
    ");
    $stmt->execute([$user_id, $other_user_id]);
    $pending_count = $stmt->fetch(PDO::FETCH_ASSOC)['pending_count'];

    // Mark messages as read
    $stmt = $pdo->prepare("
        UPDATE Messages 
        SET is_read = 1 
        WHERE sender_id = ? AND receiver_id = ? AND status = 'approved'
    ");
    $stmt->execute([$other_user_id, $user_id]);

    // Fetch conversation (only approved messages)
    $stmt = $pdo->prepare("
        SELECT m.*, u.username 
        FROM Messages m
        JOIN Users u ON u.user_id = m.sender_id
        WHERE ((m.sender_id = ? AND m.receiver_id = ?)
        OR (m.sender_id = ? AND m.receiver_id = ?))
        AND m.status = 'approved'
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
    <?php if ($pending_count > 0): ?>
        <div class="alert alert-info">
            You have <?= $pending_count ?> message(s) pending moderation.
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-warning">
            <?= htmlspecialchars($_SESSION['message']) ?>
            <?php unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Chat with <?= htmlspecialchars($other_user['username']) ?></h5>
            <a href="messages.php" class="btn btn-light btn-sm">Back to Messages</a>
        </div>
        <div class="card-body" style="height: 400px; overflow-y: auto;" id="messageContainer">
            <?php if (empty($messages)): ?>
                <div class="text-center text-muted my-4">
                    <p>No messages yet. Start the conversation!</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $message): ?>
                    <div class="mb-3 <?= $message['sender_id'] == $user_id ? 'text-right' : '' ?>">
                        <div class="d-inline-block p-2 rounded <?= $message['sender_id'] == $user_id ? 'bg-primary text-white' : 'bg-light' ?>" style="max-width: 70%;">
                            <?= htmlspecialchars($message['content']) ?>
                            <div class="small text-<?= $message['sender_id'] == $user_id ? 'light' : 'muted' ?>">
                                <?= date('M j, g:i a', strtotime($message['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <form method="POST" action="send_message.php" class="d-flex">
                <input type="hidden" name="receiver_id" value="<?= $other_user_id ?>">
                <input type="text" name="content" class="form-control mr-2" 
                    value="<?= empty($messages) ? 'Hello, could we discuss availability?' : '' ?>"
                    placeholder="Type your message..." required>
                <button type="submit" class="btn btn-primary">Send</button>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-scroll to bottom of messages
const messageContainer = document.getElementById('messageContainer');
messageContainer.scrollTop = messageContainer.scrollHeight;

// Check for new messages every 5 seconds
setInterval(() => {
    fetch(`get_new_messages.php?user=<?= $other_user_id ?>`)
        .then(response => response.json())
        .then(data => {
            if (data.messages && data.messages.length > 0) {
                // Add new messages to the container
                data.messages.forEach(message => {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = `mb-3 ${message.sender_id == <?= $user_id ?> ? 'text-right' : ''}`;
                    messageDiv.innerHTML = `
                        <div class="d-inline-block p-2 rounded ${message.sender_id == <?= $user_id ?> ? 'bg-primary text-white' : 'bg-light'}" style="max-width: 70%;">
                            ${message.content}
                            <div class="small text-${message.sender_id == <?= $user_id ?> ? 'light' : 'muted'}">
                                ${new Date(message.created_at).toLocaleString()}
                            </div>
                        </div>
                    `;
                    messageContainer.appendChild(messageDiv);
                });
                messageContainer.scrollTop = messageContainer.scrollHeight;
            }
        });
}, 5000);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

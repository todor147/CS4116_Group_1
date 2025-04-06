<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;
$error = '';
$success = '';
$messages = [];
$request = null;
$other_user = null;

// Validate request_id
if ($request_id <= 0) {
    $error = "Invalid request selected.";
} else {
    try {
        // Get request details and check if user is authorized to view it
        $stmt = $pdo->prepare("
            SELECT 
                cir.*,
                requester.id as requester_id,
                requester.first_name as requester_first_name,
                requester.last_name as requester_last_name,
                requester.username as requester_username,
                verified.id as verified_id,
                verified.first_name as verified_first_name,
                verified.last_name as verified_last_name,
                verified.username as verified_username,
                coach.id as coach_id,
                coach.first_name as coach_first_name,
                coach.last_name as coach_last_name,
                coach.username as coach_username
            FROM CustomerInsightRequests cir
            JOIN users requester ON cir.requester_id = requester.id
            JOIN users verified ON cir.verified_customer_id = verified.id
            JOIN users coach ON cir.coach_id = coach.id
            WHERE cir.id = ?
        ");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            $error = "The requested conversation could not be found.";
        } 
        // Check if user is part of this conversation
        elseif ($request['requester_id'] != $user_id && $request['verified_id'] != $user_id) {
            $error = "You are not authorized to view this conversation.";
        }
        // Check if the request is accepted
        elseif ($request['status'] != 'accepted') {
            if ($request['status'] == 'pending' && $request['verified_id'] == $user_id) {
                $error = "This request is still pending. Please accept or reject it first.";
            } elseif ($request['status'] == 'pending' && $request['requester_id'] == $user_id) {
                $error = "This request is still pending. Please wait for the verified customer to respond.";
            } else {
                $error = "This request has been rejected or is no longer active.";
            }
        } else {
            // Determine the other user in the conversation
            $other_user = ($user_id == $request['requester_id']) ? 
                [
                    'id' => $request['verified_id'],
                    'name' => $request['verified_first_name'] . ' ' . $request['verified_last_name'],
                    'username' => $request['verified_username'],
                    'role' => 'Verified Customer'
                ] : 
                [
                    'id' => $request['requester_id'],
                    'name' => $request['requester_first_name'] . ' ' . $request['requester_last_name'],
                    'username' => $request['requester_username'],
                    'role' => 'Potential Customer'
                ];
                
            // Get all messages
            $stmt = $pdo->prepare("
                SELECT 
                    cim.*,
                    sender.first_name as sender_first_name,
                    sender.last_name as sender_last_name,
                    recipient.first_name as recipient_first_name,
                    recipient.last_name as recipient_last_name
                FROM CustomerInsightMessages cim
                JOIN users sender ON cim.sender_id = sender.id
                JOIN users recipient ON cim.recipient_id = recipient.id
                WHERE cim.request_id = ?
                ORDER BY cim.created_at ASC
            ");
            $stmt->execute([$request_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Mark unread messages as read
            $stmt = $pdo->prepare("
                UPDATE CustomerInsightMessages
                SET is_read = 1
                WHERE request_id = ? AND recipient_id = ? AND is_read = 0
            ");
            $stmt->execute([$request_id, $user_id]);
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Process message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    if (empty($message)) {
        $error = "Please enter a message.";
    } else {
        try {
            // Insert the new message
            $stmt = $pdo->prepare("
                INSERT INTO CustomerInsightMessages 
                (request_id, sender_id, recipient_id, message)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$request_id, $user_id, $other_user['id'], $message]);
            
            // Redirect to prevent form resubmission
            header('Location: insight_messages.php?request_id=' . $request_id);
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <?php if (!empty($request)): ?>
                            Insight Conversation: <?= htmlspecialchars($request['coach_first_name'] . ' ' . $request['coach_last_name']) ?>
                        <?php else: ?>
                            Insight Conversation
                        <?php endif; ?>
                    </h5>
                    <div>
                        <?php if (!empty($request) && $user_id == $request['requester_id']): ?>
                            <a href="view_coach.php?id=<?= $request['coach_id'] ?>" class="btn btn-light btn-sm">Back to Coach Profile</a>
                        <?php else: ?>
                            <a href="manage_insight_requests.php" class="btn btn-light btn-sm">Back to Requests</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if (empty($error) && !empty($request)): ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="d-flex align-items-center">
                                <img src="assets/profile/default-avatar.png" alt="User" class="rounded-circle me-3" width="50">
                                <div>
                                    <h6 class="mb-0"><?= htmlspecialchars($other_user['name']) ?></h6>
                                    <p class="text-muted mb-0">@<?= htmlspecialchars($other_user['username']) ?> · <?= $other_user['role'] ?></p>
                                </div>
                            </div>
                            <div>
                                <span class="badge bg-success px-3 py-2">
                                    <i class="bi bi-chat-dots-fill me-1"></i> Active Conversation
                                </span>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <strong>
                                    <i class="bi bi-info-circle me-1"></i> 
                                    This conversation is about:
                                </strong>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <img src="assets/profile/default-avatar.png" alt="Coach" class="rounded-circle me-3" width="60">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($request['coach_first_name'] . ' ' . $request['coach_last_name']) ?></h6>
                                        <p class="text-muted mb-0">Coach</p>
                                    </div>
                                </div>
                                <hr>
                                <div class="small">
                                    <strong>Original request message:</strong>
                                    <p class="mb-0 mt-2"><?= nl2br(htmlspecialchars($request['message'])) ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-3 bg-light rounded mb-4" id="message-container" style="height: 400px; overflow-y: auto;">
                            <?php if (empty($messages)): ?>
                                <div class="text-center text-muted my-5">
                                    <i class="bi bi-chat-dots" style="font-size: 2rem;"></i>
                                    <p class="mt-3">No messages yet. Start the conversation!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): ?>
                                    <div class="mb-3 <?= $msg['sender_id'] == $user_id ? 'text-end' : '' ?>" data-message-id="<?= $msg['id'] ?>">
                                        <div class="d-inline-block p-3 rounded <?= $msg['sender_id'] == $user_id ? 'bg-primary text-white' : 'bg-white border' ?>" style="max-width: 80%;">
                                            <div class="mb-1 small <?= $msg['sender_id'] == $user_id ? 'text-white-50' : 'text-muted' ?>">
                                                <?= htmlspecialchars($msg['sender_first_name'] . ' ' . $msg['sender_last_name']) ?>
                                                · <?= date('M d, g:i a', strtotime($msg['created_at'])) ?>
                                            </div>
                                            <div>
                                                <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST" action="">
                            <div class="form-group mb-3">
                                <label for="message" class="visually-hidden">Your message</label>
                                <textarea class="form-control" id="message" name="message" rows="3" placeholder="Type your message here..." required></textarea>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send me-1"></i> Send Message
                                </button>
                            </div>
                        </form>

                        <!-- Add JavaScript for message updates -->
                        <script src="assets/js/insight_messages.js"></script>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
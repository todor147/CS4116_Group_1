<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';

if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
$error = '';
$success = '';

if (!$request_id) {
    header('Location: insight-requests.php');
    exit;
}

// Get request details and check permissions
$insight_request = null;
$other_user = null;
$coach = null;
try {
    $stmt = $pdo->prepare("
        SELECT 
            cir.*, 
            requester.user_id as requester_user_id,
            requester.username as requester_username,
            requester.profile_image as requester_image,
            verified.user_id as verified_user_id,
            verified.username as verified_username,
            verified.profile_image as verified_image,
            coach.coach_id,
            coach_user.username as coach_username,
            coach_user.profile_image as coach_image
        FROM CustomerInsightRequests cir
        JOIN Users requester ON cir.requester_id = requester.user_id
        JOIN Users verified ON cir.verified_customer_id = verified.user_id
        JOIN Coaches coach ON cir.coach_id = coach.coach_id
        JOIN Users coach_user ON coach.user_id = coach_user.user_id
        WHERE cir.request_id = ? AND (cir.requester_id = ? OR cir.verified_customer_id = ?)
        AND cir.status = 'accepted'
    ");
    $stmt->execute([$request_id, $user_id, $user_id]);
    $insight_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$insight_request) {
        // Either the request doesn't exist, user doesn't have permission, or request not accepted
        header('Location: insight-requests.php');
        exit;
    }
    
    // Determine who the other user is in this conversation
    $is_requester = ($user_id == $insight_request['requester_id']);
    $other_user_id = $is_requester ? $insight_request['verified_customer_id'] : $insight_request['requester_id'];
    $other_user = [
        'user_id' => $is_requester ? $insight_request['verified_user_id'] : $insight_request['requester_user_id'],
        'username' => $is_requester ? $insight_request['verified_username'] : $insight_request['requester_username'],
        'profile_image' => $is_requester ? $insight_request['verified_image'] : $insight_request['requester_image']
    ];
    
    $coach = [
        'coach_id' => $insight_request['coach_id'],
        'username' => $insight_request['coach_username'],
        'profile_image' => $insight_request['coach_image']
    ];
    
    // Mark messages as read
    $stmt = $pdo->prepare("
        UPDATE CustomerInsightMessages 
        SET is_read = 1 
        WHERE request_id = ? AND receiver_id = ? AND is_read = 0
    ");
    $stmt->execute([$request_id, $user_id]);
    
    // Get conversation messages
    $stmt = $pdo->prepare("
        SELECT cim.*, u.username 
        FROM CustomerInsightMessages cim
        JOIN Users u ON cim.sender_id = u.user_id
        WHERE cim.request_id = ?
        ORDER BY cim.created_at ASC
    ");
    $stmt->execute([$request_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Conversation Area -->
        <div class="col-md-9 mx-auto">
            <div class="card shadow-sm">
                <!-- Conversation Header -->
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <a href="insight-requests.php" class="btn btn-sm btn-outline-secondary me-3">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                        <div class="d-flex align-items-center">
                            <?php 
                            $profile_image = !empty($other_user['profile_image']) ? $other_user['profile_image'] : 'default.jpg';
                            $image_path = "../assets/images/profiles/{$profile_image}";
                            ?>
                            <img src="<?= $image_path ?>" alt="Profile" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                            <div class="ms-2">
                                <h5 class="mb-0"><?= htmlspecialchars($other_user['username']) ?></h5>
                                <div class="d-flex align-items-center small text-muted">
                                    <span>Insights about</span>
                                    <img src="../assets/images/profiles/<?= !empty($coach['profile_image']) ? $coach['profile_image'] : 'default.jpg' ?>" 
                                         alt="Coach" class="rounded-circle mx-1" style="width: 20px; height: 20px; object-fit: cover;">
                                    <span><?= htmlspecialchars($coach['username']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="coach-profile.php?id=<?= $coach['coach_id'] ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-person-badge"></i> View Coach Profile
                        </a>
                    </div>
                </div>
                
                <!-- Message Container -->
                <div class="card-body p-3 overflow-auto d-flex flex-column" id="messageContainer" style="height: 400px; min-height: 400px;">
                    <?php if (empty($messages)): ?>
                        <div class="text-center text-muted my-auto">
                            <div class="mb-3">
                                <i class="bi bi-chat-dots" style="font-size: 3rem;"></i>
                            </div>
                            <h5>No messages yet</h5>
                            <p>Send a message to start the conversation!</p>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column-reverse flex-grow-1">
                            <div>
                                <?php 
                                $last_date = '';
                                foreach ($messages as $message): 
                                    $message_date = date('Y-m-d', strtotime($message['created_at']));
                                    if ($message_date != $last_date) {
                                        echo '<div class="text-center my-3"><span class="badge bg-light text-dark px-3 py-2">' . format_date($message_date) . '</span></div>';
                                        $last_date = $message_date;
                                    }
                                ?>
                                    <div id="msg-<?= $message['message_id'] ?>" class="message <?= $message['sender_id'] == $user_id ? 'outgoing' : 'incoming' ?> mb-3">
                                        <div class="message-bubble">
                                            <?= nl2br(htmlspecialchars($message['content'])) ?>
                                            <div class="message-time">
                                                <?= date('g:i a', strtotime($message['created_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Message Input -->
                <div class="card-footer bg-white">
                    <form id="messageForm" class="d-flex align-items-center">
                        <input type="hidden" name="request_id" value="<?= $request_id ?>">
                        <input type="hidden" name="receiver_id" value="<?= $other_user['user_id'] ?>">
                        <textarea name="content" class="form-control me-2" placeholder="Type your message..." rows="1" required></textarea>
                        <button type="submit" class="btn btn-primary px-4">
                            Send
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Guidelines -->
            <div class="card mt-3 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Insight Guidelines</h5>
                </div>
                <div class="card-body">
                    <?php if ($is_requester): ?>
                        <p class="mb-2"><strong>Tips for requesting insights:</strong></p>
                        <ul class="small">
                            <li>Ask specific questions about the coach's teaching style, communication, and expertise</li>
                            <li>Inquire about their strengths and areas for improvement</li>
                            <li>Ask if they would recommend this coach and why</li>
                            <li>Be respectful of the verified customer's time and privacy</li>
                        </ul>
                    <?php else: ?>
                        <p class="mb-2"><strong>Tips for providing insights:</strong></p>
                        <ul class="small">
                            <li>Share your honest experience with the coach</li>
                            <li>Focus on helpful details about teaching style, expertise, and communication</li>
                            <li>Respect the coach's privacy by not sharing excessive personal details</li>
                            <li>You can end this conversation at any time by visiting your privacy settings</li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Message styling */
    .message {
        max-width: 75%;
        clear: both;
        position: relative;
    }
    
    .message.outgoing {
        float: right;
        margin-left: auto;
    }
    
    .message.incoming {
        float: left;
        margin-right: auto;
    }
    
    .message-bubble {
        padding: 0.75rem 1rem;
        border-radius: 1.25rem;
        position: relative;
        word-wrap: break-word;
    }
    
    .outgoing .message-bubble {
        background-color: #0d6efd;
        color: white;
        border-top-right-radius: 0.25rem;
    }
    
    .incoming .message-bubble {
        background-color: #f0f2f5;
        color: #212529;
        border-top-left-radius: 0.25rem;
    }
    
    .message-time {
        font-size: 0.7rem;
        margin-top: 0.25rem;
        opacity: 0.8;
    }
    
    .outgoing .message-time {
        text-align: right;
    }
    
    /* Auto-expand textarea */
    textarea {
        resize: none;
        overflow: hidden;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const messageContainer = document.getElementById('messageContainer');
    const messageForm = document.getElementById('messageForm');
    const messageInput = document.querySelector('textarea[name="content"]');
    
    // Format time with timezone consideration
    function formatMessageTime(dateTimeStr) {
        // Create date object from string (assumes server time is in Europe/Dublin)
        const date = new Date(dateTimeStr);
        
        // Format the time in the user's timezone
        return date.toLocaleTimeString('en-US', {
            hour: 'numeric', 
            minute: '2-digit', 
            hour12: true,
            timeZone: 'Europe/Dublin' // Use the server's timezone to ensure consistency
        });
    }
    
    // Scroll to bottom of messages
    if (messageContainer) {
        messageContainer.scrollTop = messageContainer.scrollHeight;
    }
    
    // Auto-expand textarea
    if (messageInput) {
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
    
    // Prevent duplicate form submissions
    let isSubmitting = false;
    
    // Handle form submission with AJAX
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Prevent multiple submissions
            if (isSubmitting) return;
            
            const form = this;
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            
            // Disable form during submission
            isSubmitting = true;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            
            const formData = new FormData(form);
            
            fetch('send_insight_message.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Clear the message input field and reset height
                messageInput.value = '';
                messageInput.style.height = 'auto';
                
                if (data.success) {
                    // Add the new message to the conversation
                    const newMessage = data.message;
                    
                    const messageDiv = document.createElement('div');
                    messageDiv.id = `msg-${newMessage.message_id}`;
                    messageDiv.className = 'message outgoing mb-3';
                    
                    messageDiv.innerHTML = `
                        <div class="message-bubble">
                            ${newMessage.content}
                            <div class="message-time">
                                ${formatMessageTime(newMessage.created_at)}
                            </div>
                        </div>
                    `;
                    
                    const messagesWrapper = messageContainer.querySelector('.d-flex.flex-column-reverse > div');
                    if (messagesWrapper) {
                        messagesWrapper.appendChild(messageDiv);
                    } else {
                        // If no messages container exists, create one
                        const newWrapper = document.createElement('div');
                        newWrapper.className = 'd-flex flex-column-reverse flex-grow-1';
                        newWrapper.innerHTML = '<div>' + messageDiv.outerHTML + '</div>';
                        
                        // Replace the "no messages" placeholder with the new message
                        messageContainer.innerHTML = '';
                        messageContainer.appendChild(newWrapper);
                    }
                    
                    // Scroll to bottom
                    messageContainer.scrollTop = messageContainer.scrollHeight;
                } else {
                    // Handle error
                    alert(data.message || 'Error sending message. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Failed to send message. Please try again.');
            })
            .finally(() => {
                // Re-enable form after submission completes
                isSubmitting = false;
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                messageInput.focus();
            });
        });
    }
    
    // Check for new messages every 5 seconds
    setInterval(checkForNewMessages, 5000);
    
    function checkForNewMessages() {
        const lastMessageId = document.querySelector('.message:last-child')?.id?.replace('msg-', '') || 0;
        
        fetch(`get_insight_messages.php?request_id=<?= $request_id ?>&last_id=${lastMessageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages && data.messages.length > 0) {
                    let needsScroll = messageContainer.scrollTop + messageContainer.clientHeight >= messageContainer.scrollHeight - 100;
                    const messagesWrapper = messageContainer.querySelector('.d-flex.flex-column-reverse > div');
                    
                    data.messages.forEach(message => {
                        // Skip if message already exists
                        if (document.getElementById(`msg-${message.message_id}`)) {
                            return;
                        }
                        
                        const messageDiv = document.createElement('div');
                        messageDiv.id = `msg-${message.message_id}`;
                        messageDiv.className = `message incoming mb-3`;
                        
                        messageDiv.innerHTML = `
                            <div class="message-bubble">
                                ${message.content}
                                <div class="message-time">
                                    ${formatMessageTime(message.created_at)}
                                </div>
                            </div>
                        `;
                        
                        if (messagesWrapper) {
                            messagesWrapper.appendChild(messageDiv);
                        }
                    });
                    
                    // Scroll to bottom if we were already near the bottom
                    if (needsScroll) {
                        messageContainer.scrollTop = messageContainer.scrollHeight;
                    }
                    
                    // Try to play notification sound
                    try {
                        const notificationSound = new Audio('../assets/sounds/notification.mp3');
                        notificationSound.play().catch(e => console.log('Could not play notification sound', e));
                    } catch (error) {
                        console.log('Could not play notification sound', error);
                    }
                }
            })
            .catch(err => console.error('Failed to fetch new messages:', err));
    }
});
</script>

<?php
// Helper function for date formatting
function format_date($date_str) {
    $date = new DateTime($date_str);
    $now = new DateTime();
    $yesterday = new DateTime('yesterday');
    
    if ($date->format('Y-m-d') === $now->format('Y-m-d')) {
        return 'Today';
    }
    
    if ($date->format('Y-m-d') === $yesterday->format('Y-m-d')) {
        return 'Yesterday';
    }
    
    $diff = $now->diff($date);
    if ($diff->days < 7) {
        return $date->format('l'); // Weekday name
    }
    
    return $date->format('F j, Y'); // Month day, Year
}

include __DIR__ . '/../includes/footer.php';
?> 
<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';
require __DIR__ . '/../includes/message_functions.php';

if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle new conversation request
if (isset($_GET['new_conversation']) && isset($_GET['recipient_id'])) {
    $recipient_id = (int)$_GET['recipient_id'];
    // Verify that this is a valid user
    $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE user_id = ?");
    $stmt->execute([$recipient_id]);
    if ($stmt->fetch()) {
        // Redirect to the messages page with this user selected
        header("Location: messages.php?user=".$recipient_id);
        exit;
    }
}

// Get selected conversation if any
$selected_user_id = isset($_GET['user']) ? (int)$_GET['user'] : null;
$selected_user = null;
$messages = [];

// Get all conversations for the current user using our helper function
try {
    $conversations = getUserConversations($pdo, $user_id);
    
    // If a user is selected, get their details and conversation history
    if ($selected_user_id) {
    $stmt = $pdo->prepare("
            SELECT u.user_id, u.username, u.profile_image, c.coach_id 
            FROM Users u 
            LEFT JOIN Coaches c ON u.user_id = c.user_id 
            WHERE u.user_id = ?
        ");
        $stmt->execute([$selected_user_id]);
        $selected_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($selected_user) {
            // Mark messages as read
            markMessagesAsRead($pdo, $user_id, $selected_user_id);
            
            // Get conversation messages
            $messages = getConversationMessages($pdo, $user_id, $selected_user_id);
        }
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid mt-4">
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Conversations List -->
        <div class="col-md-4 col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Conversations</h5>
                    <span class="badge bg-light text-primary rounded-pill"><?= count($conversations) ?></span>
                </div>
                
                <!-- Search Form -->
                <div class="p-3 border-bottom">
                    <form action="search_users.php" method="GET" class="mb-0">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search users..." aria-label="Search users">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Conversations -->
                <div class="overflow-auto" style="max-height: calc(100vh - 250px);">
                    <?php if (empty($conversations)): ?>
                        <div class="p-4 text-center text-muted">
                            <div class="mb-3">
                                <i class="bi bi-chat-square-text" style="font-size: 2rem;"></i>
                            </div>
                            <p>No conversations yet</p>
                            <a href="search_users.php" class="btn btn-sm btn-outline-primary">Find someone to message</a>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush" id="messageList">
                        <?php foreach ($conversations as $conv): ?>
                                <a href="?user=<?= $conv['other_user_id'] ?>" 
                                   class="list-group-item list-group-item-action <?= $selected_user_id == $conv['other_user_id'] ? 'active' : '' ?> <?= $conv['unread_count'] > 0 && $selected_user_id != $conv['other_user_id'] ? 'bg-light' : '' ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 position-relative">
                                            <?php 
                                            $profile_image = !empty($conv['profile_image']) ? $conv['profile_image'] : 'default.jpg';
                                            $image_path = "../assets/images/profiles/{$profile_image}";
                                            ?>
                                            <img src="<?= $image_path ?>" alt="Profile" class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php if ($conv['unread_count'] > 0 && $selected_user_id != $conv['other_user_id']): ?>
                                                <span class="position-absolute top-0 end-0 translate-middle p-1 bg-danger rounded-circle">
                                                    <span class="visually-hidden">New alerts</span>
                                                </span>
                                <?php endif; ?>
                                        </div>
                                        <div class="ms-3 flex-grow-1 overflow-hidden">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0 text-truncate" style="max-width: 130px;"><?= htmlspecialchars($conv['username']) ?></h6>
                                                <small class="text-muted"><?= get_time_ago(strtotime($conv['last_message_time'])) ?></small>
                                            </div>
                                            <p class="mb-0 small text-truncate <?= $conv['unread_count'] > 0 && $selected_user_id != $conv['other_user_id'] ? 'fw-bold' : 'text-muted' ?>">
                                                <?= !empty($conv['last_message']) ? htmlspecialchars($conv['last_message']) : 'No messages yet' ?>
                                            </p>
                                        </div>
                                    </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Message Content -->
        <div class="col-md-8 col-lg-9">
            <div class="card shadow-sm h-100">
                <?php if ($selected_user): ?>
                    <!-- Conversation Header -->
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <?php 
                            $profile_image = !empty($selected_user['profile_image']) ? $selected_user['profile_image'] : 'default.jpg';
                            $image_path = "../assets/images/profiles/{$profile_image}";
                            ?>
                            <img src="<?= $image_path ?>" alt="Profile" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                            <div class="ms-2">
                                <h5 class="mb-0"><?= htmlspecialchars($selected_user['username']) ?></h5>
                                <?php if (!empty($selected_user['coach_id'])): ?>
                                    <span class="badge bg-success">Coach</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <a href="<?= $selected_user['coach_id'] ? "coach-profile.php?id={$selected_user['coach_id']}" : "profile.php?id={$selected_user['user_id']}" ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-person"></i> View Profile
                            </a>
                        </div>
                    </div>
                    
                    <!-- Message Container -->
                    <div class="card-body p-3 overflow-auto d-flex flex-column" id="messageContainer" style="height: calc(100vh - 350px); min-height: 300px;">
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
                                                <?= htmlspecialchars($message['content']) ?>
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
                            <input type="hidden" name="receiver_id" value="<?= $selected_user_id ?>">
                            <input type="text" name="content" class="form-control me-2" 
                                placeholder="Type your message..." autocomplete="off" required>
                            <button type="submit" class="btn btn-primary px-4">
                                Send
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- No conversation selected -->
                    <div class="card-body d-flex flex-column align-items-center justify-content-center text-center" style="min-height: 400px;">
                        <div class="mb-4">
                            <i class="bi bi-chat-square-dots" style="font-size: 4rem; color: #e9ecef;"></i>
                </div>
                        <h4>Select a conversation</h4>
                        <p class="text-muted">Choose a conversation from the list to view your messages</p>
                        <p class="text-muted small">Or start a new conversation by searching for a user</p>
                        <a href="search_users.php" class="btn btn-primary mt-3">
                            <i class="bi bi-plus-circle me-1"></i> New Conversation
                        </a>
                </div>
                <?php endif; ?>
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
    
    /* Active and unread styling */
    .list-group-item.active {
        background-color: rgba(13, 110, 253, 0.1);
        color: #212529;
        border-color: rgba(13, 110, 253, 0.3);
    }
    
    .list-group-item.active h6,
    .list-group-item.active p {
        color: #212529;
    }
</style>

<script>
// Define helper functions
function get_time_ago(timestamp) {
    const now = new Date().getTime() / 1000;
    const diff = now - timestamp;
    
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    if (diff < 172800) return 'Yesterday';
    
    const date = new Date(timestamp * 1000);
    return date.toLocaleDateString('en-US', {month: 'short', day: 'numeric'});
}

function format_date(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    
    // Check if date is today
    if (date.toDateString() === now.toDateString()) {
        return 'Today';
    }
    
    // Check if date is yesterday
    const yesterday = new Date(now);
    yesterday.setDate(now.getDate() - 1);
    if (date.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    }
    
    // Check if date is within this week
    const weekAgo = new Date(now);
    weekAgo.setDate(now.getDate() - 7);
    if (date > weekAgo) {
        return date.toLocaleDateString('en-US', {weekday: 'long'});
    }
    
    // Otherwise return the full date
    return date.toLocaleDateString('en-US', {month: 'long', day: 'numeric', year: 'numeric'});
}

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

// When DOM content is loaded
document.addEventListener('DOMContentLoaded', function() {
    const messageContainer = document.getElementById('messageContainer');
    const messageForm = document.getElementById('messageForm');
    
    // Track the last displayed message ID to prevent duplicates
    let lastDisplayedMessageId = 0;
    
    // Scroll to bottom of messages
    if (messageContainer) {
        messageContainer.scrollTop = messageContainer.scrollHeight;
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
            const messageInput = form.querySelector('input[name="content"]');
            const originalButtonText = submitButton.innerHTML;
            
            // Validate message content
            const content = messageInput.value.trim();
            if (!content) {
                alert('Please enter a message.');
                messageInput.focus();
                return;
            }
            
            // Disable form during submission
            isSubmitting = true;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            
            const formData = new FormData(form);
            
            fetch('send_message.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin' // Include cookies in the request
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Server returned ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                // Clear the message input field
                messageInput.value = '';
                
                // If successful, add the message to the chat immediately
                if (data.success && !data.needs_moderation) {
                    // Create a message object for the sent message
                    const messageObj = {
                        message_id: data.message_id || Date.now(), // Use actual ID if provided
                        sender_id: <?= $user_id ?>,
                        content: formData.get('content'),
                        created_at: new Date().toISOString()
                    };
                    
                    // If no messages were previously shown, ensure the container structure is set up correctly
                    if (messageContainer.querySelector('.text-center.text-muted.my-auto')) {
                        // Replace the "no messages" placeholder
                        messageContainer.innerHTML = `
                            <div class="d-flex flex-column-reverse flex-grow-1">
                                <div></div>
                            </div>
                        `;
                    }
                    
                    // Add new message
                    const messagesWrapper = messageContainer.querySelector('.d-flex.flex-column-reverse > div');
                    if (messagesWrapper) {
                        const messageDiv = document.createElement('div');
                        messageDiv.id = `msg-${messageObj.message_id}`;
                        messageDiv.className = 'message outgoing mb-3';
                        
                        messageDiv.innerHTML = `
                            <div class="message-bubble">
                                ${messageObj.content}
                                <div class="message-time">
                                    ${formatMessageTime(messageObj.created_at)}
                                </div>
                            </div>
                        `;
                        
                        messagesWrapper.appendChild(messageDiv);
                        
                        // Scroll to the new message
                        messageContainer.scrollTop = messageContainer.scrollHeight;
                    }
                }
                
                // Commenting out immediate check for new messages to prevent duplicate display
                // setTimeout(checkForNewMessages, 500);
                
                // Handle any errors or notifications
                if (!data.success) {
                    console.error('Server reported error:', data.message);
                    alert(data.message || 'Error sending message. Please try again.');
                } else if (data.needs_moderation) {
                    // Show a message about moderation if needed
                    alert(data.message || 'Your message will be reviewed before being displayed.');
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Failed to send message. Please try again. Error: ' + error.message);
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
    
    // Function to add new messages to the container
    function addNewMessages(messages, isLocalMessage = false) {
        if (!messageContainer || !messages || messages.length === 0) return;
        
        let needsScroll = messageContainer.scrollTop + messageContainer.clientHeight >= messageContainer.scrollHeight - 100;
        let lastDate = '';
        let anyNewMessages = false;
        
        let messagesWrapper = messageContainer.querySelector('.d-flex.flex-column-reverse > div');
        
        // If messagesWrapper is null, create the required container structure
        if (!messagesWrapper) {
            const flexContainer = document.createElement('div');
            flexContainer.className = 'd-flex flex-column-reverse flex-grow-1';
            
            messagesWrapper = document.createElement('div');
            flexContainer.appendChild(messagesWrapper);
            
            // Clear message container and add our new structure
            messageContainer.innerHTML = '';
            messageContainer.appendChild(flexContainer);
        }
        
        // Track displayed content to prevent duplicate messages with different IDs
        const displayedContent = new Set();
        document.querySelectorAll('.message-bubble').forEach(el => {
            // Get text content without the time element
            const content = el.childNodes[0].textContent.trim();
            displayedContent.add(content);
        });
        
        messages.forEach(message => {
            // Check if message is already displayed (prevent duplicates)
            const messageId = `msg-${message.message_id}`;
            const messageContent = message.content.trim();
            
            // Skip if ID exists or content already displayed (for recently sent messages)
            if (document.getElementById(messageId) || 
                (!isLocalMessage && displayedContent.has(messageContent))) {
                return;
            }
            
            // Update tracking variables
            if (message.message_id > lastDisplayedMessageId) {
                lastDisplayedMessageId = message.message_id;
            }
            
            if (!isLocalMessage) {
                displayedContent.add(messageContent);
            }
            
            anyNewMessages = true;
            
            // Check if we need to add a date header
            const messageDate = new Date(message.created_at).toISOString().split('T')[0];
            if (messageDate !== lastDate) {
                const dateDiv = document.createElement('div');
                dateDiv.className = 'text-center my-3';
                dateDiv.innerHTML = `<span class="badge bg-light text-dark px-3 py-2">${format_date(messageDate)}</span>`;
                messagesWrapper.appendChild(dateDiv);
                lastDate = messageDate;
            }
            
            // Create message element
            const isOutgoing = message.sender_id == <?= $user_id ?>;
            const messageDiv = document.createElement('div');
            messageDiv.id = messageId;
            messageDiv.className = `message ${isOutgoing ? 'outgoing' : 'incoming'} mb-3`;
            
            messageDiv.innerHTML = `
                <div class="message-bubble">
                    ${message.content}
                    <div class="message-time">
                        ${formatMessageTime(message.created_at)}
                    </div>
                </div>
            `;
            
            messagesWrapper.appendChild(messageDiv);
        });
        
        // Play notification sound if there are new messages from the other user
        if (anyNewMessages) {
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
    }
    
    // Check for new messages every 3 seconds when a conversation is selected
    <?php if ($selected_user_id): ?>
    let checkMessagesInterval = setInterval(checkForNewMessages, 3000);
    
    function checkForNewMessages() {
        fetch(`get_new_messages.php?user=<?= $selected_user_id ?>&last_id=${lastDisplayedMessageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error fetching messages:', data.error);
                    return;
                }
                addNewMessages(data.messages);
            })
            .catch(err => console.error('Failed to fetch new messages:', err));
    }
    <?php endif; ?>
    
    // Update conversation list periodically
    setInterval(updateConversationList, 15000);
    
    function updateConversationList() {
        fetch('get_conversations_update.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error updating conversations:', data.error);
                    return;
                }
                
                // We'll implement this in a future update if needed
                // This would update the conversation list without reloading the page
            })
            .catch(err => console.error('Failed to update conversations:', err));
    }
});
</script>

<?php
// Helper functions for time formatting
function get_time_ago($timestamp) {
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 172800) return 'Yesterday';
    
    return date('M j', $timestamp);
}

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

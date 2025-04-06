// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Get the request ID from the URL
    const urlParams = new URLSearchParams(window.location.search);
    const requestId = urlParams.get('request_id');
    
    if (!requestId) {
        console.error('No request ID found in URL');
        return;
    }
    
    const messageContainer = document.getElementById('message-container');
    const messageForm = document.querySelector('form');
    const messageInput = document.getElementById('message');
    
    // Initial last message ID - will be updated after each fetch
    let lastMessageId = 0;
    
    // Initialize by finding the last message ID
    const initializeLastMessageId = () => {
        const messages = messageContainer.querySelectorAll('[data-message-id]');
        if (messages.length > 0) {
            const lastMessage = messages[messages.length - 1];
            lastMessageId = parseInt(lastMessage.getAttribute('data-message-id'), 10);
        }
    };
    
    // Function to add a new message to the container
    const addMessageToContainer = (message) => {
        const messageDiv = document.createElement('div');
        messageDiv.className = `mb-3 ${message.is_self ? 'text-end' : ''}`;
        messageDiv.setAttribute('data-message-id', message.id);
        
        messageDiv.innerHTML = `
            <div class="d-inline-block p-3 rounded ${message.is_self ? 'bg-primary text-white' : 'bg-white border'}" style="max-width: 80%;">
                <div class="mb-1 small ${message.is_self ? 'text-white-50' : 'text-muted'}">
                    ${message.sender_name}
                    · ${new Date(message.created_at).toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        hour: 'numeric',
                        minute: 'numeric',
                        hour12: true
                    })}
                </div>
                <div>${message.message.replace(/\n/g, '<br>')}</div>
            </div>
        `;
        
        messageContainer.appendChild(messageDiv);
        scrollToBottom();
    };
    
    // Function to scroll the message container to the bottom
    const scrollToBottom = () => {
        messageContainer.scrollTop = messageContainer.scrollHeight;
    };
    
    // Function to fetch new messages
    const fetchNewMessages = () => {
        fetch(`get_insight_messages.php?request_id=${requestId}&last_id=${lastMessageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add each new message
                    data.messages.forEach(message => {
                        addMessageToContainer(message);
                    });
                    
                    // Update last message ID
                    if (data.last_id) {
                        lastMessageId = data.last_id;
                    }
                } else {
                    console.error('Error fetching messages:', data.error);
                }
            })
            .catch(error => {
                console.error('Error fetching messages:', error);
            });
    };
    
    // Handle form submission
    if (messageForm) {
        messageForm.addEventListener('submit', function(event) {
            // Form will submit normally - the PHP will handle the redirect
            // We don't need to prevent default and use fetch here
            
            // Reset the input field (will happen after redirect)
            // messageInput.value = '';
        });
    }
    
    // Initialize and set up polling
    initializeLastMessageId();
    scrollToBottom();
    
    // Poll for new messages every 5 seconds
    setInterval(fetchNewMessages, 5000);
}); 
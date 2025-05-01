/**
 * Session Actions JavaScript
 * 
 * This script handles the actions for the session page:
 * - Complete session
 * - Cancel session
 * - Reschedule session
 * 
 * It uses direct form submissions rather than AJAX for better reliability.
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Session Actions script loaded');
    
    // Handle Complete Session buttons
    const completeButtons = document.querySelectorAll('.complete-session');
    completeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const sessionId = this.dataset.sessionId;
            console.log('Completing session:', sessionId);
            
            if (confirm('Are you sure you want to mark this session as completed?')) {
                // Create and submit a form directly for more reliable processing
                submitSessionAction(sessionId, 'completed');
            }
        });
    });
    
    // Handle Cancel Session buttons
    const cancelButtons = document.querySelectorAll('.cancel-session');
    cancelButtons.forEach(button => {
        button.addEventListener('click', function() {
            const sessionId = this.dataset.sessionId;
            console.log('Cancelling session:', sessionId);
            
            if (confirm('Are you sure you want to cancel this session?')) {
                submitSessionAction(sessionId, 'cancelled');
            }
        });
    });
    
    // Helper function to submit the session action form
    function submitSessionAction(sessionId, status) {
        // Create a form element
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'view-session.php?id=' + sessionId;
        form.style.display = 'none';
        
        // Add action field
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'update_status';
        form.appendChild(actionInput);
        
        // Add session ID field
        const sessionIdInput = document.createElement('input');
        sessionIdInput.type = 'hidden';
        sessionIdInput.name = 'session_id';
        sessionIdInput.value = sessionId;
        form.appendChild(sessionIdInput);
        
        // Add status field
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = status;
        form.appendChild(statusInput);
        
        // Add redirect success field
        const redirectInput = document.createElement('input');
        redirectInput.type = 'hidden';
        redirectInput.name = 'redirect_on_success';
        redirectInput.value = '1';
        form.appendChild(redirectInput);
        
        // Add return URL to come back to the sessions page
        const returnUrlInput = document.createElement('input');
        returnUrlInput.type = 'hidden';
        returnUrlInput.name = 'return_url';
        returnUrlInput.value = 'session.php';
        form.appendChild(returnUrlInput);
        
        // Add to body and submit
        document.body.appendChild(form);
        
        console.log('Submitting form for session', sessionId, 'with status', status);
        form.submit();
    }
}); 
<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$is_coach = ($_SESSION['user_type'] ?? '') === 'business';

// Check if inquiry ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid inquiry ID provided.";
    header('Location: dashboard.php');
    exit;
}

$inquiry_id = (int)$_GET['id'];

// Get the inquiry details
try {
    if ($is_coach) {
        // Get inquiry for coach
        $stmt = $pdo->prepare("
            SELECT si.*, u.username as learner_name, u.email as learner_email, 
                   st.name as tier_name, st.price, st.description as tier_description,
                   c.coach_id
            FROM ServiceInquiries si
            JOIN Users u ON si.user_id = u.user_id
            JOIN ServiceTiers st ON si.tier_id = st.tier_id
            JOIN Coaches c ON si.coach_id = c.coach_id
            WHERE si.inquiry_id = ? AND c.user_id = ?
        ");
    } else {
        // Get inquiry for learner
        $stmt = $pdo->prepare("
            SELECT si.*, u.username as coach_name, u.email as coach_email, 
                   st.name as tier_name, st.price, st.description as tier_description,
                   c.coach_id, c.user_id as coach_user_id
            FROM ServiceInquiries si
            JOIN Coaches c ON si.coach_id = c.coach_id
            JOIN Users u ON c.user_id = u.user_id
            JOIN ServiceTiers st ON si.tier_id = st.tier_id
            WHERE si.inquiry_id = ? AND si.user_id = ?
        ");
    }
    
    $stmt->execute([$inquiry_id, $user_id]);
    $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inquiry) {
        $_SESSION['error_message'] = "You don't have permission to view this inquiry or it doesn't exist.";
        header('Location: dashboard.php');
        exit;
    }
    
    // Get inquiry notes if any
    $notes = [];
    try {
        // Check if the InquiryNotes table exists
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS table_exists
            FROM information_schema.tables 
            WHERE table_schema = DATABASE() 
            AND table_name = 'InquiryNotes'
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['table_exists'] == 0) {
            // Table doesn't exist, create it
            $createTableSQL = "
                CREATE TABLE InquiryNotes (
                    note_id INT AUTO_INCREMENT PRIMARY KEY,
                    inquiry_id INT NOT NULL,
                    user_id INT NOT NULL,
                    note TEXT NOT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX (inquiry_id),
                    FOREIGN KEY (inquiry_id) REFERENCES ServiceInquiries(inquiry_id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
                )
            ";
            $pdo->exec($createTableSQL);
            error_log("Created InquiryNotes table");
        }
        
        // Now get notes (even if we just created the table, there won't be any notes yet)
        $stmt = $pdo->prepare("
            SELECT notes.*, u.username
            FROM InquiryNotes notes
            JOIN Users u ON notes.user_id = u.user_id
            WHERE notes.inquiry_id = ?
            ORDER BY notes.created_at ASC
        ");
        $stmt->execute([$inquiry_id]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Silently handle error - we'll just show no notes
        error_log("Error retrieving inquiry notes: " . $e->getMessage());
    }
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error retrieving inquiry: " . $e->getMessage();
    header('Location: dashboard.php');
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fs-2 fw-bold">Inquiry Details</h1>
        <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error_message']) ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success_message']) ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Inquiry Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Status:</strong> 
                        <span class="badge <?= 
                            $inquiry['status'] === 'pending' ? 'bg-warning' : 
                            ($inquiry['status'] === 'accepted' ? 'bg-success' : 
                            ($inquiry['status'] === 'completed' ? 'bg-info' : 'bg-danger')) 
                        ?>">
                            <?= ucfirst($inquiry['status']) ?>
                        </span>
                    </p>
                    <p><strong>Date Created:</strong> <?= date('F j, Y g:i A', strtotime($inquiry['created_at'])) ?></p>
                    <p><strong>Service Tier:</strong> <?= htmlspecialchars($inquiry['tier_name']) ?></p>
                    <p><strong>Price:</strong> $<?= number_format($inquiry['price'], 2) ?></p>
                    <p><strong>Description:</strong> <?= htmlspecialchars($inquiry['tier_description']) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong><?= $is_coach ? 'Learner' : 'Coach' ?>:</strong> 
                        <?= htmlspecialchars($is_coach ? $inquiry['learner_name'] : $inquiry['coach_name']) ?>
                    </p>
                    <p><strong>Email:</strong> 
                        <?= htmlspecialchars($is_coach ? $inquiry['learner_email'] : $inquiry['coach_email']) ?>
                    </p>
                    <p><strong>Message:</strong></p>
                    <div class="p-3 bg-light rounded">
                        <?= nl2br(htmlspecialchars($inquiry['message'])) ?>
                    </div>
                </div>
            </div>
            
            <?php if ($inquiry['status'] === 'pending'): ?>
                <hr>
                <div class="row mt-3">
                    <div class="col-12">
                        <h5>Update Inquiry Status</h5>
                        <form id="updateInquiryForm" class="mt-3">
                            <input type="hidden" name="inquiry_id" value="<?= $inquiry_id ?>">
                            
                            <div class="mb-3">
                                <label for="note" class="form-label">Add a Note (optional):</label>
                                <textarea name="note" id="note" class="form-control" rows="3"></textarea>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <?php if ($is_coach): ?>
                                    <button type="button" class="btn btn-success btn-action" data-action="accept">
                                        Accept Inquiry
                                    </button>
                                    <button type="button" class="btn btn-danger btn-action" data-action="reject">
                                        Reject Inquiry
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-danger btn-action" data-action="cancel">
                                        Cancel Inquiry
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($notes)): ?>
                <hr>
                <h5 class="mt-4">Notes & Updates</h5>
                <div class="mt-3">
                    <?php foreach ($notes as $note): ?>
                        <div class="card mb-2">
                            <div class="card-header bg-light d-flex justify-content-between">
                                <span><?= htmlspecialchars($note['username']) ?></span>
                                <small><?= date('M j, Y g:i A', strtotime($note['created_at'])) ?></small>
                            </div>
                            <div class="card-body">
                                <?= nl2br(htmlspecialchars($note['note'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle action buttons
    const actionButtons = document.querySelectorAll('.btn-action');
    const inquiryForm = document.getElementById('updateInquiryForm');
    
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            const formData = new FormData(inquiryForm);
            formData.append('action', action);
            
            // Confirm action
            if (!confirm('Are you sure you want to ' + action + ' this inquiry?')) {
                return;
            }
            
            // Submit via AJAX
            fetch('inquiry-update.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Inquiry has been ' + action + 'ed successfully.');
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your request.');
            });
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
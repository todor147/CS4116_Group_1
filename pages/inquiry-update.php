<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/notification_functions.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Process only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$is_coach = ($_SESSION['user_type'] ?? '') === 'business';
$response = ['success' => false, 'message' => 'An error occurred'];

// Get and validate request parameters
$inquiry_id = isset($_POST['inquiry_id']) ? (int)$_POST['inquiry_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$note = isset($_POST['note']) ? trim($_POST['note']) : '';

if ($inquiry_id <= 0) {
    $response['message'] = 'Invalid inquiry ID';
    echo json_encode($response);
    exit;
}

try {
    // Start a transaction
    $pdo->beginTransaction();
    
    // Get the inquiry details and verify permissions
    $stmt = $pdo->prepare("
        SELECT si.*, c.user_id as coach_user_id 
        FROM ServiceInquiries si
        JOIN Coaches c ON si.coach_id = c.coach_id
        WHERE si.inquiry_id = ?
    ");
    $stmt->execute([$inquiry_id]);
    $inquiry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inquiry) {
        throw new Exception('Inquiry not found');
    }
    
    // Verify that the user has permission to update this inquiry
    $has_permission = false;
    
    if ($is_coach && $inquiry['coach_user_id'] == $user_id) {
        // Coach can update inquiries sent to them
        $has_permission = true;
    } elseif (!$is_coach && $inquiry['user_id'] == $user_id) {
        // Learner can only cancel their own inquiries
        $has_permission = ($action === 'cancel');
    }
    
    if (!$has_permission) {
        throw new Exception('You do not have permission to perform this action');
    }
    
    // Determine the new status based on the action
    $new_status = '';
    
    switch ($action) {
        case 'accept':
            $new_status = 'accepted';
            break;
            
        case 'reject':
            $new_status = 'rejected';
            break;
            
        case 'cancel':
            $new_status = 'cancelled';
            break;
            
        case 'complete':
            $new_status = 'completed';
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    // Update the inquiry status
    $stmt = $pdo->prepare("
        UPDATE ServiceInquiries
        SET status = ?, last_updated = NOW()
        WHERE inquiry_id = ?
    ");
    $stmt->execute([$new_status, $inquiry_id]);
    
    // Add a note if provided
    if (!empty($note)) {
        $stmt = $pdo->prepare("
            INSERT INTO InquiryNotes
            (inquiry_id, user_id, note, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$inquiry_id, $user_id, $note]);
    }
    
    // Send notification to the appropriate user
    notifyInquiryStatusChange($pdo, $inquiry_id, $new_status);
    
    // Commit the transaction
    $pdo->commit();
    
    $response = [
        'success' => true,
        'message' => 'Inquiry updated successfully',
        'new_status' => $new_status
    ];
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error updating inquiry: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;
?> 
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

// Get existing privacy settings
$privacy_settings = [
    'allow_insight_requests' => 1,
    'share_session_history' => 1,
    'share_coach_ratings' => 1,
    'public_profile' => 1
];

try {
    // Check if the user already has privacy settings
    $stmt = $pdo->prepare("SELECT * FROM UserPrivacySettings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $existing_settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_settings) {
        $privacy_settings = $existing_settings;
    }
    
    // Check if user is a verified customer (has completed sessions with coaches)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as session_count 
        FROM Sessions 
        WHERE learner_id = ? AND status = 'completed'
    ");
    $stmt->execute([$user_id]);
    $session_count = $stmt->fetch(PDO::FETCH_ASSOC)['session_count'];
    $is_verified_customer = ($session_count > 0);
    
    // Check how many active insight conversations the user has
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as active_insights 
        FROM CustomerInsightRequests 
        WHERE verified_customer_id = ? AND status = 'accepted'
    ");
    $stmt->execute([$user_id]);
    $active_insights = $stmt->fetch(PDO::FETCH_ASSOC)['active_insights'];
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $allow_insight_requests = isset($_POST['allow_insight_requests']) ? 1 : 0;
    $share_session_history = isset($_POST['share_session_history']) ? 1 : 0;
    $share_coach_ratings = isset($_POST['share_coach_ratings']) ? 1 : 0;
    $public_profile = isset($_POST['public_profile']) ? 1 : 0;
    
    try {
        if ($existing_settings) {
            // Update existing settings
            $stmt = $pdo->prepare("
                UPDATE UserPrivacySettings 
                SET allow_insight_requests = ?, 
                    share_session_history = ?, 
                    share_coach_ratings = ?,
                    public_profile = ?
                WHERE user_id = ?
            ");
            $stmt->execute([
                $allow_insight_requests, 
                $share_session_history, 
                $share_coach_ratings,
                $public_profile,
                $user_id
            ]);
        } else {
            // Insert new settings
            $stmt = $pdo->prepare("
                INSERT INTO UserPrivacySettings 
                (user_id, allow_insight_requests, share_session_history, share_coach_ratings, public_profile) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id, 
                $allow_insight_requests, 
                $share_session_history, 
                $share_coach_ratings,
                $public_profile
            ]);
        }
        
        // If insight requests are disabled, reject all pending requests
        if ($allow_insight_requests == 0) {
            $stmt = $pdo->prepare("
                UPDATE CustomerInsightRequests 
                SET status = 'rejected' 
                WHERE verified_customer_id = ? AND status = 'pending'
            ");
            $stmt->execute([$user_id]);
        }
        
        $success = "Your privacy settings have been updated successfully.";
        
        // Update the privacy settings variable with the new values
        $privacy_settings = [
            'allow_insight_requests' => $allow_insight_requests,
            'share_session_history' => $share_session_history,
            'share_coach_ratings' => $share_coach_ratings,
            'public_profile' => $public_profile
        ];
        
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Privacy Settings</h5>
                    <a href="profile.php" class="btn btn-light btn-sm">Back to Profile</a>
                </div>
                
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-4">
                            <h6>Customer Insight Settings</h6>
                            <p class="text-muted small">Control how others can interact with you about coaches you've worked with</p>
                            
                            <?php if (!$is_verified_customer): ?>
                                <div class="alert alert-info mb-3">
                                    <i class="bi bi-info-circle"></i> Once you complete a session with a coach, you'll become a verified customer and can offer insights to other potential customers.
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="allow_insight_requests" name="allow_insight_requests" 
                                       <?= $privacy_settings['allow_insight_requests'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="allow_insight_requests">
                                    Allow insight requests from potential customers
                                </label>
                                <div class="form-text">When enabled, other users can request your insights about coaches you've worked with</div>
                            </div>
                            
                            <?php if ($active_insights > 0 && !$privacy_settings['allow_insight_requests']): ?>
                                <div class="alert alert-warning mb-3">
                                    <i class="bi bi-exclamation-triangle"></i> You currently have <?= $active_insights ?> active insight conversation(s). Disabling insight requests will not end these conversations.
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="share_session_history" name="share_session_history" 
                                       <?= $privacy_settings['share_session_history'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="share_session_history">
                                    Share session history in coach profiles
                                </label>
                                <div class="form-text">When enabled, your completed session count may be shown to other users (without personal details)</div>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="share_coach_ratings" name="share_coach_ratings" 
                                       <?= $privacy_settings['share_coach_ratings'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="share_coach_ratings">
                                    Share your coach ratings publicly
                                </label>
                                <div class="form-text">When enabled, your ratings will be visible on coach profiles (your name will still be shown with reviews)</div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h6>General Privacy Settings</h6>
                            <p class="text-muted small">Control your overall profile visibility</p>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="public_profile" name="public_profile" 
                                       <?= $privacy_settings['public_profile'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="public_profile">
                                    Public profile
                                </label>
                                <div class="form-text">When enabled, other users can view your profile details. When disabled, only your username will be visible.</div>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
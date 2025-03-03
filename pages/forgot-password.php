<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $message = 'Please enter your email address';
    } elseif (!isValidEmail($email)) {
        $message = 'Please enter a valid email address';
    } else {
        try {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store token in database
                $stmt = $pdo->prepare("UPDATE Users SET reset_token = ?, reset_expires = ? WHERE user_id = ?");
                $stmt->execute([$token, $expires, $user['user_id']]);
                
                // Send email with reset link
                $resetLink = "http://yourdomain.com/reset-password.php?token=$token";
                $subject = "Password Reset Request";
                $message = "Click this link to reset your password: $resetLink";
                $headers = "From: no-reply@yourdomain.com";
                
                if (mail($email, $subject, $message, $headers)) {
                    $message = 'Password reset link has been sent to your email';
                } else {
                    $message = 'Failed to send reset email';
                }
            } else {
                $message = 'If an account exists with that email, a reset link will be sent';
            }
        } catch (PDOException $e) {
            $message = 'Error processing your request';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">Forgot Password</h2>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-info"><?= $message ?></div>
                    <?php endif; ?>
                    <form action="forgot-password.php" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <small class="form-text text-muted">Enter your account email address</small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                        <p class="mt-3 text-center">
                            Remember your password? <a href="login.php" class="text-decoration-none">Login here</a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
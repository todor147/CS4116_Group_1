<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';
require __DIR__ . '/../includes/validation_functions.php';

// Redirect if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $message = 'Please enter your email address';
        $message_type = 'danger';
    } elseif (!isValidEmail($email)) {
        $message = 'Please enter a valid email address';
        $message_type = 'danger';
    } else {
        // Request password reset
        if (requestPasswordReset($pdo, $email)) {
            $message = 'If an account exists with that email, a reset link will be sent. Please check your email.';
            $message_type = 'success';
        } else {
            $message = 'There was a problem processing your request. Please try again later.';
            $message_type = 'danger';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">Forgot Password</h2>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['reset_link']) && isset($_SESSION['reset_email'])): ?>
                        <div class="alert alert-info">
                            <h5>Development Mode: Email Sending Bypassed</h5>
                            <p>In a production environment, an email would be sent to <strong><?= htmlspecialchars($_SESSION['reset_email']) ?></strong>.</p>
                            <p>Since you're in development mode, you can use this link instead:</p>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['reset_link']) ?>" id="resetLink" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copyResetLink()">Copy</button>
                            </div>
                            <p class="text-muted">This link will expire in 1 hour.</p>
                        </div>
                        <script>
                            function copyResetLink() {
                                var copyText = document.getElementById("resetLink");
                                copyText.select();
                                document.execCommand("copy");
                                alert("Reset link copied to clipboard!");
                            }
                        </script>
                    <?php endif; ?>
                    
                    <p class="mb-4">Enter your email address below, and we'll send you a link to reset your password.</p>
                    
                    <form action="forgot-password.php" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
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
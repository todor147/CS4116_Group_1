<?php
session_start();
require __DIR__ . '/../includes/db_connection.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'Both fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!isValidPassword($password)) {
        $error = 'Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character';
    } else {
        try {
            // Verify token
            $stmt = $pdo->prepare("SELECT * FROM Users WHERE reset_token = ? AND reset_expires > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Update password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE Users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?");
                $stmt->execute([$password_hash, $user['user_id']]);
                
                $success = 'Password has been reset successfully. <a href="login.php" class="text-decoration-none">Login here</a>';
            } else {
                $error = 'Invalid or expired reset token';
            }
        } catch (PDOException $e) {
            $error = 'Error resetting password';
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
                    <h2 class="mb-0">Reset Password</h2>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php else: ?>
                        <form action="reset-password.php?token=<?= $token ?>" method="post">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="8" pattern="^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$">
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
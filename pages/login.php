<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Check if user is admin and redirect accordingly
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        header('Location: admin.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';
require __DIR__ . '/../includes/validation_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!isValidEmail($email)) {
        $error = 'Please enter a valid email address';
    } else {
        // Check for admin login
        if ($email === 'admin@educoach.com' && $password === 'Passw0rd') {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = 1; // Admin user ID in the database
            $_SESSION['is_admin'] = true;
            $_SESSION['username'] = 'admin';
            $_SESSION['email'] = 'admin@educoach.com';
            $_SESSION['user_type'] = 'admin';
            header('Location: admin.php');
            exit;
        }
        
        // Regular user login
        $user = authenticateUser($pdo, $email, $password);
        
        if ($user) {
            // Set session variables
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['profile_image'] = $user['profile_image'];
            
            // Check if the user is a coach
            try {
                $stmt = $pdo->prepare("SELECT coach_id FROM Coaches WHERE user_id = ?");
                $stmt->execute([$user['user_id']]);
                $coach = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($coach) {
                    $_SESSION['coach_id'] = $coach['coach_id'];
                }
            } catch (PDOException $e) {
                // Silent fail - not critical
            }
            
            // Check if there is a redirect URL
            if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
                $redirect = urldecode($_GET['redirect']);
                // Validate the URL to prevent open redirect vulnerabilities
                // Only allow internal redirects
                if (strpos($redirect, '/') === 0 || strpos($redirect, 'pages/') === 0) {
                    header('Location: ' . $redirect);
                    exit;
                }
            }
            
            // Default redirect
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Invalid email or password";
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">Login to EduCoach</h2>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <form action="login.php" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <small class="form-text text-muted">
                                <a href="forgot-password.php" class="text-decoration-none">Forgot password?</a>
                            </small>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe">
                            <label class="form-check-label" for="rememberMe">Remember me</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                        <hr>
                        <div class="text-center">
                            <p class="mt-3">Don't have an account? <a href="register.php" class="text-decoration-none">Register here</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
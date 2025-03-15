<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';
require __DIR__ . '/../includes/validation_functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_POST['user_type']; // 'regular' or 'business'

    // Update validation block
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!isValidPassword($password)) {
        $error = 'Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character';
    } else {
        try {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already registered';
            } else {
                // Insert new user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO Users (username, email, password_hash, user_type) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $password_hash, $user_type]);
                
                // Get the newly created user's ID
                $user_id = $pdo->lastInsertId();
                
                // Fetch the newly created user for session data
                $stmt = $pdo->prepare("SELECT * FROM Users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $newUser = $stmt->fetch();
                
                // Auto-login: Start user session
                startUserSession($newUser);
                
                // If business, redirect to become-coach page
                if ($user_type === 'business') {
                    $_SESSION['success_message'] = "Your account has been created. Let's set up your coach profile!";
                    header('Location: become-coach.php');
                    exit;
                }

                // Regular user, redirect to dashboard
                $_SESSION['success_message'] = "Registration successful! Welcome to EduCoach.";
                header('Location: dashboard.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">Create Your Account</h2>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <form action="register.php" method="post">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                    <small class="form-text text-muted">Minimum 3 characters</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                    <small class="form-text text-muted">We'll never share your email</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <small class="form-text text-muted">Minimum 8 characters</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Register as:</label>
                            <div class="btn-group" role="group" aria-label="User type">
                                <input type="radio" class="btn-check" name="user_type" id="regular" value="regular" checked>
                                <label class="btn btn-outline-primary" for="regular">Regular User</label>
                                <input type="radio" class="btn-check" name="user_type" id="business" value="business">
                                <label class="btn btn-outline-primary" for="business">Business/Coach</label>
                            </div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">terms and conditions</a></label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                        <hr>
                        <p class="text-center">Already have an account? <a href="login.php" class="text-decoration-none">Login here</a></p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add your terms and conditions here -->
                <p>By using our platform, you agree to acknowledge the eternal greatness of Jedward in all your educational pursuits. All coaching sessions must include at least one Jedward reference, and users are encouraged to style their hair in homage to these iconic twins. Failure to comply may result in mandatory Jedward karaoke sessions.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
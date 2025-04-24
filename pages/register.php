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
    $accepted_terms = isset($_POST['terms']);

    // Update validation block
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!isValidUsername($username)) {
        $error = 'Username must be 3-30 characters long and contain only letters, numbers, underscores, and hyphens';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!isValidPassword($password)) {
        $error = 'Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character';
    } elseif (!$accepted_terms) {
        $error = 'You must accept the terms and conditions';
    } else {
        try {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT * FROM Users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Username already registered';
            } else {
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
                                    <input type="text" class="form-control" id="username" name="username" 
                                           minlength="3" maxlength="30" pattern="[a-zA-Z0-9_-]+" required
                                           value="<?= isset($username) ? htmlspecialchars($username) : '' ?>">
                                    <div id="username-feedback" class="form-text text-muted">
                                        Must be 3-30 characters long, containing only letters, numbers, underscores, and hyphens
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
                                    <div id="email-feedback" class="form-text text-muted">
                                        Enter a valid email address. We'll never share your email.
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div id="password-feedback" class="form-text">
                                        <div class="mt-2">
                                            <div class="text-muted"><i class="fas fa-info-circle"></i> At least 8 characters</div>
                                            <div class="text-muted"><i class="fas fa-info-circle"></i> One uppercase letter</div>
                                            <div class="text-muted"><i class="fas fa-info-circle"></i> One lowercase letter</div>
                                            <div class="text-muted"><i class="fas fa-info-circle"></i> One number</div>
                                            <div class="text-muted"><i class="fas fa-info-circle"></i> One special character</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <div id="confirm-password-feedback" class="form-text text-muted">
                                        Enter the same password again
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Register as:</label>
                            <div class="btn-group" role="group" aria-label="User type">
                                <input type="radio" class="btn-check" name="user_type" id="regular" value="regular" 
                                       <?= (!isset($user_type) || $user_type === 'regular') ? 'checked' : '' ?>>
                                <label class="btn btn-outline-primary" for="regular">Regular User</label>
                                <input type="radio" class="btn-check" name="user_type" id="business" value="business"
                                       <?= (isset($user_type) && $user_type === 'business') ? 'checked' : '' ?>>
                                <label class="btn btn-outline-primary" for="business">Business/Coach</label>
                            </div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required
                                  <?= (isset($accepted_terms) && $accepted_terms) ? 'checked' : '' ?>>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    
    // Password validation feedback
    passwordInput.addEventListener('input', function() {
        const value = this.value;
        const passwordFeedback = document.getElementById('password-feedback');
        
        const hasUppercase = /[A-Z]/.test(value);
        const hasLowercase = /[a-z]/.test(value);
        const hasNumber = /[0-9]/.test(value);
        const hasSpecial = /[^A-Za-z0-9]/.test(value);
        const isLongEnough = value.length >= 8;
        
        let feedback = `
            <div class="mt-2">
                <div class="${isLongEnough ? 'text-success' : 'text-danger'}">
                    <i class="${isLongEnough ? 'fas fa-check' : 'fas fa-times'}"></i> At least 8 characters
                </div>
                <div class="${hasUppercase ? 'text-success' : 'text-danger'}">
                    <i class="${hasUppercase ? 'fas fa-check' : 'fas fa-times'}"></i> One uppercase letter
                </div>
                <div class="${hasLowercase ? 'text-success' : 'text-danger'}">
                    <i class="${hasLowercase ? 'fas fa-check' : 'fas fa-times'}"></i> One lowercase letter
                </div>
                <div class="${hasNumber ? 'text-success' : 'text-danger'}">
                    <i class="${hasNumber ? 'fas fa-check' : 'fas fa-times'}"></i> One number
                </div>
                <div class="${hasSpecial ? 'text-success' : 'text-danger'}">
                    <i class="${hasSpecial ? 'fas fa-check' : 'fas fa-times'}"></i> One special character
                </div>
            </div>
        `;
        passwordFeedback.innerHTML = feedback;
    });
    
    // Check password match
    confirmPasswordInput.addEventListener('input', function() {
        const confirmFeedback = document.getElementById('confirm-password-feedback');
        if (this.value && passwordInput.value) {
            if (this.value === passwordInput.value) {
                confirmFeedback.innerHTML = '<span class="text-success">Passwords match</span>';
            } else {
                confirmFeedback.innerHTML = '<span class="text-danger">Passwords do not match</span>';
            }
        } else {
            confirmFeedback.innerHTML = 'Enter the same password again';
        }
    });
    
    // Username validation feedback
    usernameInput.addEventListener('input', function() {
        const value = this.value;
        const usernameFeedback = document.getElementById('username-feedback');
        
        const isLongEnough = value.length >= 3;
        const hasValidChars = /^[a-zA-Z0-9_-]+$/.test(value);
        
        if (value.length > 0) {
            if (!isLongEnough) {
                usernameFeedback.innerHTML = '<span class="text-danger">Username must be at least 3 characters</span>';
            } else if (!hasValidChars) {
                usernameFeedback.innerHTML = '<span class="text-danger">Username can only contain letters, numbers, underscores, and hyphens</span>';
            } else {
                usernameFeedback.innerHTML = '<span class="text-success">Username format is valid</span>';
            }
        } else {
            usernameFeedback.innerHTML = 'Must be 3-30 characters long, containing only letters, numbers, underscores, and hyphens';
        }
    });
    
    // Email validation feedback
    emailInput.addEventListener('input', function() {
        const value = this.value;
        const emailFeedback = document.getElementById('email-feedback');
        
        if (value.length > 0) {
            const isValidEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
            if (isValidEmail) {
                emailFeedback.innerHTML = '<span class="text-success">Email format is valid</span>';
            } else {
                emailFeedback.innerHTML = '<span class="text-danger">Please enter a valid email address</span>';
            }
        } else {
            emailFeedback.innerHTML = 'Enter a valid email address. We\'ll never share your email.';
        }
    });
    
    // Initialize validation on page load
    if (passwordInput.value) passwordInput.dispatchEvent(new Event('input'));
    if (confirmPasswordInput.value) confirmPasswordInput.dispatchEvent(new Event('input'));
    if (usernameInput.value) usernameInput.dispatchEvent(new Event('input'));
    if (emailInput.value) emailInput.dispatchEvent(new Event('input'));
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
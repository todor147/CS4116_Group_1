<?php
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';
require __DIR__ . '/../includes/validation_functions.php';

if (!empty($_SESSION['logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$username = $email = '';
$user_type = 'regular';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $user_type = ($_POST['user_type'] ?? 'regular') === 'business' ? 'business' : 'regular';
        $accepted_terms = isset($_POST['terms']);

        if ($username === '' || $email === '' || $password === '') {
            $error = 'All fields are required.';
        } elseif (!isValidUsername($username)) {
            $error = 'Username must be 3–30 characters: letters, numbers, underscores and hyphens only.';
        } elseif (!isValidEmail($email)) {
            $error = 'Please enter a valid email address.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (!isValidPassword($password)) {
            $error = 'Password needs 8+ characters with upper- and lower-case letters, a number and a special character.';
        } elseif (!$accepted_terms) {
            $error = 'Please accept the terms and conditions to continue.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->fetch()) {
                    $error = 'That username or email is already registered.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO Users (username, email, password_hash, user_type) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $hash, $user_type]);

                    $stmt = $pdo->prepare("SELECT * FROM Users WHERE user_id = ?");
                    $stmt->execute([$pdo->lastInsertId()]);
                    startUserSession($stmt->fetch());

                    if ($user_type === 'business') {
                        $_SESSION['success_message'] = "Account created — let's set up your coach profile!";
                        header('Location: become-coach.php');
                    } else {
                        $_SESSION['success_message'] = 'Welcome to EduCoach! Your account is ready.';
                        header('Location: dashboard.php');
                    }
                    exit;
                }
            } catch (PDOException $e) {
                error_log('Registration failed: ' . $e->getMessage());
                $error = 'Something went wrong creating your account. Please try again.';
            }
        }
    }
}

$page_title = 'Create your account — EduCoach';
include __DIR__ . '/../includes/header.php';
?>

<section class="section-sm">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-9 col-lg-7">
                <div class="card border-0 shadow-lg">
                    <div class="card-body p-4 p-sm-5">
                        <div class="text-center mb-4">
                            <h1 class="h3 mb-1">Create your account</h1>
                            <p class="text-muted mb-0">Join thousands of learners and coaches on EduCoach.</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                                <i class="bi bi-exclamation-circle"></i><span><?= e($error) ?></span>
                            </div>
                        <?php endif; ?>

                        <form action="register.php" method="post" novalidate>
                            <?= csrf_field() ?>

                            <div class="mb-3">
                                <label class="form-label d-block">I want to…</label>
                                <div class="row g-2 role-picker">
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="user_type" id="role-regular" value="regular" <?= $user_type !== 'business' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-primary w-100 py-3" for="role-regular">
                                            <i class="bi bi-mortarboard d-block fs-4 mb-1"></i>Learn
                                            <span class="d-block small text-muted">Find a coach</span>
                                        </label>
                                    </div>
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="user_type" id="role-business" value="business" <?= $user_type === 'business' ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-primary w-100 py-3" for="role-business">
                                            <i class="bi bi-briefcase d-block fs-4 mb-1"></i>Coach
                                            <span class="d-block small text-muted">Offer my services</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username"
                                           minlength="3" maxlength="30" pattern="[a-zA-Z0-9_-]+" required
                                           value="<?= e($username) ?>" autocomplete="username">
                                    <div id="username-feedback" class="form-text">3–30 letters, numbers, _ or -</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           value="<?= e($email) ?>" autocomplete="email">
                                    <div id="email-feedback" class="form-text">We'll never share your email.</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password">
                                    <div id="password-feedback" class="form-text">
                                        <div class="text-muted"><i class="bi bi-dot"></i> At least 8 characters</div>
                                        <div class="text-muted"><i class="bi bi-dot"></i> Upper &amp; lower case</div>
                                        <div class="text-muted"><i class="bi bi-dot"></i> A number &amp; a special character</div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                                    <div id="confirm-password-feedback" class="form-text">Enter the same password again.</div>
                                </div>
                            </div>

                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">terms and conditions</a>
                                    and <a href="privacy.php">privacy policy</a>.
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Create account</button>
                        </form>

                        <p class="text-center text-muted mt-4 mb-0">
                            Already have an account? <a href="login.php" class="fw-semibold">Log in</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Terms &amp; conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>By creating an EduCoach account you agree to use the platform respectfully and lawfully.
                   Coaches are responsible for the accuracy of the services they list; learners are responsible
                   for the information they provide when booking sessions.</p>
                <p>EduCoach is a marketplace that connects learners with independent coaches. We do not guarantee
                   specific learning outcomes, and any agreement for a session is made directly between the learner
                   and the coach.</p>
                <p>You may close your account at any time. We may suspend accounts that violate these terms or our
                   community guidelines. For the full policy, see our Terms of Service and Privacy Policy pages.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const password = document.getElementById('password');
    const confirm = document.getElementById('confirm_password');
    const username = document.getElementById('username');
    const email = document.getElementById('email');
    const ok = (t) => `<span class="text-success"><i class="bi bi-check-circle"></i> ${t}</span>`;
    const no = (t) => `<span class="text-danger"><i class="bi bi-x-circle"></i> ${t}</span>`;

    password.addEventListener('input', function () {
        const v = this.value;
        const checks = [
            [v.length >= 8, 'At least 8 characters'],
            [/[A-Z]/.test(v), 'One uppercase letter'],
            [/[a-z]/.test(v), 'One lowercase letter'],
            [/[0-9]/.test(v), 'One number'],
            [/[^A-Za-z0-9]/.test(v), 'One special character'],
        ];
        document.getElementById('password-feedback').innerHTML =
            checks.map(([pass, label]) => `<div>${pass ? ok(label) : no(label)}</div>`).join('');
    });

    confirm.addEventListener('input', function () {
        const fb = document.getElementById('confirm-password-feedback');
        if (!this.value) { fb.innerHTML = 'Enter the same password again.'; return; }
        fb.innerHTML = this.value === password.value ? ok('Passwords match') : no('Passwords do not match');
    });

    username.addEventListener('input', function () {
        const fb = document.getElementById('username-feedback');
        const v = this.value;
        if (!v) { fb.innerHTML = '3–30 letters, numbers, _ or -'; return; }
        if (v.length < 3) { fb.innerHTML = no('At least 3 characters'); return; }
        fb.innerHTML = /^[a-zA-Z0-9_-]+$/.test(v) ? ok('Looks good') : no('Only letters, numbers, _ and -');
    });

    email.addEventListener('input', function () {
        const fb = document.getElementById('email-feedback');
        const v = this.value;
        if (!v) { fb.innerHTML = "We'll never share your email."; return; }
        fb.innerHTML = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? ok('Valid email') : no('Enter a valid email address');
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

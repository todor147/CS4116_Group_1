<?php
// db_connection boots config (env, secure session, helpers) and gives us $pdo.
require __DIR__ . '/../includes/db_connection.php';
require __DIR__ . '/../includes/auth_functions.php';
require __DIR__ . '/../includes/validation_functions.php';

// Already signed in? Send them on their way.
if (!empty($_SESSION['logged_in'])) {
    header('Location: ' . (($_SESSION['user_type'] ?? '') === 'admin' ? 'admin.php' : 'dashboard.php'));
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Please enter your email or username and your password.';
        } else {
            $user = authenticateUser($pdo, $email, $password);

            if ($user) {
                startUserSession($user);

                // Cache coach_id for coaches.
                try {
                    $stmt = $pdo->prepare("SELECT coach_id FROM Coaches WHERE user_id = ?");
                    $stmt->execute([$user['user_id']]);
                    if ($coach = $stmt->fetch()) {
                        $_SESSION['coach_id'] = $coach['coach_id'];
                    }
                } catch (PDOException $e) {
                    error_log('Coach lookup failed on login: ' . $e->getMessage());
                }

                // Safe internal redirect only.
                $redirect = $_GET['redirect'] ?? '';
                if ($redirect !== '' && preg_match('#^[a-zA-Z0-9_\-./?=&]+$#', $redirect) && !str_contains($redirect, '//')) {
                    header('Location: ' . $redirect);
                    exit;
                }
                header('Location: ' . (($user['user_type'] ?? '') === 'admin' ? 'admin.php' : 'dashboard.php'));
                exit;
            }
            $error = 'Invalid email or password.';
        }
    }
}

$page_title = 'Log in — EduCoach';
include __DIR__ . '/../includes/header.php';
?>

<section class="section-sm">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <div class="card border-0 shadow-lg">
                    <div class="card-body p-4 p-sm-5">
                        <div class="text-center mb-4">
                            <span class="brand-mark mx-auto mb-3" style="width:48px;height:48px;font-size:1.4rem">
                                <i class="bi bi-mortarboard-fill"></i>
                            </span>
                            <h1 class="h3 mb-1">Welcome back</h1>
                            <p class="text-muted mb-0">Log in to continue learning with EduCoach.</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
                                <i class="bi bi-exclamation-circle"></i><span><?= e($error) ?></span>
                            </div>
                        <?php endif; ?>

                        <form action="login.php<?= isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>" method="post" novalidate>
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email or username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="email" name="email"
                                           value="<?= e($email) ?>" autocomplete="username" required>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <label for="password" class="form-label">Password</label>
                                    <a href="forgot-password.php" class="small">Forgot password?</a>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password"
                                           autocomplete="current-password" required>
                                    <button class="btn btn-outline-secondary" type="button" data-toggle-password="#password" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mt-3">Log in</button>
                        </form>

                        <p class="text-center text-muted mt-4 mb-0">
                            New to EduCoach? <a href="register.php" class="fw-semibold">Create an account</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

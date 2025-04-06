<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';

// Process form submission
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    // Validate input
    if (empty($name)) {
        $error = 'Please enter your name.';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (empty($subject)) {
        $error = 'Please enter a subject.';
    } elseif (empty($message)) {
        $error = 'Please enter your message.';
    } else {
        // Store the contact form submission
        try {
            $stmt = $pdo->prepare("
                INSERT INTO ContactSubmissions 
                (name, email, subject, message, created_at, status)
                VALUES (?, ?, ?, ?, NOW(), 'new')
            ");
            $stmt->execute([$name, $email, $subject, $message]);
            $success = true;
            
            // Clear form data after successful submission
            $name = $email = $subject = $message = '';
        } catch (PDOException $e) {
            $error = 'Sorry, there was an error processing your submission. Please try again later.';
            error_log('Contact form error: ' . $e->getMessage());
        }
    }
}

// Include header
include __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-4">Contact Us</h1>
            <p class="lead text-muted">We'd love to hear from you. Get in touch with our team.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <h4 class="alert-heading">Thank you for your message!</h4>
                    <p>We have received your inquiry and will get back to you as soon as possible.</p>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm mb-5">
                <div class="card-body p-4 p-md-5">
                    <form method="post" action="contact.php">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="name" class="form-label">Your Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= isset($name) ? htmlspecialchars($name) : '' ?>" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" value="<?= isset($subject) ? htmlspecialchars($subject) : '' ?>" required>
                        </div>
                        <div class="mb-4">
                            <label for="message" class="form-label">Your Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required><?= isset($message) ? htmlspecialchars($message) : '' ?></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body py-4">
                            <i class="bi bi-envelope text-primary" style="font-size: 2.5rem;"></i>
                            <h5 class="mt-3">Email Us</h5>
                            <p class="text-muted">support@educoach.com</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body py-4">
                            <i class="bi bi-telephone text-primary" style="font-size: 2.5rem;"></i>
                            <h5 class="mt-3">Call Us</h5>
                            <p class="text-muted">+1 (555) 123-4567</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-body py-4">
                            <i class="bi bi-geo-alt text-primary" style="font-size: 2.5rem;"></i>
                            <h5 class="mt-3">Visit Us</h5>
                            <p class="text-muted">123 Learning Lane<br>Education City, EC 12345</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
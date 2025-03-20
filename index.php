<?php
session_start();
require_once 'includes/db_connection.php';

// Include the header
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h1>Welcome to EduCoach</h1>
            <p>This is the homepage of our educational coaching platform.</p>
            
            <?php if (isset($_SESSION['logged_in'])): ?>
                <p>You are logged in as: <?= htmlspecialchars($_SESSION['username'] ?? '') ?></p>
            <?php else: ?>
                <p>Please <a href="pages/login.php">login</a> or <a href="pages/register.php">register</a> to continue.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container mt-5">
    <div class="jumbotron bg-light p-5 rounded-3 mb-4">
        <h1>Welcome to EduCoach</h1>
        
        <?php if (isset($_SESSION['logged_in'])): ?>
            <div class="row">
                <div class="col-md-8">
                    <h3>Hello, <?= htmlspecialchars($_SESSION['username']) ?>!</h3>
                    
                    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'business'): ?>
                        <div class="card mt-4">
                            <div class="card-body">
                                <h5 class="card-title">Coach Dashboard</h5>
                                <a href="sessions.php" class="btn btn-primary">View Your Sessions</a>
                                <a href="profile.php" class="btn btn-secondary">Edit Profile</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card mt-4">
                            <div class="card-body">
                                <h5 class="card-title">Learner Dashboard</h5>
                                <a href="search.php" class="btn btn-primary">Find a Coach</a>
                                <a href="sessions.php" class="btn btn-secondary">Your Sessions</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Quick Actions</h5>
                            <a href="profile.php" class="btn btn-outline-primary mb-2">Edit Profile</a>
                            <a href="messages.php" class="btn btn-outline-secondary mb-2">Messages</a>
                            <a href="logout.php" class="btn btn-outline-danger">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Get Started</h5>
                            <p class="card-text">Join our platform to connect with expert coaches or start your coaching journey.</p>
                            <a href="register.php" class="btn btn-primary">Register Now</a>
                            <a href="login.php" class="btn btn-secondary">Login</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">About EduCoach</h5>
                            <p class="card-text">EduCoach is your platform for personalized learning and coaching. Connect with experts in various fields to enhance your skills and knowledge.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?> 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduCoach</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
            color: #2c3e50;
        }
        .nav-link {
            font-weight: 500;
        }
        .btn-messages {
            border-radius: 50px;
            padding: 0.375rem 1rem;
        }
        .btn-login {
            border-radius: 4px;
            font-weight: 500;
        }
        .hero-section {
            background-color: #f8f9fa;
            padding: 4rem 0;
        }
        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
        }
        .hero-text {
            font-size: 1.1rem;
            color: #6c757d;
        }
        .category-card {
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
            text-align: center;
        }
        .category-card:hover {
            transform: translateY(-5px);
        }
        .category-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto;
        }
        .category-title {
            margin-top: 1rem;
            font-weight: 600;
        }
        .user-dropdown .dropdown-toggle::after {
            display: none;
        }
        .user-dropdown .dropdown-toggle {
            display: flex;
            align-items: center;
            font-weight: 500;
            position: relative;
            width: auto;
            overflow: visible;
        }
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 8px;
            object-fit: cover;
        }
        .dropdown-menu .dropdown-item {
            clear: both;
            white-space: nowrap;
            display: block;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white py-3 shadow-sm">
        <div class="container">
            <a class="navbar-brand text-primary" href="home.php">EduCoach</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'home.php' ? 'active' : '' ?>" href="home.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'coach-search.php' ? 'active' : '' ?>" href="coach-search.php">Find Coaches</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="search.php?sort=rating">Highest Rated</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="search.php?category=sports">Sports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="search.php?category=education">Education</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="search.php?category=music">Music</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="help.php">Help Center</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <?php if (isset($_SESSION['logged_in'])): ?>
                        <a href="messages.php" class="btn btn-outline-primary btn-messages me-3">
                            <i class="bi bi-chat-dots-fill"></i> Messages
                        </a>
                        <div class="dropdown user-dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php 
                                // Ultra-simplified profile image handling
                                $profile_image = isset($_SESSION['profile_image']) && !empty($_SESSION['profile_image']) ? $_SESSION['profile_image'] : 'default.jpg';
                                
                                // Create the appropriate relative path based on current location
                                $current_script = $_SERVER['SCRIPT_NAME'];
                                
                                // Determine if we're in a subdirectory
                                if (strpos($current_script, '/pages/') !== false) {
                                    // We're in a pages directory, need to go up one level
                                    $image_base_path = "../assets/images/profiles/";
                                } else {
                                    // We're at root level
                                    $image_base_path = "assets/images/profiles/";
                                }
                                
                                // Simple check for the profile image with file_exists
                                $image_file_path = __DIR__ . "/../assets/images/profiles/{$profile_image}";
                                $default_file_path = __DIR__ . "/../assets/images/profiles/default.jpg";
                                
                                // Add a cache buster
                                $cache_buster = "?t=" . time();
                                
                                if (file_exists($image_file_path)) {
                                    // Use the user's profile image for display
                                    $display_image = $image_base_path . $profile_image . $cache_buster;
                                    error_log("HEADER: Using user's profile image: {$display_image}");
                                } else {
                                    // Use the default image
                                    $display_image = $image_base_path . "default.jpg" . $cache_buster;
                                    error_log("HEADER: Using default image: {$display_image} (user image not found at {$image_file_path})");
                                }
                                
                                // Log default image existence
                                error_log("DEFAULT IMAGE check: {$default_file_path} exists? " . (file_exists($default_file_path) ? "YES" : "NO"));
                                ?>
                                
                                <img src="<?= $display_image ?>" alt="<?= htmlspecialchars($_SESSION['username']) ?>" class="user-avatar">
                                <span class="username"><?= htmlspecialchars($_SESSION['username']) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'business'): ?>
                                <li><a class="dropdown-item" href="coach-settings.php"><i class="bi bi-gear me-2"></i>Coach Settings</a></li>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                                <li><a class="dropdown-item" href="manage-categories.php"><i class="bi bi-tags me-2"></i>Manage Categories</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary btn-login">Login / Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <?php
    ?> 
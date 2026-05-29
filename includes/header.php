<?php
require_once __DIR__ . '/config.php';

$current_page = basename($_SERVER['PHP_SELF']);

// Notification count for the bell badge (logged-in users only).
$unread_notification_count = 0;
if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']) && isset($pdo)) {
    require_once __DIR__ . '/notification_functions.php';
    $unread_notification_count = getUnreadNotificationCount($pdo, $_SESSION['user_id']);
}

$page_title = $page_title ?? 'EduCoach — Find your perfect coach';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="EduCoach connects learners with expert tutors and coaches across maths, languages, music, coding and more. Search, book and learn — online or in person.">
    <meta name="theme-color" content="#4f46e5">
    <title><?= e($page_title) ?></title>

    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap + icon sets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

    <!-- App styles -->
    <link rel="stylesheet" href="<?= asset('assets/css/styles.css') ?>">
</head>
<body>
    <a class="visually-hidden-focusable skip-link" href="#main-content">Skip to content</a>

    <nav class="navbar navbar-expand-lg sticky-top app-navbar" id="appNavbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="home.php">
                <span class="brand-mark"><i class="bi bi-mortarboard-fill"></i></span>
                <span>Edu<span class="text-primary">Coach</span></span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'home.php' ? 'active' : '' ?>" href="home.php">Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= $current_page === 'coach-search.php' ? 'active' : '' ?>"
                           href="#" id="findCoachesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Find coaches
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="findCoachesDropdown">
                            <li><a class="dropdown-item" href="coach-search.php"><i class="bi bi-grid me-2"></i>All coaches</a></li>
                            <li><a class="dropdown-item" href="coach-search.php?sort_by=rating_desc"><i class="bi bi-star me-2"></i>Highest rated</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Browse by category</h6></li>
                            <li><a class="dropdown-item" href="coach-search.php?category=1">Mathematics</a></li>
                            <li><a class="dropdown-item" href="coach-search.php?category=2">Languages</a></li>
                            <li><a class="dropdown-item" href="coach-search.php?category=3">Sciences</a></li>
                            <li><a class="dropdown-item" href="coach-search.php?category=5">Computer Science</a></li>
                            <li><a class="dropdown-item" href="coach-search.php?category=6">Test Preparation</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'how-it-works.php' ? 'active' : '' ?>" href="how-it-works.php">How it works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'help.php' ? 'active' : '' ?>" href="help.php">Help</a>
                    </li>
                </ul>

                <ul class="navbar-nav align-items-lg-center">
                    <?php if (!empty($_SESSION['logged_in'])): ?>
                        <li class="nav-item">
                            <a class="nav-link nav-icon position-relative <?= $current_page === 'notifications.php' ? 'active' : '' ?>"
                               href="notifications.php" id="notificationLink" aria-label="Notifications">
                                <i class="bi bi-bell"></i>
                                <span id="notificationBadge"
                                      class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?= $unread_notification_count <= 0 ? 'd-none' : '' ?>">
                                    <?= $unread_notification_count > 99 ? '99+' : ($unread_notification_count > 0 ? $unread_notification_count : '') ?>
                                    <span class="visually-hidden">unread notifications</span>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link nav-icon <?= $current_page === 'messages.php' ? 'active' : '' ?>"
                               href="messages.php" aria-label="Messages"><i class="bi bi-chat-dots"></i></a>
                        </li>
                        <li class="nav-item dropdown user-dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userDropdown"
                               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php if (!empty($_SESSION['profile_image'])): ?>
                                    <img src="<?= asset('assets/images/profiles/' . $_SESSION['profile_image']) ?>" class="user-avatar" alt="">
                                <?php else: ?>
                                    <span class="user-avatar user-avatar--placeholder"><i class="bi bi-person"></i></span>
                                <?php endif; ?>
                                <span class="d-none d-lg-inline"><?= e($_SESSION['username'] ?? 'Account') ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="session.php"><i class="bi bi-calendar-check me-2"></i>My sessions</a></li>
                                <?php if (($_SESSION['user_type'] ?? '') === 'admin'): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="admin.php"><i class="bi bi-shield-lock me-2"></i>Admin panel</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log out</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page === 'login.php' ? 'active' : '' ?>" href="login.php">Log in</a>
                        </li>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-primary px-3" href="register.php">Get started</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main id="main-content">

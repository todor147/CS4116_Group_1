                <div class="d-flex align-items-center">
                    <?php if (isset($_SESSION['logged_in'])): ?>
                        <?php
                        // Get unread messages count
                        $unread_count = 0;
                        if (isset($_SESSION['user_id'])) {
                            try {
                                $stmt = $pdo->prepare("
                                    SELECT COUNT(*) as count 
                                    FROM Messages 
                                    WHERE receiver_id = ? 
                                    AND is_read = 0
                                    AND status = 'approved'
                                ");
                                $stmt->execute([$_SESSION['user_id']]);
                                $unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            } catch (PDOException $e) {
                                // Silent fail, we'll just show 0
                            }
                        }
                        ?>
                        <a href="messages.php" class="btn btn-outline-primary btn-messages me-3 position-relative">
                            <i class="bi bi-chat-dots-fill"></i> Messages
                            <?php if ($unread_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= $unread_count > 99 ? '99+' : $unread_count ?>
                                    <span class="visually-hidden">unread messages</span>
                                </span>
                            <?php endif; ?>
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
                                <li><a class="dropdown-item" href="admin.php"><i class="bi bi-shield-lock me-2"></i>Admin Panel</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-primary me-2">Log In</a>
                        <a href="register.php" class="btn btn-primary">Sign Up</a>
                    <?php endif; ?>
                </div> 
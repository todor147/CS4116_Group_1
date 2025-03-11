<?php
/**
 * Authenticate a user with email and password
 * 
 * @param PDO $pdo Database connection
 * @param string $email User email
 * @param string $password User password
 * @return array|false User data if authentication successful, false otherwise
 */
function authenticateUser($pdo, $email, $password) {
    $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ? AND is_banned = 0");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Update last login time
        updateLastLogin($pdo, $user['user_id']);
        return $user;
    }
    return false;
}

/**
 * Start a user session
 * 
 * @param array $user User data
 * @return void
 */
function startUserSession($user) {
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['logged_in'] = true;
    $_SESSION['profile_image'] = $user['profile_image'];
    
    // Set session timeout - 2 hours
    $_SESSION['last_activity'] = time();
    $_SESSION['expire_time'] = 7200; // 2 hours in seconds
}

/**
 * Check if a user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    // Check if session exists and user is logged in
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        // Check for session timeout
        if (isset($_SESSION['last_activity']) && isset($_SESSION['expire_time'])) {
            if (time() - $_SESSION['last_activity'] < $_SESSION['expire_time']) {
                // Update last activity time
                $_SESSION['last_activity'] = time();
                return true;
            } else {
                // Session expired, logout user
                logoutUser();
                return false;
            }
        }
        return true;
    }
    return false;
}

/**
 * Check if a user is a coach
 * 
 * @return bool True if user is a coach, false otherwise
 */
function isCoach() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'business';
}

/**
 * Check if a user is an admin
 * 
 * @return bool True if user is an admin, false otherwise
 */
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * Log out a user
 * 
 * @return void
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Update a user's last login time
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @return void
 */
function updateLastLogin($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("UPDATE Users SET last_login = NOW() WHERE user_id = ?");
        $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        // Log error but don't disrupt login process
        error_log("Failed to update last login: " . $e->getMessage());
    }
}

/**
 * Get a user by ID
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @return array|false User data if found, false otherwise
 */
function getUserById($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to get user: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if a user is a verified customer for a coach
 * 
 * @param PDO $pdo Database connection
 * @param int $user_id User ID
 * @param int $coach_id Coach ID
 * @return bool True if user is a verified customer, false otherwise
 */
function isVerifiedCustomer($pdo, $user_id, $coach_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM Sessions 
            WHERE learner_id = ? AND coach_id = ? AND status = 'completed'
        ");
        $stmt->execute([$user_id, $coach_id]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Failed to check verified customer status: " . $e->getMessage());
        return false;
    }
}

/**
 * Request a password reset for a user
 * 
 * @param PDO $pdo Database connection
 * @param string $email User email
 * @return bool True if reset request successful, false otherwise
 */
function requestPasswordReset($pdo, $email) {
    try {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Save token to database
        $stmt = $pdo->prepare("UPDATE Users SET reset_token = ?, reset_expires = ? WHERE user_id = ?");
        $stmt->execute([$token, $expires, $user['user_id']]);
        
        // Create reset link
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/pages/reset-password.php?token=" . $token;
        $subject = "Password Reset Request";
        $message = "Hello,\n\nYou have requested to reset your password. Please click the link below to reset your password:\n\n$resetLink\n\nThis link will expire in 1 hour.\n\nIf you did not request this, please ignore this email.";
        $headers = "From: noreply@educoach.com";
        
        // Check if we're in development mode
        $is_development = ($_SERVER['SERVER_NAME'] === 'localhost' || strpos($_SERVER['SERVER_NAME'], '127.0.0.1') === 0);
        
        if ($is_development) {
            // Development: Store the reset link in session for display
            $_SESSION['reset_link'] = $resetLink;
            $_SESSION['reset_email'] = $email;
            
            // Log email details for debugging
            error_log("DEV MODE: Password reset link for $email: $resetLink");
            
            return true;
        } else {
            // Production: Actually send the email
            return mail($email, $subject, $message, $headers);
        }
        
    } catch (PDOException $e) {
        error_log("Failed to request password reset: " . $e->getMessage());
        return false;
    }
}

/**
 * Reset a user's password using a reset token
 * 
 * @param PDO $pdo Database connection
 * @param string $token Reset token
 * @param string $password New password
 * @return bool True if password reset successful, false otherwise
 */
function resetPassword($pdo, $token, $password) {
    try {
        // Check if token is valid and not expired
        $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // Hash new password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password and clear token
        $stmt = $pdo->prepare("UPDATE Users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?");
        $stmt->execute([$password_hash, $user['user_id']]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Failed to reset password: " . $e->getMessage());
        return false;
    }
}
?> 
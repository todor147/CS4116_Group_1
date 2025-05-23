<?php
/**
 * Email validation
 * 
 * @param string $email Email to validate
 * @return bool True if email is valid, false otherwise
 */
function isValidEmail($email) {
    // First use PHP's filter_var for basic validation
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return false;
    }

    // Additional validation for more strict email format
    // Check if the domain has at least one dot
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return false;
    }
    
    $domain = $parts[1];
    if (strpos($domain, '.') === false) {
        return false;
    }
    
    // Check for common disposable email domains - expand this list as needed
    $disallowed_domains = [
        // Add domains if you want to block disposable email providers
    ];
    
    foreach ($disallowed_domains as $disallowed) {
        if (stripos($domain, $disallowed) !== false) {
            return false;
        }
    }
    
    return true;
}

/**
 * Password validation
 * Checks if password meets security requirements:
 * - At least 8 characters
 * - At least one uppercase letter
 * - At least one lowercase letter
 * - At least one number
 * - At least one special character
 * 
 * @param string $password Password to validate
 * @return bool True if password is valid, false otherwise
 */
function isValidPassword($password) {
    // At least 8 characters
    if (strlen($password) < 8) {
        return false;
    }
    
    // Check for at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    // Check for at least one lowercase letter
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    // Check for at least one number
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    // Check for at least one special character
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return false;
    }
    
    return true;
}

/**
 * Username validation
 * Checks if username meets requirements:
 * - 3-30 characters
 * - Only alphanumeric characters, underscores, and hyphens
 * 
 * @param string $username Username to validate
 * @return bool True if username is valid, false otherwise
 */
function isValidUsername($username) {
    // Length check - between 3 and 30 characters
    if (strlen(trim($username)) < 3 || strlen(trim($username)) > 30) {
        return false;
    }
    
    // Character validation - only letters, numbers, underscores, and hyphens
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        return false;
    }
    
    // Prevent usernames with only special characters
    if (!preg_match('/[a-zA-Z0-9]/', $username)) {
        return false;
    }
    
    // Prevent common fake usernames
    $disallowed_usernames = [
        'admin', 'administrator', 'system', 'root', 'superuser', 'support'
    ];
    
    if (in_array(strtolower($username), $disallowed_usernames)) {
        return false;
    }
    
    return true;
}

/**
 * Sanitize text input to prevent XSS attacks
 * 
 * @param string $text Text to sanitize
 * @return string Sanitized text
 */
function sanitizeText($text) {
    return htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if file is a valid image
 * 
 * @param array $file $_FILES array element
 * @return bool True if file is a valid image, false otherwise
 */
function isValidImage($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        return false;
    }
    
    return true;
}

/**
 * Validates text length between min and max values
 * 
 * @param string $text Text to validate
 * @param int $min Minimum length
 * @param int $max Maximum length
 * @return bool True if text length is valid, false otherwise
 */
function isValidLength($text, $min, $max) {
    $length = strlen(trim($text));
    return $length >= $min && $length <= $max;
}

/**
 * Generate a random token
 * 
 * @param int $length Length of token
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}
?> 
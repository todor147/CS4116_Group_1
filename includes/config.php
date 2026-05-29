<?php
/**
 * EduCoach — central application bootstrap.
 *
 * Loads environment configuration, configures error handling and sessions,
 * and exposes small helpers used across the app. Include this once at the top
 * of any entry point (db_connection.php already does this for you).
 */

if (defined('EDUCOACH_BOOTSTRAPPED')) {
    return;
}
define('EDUCOACH_BOOTSTRAPPED', true);

define('APP_ROOT', dirname(__DIR__));

/* -------------------------------------------------------------------------
 * Environment variables
 * ---------------------------------------------------------------------- */

/**
 * Load variables from a .env file into the process environment.
 * Real environment variables (set by the host) always take precedence,
 * so the same code runs locally and in production without edits.
 */
function load_env(string $path): void
{
    if (!is_readable($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Strip optional surrounding quotes.
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'")) {
            $value = substr($value, 1, -1);
        }
        // Don't clobber variables provided by the hosting environment.
        if (getenv($key) === false && !isset($_ENV[$key]) && !isset($_SERVER[$key])) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

load_env(APP_ROOT . '/.env');

/**
 * Read an environment variable with an optional default.
 */
function env(string $key, $default = null)
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return match (strtolower((string) $value)) {
        'true'  => true,
        'false' => false,
        'null'  => null,
        default => $value,
    };
}

/* -------------------------------------------------------------------------
 * Environment / error handling
 * ---------------------------------------------------------------------- */

// APP_ENV: "production" or "development". Falls back to legacy IS_PRODUCTION.
$appEnv = env('APP_ENV');
if ($appEnv === null) {
    $appEnv = env('IS_PRODUCTION', false) ? 'production' : 'development';
}
define('APP_ENV', $appEnv);
define('IS_PRODUCTION', APP_ENV === 'production');

error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/logs/php_errors.log');
// Never leak stack traces / warnings to visitors in production.
ini_set('display_errors', IS_PRODUCTION ? '0' : '1');

date_default_timezone_set(env('APP_TIMEZONE', 'Europe/Dublin'));

/* -------------------------------------------------------------------------
 * Sessions (secure defaults)
 * ---------------------------------------------------------------------- */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* -------------------------------------------------------------------------
 * Security headers (safe, broadly-compatible defaults)
 * ---------------------------------------------------------------------- */

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

/* -------------------------------------------------------------------------
 * URL / asset helpers
 * ---------------------------------------------------------------------- */

// Base path lets the app live in a sub-directory if ever needed ("" = web root).
define('BASE_PATH', rtrim((string) env('BASE_PATH', ''), '/'));

/** Build an absolute, root-relative URL to an asset (CSS, JS, image). */
function asset(string $path): string
{
    return BASE_PATH . '/' . ltrim($path, '/');
}

/* -------------------------------------------------------------------------
 * CSRF protection
 * ---------------------------------------------------------------------- */

/** Return the current CSRF token, creating one if needed. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Hidden input markup to drop inside a <form>. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

/** Validate a submitted token against the session token. */
function verify_csrf(?string $token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** Escape a string for safe HTML output. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

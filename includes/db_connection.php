<?php
/**
 * EduCoach — database connection (PDO/MySQL).
 *
 * Configuration comes entirely from environment variables, so the same code
 * runs locally, in Docker, and on any free host without edits. Two forms are
 * supported:
 *
 *   1. A single DATABASE_URL  (e.g. mysql://user:pass@host:3306/dbname)
 *   2. Discrete DB_* variables (DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT)
 *
 * Optional TLS (required by some managed providers such as TiDB Cloud / Aiven):
 *   DB_SSL=true            enable TLS
 *   DB_SSL_CA=/path/ca.pem path to a CA bundle (optional but recommended)
 *
 * Exposes a ready-to-use $pdo instance.
 */

require_once __DIR__ . '/config.php';

/** Resolve connection settings from the environment. */
function db_settings(): array
{
    $url = env('DATABASE_URL');
    if ($url) {
        $p = parse_url($url);
        return [
            'host'    => $p['host'] ?? 'localhost',
            'port'    => (int) ($p['port'] ?? 3306),
            'name'    => isset($p['path']) ? ltrim($p['path'], '/') : '',
            'user'    => isset($p['user']) ? urldecode($p['user']) : '',
            'pass'    => isset($p['pass']) ? urldecode($p['pass']) : '',
            'charset' => 'utf8mb4',
        ];
    }

    return [
        'host'    => env('DB_HOST', 'localhost'),
        'port'    => (int) env('DB_PORT', 3306),
        'name'    => env('DB_NAME', 'cs4116_marketplace'),
        'user'    => env('DB_USER', 'root'),
        'pass'    => env('DB_PASS', ''),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
    ];
}

$cfg = db_settings();

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $cfg['host'],
    $cfg['port'],
    $cfg['name'],
    $cfg['charset']
);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Optional TLS for managed database providers.
if (env('DB_SSL', false)) {
    $ca = env('DB_SSL_CA');
    if ($ca) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
    } else {
        // Encrypt the connection even when a CA bundle isn't supplied.
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
}

try {
    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], $options);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());

    http_response_code(503);
    $detail = IS_PRODUCTION ? '' : '<p style="color:#666;font-size:.9rem">' . e($e->getMessage()) . '</p>';
    echo <<<HTML
        <!doctype html>
        <html lang="en"><head><meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Service unavailable — EduCoach</title>
        <style>
            body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f5f7fb;
                 display:grid;place-items:center;min-height:100vh;margin:0;color:#1f2937}
            .box{background:#fff;border-radius:16px;padding:2.5rem;max-width:30rem;text-align:center;
                 box-shadow:0 10px 30px rgba(0,0,0,.08)}
            h1{margin:.25rem 0 1rem;font-size:1.4rem}
        </style></head>
        <body><div class="box">
            <h1>We can't reach the database right now</h1>
            <p>Please try again in a moment. If this keeps happening, the database
               connection settings may need to be checked.</p>
            $detail
        </div></body></html>
        HTML;
    exit;
}

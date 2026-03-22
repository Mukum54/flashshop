<?php
/**
 * config.php — Production Database & App Configuration
 * FlashShop E-Commerce
 *
 * Reads all credentials from environment variables so no
 * hardcoded secrets ever enter source control.
 *
 * On AWS EC2: set vars in /etc/environment or via PM2 env_file.
 * Locally:    copy .env.example → .env, then source it.
 */

// ── Production error reporting (never expose errors publicly) ──
if (getenv('APP_ENV') === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/logs/php_errors.log');
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// ── Load .env file if it exists (development / local) ────────
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            // Strip inline comments
            $value = explode(' #', $value)[0];
            $value = trim($value, '"\'');
            if (!array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

// ── Read credentials from environment ─────────────────────────
$db_host     = getenv('DB_HOST')     ?: 'localhost';
$db_port     = getenv('DB_PORT')     ?: '3306';
$db_name     = getenv('DB_NAME')     ?: 'flashshop';
$db_user     = getenv('DB_USER')     ?: 'root';
$db_password = getenv('DB_PASSWORD') ?: '';
$db_charset  = 'utf8mb4';

define('APP_ENV',    getenv('APP_ENV')    ?: 'development');
define('APP_SECRET', getenv('APP_SECRET') ?: 'fallback-secret-change-me');
define('WHATSAPP_NUMBER', getenv('WHATSAPP_NUMBER') ?: '237654492653');

// ── Create PDO connection ────────────────────────────────────
$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset={$db_charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,      // True prepared statements → SQL injection prevention
    PDO::ATTR_PERSISTENT         => false,
    PDO::MYSQL_ATTR_FOUND_ROWS   => true,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_password, $options);
} catch (PDOException $e) {
    // Log the real error, show a safe message to users
    error_log('[FlashShop] DB Connection failed: ' . $e->getMessage());
    $isProduction = getenv('APP_ENV') === 'production';
    http_response_code(503);
    die('<div style="font-family:system-ui,sans-serif;padding:60px 40px;max-width:560px;margin:60px auto;background:#fff;border-radius:16px;box-shadow:0 4px 30px rgba(0,0,0,.08);text-align:center;">'
        . '<div style="font-size:3rem;margin-bottom:20px;">⚙️</div>'
        . '<h2 style="color:#1C1C1E;margin-bottom:10px;">Service Temporarily Unavailable</h2>'
        . '<p style="color:#6b7280;margin-bottom:24px;">We\'re having trouble connecting to the database. Please try again in a moment.</p>'
        . (!$isProduction ? '<code style="font-size:0.8rem;color:#dc2626;background:#fef2f2;padding:10px 16px;border-radius:8px;display:block;">' . htmlspecialchars($e->getMessage()) . '</code>' : '')
        . '</div>');
}

// ── Session hardening ──────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();

    // Session fixation protection — regenerate ID on first visit
    if (empty($_SESSION['__initiated'])) {
        session_regenerate_id(true);
        $_SESSION['__initiated'] = true;
    }
}

// ── Security headers ───────────────────────────────────────────
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; "
         . "script-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
         . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.gstatic.com; "
         . "font-src 'self' https://fonts.gstatic.com; "
         . "img-src 'self' data: https://images.unsplash.com blob:; "
         . "connect-src 'self';");
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

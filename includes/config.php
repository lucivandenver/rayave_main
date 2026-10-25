<?php
session_start();

// Attempt to load Composer autoloader before anything else
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// Load environment variables from .env if present
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            $val = trim($val, "\"'\t ");
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Manila');
}

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'flowershop');

if (!defined('APP_URL')) {
    define('APP_URL', getenv('APP_URL') ?: 'http://localhost/flowershop-main');
}

if (!defined('MAIL_FROM_NAME')) {
    define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'Raymundo Avenue');
}

if (!defined('MAIL_FROM_ADDRESS')) {
    define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'raymundoavenueflowershop@gmail.com');
}

if (!defined('MAIL_SMTP_HOST')) {
    define('MAIL_SMTP_HOST', getenv('MAIL_SMTP_HOST') ?: 'smtp.gmail.com');
}

if (!defined('MAIL_SMTP_PORT')) {
    define('MAIL_SMTP_PORT', (int) (getenv('MAIL_SMTP_PORT') ?: 587));
}

if (!defined('MAIL_SMTP_USER')) {
    define('MAIL_SMTP_USER', getenv('MAIL_SMTP_USER') ?: '');
}

if (!defined('MAIL_SMTP_PASS')) {
    define('MAIL_SMTP_PASS', getenv('MAIL_SMTP_PASS') ?: '');
}

if (!defined('MAIL_SMTP_ENCRYPTION')) {
    define('MAIL_SMTP_ENCRYPTION', getenv('MAIL_SMTP_ENCRYPTION') ?: 'tls');
}

if (!defined('PASSWORD_RESET_EXPIRY_MINUTES')) {
    define('PASSWORD_RESET_EXPIRY_MINUTES', (int) (getenv('PASSWORD_RESET_EXPIRY_MINUTES') ?: 30));
}

if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
}

if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
}

if (!defined('GOOGLE_REDIRECT_URI')) {
    define('GOOGLE_REDIRECT_URI', getenv('GOOGLE_REDIRECT_URI') ?: rtrim(APP_URL, '/') . '/google-callback.php');
}

if (!defined('GROQ_API_BASE')) {
    define('GROQ_API_BASE', getenv('GROQ_API_BASE') ?: 'https://api.groq.com/openai/v1');
}

if (!defined('GROQ_MODEL')) {
    define('GROQ_MODEL', getenv('GROQ_MODEL') ?: 'llama-3.3-70b-versatile');
}

if (!defined('GROQ_API_KEY')) {
    define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: '');
}

if (!defined('AUTO_CANCEL_PENDING_MIN_HOURS')) {
    define('AUTO_CANCEL_PENDING_MIN_HOURS', 6);
}

if (!defined('AUTO_CANCEL_PENDING_MAX_HOURS')) {
    define('AUTO_CANCEL_PENDING_MAX_HOURS', 12);
}

if (!defined('GOOGLE_MAPS_API_KEY')) {
    define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY') ?: '');
}

if (!defined('STORE_ORIGIN_LAT')) {
    define('STORE_ORIGIN_LAT', getenv('STORE_ORIGIN_LAT') ?: '12.592676');
}

if (!defined('STORE_ORIGIN_LNG')) {
    define('STORE_ORIGIN_LNG', getenv('STORE_ORIGIN_LNG') ?: '121.5180891');
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Include functions after database connection is established
require_once 'functions.php';
?>
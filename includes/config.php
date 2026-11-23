<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
            if (getenv($key) === false && !array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

// Error reporting: show errors in local dev, log silently in Railway/production
$isProductionEnv = (
    getenv('RAILWAY_ENVIRONMENT') !== false
    || getenv('RAILWAY_PUBLIC_DOMAIN') !== false
    || strtolower((string) getenv('APP_ENV')) === 'production'
);
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors', $isProductionEnv ? '0' : '1');

if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Manila');
}

$isDockerEnv = (getenv('DOCKER_ENV') === 'true' || file_exists('/.dockerenv'));

// Resolve database settings (supports Local Laragon, Docker Compose, and Railway MySQL)
$urlHost = $urlPort = $urlUser = $urlPass = $urlName = null;
$rawDbUrl = getenv('MYSQL_URL') ?: (getenv('DATABASE_URL') ?: '');
if ($rawDbUrl !== '') {
    $parsedUrl = @parse_url($rawDbUrl);
    if (is_array($parsedUrl)) {
        $urlHost = $parsedUrl['host'] ?? null;
        $urlPort = isset($parsedUrl['port']) ? (string) $parsedUrl['port'] : null;
        $urlUser = isset($parsedUrl['user']) ? rawurldecode($parsedUrl['user']) : null;
        $urlPass = isset($parsedUrl['pass']) ? rawurldecode($parsedUrl['pass']) : null;
        $urlName = isset($parsedUrl['path']) ? ltrim($parsedUrl['path'], '/') : null;
    }
}

$resolvedDbHost = getenv('DOCKER_DB_HOST')
    ?: (getenv('MYSQLHOST')
        ?: ($urlHost ?: (getenv('DB_HOST') ?: 'localhost')));

if (getenv('DOCKER_ENV') === 'true' && ($resolvedDbHost === 'localhost' || $resolvedDbHost === '127.0.0.1')) {
    $resolvedDbHost = 'db';
}

$resolvedDbPort = getenv('DOCKER_DB_PORT')
    ?: (getenv('MYSQLPORT')
        ?: ($urlPort ?: (getenv('DB_PORT') ?: '3306')));

$resolvedDbUser = getenv('DOCKER_DB_USER')
    ?: (getenv('MYSQLUSER')
        ?: ($urlUser ?: (getenv('DB_USER') ?: 'root')));

$resolvedDbPass = '';
if (getenv('DOCKER_DB_PASS') !== false) {
    $resolvedDbPass = (string) getenv('DOCKER_DB_PASS');
} elseif (getenv('MYSQLPASSWORD') !== false) {
    $resolvedDbPass = (string) getenv('MYSQLPASSWORD');
} elseif ($urlPass !== null) {
    $resolvedDbPass = (string) $urlPass;
} elseif (getenv('DB_PASS') !== false) {
    $resolvedDbPass = (string) getenv('DB_PASS');
}

$resolvedDbName = getenv('DOCKER_DB_NAME')
    ?: (getenv('MYSQLDATABASE')
        ?: ($urlName ?: (getenv('DB_NAME') ?: 'flowershop')));

define('DB_HOST', $resolvedDbHost);
define('DB_PORT', $resolvedDbPort);
define('DB_USER', $resolvedDbUser);
define('DB_PASS', $resolvedDbPass);
define('DB_NAME', $resolvedDbName);

if (!defined('APP_URL')) {
    $railwayDomain = trim((string) (getenv('RAILWAY_PUBLIC_DOMAIN') ?: ''));
    $resolvedAppUrl = getenv('DOCKER_APP_URL')
        ?: (getenv('APP_URL')
            ?: ($railwayDomain !== '' ? 'https://' . $railwayDomain : 'http://localhost/flowershop-main'));

    if (($isDockerEnv || $isProductionEnv) && !empty($_SERVER['HTTP_HOST'])) {
        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        $isHttps = ($forwardedProto === 'https')
            || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($railwayDomain !== '');
        $scheme = $isHttps ? 'https' : 'http';
        $basePath = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/flowershop-main') ? '/flowershop-main' : '';
        $resolvedAppUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . $basePath;
    }
    define('APP_URL', $resolvedAppUrl);
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
    define('GROQ_MODEL', getenv('GROQ_MODEL') ?: 'openai/gpt-oss-120b');
}

if (!defined('GROQ_API_KEY')) {
    $rawGroqKey = trim((string) (getenv('GROQ_API_KEY') ?: ''));
    if (str_starts_with($rawGroqKey, 'gsgsk_')) {
        $rawGroqKey = substr($rawGroqKey, 2);
    }
    define('GROQ_API_KEY', $rawGroqKey);
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
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Include functions after database connection is established
require_once 'functions.php';
?>
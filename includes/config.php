<?php
session_start();

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'flowershop');

if (!defined('GROQ_API_BASE')) {
    define('GROQ_API_BASE', getenv('GROQ_API_BASE') ?: 'GROQ_API_BASE');
}

if (!defined('GROQ_MODEL')) {
    define('GROQ_MODEL', getenv('GROQ_MODEL') ?: 'GROQ_MODEL');
}

if (!defined('GROQ_API_KEY')) {
    define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: 'GROQ_API_KEY');
}

if (!defined('AUTO_CANCEL_PENDING_MIN_HOURS')) {
    define('AUTO_CANCEL_PENDING_MIN_HOURS', 6);
}

if (!defined('AUTO_CANCEL_PENDING_MAX_HOURS')) {
    define('AUTO_CANCEL_PENDING_MAX_HOURS', 12);
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
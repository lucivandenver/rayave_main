<?php
// Clears all cookies for the current site and destroys PHP session.
// Visit this file in your browser (e.g. http://localhost/flowershop-main/clear_site_cookies.php)

// Start session if present
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Clear all cookies sent by the browser
if (!empty($_COOKIE)) {
    foreach ($_COOKIE as $name => $value) {
        // Clear cookie for root path
        setcookie($name, '', time() - 3600, '/');
        // Also try clearing with typical flags
        setcookie($name, '', time() - 3600, '/', '', false, true);
    }
}

// Destroy PHP session
$_SESSION = [];
@session_unset();
@session_destroy();
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? true);
}

// Redirect back to the shop home page
header('Location: /flowershop-main/index.php');
exit();

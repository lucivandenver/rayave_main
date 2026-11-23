<?php
require_once 'includes/config.php';
ensureUserSecurityColumns();

if (empty(GOOGLE_CLIENT_ID) || empty(GOOGLE_CLIENT_SECRET)) {
    $_SESSION['error'] = 'Google sign-in is not configured yet.';
    redirect('login.php');
}

try {
    $state = bin2hex(random_bytes(24));
} catch (Exception $e) {
    $state = bin2hex(openssl_random_pseudo_bytes(24));
}

$_SESSION['google_oauth_state'] = $state;

$params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'prompt' => 'select_account',
    'access_type' => 'offline'
];

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
exit;

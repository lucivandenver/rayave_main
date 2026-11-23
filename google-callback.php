<?php
require_once 'includes/config.php';
ensureUserSecurityColumns();

function abortGoogleLogin($message)
{
    $_SESSION['error'] = $message;
    redirect('login.php');
}

if (isset($_GET['error'])) {
    abortGoogleLogin('Google sign-in was cancelled.');
}

$expectedState = $_SESSION['google_oauth_state'] ?? null;
unset($_SESSION['google_oauth_state']);

if (!$expectedState || (($_GET['state'] ?? '') !== $expectedState)) {
    abortGoogleLogin('Invalid Google login attempt. Please try again.');
}

$authCode = $_GET['code'] ?? '';
if ($authCode === '') {
    abortGoogleLogin('Missing Google authorization code.');
}

if (empty(GOOGLE_CLIENT_ID) || empty(GOOGLE_CLIENT_SECRET)) {
    abortGoogleLogin('Google sign-in is not configured yet.');
}

$tokenResponse = requestGoogleToken($authCode);
if (!$tokenResponse || empty($tokenResponse['access_token'])) {
    abortGoogleLogin('Unable to verify Google credentials.');
}

$userInfo = fetchGoogleUserProfile($tokenResponse['access_token']);
if (!$userInfo || empty($userInfo['sub']) || empty($userInfo['email'])) {
    abortGoogleLogin('Unable to retrieve your Google profile.');
}

$googleId = $userInfo['sub'];
$email = strtolower($userInfo['email']);
$fullName = trim($userInfo['name'] ?? '');
$avatarUrl = $userInfo['picture'] ?? null;
$emailVerified = !empty($userInfo['email_verified']);
$now = date('Y-m-d H:i:s');
$verifiedAt = $emailVerified ? $now : null;

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT * FROM users WHERE google_id = ? OR email = ? LIMIT 1');
    $stmt->execute([$googleId, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $userId = $user['id'];
        $update = $pdo->prepare('UPDATE users SET google_id = COALESCE(google_id, ?), email_verified_at = COALESCE(email_verified_at, ?), avatar_url = COALESCE(?, avatar_url) WHERE id = ?');
        $update->execute([$googleId, $verifiedAt, $avatarUrl, $userId]);
    } else {
        $usernameSeed = $userInfo['given_name'] ?? ($userInfo['family_name'] ?? explode('@', $email)[0]);
        $username = generateUniqueUsername($usernameSeed);
        $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role, google_id, email_verified_at, avatar_url, email_opt_in) VALUES (?, ?, ?, ?, 'customer', ?, ?, ?, 1)");
        $insert->execute([
            $username,
            $email,
            $randomPassword,
            $fullName !== '' ? $fullName : ucfirst($usernameSeed),
            $googleId,
            $verifiedAt ?: $now,
            $avatarUrl
        ]);
        $userId = (int) $pdo->lastInsertId();
    }

    $pdo->commit();

    $stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE (google_id = ? OR email = ?) ORDER BY google_id IS NULL LIMIT 1');
    $stmt->execute([$googleId, $email]);
    $authUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$authUser) {
        abortGoogleLogin('Account creation succeeded but login failed. Please try again.');
    }

    // prevent login if account disabled
    $check = $pdo->prepare('SELECT is_disabled FROM users WHERE id = ?');
    $check->execute([$authUser['id']]);
    $s = $check->fetch(PDO::FETCH_ASSOC);
    if (!empty($s['is_disabled'])) {
        abortGoogleLogin('This account has been disabled. Contact support.');
    }

    $_SESSION['user_id'] = $authUser['id'];
    $_SESSION['username'] = $authUser['username'];
    $_SESSION['role'] = $authUser['role'];
    $_SESSION['success'] = 'Signed in with Google successfully.';

    // Log activity for Google sign-in
    if (function_exists('logUserActivity')) {
        logUserActivity($authUser['id'], 'login', ['source' => 'google']);
    }

    $redirectTo = $_SESSION['redirect_to'] ?? 'index.php';
    unset($_SESSION['redirect_to']);

    redirect($redirectTo);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Google login failed: ' . $e->getMessage());
    abortGoogleLogin('We could not complete Google sign-in. Please try again.');
}

function requestGoogleToken($code)
{
    $payload = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_TIMEOUT => 20
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        error_log('Google token exchange failed: ' . curl_error($ch));
        curl_close($ch);
        return null;
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status >= 400) {
        error_log('Google token exchange HTTP ' . $status . ': ' . $response);
        return null;
    }

    return json_decode($response, true);
}

function fetchGoogleUserProfile($accessToken)
{
    $ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
        CURLOPT_TIMEOUT => 20
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        error_log('Google userinfo failed: ' . curl_error($ch));
        curl_close($ch);
        return null;
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status >= 400) {
        error_log('Google userinfo HTTP ' . $status . ': ' . $response);
        return null;
    }

    return json_decode($response, true);
}

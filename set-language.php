<?php
require_once 'includes/config.php';

$language = $_POST['language'] ?? $_GET['language'] ?? '';
$language = setCurrentLanguage($language);

$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? 'index.php');
$redirect = trim((string) $redirect);

if ($redirect === '') {
    $redirect = 'index.php';
}

// Keep redirects on the current site only
$parsed = parse_url($redirect);
if (!empty($parsed['scheme']) || !empty($parsed['host'])) {
    $redirect = 'index.php';
}

$_SESSION['success'] = 'Language updated to ' . currentLanguageLabel() . '.';
redirect($redirect);

<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    $_SESSION['error'] = 'You must be signed in as an admin to update ordering mode.';
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}

$shouldAllowOutOfStock = isset($_POST['allow_out_of_stock']) && (string) $_POST['allow_out_of_stock'] === '1';
$adminId = $_SESSION['user_id'] ?? null;

if (updateOutOfStockOrderingToggle($shouldAllowOutOfStock, $adminId)) {
    $_SESSION['success'] = $shouldAllowOutOfStock
        ? 'Orders are allowed. Orders can proceed even if inventory is being updated.'
        : 'Orders are disallowed. Orders cannot proceed while the system is under maintenance for inventory updates.';
} else {
    $_SESSION['error'] = 'Unable to update the maintenance toggle right now. Please try again.';
}

$redirectTarget = $_POST['redirect'] ?? 'dashboard.php';
$redirectTarget = basename($redirectTarget);
if ($redirectTarget === '' || !preg_match('/^[A-Za-z0-9_-]+\.php$/', $redirectTarget)) {
    $redirectTarget = 'dashboard.php';
}

redirect($redirectTarget);

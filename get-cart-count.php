<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['count' => 0]);
    exit;
}

$cart_items = getCartItems($_SESSION['user_id']);
$count = count($cart_items);

echo json_encode(['count' => $count]);
?>
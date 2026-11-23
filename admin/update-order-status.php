<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
    $status = $_POST['status'] ?? '';
    $tracking_number = trim($_POST['tracking_number'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($description === '') {
        $descriptions = getOrderStatusDescriptions($tracking_number);
        $description = $descriptions[$status] ?? 'Order status updated';
    }

    if (updateOrderStatus($order_id, $status, $description, true, $tracking_number)) {
        $_SESSION['success'] = "Order #$order_id status updated to " . ucfirst($status) . "!";
    } else {
        $_SESSION['error'] = "Failed to update order status.";
    }
}

// Redirect back
$redirect_url = $_POST['redirect_to'] ?? 'orders.php';
redirect($redirect_url);
?>
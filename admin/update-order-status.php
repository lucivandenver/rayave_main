<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $tracking_number = $_POST['tracking_number'] ?? '';
    $description = $_POST['description'] ?? '';

    // Update order status
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, tracking_number = ? WHERE id = ?");
    if ($stmt->execute([$status, $tracking_number, $order_id])) {
        // Add tracking entry
        if (empty($description)) {
            $descriptions = [
                'pending' => 'Order placed successfully',
                'confirmed' => 'Order confirmed and payment verified',
                'processing' => 'Order is being processed and prepared for shipment',
                'shipped' => 'Order has been shipped with tracking: ' . $tracking_number,
                'delivered' => 'Order has been delivered successfully',
                'cancelled' => 'Order has been cancelled'
            ];
            $description = $descriptions[$status] ?? 'Order status updated';
        }

        updateOrderStatus($order_id, $status, $description);

        $_SESSION['success'] = "Order #$order_id status updated to " . ucfirst($status) . "!";
    } else {
        $_SESSION['error'] = "Failed to update order status.";
    }
}

// Redirect back
$redirect_url = $_POST['redirect_to'] ?? 'orders.php';
redirect($redirect_url);
?>
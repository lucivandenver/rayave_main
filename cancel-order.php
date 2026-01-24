<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('profile.php#orders');
}

$order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
$redirect_to = $_POST['redirect_to'] ?? 'profile.php#orders';

if ($order_id <= 0) {
    $_SESSION['error'] = 'Invalid order selection.';
    redirect('profile.php#orders');
}

if (stripos($redirect_to, 'http') === 0) {
    $redirect_to = 'profile.php#orders';
}

$stmt = $pdo->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['error'] = 'Order not found or access denied.';
    redirect($redirect_to);
}

if ($order['status'] !== 'pending') {
    $_SESSION['error'] = 'Only pending orders can be cancelled.';
    redirect($redirect_to);
}

try {
    $pdo->beginTransaction();

    $itemsStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $itemsStmt->execute([$order_id]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        // Use existing "return" reason to avoid enum issues in inventory_logs
        updateInventory($item['product_id'], $item['quantity'], 'return', "Customer cancelled order #$order_id");
    }

    if (!updateOrderStatus($order_id, 'cancelled', 'Order cancelled by customer', false)) {
        throw new RuntimeException('Failed to update order status.');
    }

    $pdo->commit();
    $_SESSION['success'] = "Order #$order_id was cancelled successfully.";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Order cancellation failed: ' . $e->getMessage());
    $_SESSION['error'] = 'Unable to cancel the order right now. Please try again or contact support.';
}

redirect($redirect_to);
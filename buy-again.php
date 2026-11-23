<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('my-orders.php');
}

$userId = $_SESSION['user_id'];
$orderId = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
$redirectTo = $_POST['redirect_to'] ?? 'my-orders.php';
$allowOutOfStockOrders = isOutOfStockOrderingEnabled();

$isAllowedRedirect = is_string($redirectTo) &&
    (preg_match('/^(my-orders|cart)\.php/i', $redirectTo) === 1 || $redirectTo === 'my-orders.php');
if (!$isAllowedRedirect || stripos($redirectTo, 'http') === 0) {
    $redirectTo = 'my-orders.php';
}

if ($orderId <= 0) {
    $_SESSION['error'] = 'Invalid order selected.';
    redirect($redirectTo);
}

$orderStmt = $pdo->prepare('SELECT id FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
$orderStmt->execute([$orderId, $userId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['error'] = 'Order not found.';
    redirect($redirectTo);
}

$orderItemId = isset($_POST['order_item_id']) ? (int) $_POST['order_item_id'] : 0;
$singleProductId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

if ($orderItemId > 0) {
    $itemsStmt = $pdo->prepare('SELECT oi.product_id, oi.quantity, p.name, p.availability_status, p.stock_quantity
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ? AND oi.id = ?');
    $itemsStmt->execute([$orderId, $orderItemId]);
} elseif ($singleProductId > 0) {
    $itemsStmt = $pdo->prepare('SELECT oi.product_id, oi.quantity, p.name, p.availability_status, p.stock_quantity
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ? AND oi.product_id = ?');
    $itemsStmt->execute([$orderId, $singleProductId]);
} else {
    $itemsStmt = $pdo->prepare('SELECT oi.product_id, oi.quantity, p.name, p.availability_status, p.stock_quantity
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?');
    $itemsStmt->execute([$orderId]);
}
$orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($orderItems)) {
    $_SESSION['error'] = 'The selected item is no longer available for reorder.';
    redirect($redirectTo);
}

$addedCount = 0;
$skippedLabels = [];

foreach ($orderItems as $item) {
    $productId = isset($item['product_id']) ? (int) $item['product_id'] : 0;
    $quantity = max(1, (int) ($item['quantity'] ?? 1));

    if ($productId <= 0) {
        continue;
    }

    if (!isset($item['name']) || $item['name'] === null) {
        $skippedLabels[] = 'Item #' . $productId . ' is no longer available';
        continue;
    }

    if (isset($item['availability_status']) && $item['availability_status'] === 'unavailable') {
        $skippedLabels[] = sprintf('%s is unavailable', $item['name']);
        continue;
    }

    if (!$allowOutOfStockOrders && isset($item['stock_quantity']) && (int) $item['stock_quantity'] <= 0) {
        $skippedLabels[] = sprintf('%s is out of stock', $item['name']);
        continue;
    }

    if (addToCart($userId, $productId, $quantity)) {
        $addedCount++;
    } else {
        $skippedLabels[] = sprintf('Could not add %s', $item['name'] ?? ('item #' . $productId));
    }
}

if ($addedCount > 0) {
    $_SESSION['success'] = $addedCount === count($orderItems)
        ? 'Items were added to your cart. You can checkout when ready.'
        : 'Available items were added to your cart. Review before checkout.';

    if (!empty($skippedLabels)) {
        $_SESSION['error'] = implode(' | ', array_slice($skippedLabels, 0, 3));
    }
} else {
    $_SESSION['error'] = !empty($skippedLabels)
        ? implode(' | ', array_slice($skippedLabels, 0, 3))
        : 'Unable to copy items from that order. Please try again later.';
}

redirect($redirectTo);

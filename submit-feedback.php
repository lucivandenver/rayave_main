<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('profile.php');
}

$order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
$order_item_id = isset($_POST['order_item_id']) ? (int) $_POST['order_item_id'] : 0;
$product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
$comment = trim($_POST['comment'] ?? '');

if ($order_id <= 0 || $order_item_id <= 0 || $product_id <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
    $_SESSION['error'] = 'Please complete the feedback form.';
    redirect('order-tracking.php?id=' . $order_id);
}

$stmt = $pdo->prepare("SELECT oi.*, o.user_id, o.status FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE oi.id = ? AND o.id = ?");
$stmt->execute([$order_item_id, $order_id]);
$order_item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order_item || (int) $order_item['user_id'] !== (int) $_SESSION['user_id']) {
    $_SESSION['error'] = 'You can only review items you ordered.';
    redirect('order-tracking.php?id=' . $order_id);
}

if ($order_item['status'] !== 'delivered') {
    $_SESSION['error'] = 'Feedback is available once your order is delivered.';
    redirect('order-tracking.php?id=' . $order_id);
}

if (saveCustomerFeedback($order_id, $order_item_id, $product_id, $_SESSION['user_id'], $rating, $comment)) {
    $_SESSION['success'] = 'Thanks for sharing your feedback! We will review it shortly.';
} else {
    $_SESSION['error'] = 'Unable to save your feedback. Please try again later.';
}

redirect('order-tracking.php?id=' . $order_id);
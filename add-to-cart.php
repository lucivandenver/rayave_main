<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    $_SESSION['error'] = "Please login to add items to cart";
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'] ?? 1;

    // Validate quantity
    if ($quantity < 1) {
        $_SESSION['error'] = "Quantity must be at least 1";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'products.php'));
        exit();
    }

    // Check if product exists and has stock
    $product = getProduct($product_id);
    if (!$product) {
        $_SESSION['error'] = "Product not found";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'products.php'));
        exit();
    }

    if (($product['availability_status'] ?? 'available') !== 'available') {
        $_SESSION['error'] = "This product is currently unavailable.";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'products.php'));
        exit();
    }

    if ($product['stock_quantity'] < $quantity || $product['stock_quantity'] <= 0) {
        $_SESSION['error'] = "Not enough stock available";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'products.php'));
        exit();
    }

    if (addToCart($_SESSION['user_id'], $product_id, $quantity)) {
        $_SESSION['success'] = "Product added to cart successfully!";
    } else {
        $_SESSION['error'] = "Failed to add product to cart";
    }
} else {
    $_SESSION['error'] = "Invalid request";
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'products.php'));
exit();
?>
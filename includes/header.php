<?php
// Start session and include config at the very top
if (!isset($_SESSION)) {
    session_start();
}
require_once 'config.php';

if (function_exists('autoCancelPendingOrders')) {
    autoCancelPendingOrders(AUTO_CANCEL_PENDING_MIN_HOURS, AUTO_CANCEL_PENDING_MAX_HOURS);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raymundo Avenue. - Flower Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
        <link rel="stylesheet" href="assets/css/rayave-chatbot.css">
    <?php endif; ?>
    <style>
        .site-logo {
            height: 40px;
            width: 40px;
            object-fit: cover;
            vertical-align: middle;
            border-radius: 50%;
            overflow: hidden;
            display: inline-block;
            background-color: transparent;
        }

        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/images/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
        }

        .product-card {
            transition: transform 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .category-img {
            height: 200px;
            object-fit: cover;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/images/products/FB_IMG_1762963948008_transparent.png" alt="RaymundoAvenue Logo"
                    class="site-logo me-2">
                <span class="d-none d-md-inline">Raymundo Avenue.</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <a class="nav-link" href="index.php">Home</a>
                    <a class="nav-link" href="products.php">Products</a>

                    <?php if (isLoggedIn()): ?>
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                            <?php
                            $cart_count = getCartItemCount($_SESSION['user_id']);
                            if ($cart_count > 0): ?>
                                <span class="badge bg-danger cart-count-badge">
                                    <?php echo $cart_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <?php if (!isAdmin()): ?>
                            <?php $unread_notifications = getUnreadNotificationCount($_SESSION['user_id']); ?>
                            <a class="nav-link position-relative" href="notifications.php">
                                <i class="fas fa-bell"></i> Notifications
                                <?php if ($unread_notifications > 0): ?>
                                    <span class="badge bg-warning text-dark ms-1">
                                        <?php echo $unread_notifications; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>
                        <a class="nav-link" href="profile.php">Profile</a>
                        <?php if (isAdmin()): ?>
                            <a class="nav-link" href="admin/">Admin</a>
                        <?php endif; ?>
                        <a class="nav-link" href="logout.php">Logout</a>
                    <?php else: ?>
                        <a class="nav-link" href="login.php">Login</a>
                        <a class="nav-link" href="register.php">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
<?php
// Start session and include config at the very top
if (!isset($_SESSION)) {
    session_start();
}
require_once 'config.php';
if (function_exists('ensureSecurityTables')) {
    ensureSecurityTables();
}

if (function_exists('autoCancelPendingOrders')) {
    autoCancelPendingOrders(AUTO_CANCEL_PENDING_MIN_HOURS, AUTO_CANCEL_PENDING_MAX_HOURS);
}

if (function_exists('runDailyBirthdayNotifications')) {
    runDailyBirthdayNotifications();
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getCurrentLanguage()); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="application-name" content="RayAve">
    <meta name="apple-mobile-web-app-title" content="RayAve">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#0f8f62">
    <title>Raymundo Avenue. - Flower Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/icons/rayave-icon-192.png">
    <link rel="apple-touch-icon" href="assets/icons/rayave-icon-192.png">
    <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
        <link rel="stylesheet" href="assets/css/rayave-chatbot.css">
    <?php endif; ?>
    <style>
        :root {
            --site-gutter: clamp(0.75rem, 2vw, 1.25rem);
        }

        html {
            -webkit-text-size-adjust: 100%;
        }

        body {
            overflow-x: hidden;
        }

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

        .navbar .navbar-collapse {
            flex-grow: 0;
        }

        .navbar .navbar-nav {
            flex-wrap: wrap;
        }

        .navbar .nav-link {
            white-space: nowrap;
        }

        .navbar .badge {
            vertical-align: middle;
        }

        .language-switch {
            display: inline-flex;
            align-items: center;
        }

        .language-switch .dropdown-toggle {
            min-height: 34px;
            padding: 0.3rem 0.85rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            font-size: 0.88rem;
            line-height: 1;
            box-shadow: none;
        }

        .language-switch .dropdown-toggle:hover,
        .language-switch .dropdown-toggle:focus {
            background: rgba(255, 255, 255, 0.16);
            color: #fff;
        }

        .language-switch .dropdown-menu {
            min-width: 12rem;
            max-width: min(92vw, 16rem);
            max-height: min(70vh, 28rem);
            border-radius: 0.9rem;
            padding: 0.35rem;
            box-shadow: 0 16px 35px rgba(15, 23, 42, 0.18);
            overflow-y: auto;
        }

        .language-switch .dropdown-item {
            border-radius: 0.65rem;
            padding: 0.55rem 0.75rem;
        }

        .language-search {
            position: sticky;
            top: 0;
            z-index: 2;
            padding: 0.25rem 0.25rem 0.4rem;
            background: #fff;
        }

        .language-search input {
            width: 100%;
            min-height: 36px;
            border-radius: 0.75rem;
            border: 1px solid #d1d5db;
            padding: 0.45rem 0.75rem;
            font-size: 0.95rem;
        }

        .language-switch .dropdown-item.active,
        .language-switch .dropdown-item:active {
            background: #198754;
            color: #fff;
        }

        @media (max-width: 991.98px) {
            .navbar .container {
                padding-inline: var(--site-gutter);
            }

            .navbar .navbar-collapse {
                margin-top: 0.75rem;
                padding: 0.75rem;
                background: rgba(0, 0, 0, 0.08);
                border-radius: 1rem;
            }

            .navbar .navbar-nav {
                gap: 0.25rem;
            }

            .navbar .nav-link {
                padding: 0.75rem 0.95rem;
                border-radius: 0.75rem;
            }
        }

        @media (max-width: 575.98px) {
            .navbar-brand {
                min-width: 0;
            }

            .site-logo {
                height: 36px;
                width: 36px;
            }

            .navbar .nav-link,
            .navbar .btn {
                width: 100%;
                justify-content: flex-start;
            }

            .navbar .navbar-nav {
                width: 100%;
            }
        }
    </style>
</head>

<body data-page="<?php echo basename($_SERVER['PHP_SELF']); ?>"
    data-user-role="<?php echo isLoggedIn() ? (isAdmin() ? 'admin' : 'customer') : 'guest'; ?>">
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a class="navbar-brand d-flex align-items-center mb-0" href="index.php">
                    <img src="assets/images/products/FB_IMG_1762963948008_transparent.png" alt="RaymundoAvenue Logo"
                        class="site-logo me-2">
                    <span class="d-none d-md-inline">Raymundo Avenue.</span>
                </a>

                <div class="dropdown language-switch">
                    <button class="dropdown-toggle" type="button" id="languageSwitchDropdown" data-bs-toggle="dropdown"
                        aria-expanded="false" aria-label="<?php echo htmlspecialchars(t('Language')); ?>">
                        <span class="d-none d-sm-inline"><?php echo htmlspecialchars(currentLanguageLabel()); ?></span>
                        <span class="d-sm-none"><?php echo htmlspecialchars(strtoupper(getCurrentLanguage())); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageSwitchDropdown">
                        <li class="language-search">
                            <input type="search" class="form-control language-search-input" placeholder="<?php echo htmlspecialchars(t('Search language')); ?>" autocomplete="off">
                        </li>
                        <?php foreach (getAvailableLanguages() as $code => $language): ?>
                            <li>
                                <form method="POST" action="set-language.php" class="mb-0">
                                    <input type="hidden" name="language" value="<?php echo htmlspecialchars($code); ?>">
                                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'index.php'); ?>">
                                    <button type="submit" class="dropdown-item language-option <?php echo getCurrentLanguage() === $code ? 'active' : ''; ?>" data-language-name="<?php echo htmlspecialchars(strtolower($language['native'])); ?>">
                                        <?php echo htmlspecialchars($language['native']); ?>
                                    </button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <a class="nav-link" href="index.php"><?php echo htmlspecialchars(t('Home')); ?></a>
                    <a class="nav-link" href="products.php"><?php echo htmlspecialchars(t('Products')); ?></a>

                    <?php if (isLoggedIn()): ?>
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> <?php echo htmlspecialchars(t('Cart')); ?>
                            <?php
                            $cart_count = getCartItemCount($_SESSION['user_id']);
                            if ($cart_count > 0): ?>
                                <span class="badge bg-danger cart-count-badge">
                                    <?php echo $cart_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <?php $unread_notifications = getUnreadNotificationCount($_SESSION['user_id']); ?>
                        <a class="nav-link position-relative" href="notifications.php"
                            data-customer-notification-link>
                            <i class="fas fa-bell"></i> <?php echo htmlspecialchars(t('Notifications')); ?>
                            <?php if ($unread_notifications > 0): ?>
                                <span class="badge bg-warning text-dark ms-1" data-notification-badge>
                                    <?php echo $unread_notifications; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link" href="<?php echo isAdmin() ? 'admin/orders.php' : 'my-orders.php'; ?>"><?php echo htmlspecialchars(t('Orders')); ?></a>
                        <a class="nav-link" href="profile.php"><?php echo htmlspecialchars(t('Profile')); ?></a>
                        <?php if (isAdmin()): ?>
                            <a class="nav-link" href="admin/"><?php echo htmlspecialchars(t('Admin')); ?></a>
                        <?php endif; ?>
                        <a class="nav-link" href="logout.php"><?php echo htmlspecialchars(t('Logout')); ?></a>
                    <?php else: ?>
                        <a class="nav-link" href="login.php"><?php echo htmlspecialchars(t('Login')); ?></a>
                        <a class="nav-link" href="register.php"><?php echo htmlspecialchars(t('Register')); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success d-none" data-modal-alert data-alert-title="<?php echo htmlspecialchars(t('Success')); ?>">
            <?php echo htmlspecialchars($_SESSION['success']); ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger d-none" data-modal-alert data-alert-title="<?php echo htmlspecialchars(t('Action Needed')); ?>">
            <?php echo htmlspecialchars($_SESSION['error']); ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <script>
        (function () {
            const searchInput = document.querySelector('.language-search-input');
            if (!searchInput) {
                return;
            }

            const options = Array.from(document.querySelectorAll('.language-option'));
            searchInput.addEventListener('input', function () {
                const term = this.value.trim().toLowerCase();
                options.forEach((option) => {
                    const name = (option.dataset.languageName || option.textContent || '').toLowerCase();
                    option.closest('li').style.display = !term || name.includes(term) ? '' : 'none';
                });
            });
        })();
    </script>
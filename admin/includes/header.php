<?php
// Admin header file
if (!isset($pdo)) {
    require_once '../../includes/config.php';
}

if (function_exists('autoCancelPendingOrders')) {
    autoCancelPendingOrders(AUTO_CANCEL_PENDING_MIN_HOURS, AUTO_CANCEL_PENDING_MAX_HOURS);
}

$adminUnreadNotifications = 0;
if (isset($_SESSION['user_id']) && isAdmin()) {
    $adminUnreadNotifications = getUnreadNotificationCount($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - RaymundoAvenue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --admin-nav-height: 72px;
            --admin-nav-bg: #0b1628;
            --admin-accent: #22c55e;
            --admin-accent-secondary: #14b8a6;
        }

        html,
        body {
            height: 100%;
            overflow-x: hidden;
        }

        body {
            background-color: #f8fafc;
            display: flex;
            flex-direction: column;
        }

        .admin-shell {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .admin-shell > .row {
            flex: 1 1 auto;
            min-height: 0;
        }

        .admin-shell main {
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .site-logo {
            height: 48px;
            width: 48px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 8px 20px rgba(20, 184, 166, 0.35);
            object-fit: cover;
            object-position: center;
            background: #fff;
        }

        .admin-navbar {
            background: var(--admin-nav-bg);
            background-image: linear-gradient(120deg, rgba(20, 184, 166, 0.12), rgba(34, 197, 94, 0.08));
            min-height: var(--admin-nav-height);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .admin-navbar .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.03rem;
        }

        .brand-stack {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }

        .brand-stack small {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.35rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .admin-navbar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            padding: 0.4rem 0.95rem;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .admin-navbar .nav-link:hover,
        .admin-navbar .nav-link:focus {
            color: #fff;
            background: rgba(255, 255, 255, 0.12);
        }

        .admin-navbar .nav-pill {
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding-inline: 1.1rem;
        }

        .admin-navbar .navbar-toggler {
            border: 1px solid rgba(255, 255, 255, 0.35);
        }

        .admin-navbar .navbar-toggler:focus {
            box-shadow: none;
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #111827 0%, #1f2937 50%, #0f172a 100%);
            color: #e2e8f0;
            padding: 1.25rem 0.85rem;
            border-right: 1px solid rgba(148, 163, 184, 0.15);
        }

        .sidebar .nav {
            gap: 0.3rem;
        }

        .sidebar .nav-link {
            color: #e2e8f0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.01em;
            padding: 0.65rem 0.85rem;
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
            position: relative;
        }

        .sidebar .nav-link .nav-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.08);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: inherit;
            transition: background 0.2s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link:focus {
            color: #f8fafc;
            background: rgba(15, 118, 110, 0.35);
            transform: translateX(4px);
        }

        .sidebar .nav-link:hover .nav-icon {
            background: rgba(248, 250, 252, 0.2);
        }

        .sidebar .nav-link.active {
            background: linear-gradient(90deg, #10b981, #14b8a6);
            box-shadow: 0 8px 16px rgba(20, 184, 166, 0.25);
            color: #0f172a;
        }

        .sidebar .nav-link.active .nav-icon {
            background: rgba(15, 23, 42, 0.12);
            color: #0f172a;
        }

        .sidebar .nav-link::after {
            content: '';
            position: absolute;
            inset: 6px;
            border-radius: 12px;
            border: 1px solid transparent;
            transition: border-color 0.2s ease;
        }

        .sidebar .nav-link:hover::after,
        .sidebar .nav-link.active::after {
            border-color: rgba(255, 255, 255, 0.2);
        }

        .navbar-brand {
            font-weight: bold;
        }

        .admin-shell > .row {
            min-height: calc(100vh - var(--admin-nav-height));
        }

        @media (min-width: 992px) {
            .sidebar {
                position: sticky;
                top: var(--admin-nav-height);
                height: calc(100vh - var(--admin-nav-height));
                overflow: hidden;
            }

            main {
                max-height: calc(100vh - var(--admin-nav-height));
                overflow-y: auto;
                padding-bottom: 2rem;
            }

            .admin-shell {
                padding-right: 0;
                padding-left: 0;
            }
        }

        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                z-index: 1040;
                width: 260px;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.45);
            }

            .sidebar.collapse.show {
                transform: translateX(0);
            }

            .sidebar.collapse:not(.show) {
                display: block;
            }

            .sidebar .nav-link {
                padding: 0.75rem 0.85rem;
            }

            main {
                margin-top: 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar sticky-top shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center gap-3" href="dashboard.php">
                <img src="../assets/images/products/FB_IMG_1762963948008_transparent.png" alt="Admin Logo" class="site-logo">
                <div class="brand-stack">
                    <span class="text-white">RayAve Admin</span>
                    <small>Operations</small>
                </div>
            </a>
            <div class="d-flex align-items-center gap-2">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminTopNav"
                    aria-controls="adminTopNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <button class="btn btn-outline-light d-lg-none" type="button" data-bs-toggle="collapse"
                    data-bs-target="#adminSidebar" aria-controls="adminSidebar" aria-expanded="false"
                    aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <div class="collapse navbar-collapse" id="adminTopNav">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <button class="nav-link nav-pill position-relative btn btn-link text-decoration-none" type="button"
                        data-bs-toggle="modal" data-bs-target="#adminNotificationsModal">
                        <i class="fas fa-bell"></i>
                        <span class="badge bg-warning text-dark ms-1 notification-badge <?php echo $adminUnreadNotifications > 0 ? '' : 'd-none'; ?>"
                            data-admin-unread-count>
                            <?php echo (int) $adminUnreadNotifications; ?>
                        </span>
                    </button>
                    <a class="nav-link" href="../index.php">
                        <i class="fas fa-external-link-alt"></i>
                        <span class="d-none d-lg-inline">View Shop</span>
                    </a>
                    <a class="nav-link" href="../profile.php">
                        <i class="fas fa-user"></i>
                        <span class="d-none d-lg-inline">Profile</span>
                    </a>
                    <a class="nav-link" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="d-none d-lg-inline">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Notifications Modal -->
    <div class="modal fade" id="adminNotificationsModal" tabindex="-1" aria-labelledby="adminNotificationsLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="adminNotificationsLabel">Notifications Center</h5>
                        <small class="text-muted">Alerts for customer messages, orders, and system updates.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-end flex-wrap gap-2 mb-3" id="notificationsMarkAllWrapper"
                        style="display: none;">
                        <form method="POST" action="notifications.php" class="mb-0" data-notification-form>
                            <input type="hidden" name="mark_all" value="1">
                            <button type="submit" class="btn btn-outline-secondary btn-sm">Mark all as read</button>
                        </form>
                        <form method="POST" action="notifications.php" class="mb-0" data-notification-form>
                            <input type="hidden" name="delete_all" value="1">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete all</button>
                        </form>
                    </div>
                    <div id="notificationsList">
                        <div class="text-center py-5 text-muted" id="notificationsLoading">
                            <div class="spinner-border text-success mb-3" role="status"></div>
                            <p class="mb-0">Loading notifications...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modalEl = document.getElementById('adminNotificationsModal');
            if (!modalEl) {
                return;
            }

            const listEl = document.getElementById('notificationsList');
            const markAllWrapper = document.getElementById('notificationsMarkAllWrapper');
            const badgeEl = document.querySelector('[data-admin-unread-count]');
            let notificationsLoaded = false;

            function updateBadge(count) {
                if (!badgeEl) {
                    return;
                }
                const parsed = parseInt(count, 10) || 0;
                if (parsed > 0) {
                    badgeEl.textContent = parsed;
                    badgeEl.classList.remove('d-none');
                } else {
                    badgeEl.textContent = '';
                    badgeEl.classList.add('d-none');
                }
            }

            function showLoading() {
                listEl.innerHTML = '<div class="text-center py-5 text-muted">' +
                    '<div class="spinner-border text-success mb-3" role="status"></div>' +
                    '<p class="mb-0">Loading notifications...</p>' +
                    '</div>';
            }

            function attachFormHandlers() {
                const forms = modalEl.querySelectorAll('form[data-notification-form]');
                forms.forEach((form) => {
                    if (form.dataset.bound === '1') {
                        return;
                    }
                    form.addEventListener('click', function (event) {
                        event.stopPropagation();
                    });
                    form.addEventListener('submit', function (event) {
                        event.preventDefault();
                        submitNotificationForm(form);
                    });
                    form.dataset.bound = '1';
                });
            }

            function bindNotificationRows(scope) {
                const root = scope || modalEl;
                const rows = root.querySelectorAll('[data-notification-link]');
                rows.forEach((row) => {
                    if (row.dataset.boundLink === '1') {
                        return;
                    }
                    const innerForms = row.querySelectorAll('form');
                    innerForms.forEach((innerForm) => {
                        if (innerForm.dataset.stopClick === '1') {
                            return;
                        }
                        innerForm.addEventListener('click', function (event) {
                            event.stopPropagation();
                        });
                        innerForm.dataset.stopClick = '1';
                    });
                    row.addEventListener('click', function () {
                        const destination = row.dataset.notificationLink;
                        if (destination) {
                            window.location.href = destination;
                        }
                    });
                    row.dataset.boundLink = '1';
                });
            }

            window.bindAdminNotificationRows = bindNotificationRows;

            function submitNotificationForm(form) {
                const formData = new FormData(form);
                formData.append('ajax', '1');

                fetch('notifications.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            updateBadge(data.unreadCount ?? 0);
                            loadNotifications(true);
                        } else if (data.message) {
                            alert(data.message);
                        }
                    })
                    .catch(() => {
                        alert('Unable to update notification right now.');
                    });
            }

            function loadNotifications(forceReload = false) {
                if (notificationsLoaded && !forceReload) {
                    return;
                }
                showLoading();
                fetch('notifications.php?modal=1', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then((response) => response.json())
                    .then((data) => {
                        listEl.innerHTML = data.html || '<div class="text-center text-muted py-5">No notifications found.</div>';
                        if (markAllWrapper) {
                            markAllWrapper.style.display = data.hasAny ? 'flex' : 'none';
                        }
                        updateBadge(data.unreadCount ?? 0);
                        attachFormHandlers();
                        bindNotificationRows();
                        notificationsLoaded = true;
                    })
                    .catch(() => {
                        listEl.innerHTML = '<div class="text-center text-danger py-5">Failed to load notifications.</div>';
                    });
            }

            modalEl.addEventListener('shown.bs.modal', function () {
                loadNotifications();
            });

            modalEl.addEventListener('hidden.bs.modal', function () {
                notificationsLoaded = false;
            });
        })();
    </script>

    <div class="container-fluid admin-shell">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse" id="adminSidebar">
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
                                href="dashboard.php">
                                <span class="nav-icon"><i class="fas fa-tachometer-alt"></i></span>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>"
                                href="products.php">
                                <span class="nav-icon"><i class="fas fa-boxes"></i></span>
                                <span>Products</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>"
                                href="inventory.php">
                                <span class="nav-icon"><i class="fas fa-warehouse"></i></span>
                                <span>Inventory</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>"
                                href="orders.php">
                                <span class="nav-icon"><i class="fas fa-shopping-cart"></i></span>
                                <span>Orders</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'coupons.php' ? 'active' : ''; ?>"
                                href="coupons.php">
                                <span class="nav-icon"><i class="fas fa-ticket-alt"></i></span>
                                <span>Coupons</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>"
                                href="customers.php">
                                <span class="nav-icon"><i class="fas fa-users"></i></span>
                                <span>Customers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['contact-messages.php', 'contact-message-view.php']) ? 'active' : ''; ?>"
                                href="contact-messages.php">
                                <span class="nav-icon"><i class="fas fa-headset"></i></span>
                                <span>Support Messages</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>"
                                href="feedback.php">
                                <span class="nav-icon"><i class="fas fa-comments"></i></span>
                                <span>Feedback</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>"
                                href="reports.php">
                                <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                                <span>Reports</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Alerts -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                        <?php echo $_SESSION['success']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                        <?php echo $_SESSION['error']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
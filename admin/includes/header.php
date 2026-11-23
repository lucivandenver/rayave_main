<?php
// Admin header file
if (!isset($pdo)) {
    require_once '../../includes/config.php';
}

if (function_exists('autoCancelPendingOrders')) {
    autoCancelPendingOrders(AUTO_CANCEL_PENDING_MIN_HOURS, AUTO_CANCEL_PENDING_MAX_HOURS);
}

if (function_exists('runDailyBirthdayNotifications')) {
    runDailyBirthdayNotifications();
}

$adminUnreadNotifications = 0;
if (isset($_SESSION['user_id']) && isAdmin()) {
    $adminUnreadNotifications = getUnreadNotificationCount($_SESSION['user_id']);
}

$currentAdminPage = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
$allowOutOfStockOrders = isOutOfStockOrderingEnabled();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getCurrentLanguage()); ?>">

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

            .admin-navbar .navbar-collapse {
                margin-top: 0.75rem;
                padding: 0.75rem;
                background: rgba(255, 255, 255, 0.06);
                border-radius: 1rem;
            }

            .admin-navbar .navbar-nav {
                gap: 0.35rem;
                width: 100%;
            }

            .admin-navbar .nav-link,
            .admin-navbar .btn,
            .admin-navbar .nav-pill {
                width: 100%;
                justify-content: flex-start;
            }
        }

        @media (max-width: 575.98px) {
            .admin-navbar .site-logo {
                height: 40px;
                width: 40px;
            }

            .brand-stack small {
                letter-spacing: 0.18rem;
            }

            .sidebar {
                width: min(88vw, 290px);
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark admin-navbar sticky-top shadow-sm">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a class="navbar-brand d-flex align-items-center gap-3 mb-0" href="dashboard.php">
                    <img src="../assets/images/products/FB_IMG_1762963948008_transparent.png" alt="Admin Logo" class="site-logo">
                    <div class="brand-stack">
                        <span class="text-white">RayAve Admin</span>
                        <small>Operations</small>
                    </div>
                </a>

                <div class="dropdown language-switch">
                    <button class="dropdown-toggle" type="button" id="adminLanguageSwitchDropdown" data-bs-toggle="dropdown"
                        aria-expanded="false" aria-label="<?php echo htmlspecialchars(t('Language')); ?>">
                        <span class="d-none d-sm-inline"><?php echo htmlspecialchars(currentLanguageLabel()); ?></span>
                        <span class="d-sm-none"><?php echo htmlspecialchars(strtoupper(getCurrentLanguage())); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminLanguageSwitchDropdown">
                        <li class="language-search">
                            <input type="search" class="form-control language-search-input" placeholder="<?php echo htmlspecialchars(t('Search language')); ?>" autocomplete="off">
                        </li>
                        <?php foreach (getAvailableLanguages() as $code => $language): ?>
                            <li>
                                <form method="POST" action="../set-language.php" class="mb-0">
                                    <input type="hidden" name="language" value="<?php echo htmlspecialchars($code); ?>">
                                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'dashboard.php'); ?>">
                                    <button type="submit" class="dropdown-item language-option <?php echo getCurrentLanguage() === $code ? 'active' : ''; ?>" data-language-name="<?php echo htmlspecialchars(strtolower($language['native'])); ?>">
                                        <?php echo htmlspecialchars($language['native']); ?>
                                    </button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
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
                    <form method="POST" action="toggle-ordering-mode.php" class="nav-link nav-pill text-white-50 p-2"
                        style="border: 1px solid rgba(255,255,255,0.25); border-radius: 999px;"
                        data-ordering-override-form>
                        <input type="hidden" name="allow_out_of_stock" value="<?php echo $allowOutOfStockOrders ? '1' : '0'; ?>">
                        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($currentAdminPage); ?>">
                        <div class="form-check form-switch text-white mb-0 small">
                            <input class="form-check-input" type="checkbox" role="switch"
                                id="allowOutOfStockSwitch"
                                <?php echo !$allowOutOfStockOrders ? 'checked' : ''; ?>
                                data-ordering-override-toggle>
                            <label class="form-check-label" for="allowOutOfStockSwitch">
                                <span class="d-none d-lg-inline"><?php echo $allowOutOfStockOrders ? 'Allow orders w/o stock' : 'Disallow Orders w/o stock'; ?></span>
                                <span class="d-lg-none"><?php echo $allowOutOfStockOrders ? 'Allow OOS' : 'Disallow OOS'; ?></span>
                            </label>
                        </div>
                        <div class="small fw-semibold mt-1 <?php echo $allowOutOfStockOrders ? 'text-success' : 'text-warning'; ?>">
                            <?php echo $allowOutOfStockOrders ? 'Maintenance OFF' : 'Maintenance ON'; ?>
                        </div>
                    </form>
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
                        <h5 class="modal-title" id="adminNotificationsLabel"><?php echo htmlspecialchars(t('Notifications Center')); ?></h5>
                        <small class="text-muted">Alerts for customer messages, orders, and system updates.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-end flex-wrap gap-2 mb-3" id="notificationsMarkAllWrapper"
                        style="display: none;">
                        <form method="POST" action="notifications.php" class="mb-0" data-notification-form>
                            <input type="hidden" name="mark_all" value="1">
                            <button type="submit" class="btn btn-outline-secondary btn-sm"><?php echo htmlspecialchars(t('Mark all as read')); ?></button>
                        </form>
                        <form method="POST" action="notifications.php" class="mb-0" data-notification-form>
                            <input type="hidden" name="delete_all" value="1">
                            <button type="submit" class="btn btn-outline-danger btn-sm"><?php echo htmlspecialchars(t('Delete all')); ?></button>
                        </form>
                    </div>
                    <div id="notificationsList">
                        <div class="text-center py-5 text-muted" id="notificationsLoading">
                            <div class="spinner-border text-success mb-3" role="status"></div>
                            <p class="mb-0"><?php echo htmlspecialchars(t('Loading notifications...')); ?></p>
                        </div>
                    </div>
                </div>
            </div>

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
        </div>
    </div>

    <script>
        (function () {
            const modalEl = document.getElementById('adminNotificationsModal');

            const orderingToggle = document.querySelector('[data-ordering-override-toggle]');
            if (orderingToggle) {
                orderingToggle.addEventListener('change', function () {
                    const form = orderingToggle.closest('[data-ordering-override-form]');
                    if (!form) {
                        return;
                    }
                    const hiddenField = form.querySelector('input[name="allow_out_of_stock"]');
                    if (hiddenField) {
                        // Inverted logic: checked = DISALLOW (0), unchecked = ALLOW (1)
                        hiddenField.value = orderingToggle.checked ? '0' : '1';
                    }
                    form.submit();
                });
            }

            if (!modalEl) {
                return;
            }

            const listEl = document.getElementById('notificationsList');
            const markAllWrapper = document.getElementById('notificationsMarkAllWrapper');
            const badgeEl = document.querySelector('[data-admin-unread-count]');
            let notificationsLoaded = false;
            let markOnViewPromise = null;
            const queueAlert = function (payload) {
                window.__queuedAlerts = window.__queuedAlerts || [];
                if (window.showGlobalAlert) {
                    window.showGlobalAlert(payload);
                } else {
                    window.__queuedAlerts.push(payload);
                }
            };

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
                            queueAlert({
                                type: 'danger',
                                title: 'Notification Update Failed',
                                message: data.message
                            });
                        }
                    })
                    .catch(() => {
                        queueAlert({
                            type: 'danger',
                            title: 'Notification Update Failed',
                            message: 'Unable to update notification right now.'
                        });
                    });
            }

            function loadNotifications(forceReload = false) {
                if (!forceReload && notificationsLoaded) {
                    return Promise.resolve();
                }

                if (forceReload) {
                    notificationsLoaded = false;
                }

                showLoading();
                return fetch('notifications.php?modal=1', {
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
                    .catch((error) => {
                        listEl.innerHTML = '<div class="text-center text-danger py-5">Failed to load notifications.</div>';
                        throw error;
                    });
            }

            function markNotificationsViewed() {
                const unreadCount = badgeEl ? parseInt(badgeEl.textContent, 10) || 0 : 0;
                if (unreadCount === 0 || markOnViewPromise) {
                    return Promise.resolve();
                }

                const formData = new FormData();
                formData.append('mark_all', '1');
                formData.append('ajax', '1');

                markOnViewPromise = fetch('notifications.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                    .then((response) => response.json())
                    .then((data) => {
                        updateBadge(data.unreadCount ?? 0);
                        if ((data.unreadCount ?? 0) === 0) {
                            return loadNotifications(true);
                        }
                        return null;
                    })
                    .catch(() => {
                        // no-op; admin can still use manual mark buttons
                    })
                    .finally(() => {
                        markOnViewPromise = null;
                    });

                return markOnViewPromise;
            }

            modalEl.addEventListener('shown.bs.modal', function () {
                loadNotifications()
                    .finally(() => {
                        markNotificationsViewed();
                    });
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
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'broadcast.php' ? 'active' : ''; ?>"
                                href="broadcast.php">
                                <span class="nav-icon"><i class="fas fa-bullhorn"></i></span>
                                <span>Announcements</span>
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
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'security.php' ? 'active' : ''; ?>"
                                href="security.php">
                                <span class="nav-icon"><i class="fas fa-shield-alt"></i></span>
                                <span>Security</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Alerts -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success d-none" data-modal-alert data-alert-title="Success">
                        <?php echo htmlspecialchars($_SESSION['success']); ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger d-none" data-modal-alert data-alert-title="Action Needed">
                        <?php echo htmlspecialchars($_SESSION['error']); ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

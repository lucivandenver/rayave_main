<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

function buildNotificationDestination(array $notification): array
{
    $link = null;
    $label = '';

    if (!empty($notification['related_id'])) {
        if ($notification['type'] === 'order') {
            $link = 'order-detail.php?id=' . (int) $notification['related_id'];
            $label = 'View order';
        } elseif ($notification['type'] === 'support') {
            $link = 'contact-message-view.php?id=' . (int) $notification['related_id'];
            $label = 'Open conversation';
        }
    }

    return [$link, $label];
}

function renderNotificationsHtml(array $notifications, bool $attachDataAttr = false): string
{
    ob_start();
    if (empty($notifications)) {
        ?>
        <div class="alert alert-light border text-center py-5">
            <i class="fas fa-bell-slash fa-2x text-muted mb-3"></i>
            <p class="mb-0 text-muted">No alerts yet. We'll notify you as soon as something needs attention.</p>
        </div>
        <?php
    } else {
        $formAttr = $attachDataAttr ? ' data-notification-form="1"' : '';
        ?>
        <div class="card border-0 shadow-sm">
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $notification): ?>
                    <?php [$targetLink, $targetLabel] = buildNotificationDestination($notification); ?>
                    <div class="list-group-item d-flex justify-content-between align-items-start gap-3 <?php echo $notification['is_read'] ? '' : 'bg-light'; ?> <?php echo $targetLink ? 'admin-notification-row' : ''; ?>"
                        <?php echo $targetLink ? 'data-notification-link="' . htmlspecialchars($targetLink) . '" role="button"' : ''; ?>>
                        <div class="me-3">
                            <h5 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h5>
                            <p class="mb-2 text-muted small"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                            <small class="text-muted">
                                <i class="far fa-clock me-1"></i>
                                <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?>
                            </small>
                        </div>
                        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 ms-auto">
                            <?php if ($targetLink): ?>
                                <a href="<?php echo $targetLink; ?>" class="btn btn-outline-success btn-sm"
                                    onclick="event.stopPropagation();">
                                    <?php echo $targetLabel ?: 'Open'; ?>
                                </a>
                            <?php endif; ?>
                            <?php if (!$notification['is_read']): ?>
                                <form method="POST" class="mb-0"<?php echo $formAttr; ?>>
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">Mark read</button>
                                </form>
                            <?php else: ?>
                                <span class="badge bg-secondary align-self-center">Read</span>
                            <?php endif; ?>
                            <form method="POST" class="mb-0"<?php echo $formAttr; ?>>
                                <input type="hidden" name="delete_notification_id" value="<?php echo $notification['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    return trim(ob_get_clean());
}

$userId = $_SESSION['user_id'];
$isAjaxRequest = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || isset($_POST['ajax']) || isset($_GET['ajax']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = false;
    $message = '';

    if (isset($_POST['delete_all'])) {
        $success = deleteAllNotifications($userId);
        $message = $success ? 'All notifications deleted.' : 'Unable to delete notifications right now.';
    } elseif (isset($_POST['delete_notification_id'])) {
        $notificationId = (int) $_POST['delete_notification_id'];
        $success = deleteNotification($notificationId, $userId);
        $message = $success ? 'Notification deleted.' : 'Failed to delete notification.';
    } elseif (isset($_POST['mark_all'])) {
        $success = markAllNotificationsRead($userId);
        $message = $success ? 'All notifications marked as read.' : 'Unable to mark notifications right now.';
    } elseif (isset($_POST['notification_id'])) {
        $notificationId = (int) $_POST['notification_id'];
        $success = markNotificationRead($notificationId, $userId);
        $message = $success ? 'Notification marked as read.' : 'Failed to update notification.';
    }

    if ($isAjaxRequest) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'unreadCount' => getUnreadNotificationCount($userId)
        ]);
        exit;
    }

    if ($success) {
        $_SESSION['success'] = $message;
    } elseif ($message) {
        $_SESSION['error'] = $message;
    }
    redirect('notifications.php');
}

$notifications = getUserNotifications($userId);

if (isset($_GET['modal'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'html' => renderNotificationsHtml($notifications, true),
        'hasAny' => !empty($notifications),
        'unreadCount' => getUnreadNotificationCount($userId)
    ]);
    exit;
}

include 'includes/header.php';
?>

<div class="py-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="mb-1">Notifications Center</h2>
            <p class="text-muted mb-0">Alerts for customer messages, orders, and system updates.</p>
        </div>
        <?php if (!empty($notifications)): ?>
            <div class="d-flex flex-wrap gap-2">
                <form method="POST" class="mb-0">
                    <button type="submit" name="mark_all" class="btn btn-outline-secondary btn-sm">
                        Mark all as read
                    </button>
                </form>
                <form method="POST" class="mb-0" onsubmit="return confirm('Delete all notifications?');">
                    <button type="submit" name="delete_all" class="btn btn-outline-danger btn-sm">
                        Delete all
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success'];
            unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php echo $_SESSION['error'];
            unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php echo renderNotificationsHtml($notifications); ?>
</div>

    <?php
    $pageScripts = <<<HTML
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.bindAdminNotificationRows) {
                window.bindAdminNotificationRows(document);
            }
        });
    </script>
    HTML;
    ?>

<?php include 'includes/footer.php'; ?>
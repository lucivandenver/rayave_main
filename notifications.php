<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (isAdmin()) {
    redirect('admin/notifications.php');
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_all'])) {
        if (deleteAllNotifications($userId)) {
            $_SESSION['success'] = 'All notifications deleted.';
        } else {
            $_SESSION['error'] = 'Unable to delete notifications right now.';
        }
        redirect('notifications.php');
    } elseif (isset($_POST['delete_notification_id'])) {
        $notificationId = (int) $_POST['delete_notification_id'];
        if (deleteNotification($notificationId, $userId)) {
            $_SESSION['success'] = 'Notification deleted.';
        } else {
            $_SESSION['error'] = 'Failed to delete notification.';
        }
        redirect('notifications.php');
    } elseif (isset($_POST['mark_all'])) {
        if (markAllNotificationsRead($userId)) {
            $_SESSION['success'] = 'All notifications marked as read.';
        } else {
            $_SESSION['error'] = 'Unable to mark notifications as read right now.';
        }
        redirect('notifications.php');
    } elseif (isset($_POST['notification_id'])) {
        $notificationId = (int) $_POST['notification_id'];
        if (markNotificationRead($notificationId, $userId)) {
            $_SESSION['success'] = 'Notification marked as read.';
        } else {
            $_SESSION['error'] = 'Failed to update notification.';
        }
        redirect('notifications.php');
    }
}

$notifications = getUserNotifications($userId);

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="mb-0">Notifications</h2>
            <p class="text-muted mb-0">Stay updated with your orders and system alerts.</p>
        </div>
        <?php if (!empty($notifications)): ?>
            <div class="d-flex flex-wrap gap-2">
                <form method="POST">
                    <button type="submit" name="mark_all" class="btn btn-outline-secondary btn-sm">
                        Mark All as Read
                    </button>
                </form>
                <form method="POST" onsubmit="return confirm('Delete all notifications?');">
                    <button type="submit" name="delete_all" class="btn btn-outline-danger btn-sm">
                        Delete All
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

    <?php if (empty($notifications)): ?>
        <div class="alert alert-light border text-center py-5">
            <i class="fas fa-bell-slash fa-2x text-muted mb-3"></i>
            <p class="mb-0 text-muted">You're all caught up! We'll keep you posted when something new happens.</p>
        </div>
    <?php else: ?>
        <div class="list-group shadow-sm">
            <?php foreach ($notifications as $notification): ?>
                <?php
                $ctaLink = null;
                $ctaLabel = '';

                if ($notification['type'] === 'order' && !empty($notification['related_id'])) {
                    $ctaLink = 'order-tracking.php?id=' . (int) $notification['related_id'];
                    $ctaLabel = 'Track Order';
                } elseif ($notification['type'] === 'support') {
                    $ctaLink = 'contact.php#conversationPanel';
                    $ctaLabel = 'Open Conversation';
                }
                ?>
                <div
                    class="list-group-item d-flex flex-wrap justify-content-between align-items-start gap-3 <?php echo $notification['is_read'] ? '' : 'bg-light'; ?>">
                    <div class="me-3">
                        <h5 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h5>
                        <p class="mb-2 text-muted small"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                        <small class="text-muted">
                            <i class="far fa-clock me-1"></i>
                            <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?>
                        </small>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                        <?php if ($ctaLink): ?>
                            <a href="<?php echo $ctaLink; ?>" class="btn btn-sm btn-outline-success">
                                <?php echo $ctaLabel; ?>
                            </a>
                        <?php endif; ?>
                        <?php if (!$notification['is_read']): ?>
                            <form method="POST">
                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-primary">Mark Read</button>
                            </form>
                        <?php else: ?>
                            <span class="badge bg-secondary align-self-center">Read</span>
                        <?php endif; ?>
                        <form method="POST" onsubmit="return confirm('Delete this notification?');">
                            <input type="hidden" name="delete_notification_id" value="<?php echo $notification['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
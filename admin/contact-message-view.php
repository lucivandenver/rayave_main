<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$threadId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$targetUserId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$subjectOverride = isset($_GET['subject']) ? trim($_GET['subject']) : '';
$newThreadCreated = false;

$thread = null;

if ($threadId > 0) {
    $thread = getContactThread($threadId);
}

if (!$thread && $targetUserId > 0) {
    $thread = getContactThreadByUser($targetUserId);

    if (!$thread) {
        $customer = getUserById($targetUserId);
        if (!$customer) {
            $_SESSION['error'] = 'Customer not found.';
            redirect('contact-messages.php');
        }

        $subjectLabel = $subjectOverride !== '' ? $subjectOverride : 'Support Assistance';
        $displayName = $customer['full_name'] ?: ($customer['username'] ?: 'Customer');

        $thread = createContactThread(
            $customer['id'],
            $displayName,
            $customer['email'],
            $subjectLabel
        );

        $newThreadCreated = (bool) $thread;
    }

    if ($thread) {
        $threadId = $thread['id'];
    }
}

if (!$thread) {
    $_SESSION['error'] = 'Conversation not found.';
    redirect('contact-messages.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reply = trim($_POST['reply_message'] ?? '');
    $status = $_POST['status'] ?? $thread['status'];

    if ($reply === '') {
        $_SESSION['error'] = 'Reply message cannot be empty.';
        redirect('contact-message-view.php?id=' . $threadId);
    }

    if (!addContactThreadMessage($threadId, 'admin', $_SESSION['user_id'], $reply)) {
        $_SESSION['error'] = 'Failed to send reply.';
        redirect('contact-message-view.php?id=' . $threadId);
    }

    if ($status !== $thread['status']) {
        updateContactThreadStatus($threadId, $status);
    }

    if (!empty($thread['user_id'])) {
        $title = 'New message from support';
        $body = 'Our admin replied to your inquiry: "' . substr($reply, 0, 80) . '"';
        createNotification($thread['user_id'], $title, $body, 'support', $threadId);
    }

    $_SESSION['success'] = 'Reply sent successfully.';
    redirect('contact-message-view.php?id=' . $threadId);
}

$messages = getContactThreadMessages($threadId);
markThreadMessagesRead($threadId, 'admin');

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mt-4 mb-3">
    <div>
        <h2 class="mb-1">Conversation with <?php echo htmlspecialchars($thread['name']); ?></h2>
        <p class="text-muted mb-0">Subject: <?php echo htmlspecialchars($thread['subject']); ?></p>
    </div>
    <a href="contact-messages.php" class="btn btn-outline-secondary btn-sm">Back to list</a>
</div>

<?php if ($newThreadCreated): ?>
    <div class="alert alert-info">New conversation created. Send your first message below.</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Conversation</h5>
            </div>
            <div class="card-body" style="max-height: 480px; overflow-y: auto; background:#f8fafc;">
                <?php if (empty($messages)): ?>
                    <p class="text-muted mb-0">No messages yet.</p>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div
                            class="d-flex <?php echo $msg['sender_type'] === 'admin' ? 'justify-content-end' : 'justify-content-start'; ?> mb-3">
                            <div class="p-3 rounded-3 shadow-sm <?php echo $msg['sender_type'] === 'admin' ? 'bg-success text-white' : 'bg-white'; ?>"
                                style="max-width: 75%;">
                                <div><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                <small class="d-block mt-2 opacity-75">
                                    <?php echo $msg['sender_type'] === 'admin' ? 'Admin' : htmlspecialchars($thread['name']); ?>
                                    ·
                                    <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="card-body border-top">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Reply</label>
                        <textarea name="reply_message" class="form-control" rows="4" placeholder="Write a response..."
                            required></textarea>
                    </div>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Thread Status</label>
                            <select name="status" class="form-select">
                                <option value="open" <?php echo $thread['status'] === 'open' ? 'selected' : ''; ?>>Open
                                    (waiting admin)</option>
                                <option value="pending" <?php echo $thread['status'] === 'pending' ? 'selected' : ''; ?>>
                                    Pending (waiting customer)</option>
                                <option value="closed" <?php echo $thread['status'] === 'closed' ? 'selected' : ''; ?>>
                                    Closed</option>
                            </select>
                        </div>
                        <div class="col-md-8 text-md-end">
                            <button type="submit" class="btn btn-success">Send Reply</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Customer Info</h5>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($thread['name']); ?></p>
                <p class="mb-2"><strong>Email:</strong> <a
                        href="mailto:<?php echo htmlspecialchars($thread['email']); ?>"><?php echo htmlspecialchars($thread['email']); ?></a>
                </p>
                <p class="mb-0"><strong>Status:</strong> <?php echo ucfirst($thread['status']); ?></p>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Quick actions</h5>
            </div>
            <div class="card-body">
                <a href="mailto:<?php echo htmlspecialchars($thread['email']); ?>"
                    class="btn btn-outline-secondary w-100 mb-2">
                    <i class="fas fa-envelope me-2"></i>Email Customer
                </a>
                <?php if (!empty($thread['user_id'])): ?>
                    <a href="customers.php?view=<?php echo $thread['user_id']; ?>" class="btn btn-outline-primary w-100">
                        <i class="fas fa-user me-2"></i>View Customer Profile
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
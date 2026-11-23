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
        $displayName = $customer['full_name'] ?: ($customer['email'] ?: 'Customer');

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
    $attachmentErrors = [];
    $adminAttachment = processSupportAttachmentUpload($_FILES['reply_attachment'] ?? null, $attachmentErrors);

    if (!empty($attachmentErrors)) {
        $_SESSION['error'] = implode(' ', $attachmentErrors);
        redirect('contact-message-view.php?id=' . $threadId);
    }

    if ($reply === '' && !$adminAttachment) {
        $_SESSION['error'] = 'Please enter a message or attach an image.';
        redirect('contact-message-view.php?id=' . $threadId);
    }

    if (!addContactThreadMessage($threadId, 'admin', $_SESSION['user_id'], $reply, $adminAttachment)) {
        $_SESSION['error'] = 'Failed to send reply.';
        redirect('contact-message-view.php?id=' . $threadId);
    }

    if (!empty($thread['user_id'])) {
        $title = 'New message from support';
        $replyPreview = $reply !== '' ? substr($reply, 0, 80) : '[Image attachment]';
        $body = 'Our admin replied to your inquiry: "' . $replyPreview . '"';
        createNotification($thread['user_id'], $title, $body, 'support', $threadId);
    }

    $_SESSION['success'] = 'Reply sent successfully.';
    redirect('contact-message-view.php?id=' . $threadId);
}

$messages = getContactThreadMessages($threadId);
markThreadMessagesRead($threadId, 'admin');
$emojiCodes = ['1F60A', '1F602', '1F60D', '1F389', '1F64F', '1F338', '1F33C', '1F44D', '1F622', '1F62E'];

include 'includes/header.php';
?>

<style>
    .chat-attachment img {
        max-height: 240px;
        border-radius: 12px;
        object-fit: cover;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
    }

    .emoji-picker {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 999px;
        padding: 0.2rem 0.6rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.2rem;
        background: #ffffff;
    }

    .emoji-picker button {
        background: transparent;
        border: none;
        font-size: 1.3rem;
        line-height: 1;
        cursor: pointer;
    }

    .message-composer {
        display: flex;
        align-items: stretch;
        gap: 0.75rem;
        border: 2px solid #22c55e;
        border-radius: 18px;
        padding: 0.75rem 1rem;
        background: #ffffff;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
    }

    .message-composer .composer-textarea {
        flex: 1;
        border: none;
        box-shadow: none;
        resize: none;
        padding: 0;
        font-size: 1rem;
        min-height: 110px;
    }

    .message-composer .composer-textarea:focus {
        box-shadow: none;
    }

    .composer-actions {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
    }

    .composer-icon-btn {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        border: 1px dashed rgba(34, 197, 94, 0.6);
        color: #16a34a;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        cursor: pointer;
        transition: all 0.2s ease;
        background: rgba(34, 197, 94, 0.08);
    }

    .composer-icon-btn:hover {
        background: rgba(34, 197, 94, 0.18);
    }

    .composer-send-btn {
        border: none;
        border-radius: 14px;
        padding: 0.6rem 1.2rem;
        background: linear-gradient(120deg, #22c55e, #16a34a);
        color: #fff;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        box-shadow: 0 12px 30px rgba(34, 197, 94, 0.35);
    }

    .composer-send-btn:hover {
        opacity: 0.9;
    }

    .visually-hidden-file {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        border: 0;
    }
</style>

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
            <div class="card-body" id="adminConversationPanel" style="max-height: 480px; overflow-y: auto; background:#f8fafc;">
                <?php if (empty($messages)): ?>
                    <p class="text-muted mb-0">No messages yet.</p>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div
                            class="d-flex <?php echo $msg['sender_type'] === 'admin' ? 'justify-content-end' : 'justify-content-start'; ?> mb-3">
                            <div class="p-3 rounded-3 shadow-sm <?php echo $msg['sender_type'] === 'admin' ? 'bg-success text-white' : 'bg-white'; ?>"
                                style="max-width: 75%;">
                                <?php if (trim($msg['message']) !== ''): ?>
                                    <div><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($msg['attachment_path'])): ?>
                                    <div class="chat-attachment mt-2">
                                        <a href="<?php echo htmlspecialchars($msg['attachment_path']); ?>" target="_blank" rel="noopener">
                                            <img src="<?php echo htmlspecialchars($msg['attachment_path']); ?>" alt="Conversation attachment" class="img-fluid">
                                        </a>
                                        <?php if (!empty($msg['attachment_name'])): ?>
                                            <small class="d-block mt-1 opacity-75">
                                                <?php echo htmlspecialchars($msg['attachment_name']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
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
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <label class="form-label mb-0" for="replyMessage">Reply</label>
                            <button type="button" class="btn btn-outline-success btn-sm" data-emoji-toggle="adminEmojiPicker"
                                data-emoji-target="#replyMessage">
                                &#x1F60A; Emoji
                            </button>
                        </div>
                        <div class="message-composer mt-2">
                            <textarea name="reply_message" class="form-control composer-textarea" rows="4"
                                placeholder="Write a response..." id="replyMessage"></textarea>
                            <div class="composer-actions">
                                <label class="composer-icon-btn mb-0" for="replyAttachment" title="Attach an image"
                                    aria-label="Attach an image">
                                    <span>&plus;</span>
                                </label>
                                <input type="file" name="reply_attachment" id="replyAttachment" accept="image/*"
                                    class="visually-hidden-file">
                                <button type="submit" class="composer-send-btn">
                                    <span class="d-none d-md-inline">Send</span>
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                        <div class="emoji-picker mt-2 d-none" id="adminEmojiPicker">
                            <?php foreach ($emojiCodes as $code): ?>
                                <button type="button" class="emoji-btn" data-emoji-code="<?php echo $code; ?>">
                                    <?php echo '&#x' . $code . ';'; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
                        <small class="text-muted mb-0">Customers see replies instantly in their conversation.</small>
                        <small class="text-muted mb-0">PNG, JPG, GIF, or WEBP up to 5MB.</small>
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

<script>
    function insertEmojiAtCursor(target, hexCode) {
        if (!target || !hexCode) {
            return;
        }

        const codePoint = parseInt(hexCode, 16);
        if (Number.isNaN(codePoint)) {
            return;
        }

        const emojiChar = String.fromCodePoint(codePoint);
        const start = typeof target.selectionStart === 'number' ? target.selectionStart : target.value.length;
        const end = typeof target.selectionEnd === 'number' ? target.selectionEnd : target.value.length;
        const before = target.value.substring(0, start);
        const after = target.value.substring(end);

        target.value = before + emojiChar + after;
        const cursor = start + emojiChar.length;
        target.setSelectionRange(cursor, cursor);
        target.focus();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const panel = document.getElementById('adminConversationPanel');
        if (panel) {
            panel.scrollTop = panel.scrollHeight;
        }

        document.querySelectorAll('[data-emoji-toggle]').forEach(function (toggle) {
            const pickerId = toggle.getAttribute('data-emoji-toggle');
            const targetSelector = toggle.getAttribute('data-emoji-target');
            const picker = document.getElementById(pickerId);
            const target = targetSelector ? document.querySelector(targetSelector) : null;

            toggle.addEventListener('click', function () {
                if (picker) {
                    picker.classList.toggle('d-none');
                }
            });

            if (picker) {
                picker.querySelectorAll('button[data-emoji-code]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        insertEmojiAtCursor(target, btn.getAttribute('data-emoji-code'));
                        picker.classList.add('d-none');
                    });
                });
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
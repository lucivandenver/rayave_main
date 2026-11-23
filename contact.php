<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$errors = [];
$successMessage = '';
$isLoggedIn = isLoggedIn();
$userId = $_SESSION['user_id'] ?? null;
$activeThread = null;
$threadMessages = [];
$customerProfile = null;

if ($isLoggedIn) {
    $stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $customerProfile = $stmt->fetch(PDO::FETCH_ASSOC);
    $activeThread = getContactThreadByUser($userId);
    if ($activeThread) {
        $threadMessages = getContactThreadMessages($activeThread['id']);
        markThreadMessagesRead($activeThread['id'], 'customer');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? 'contact';

    if ($formType === 'reply' && $isLoggedIn && $activeThread) {
        $replyMessage = trim($_POST['conversation_message'] ?? '');
        $attachmentErrors = [];
        $customerAttachment = processSupportAttachmentUpload($_FILES['conversation_attachment'] ?? null, $attachmentErrors);

        if (!empty($attachmentErrors)) {
            $errors = array_merge($errors, $attachmentErrors);
        }

        if ($replyMessage === '' && !$customerAttachment) {
            $errors[] = "Please enter a message or attach an image.";
        }

        if (empty($errors)) {
            if (addContactThreadMessage($activeThread['id'], 'customer', $userId, $replyMessage, $customerAttachment)) {
                $threadMessages = getContactThreadMessages($activeThread['id']);
                markThreadMessagesRead($activeThread['id'], 'customer');
                $successMessage = "Message sent! We'll notify you once the admin replies.";
            } else {
                $errors[] = "Unable to send your message right now. Please try again.";
            }
        }
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $messageBody = trim($_POST['message'] ?? '');

        if ($name === '' || $email === '' || $subject === '' || $messageBody === '') {
            $errors[] = "All fields are required.";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        }

        if (empty($errors)) {
            if ($isLoggedIn) {
                if (!$activeThread) {
                    $activeThread = createContactThread($userId, $name, $email, $subject);
                }

                if ($activeThread && addContactThreadMessage($activeThread['id'], 'customer', $userId, $messageBody)) {
                    $threadMessages = getContactThreadMessages($activeThread['id']);
                    markThreadMessagesRead($activeThread['id'], 'customer');
                    $successMessage = "Message sent! You can continue the conversation below.";
                } else {
                    $errors[] = "Unable to save your message right now. Please try again.";
                }
            } else {
                $_SESSION['success'] = "Thank you for your message! We'll get back to you within 24 hours.";
                redirect('contact.php');
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<?php
$defaultName = htmlspecialchars($_POST['name'] ?? ($customerProfile['full_name'] ?? ''));
$defaultEmail = htmlspecialchars($_POST['email'] ?? ($customerProfile['email'] ?? ''));
$defaultSubject = htmlspecialchars($_POST['subject'] ?? '');
$defaultMessage = htmlspecialchars($_POST['message'] ?? '');
$emojiCodes = ['1F60A', '1F602', '1F60D', '1F389', '1F64F', '1F338', '1F33C', '1F44D', '1F622', '1F62E'];
?>

<style>
    .chat-window {
        height: 420px;
        overflow-y: auto;
        background: #f8f9fa;
        border-radius: 12px;
        padding: 1rem;
        scrollbar-width: thin;
        scrollbar-color: rgba(15, 23, 42, 0.35) transparent;
    }

    .chat-window::-webkit-scrollbar {
        width: 6px;
    }

    .chat-window::-webkit-scrollbar-track {
        background: transparent;
    }

    .chat-window::-webkit-scrollbar-thumb {
        background: rgba(15, 23, 42, 0.25);
        border-radius: 999px;
    }

    .chat-bubble {
        border-radius: 16px;
        padding: 0.9rem 1.1rem;
        max-width: 75%;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
        margin-bottom: 0.85rem;
        position: relative;
    }

    .chat-bubble.me {
        background: linear-gradient(120deg, #22c55e, #16a34a);
        color: #fff;
        margin-left: auto;
    }

    .chat-bubble.them {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        color: #111827;
        margin-right: auto;
    }

    .chat-bubble small {
        display: block;
        opacity: 0.8;
        margin-top: 0.35rem;
        font-size: 0.75rem;
    }

    .chat-attachment img {
        max-height: 220px;
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
        min-height: 90px;
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

<div class="container py-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <h1 class="display-5 text-center mb-4">Contact Us</h1>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger d-none" data-modal-alert data-alert-title="Message Not Sent">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($successMessage): ?>
                <div class="alert alert-success d-none" data-modal-alert data-alert-title="Message Delivered">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-md-8">
                    <?php if ($isLoggedIn && $activeThread): ?>
                        <div class="card border-0 shadow-sm">
                            <div
                                class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">Your Conversation</h5>
                                    <small class="text-white-50">Subject:
                                        <?php echo htmlspecialchars($activeThread['subject']); ?></small>
                                </div>
                                <span class="badge bg-light text-success text-uppercase">Status:
                                    <?php echo strtoupper($activeThread['status']); ?></span>
                            </div>
                            <div class="card-body">
                                <div class="chat-window mb-3" id="conversationPanel">
                                    <?php if (empty($threadMessages)): ?>
                                        <p class="text-muted text-center mb-0">No messages yet. Start the conversation below.
                                        </p>
                                    <?php else: ?>
                                        <?php foreach ($threadMessages as $msg): ?>
                                            <?php $isMe = $msg['sender_type'] === 'customer'; ?>
                                            <div
                                                class="d-flex <?php echo $isMe ? 'justify-content-end' : 'justify-content-start'; ?>">
                                                <div class="chat-bubble <?php echo $isMe ? 'me' : 'them'; ?>">
                                                    <?php if (trim($msg['message']) !== ''): ?>
                                                        <div><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($msg['attachment_path'])): ?>
                                                        <div class="chat-attachment mt-2">
                                                            <a href="<?php echo htmlspecialchars($msg['attachment_path']); ?>"
                                                                target="_blank" rel="noopener">
                                                                <img src="<?php echo htmlspecialchars($msg['attachment_path']); ?>"
                                                                    alt="Conversation attachment" class="img-fluid">
                                                            </a>
                                                            <?php if (!empty($msg['attachment_name'])): ?>
                                                                <small class="d-block mt-1">
                                                                    <?php echo htmlspecialchars($msg['attachment_name']); ?>
                                                                </small>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <small><?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="form_type" value="reply">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <label class="form-label mb-0" for="conversationMessage">Type your message</label>
                                            <button type="button" class="btn btn-outline-success btn-sm"
                                                data-emoji-toggle="customerEmojiPicker"
                                                data-emoji-target="#conversationMessage">
                                                &#x1F60A; Emoji
                                            </button>
                                        </div>
                                        <div class="message-composer mt-2">
                                            <textarea name="conversation_message" class="form-control composer-textarea"
                                                rows="3" id="conversationMessage"
                                                placeholder="Write a reply..."></textarea>
                                            <div class="composer-actions">
                                                <label class="composer-icon-btn mb-0" for="conversationAttachment"
                                                    title="Attach an image" aria-label="Attach an image">
                                                    <span>&plus;</span>
                                                </label>
                                                <input type="file" name="conversation_attachment"
                                                    id="conversationAttachment" accept="image/*"
                                                    class="visually-hidden-file">
                                                <button type="submit" class="composer-send-btn">
                                                    <span class="d-none d-md-inline">Send</span>
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="emoji-picker mt-2 d-none" id="customerEmojiPicker">
                                            <?php foreach ($emojiCodes as $code): ?>
                                                <button type="button" class="emoji-btn" data-emoji-code="<?php echo $code; ?>">
                                                    <?php echo '&#x' . $code . ';'; ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
                                        <small class="text-muted mb-0">We'll notify you via the bell icon when the admin
                                            replies.</small>
                                        <small class="text-muted mb-0">PNG, JPG, GIF, or WEBP up to 5MB.</small>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Send us a Message</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!$isLoggedIn): ?>
                                    <div class="alert alert-info">
                                        Create an account or log in to keep a conversation history with our team.
                                    </div>
                                <?php endif; ?>
                                <form method="POST">
                                    <input type="hidden" name="form_type" value="contact">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Your Name</label>
                                            <input type="text" name="name" class="form-control" required
                                                value="<?php echo $defaultName; ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" name="email" class="form-control" required
                                                value="<?php echo $defaultEmail; ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Subject</label>
                                        <input type="text" name="subject" class="form-control" required
                                            value="<?php echo $defaultSubject; ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Message</label>
                                        <textarea name="message" class="form-control" rows="5"
                                            required><?php echo $defaultMessage; ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success">Send Message</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Visit Us</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong><i class="fas fa-map-marker-alt text-success me-2"></i>Address</strong>
                                <p class="mb-0">Bagumbayan, Roxas<br>Oriental Mindoro, 5212</p>
                            </div>
                            <div class="mb-3">
                                <strong><i class="fas fa-phone text-success me-2"></i>Phone</strong>
                                <p class="mb-0">0906 592 9943</p>
                            </div>
                            <div class="mb-3">
                                <strong><i class="fas fa-envelope text-success me-2"></i>Email</strong>
                                <p class="mb-0">raymundoavenue893@gmail.com</p>
                            </div>
                            <div class="mb-3">
                                <strong><i class="fas fa-clock text-success me-2"></i>Hours</strong>
                                <p class="mb-0">
                                    Mon-Fri: 9AM-7PM<br>
                                    Sat: 10AM-6PM<br>
                                    Sun: 11AM-5PM
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($isLoggedIn && $activeThread): ?>
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
            const panel = document.getElementById('conversationPanel');
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
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
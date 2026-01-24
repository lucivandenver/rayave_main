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
        if ($replyMessage === '') {
            $errors[] = "Message cannot be empty.";
        }

        if (empty($errors)) {
            if (addContactThreadMessage($activeThread['id'], 'customer', $userId, $replyMessage)) {
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
</style>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <h1 class="display-5 text-center mb-4">Contact Us</h1>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($successMessage): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success'];
                    unset($_SESSION['success']); ?>
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
                                                    <div><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                                    <small><?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="form_type" value="reply">
                                    <div class="mb-3">
                                        <label class="form-label">Type your message</label>
                                        <textarea name="conversation_message" class="form-control" rows="3"
                                            placeholder="Write a reply..." required></textarea>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">We'll notify you via the bell icon when the admin
                                            replies.</small>
                                        <button type="submit" class="btn btn-success">Send Message</button>
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
                                <p class="mb-0">(+63) 974-7385-789</p>
                            </div>
                            <div class="mb-3">
                                <strong><i class="fas fa-envelope text-success me-2"></i>Email</strong>
                                <p class="mb-0">raymundoavenue@gmail.com</p>
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
        document.addEventListener('DOMContentLoaded', function () {
            const panel = document.getElementById('conversationPanel');
            if (panel) {
                panel.scrollTop = panel.scrollHeight;
            }
        });
    </script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
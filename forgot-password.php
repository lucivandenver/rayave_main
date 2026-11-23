<?php
require_once 'includes/config.php';
ensureUserSecurityColumns();

$feedback = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailInput = trim($_POST['email'] ?? '');
    $email = filter_var($emailInput, FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id, email, full_name, username FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $feedback = 'If that email address is registered, you will receive password reset instructions shortly.';

        if ($user) {
            try {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiresAt = date('Y-m-d H:i:s', time() + (int) PASSWORD_RESET_EXPIRY_MINUTES * 60);

                $update = $pdo->prepare('UPDATE users SET password_reset_token = ?, password_reset_expires_at = ? WHERE id = ?');
                $update->execute([$tokenHash, $expiresAt, $user['id']]);

                $mailResult = sendPasswordResetEmail($user, $token);
                if (!$mailResult['success']) {
                    $errors[] = 'We could not send the reset email. Please contact support: ' . $mailResult['message'];
                    $feedback = null;
                }
            } catch (Exception $e) {
                error_log('Password reset generation failed: ' . $e->getMessage());
                $errors[] = 'We could not process your request right now. Please try again later.';
                $feedback = null;
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Forgot Password</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger d-none" data-modal-alert data-alert-title="Reset Request Error">
                            <?php foreach ($errors as $error): ?>
                                <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($feedback): ?>
                        <div class="alert alert-success d-none" data-modal-alert data-alert-title="Check Your Email">
                            <?php echo htmlspecialchars($feedback); ?>
                        </div>
                    <?php endif; ?>

                    <p class="text-muted">Enter the email associated with your account and we'll email you a secure link to reset your password.</p>

                    <form method="POST" class="mt-3">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <button type="submit" class="btn btn-success w-100">Send reset link</button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="login.php">Back to login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<?php
require_once 'includes/config.php';
ensureUserSecurityColumns();

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($token === '') {
        $errors[] = 'Reset link is missing or has expired.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'New password must be at least 6 characters.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $tokenHash = hash('sha256', $token);
        $stmt = $pdo->prepare('SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expires_at >= NOW() LIMIT 1');
        $stmt->execute([$tokenHash]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $errors[] = 'This reset link is invalid or has already been used.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare('UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires_at = NULL WHERE id = ?');
            if ($update->execute([$hashedPassword, $user['id']])) {
                $_SESSION['success'] = 'Password updated successfully. You can now log in with your new password.';
                redirect('login.php');
            } else {
                $errors[] = 'Unable to update your password. Please try again.';
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
                    <h4 class="mb-0">Reset Password</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger d-none" data-modal-alert data-alert-title="Reset Failed">
                            <?php foreach ($errors as $error): ?>
                                <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($token === ''): ?>
                        <div class="alert alert-warning d-none" data-modal-alert data-alert-title="Reset Link Invalid">This reset link is invalid. Please request a new one.</div>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Update Password</button>
                        </form>
                    <?php endif; ?>

                    <div class="text-center mt-3">
                        <a href="login.php">Return to login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

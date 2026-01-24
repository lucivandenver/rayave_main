<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $errors = [];

    if (empty($username) || empty($password)) {
        $errors[] = "Please enter both username and password";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['success'] = "Welcome back, " . $user['username'] . "!";

            // Redirect to intended page or home
            $redirect = isset($_SESSION['redirect_to']) ? $_SESSION['redirect_to'] : 'index.php';
            unset($_SESSION['redirect_to']);
            redirect($redirect);
        } else {
            $errors[] = "Invalid username or password";
        }
    }
}

// Store redirect URL if coming from protected page, but ignore auth screens
if (!isLoggedIn() && isset($_SERVER['HTTP_REFERER'])) {
    $refererParts = parse_url($_SERVER['HTTP_REFERER']);
    $refererPath = $refererParts['path'] ?? '';
    $refererHost = $refererParts['host'] ?? '';
    $ignoredPages = ['login.php', 'register.php'];

    if ($refererPath && (empty($refererHost) || $refererHost === $_SERVER['HTTP_HOST'])) {
        $refererPage = basename($refererPath);
        if (!in_array($refererPage, $ignoredPages, true)) {
            $_SESSION['redirect_to'] = $refererPath;
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Login</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <p class="mb-0"><?php echo $error; ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username or Email</label>
                            <input type="text" class="form-control" id="username" name="username"
                                value="<?php echo $_POST['username'] ?? ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Login</button>
                    </form>

                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="register.php">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
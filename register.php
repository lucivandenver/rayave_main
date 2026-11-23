<?php
require_once 'includes/config.php';
ensureUserSecurityColumns();

if (isLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $birthday = trim((string) ($_POST['birthday'] ?? ''));
    $terms_of_service = array_key_exists('terms_of_service', $_POST) ? 1 : 0;

    $errors = [];

    if (empty($full_name) || empty($email) || empty($password) || empty($birthday)) {
        $errors[] = "All fields are required";
    }

    if (!$terms_of_service) {
        $errors[] = "You must agree to the Terms of Service to register";
    }

    if (!empty($birthday)) {
        $birthdayDate = DateTime::createFromFormat('Y-m-d', $birthday);
        $isBirthdayValid = $birthdayDate && $birthdayDate->format('Y-m-d') === $birthday;

        if (!$isBirthdayValid) {
            $errors[] = "Please provide a valid birthday.";
        } elseif ($birthday > date('Y-m-d')) {
            $errors[] = "Birthday cannot be in the future.";
        }
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "Email already exists";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Generate a username automatically
        $usernameSeed = $full_name ?: explode('@', $email)[0];
        $username = generateUniqueUsername($usernameSeed);

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, birthday, role) VALUES (?, ?, ?, ?, ?, 'customer')");
        if ($stmt->execute([$username, $email, $hashed_password, $full_name, $birthday])) {
            $_SESSION['success'] = "Registration successful! Please login.";
            redirect('login.php');
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}

$termsChecked = $_SERVER['REQUEST_METHOD'] === 'POST' ? array_key_exists('terms_of_service', $_POST) : false;
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Register</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger d-none" data-modal-alert data-alert-title="Registration Error">
                            <?php foreach ($errors as $error): ?>
                                <p class="mb-0"><?php echo $error; ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                value="<?php echo $_POST['full_name'] ?? ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="birthday" class="form-label">Birthday</label>
                            <input type="date" class="form-control" id="birthday" name="birthday"
                                max="<?php echo date('Y-m-d'); ?>"
                                value="<?php echo $_POST['birthday'] ?? ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?php echo $_POST['email'] ?? ''; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                required>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="terms_of_service" name="terms_of_service" <?php echo $termsChecked ? 'checked' : ''; ?> required>
                            <label class="form-check-label" for="terms_of_service">
                                    I have read and agree to the <span style="color: #0f8f62; text-decoration: underline;">Terms of Service</span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-success w-100 mb-3">Register</button>
                    </form>

                    <?php if (!empty(GOOGLE_CLIENT_ID) && !empty(GOOGLE_CLIENT_SECRET)): ?>
                        <!-- Google Sign-Up Button -->
                        <div class="mb-3">
                            <a href="google-auth.php" class="btn w-100 d-flex align-items-center justify-content-center gap-2" style="background-color: #ffffff; color: #333; border: 1px solid #ddd; padding: 10px;">
                                <img src="https://www.google.com/favicon.ico" alt="Google" style="width: 20px; height: 20px;">
                                <span>Sign up with Google</span>
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
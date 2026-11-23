<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
ensureUserSecurityColumns();
ensureRewardsSystem();

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user addresses
$addresses = getUserAddresses($user_id);
$rewardPoints = getUserRewardPoints($user_id);
$qualifyingOrders = getUserQualifyingOrderCount($user_id);
$currentRewardTier = getCurrentRewardTierForOrders($qualifyingOrders);
$nextRewardTier = getNextRewardTierForOrders($qualifyingOrders);
$ordersProgressPercent = 100;
if ($nextRewardTier) {
    $required = max(1, (int) $nextRewardTier['required_orders']);
    $ordersProgressPercent = min(100, ($qualifyingOrders / $required) * 100);
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $birthday = trim((string) ($_POST['birthday'] ?? ''));

    if ($birthday !== '') {
        $birthdayDate = DateTime::createFromFormat('Y-m-d', $birthday);
        $isBirthdayValid = $birthdayDate && $birthdayDate->format('Y-m-d') === $birthday;

        if (!$isBirthdayValid) {
            $_SESSION['error'] = "Please provide a valid birthday.";
            redirect('profile.php#profile');
        }

        if ($birthday > date('Y-m-d')) {
            $_SESSION['error'] = "Birthday cannot be in the future.";
            redirect('profile.php#profile');
        }
    } else {
        $birthday = null;
    }

    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, birthday = ? WHERE id = ?");
    if ($stmt->execute([$full_name, $email, $phone, $birthday, $user_id])) {
        $_SESSION['success'] = "Profile updated successfully!";
        redirect('profile.php');
    } else {
        $_SESSION['error'] = "Failed to update profile.";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!password_verify($current_password, $user['password'])) {
        $_SESSION['error'] = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $_SESSION['error'] = "Password must be at least 6 characters long.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed_password, $user_id])) {
            $_SESSION['success'] = "Password changed successfully!";
            redirect('profile.php');
        } else {
            $_SESSION['error'] = "Failed to change password.";
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">My Account</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="tab">
                        <i class="fas fa-user me-2"></i>Profile
                    </a>
                    <a href="#addresses" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                        <i class="fas fa-map-marker-alt me-2"></i>Addresses
                    </a>
                    <a href="#password" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                        <i class="fas fa-lock me-2"></i>Change Password
                    </a>
                    <a href="rewards.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-gift me-2"></i>Rewards
                    </a>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="col-lg-9">
            <div class="tab-content">
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="full_name" class="form-control"
                                            value="<?php echo $user['full_name'] ?? ''; ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control"
                                            value="<?php echo $user['email']; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control"
                                            value="<?php echo $user['phone'] ?? ''; ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Birthday</label>
                                        <input type="date" name="birthday" class="form-control"
                                            max="<?php echo date('Y-m-d'); ?>"
                                            value="<?php echo htmlspecialchars($user['birthday'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Member Since</label>
                                    <input type="text" class="form-control"
                                        value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" readonly>
                                </div>

                                <div class="border rounded p-3 mb-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                        <h6 class="mb-0 text-success"><i class="fas fa-gift me-2"></i>Rewards Snapshot</h6>
                                        <a href="rewards.php" class="btn btn-sm btn-outline-success">View Rewards</a>
                                    </div>
                                    <div class="small text-muted mb-2">
                                        Points: <strong><?php echo (int) $rewardPoints; ?></strong> • Qualifying orders: <strong><?php echo (int) $qualifyingOrders; ?></strong>
                                    </div>
                                    <?php if ($currentRewardTier): ?>
                                        <div class="small mb-2">Current unlocked tier: <strong><?php echo number_format((float) $currentRewardTier['discount_percent'], 2); ?>% off</strong> (<?php echo (int) $currentRewardTier['required_orders']; ?>+ orders)</div>
                                    <?php else: ?>
                                        <div class="small mb-2">No unlocked reward tier yet.</div>
                                    <?php endif; ?>

                                    <?php if ($nextRewardTier): ?>
                                        <div class="small text-muted mb-1">
                                            Next tier at <?php echo (int) $nextRewardTier['required_orders']; ?> orders (<?php echo number_format((float) $nextRewardTier['discount_percent'], 2); ?>% off)
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-success" role="progressbar"
                                                style="width: <?php echo number_format((float) $ordersProgressPercent, 2, '.', ''); ?>%;"
                                                aria-valuenow="<?php echo (int) round($ordersProgressPercent); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="small text-success">You already unlocked the highest available tier.</div>
                                    <?php endif; ?>
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-success">Update
                                    Profile</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Addresses Tab -->
                <div class="tab-pane fade" id="addresses">
                    <div class="card">
                        <div
                            class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">My Addresses</h5>
                            <button class="btn btn-light btn-sm" data-bs-toggle="modal"
                                data-bs-target="#addAddressModal">
                                <i class="fas fa-plus"></i> Add New Address
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (empty($addresses)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                                    <h5>No Addresses Saved</h5>
                                    <p class="text-muted">Add your first address to make checkout easier.</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($addresses as $address): ?>
                                        <div class="col-md-6 mb-3">
                                            <div
                                                class="card address-card h-100 <?php echo $address['is_default'] ? 'border-success' : ''; ?>">
                                                <div class="card-body">
                                                    <?php if ($address['is_default']): ?>
                                                        <span class="badge bg-success mb-2">Default</span>
                                                    <?php endif; ?>
                                                    <h6 class="card-title text-capitalize">
                                                        <?php echo $address['address_type']; ?> Address
                                                    </h6>
                                                    <p class="card-text">
                                                        <?php echo $address['street_address']; ?><br>
                                                        <?php echo $address['city']; ?>, <?php echo $address['state']; ?>
                                                        <?php echo $address['zip_code']; ?><br>
                                                        <?php echo $address['country']; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Password Tab -->
                <div class="tab-pane fade" id="password">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-success">Change
                                    Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="checkout.php">
                <div class="modal-body">
                    <input type="hidden" name="add_address" value="1">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Address Type</label>
                            <select name="address_type" class="form-select" required>
                                <option value="home">Home</option>
                                <option value="work">Work</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="is_default" id="is_default">
                                <label class="form-check-label" for="is_default">
                                    Set as default address
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Street Address</label>
                        <input type="text" name="street_address" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">State</label>
                            <input type="text" name="state" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ZIP Code</label>
                            <input type="text" name="zip_code" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" value="USA" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Add Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Tab functionality
    document.addEventListener('DOMContentLoaded', function () {
        // Activate tab based on URL hash
        const urlHash = window.location.hash;
        if (urlHash) {
            const tab = document.querySelector(`a[href="${urlHash}"]`);
            if (tab) {
                const tabInstance = new bootstrap.Tab(tab);
                tabInstance.show();
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
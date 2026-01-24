<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user addresses
$addresses = getUserAddresses($user_id);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
    if ($stmt->execute([$full_name, $email, $phone, $user_id])) {
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
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
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
                    <a href="#orders" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                        <i class="fas fa-shopping-bag me-2"></i>My Orders
                    </a>
                    <a href="#addresses" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                        <i class="fas fa-map-marker-alt me-2"></i>Addresses
                    </a>
                    <a href="#password" class="list-group-item list-group-item-action" data-bs-toggle="tab">
                        <i class="fas fa-lock me-2"></i>Change Password
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
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control" value="<?php echo $user['username']; ?>"
                                            readonly>
                                        <small class="form-text text-muted">Username cannot be changed</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
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
                                <div class="mb-3">
                                    <label class="form-label">Member Since</label>
                                    <input type="text" class="form-control"
                                        value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" readonly>
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-success">Update
                                    Profile</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Orders Tab -->
                <div class="tab-pane fade" id="orders">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">My Orders</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($orders)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                    <h5>No Orders Yet</h5>
                                    <p class="text-muted">You haven't placed any orders yet.</p>
                                    <a href="products.php" class="btn btn-success">Start Shopping</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Order #</th>
                                                <th>Date</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td>#<?php echo $order['id']; ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php
                                                        echo $order['status'] === 'delivered' ? 'success' :
                                                            ($order['status'] === 'cancelled' ? 'danger' :
                                                                ($order['status'] === 'pending' ? 'warning' : 'info'));
                                                        ?>">
                                                            <?php echo ucfirst($order['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column flex-md-row gap-2">
                                                            <a href="order-tracking.php?id=<?php echo $order['id']; ?>"
                                                                class="btn btn-sm btn-outline-primary">View Details</a>
                                                            <?php if ($order['status'] === 'pending'): ?>
                                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                                    data-bs-toggle="modal" data-bs-target="#cancelOrderModal"
                                                                    data-order-id="<?php echo $order['id']; ?>"
                                                                    data-redirect="profile.php#orders">
                                                                    Cancel
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
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

<!-- Cancel Order Modal -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="cancel-order.php" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelOrderLabel">Cancel Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Cancelling will immediately stop processing and return stock to inventory. Are you sure?
                </p>
            </div>
            <div class="modal-footer">
                <input type="hidden" name="order_id" id="cancelOrderId">
                <input type="hidden" name="redirect_to" id="cancelRedirect">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Order</button>
                <button type="submit" class="btn btn-danger">Cancel Order</button>
            </div>
        </form>
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

        const cancelModal = document.getElementById('cancelOrderModal');
        if (cancelModal) {
            cancelModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                document.getElementById('cancelOrderId').value = button.getAttribute('data-order-id');
                document.getElementById('cancelRedirect').value = button.getAttribute('data-redirect');
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
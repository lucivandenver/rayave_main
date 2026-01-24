<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$customer_id = $_GET['id'] ?? null;

if (!$customer_id) {
    redirect('customers.php');
}

// Get customer details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    redirect('customers.php');
}

// Get customer orders
$orders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$orders->execute([$customer_id]);
$customer_orders = $orders->fetchAll(PDO::FETCH_ASSOC);

// Get customer addresses
$addresses = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC");
$addresses->execute([$customer_id]);
$customer_addresses = $addresses->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Customer Details</h2>
        <a href="customers.php" class="btn btn-secondary">Back to Customers</a>
    </div>

    <div class="row">
        <!-- Customer Information -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center"
                            style="width: 80px; height: 80px;">
                            <i class="fas fa-user fa-2x text-white"></i>
                        </div>
                        <h4 class="mt-3"><?php echo $customer['full_name'] ?: $customer['username']; ?></h4>
                        <p class="text-muted">@<?php echo $customer['username']; ?></p>
                    </div>

                    <div class="customer-info">
                        <div class="mb-3">
                            <strong>Email:</strong><br>
                            <a href="mailto:<?php echo $customer['email']; ?>"><?php echo $customer['email']; ?></a>
                        </div>

                        <?php if ($customer['phone']): ?>
                            <div class="mb-3">
                                <strong>Phone:</strong><br>
                                <a href="tel:<?php echo $customer['phone']; ?>"><?php echo $customer['phone']; ?></a>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <strong>Member Since:</strong><br>
                            <?php echo date('F j, Y', strtotime($customer['created_at'])); ?>
                        </div>

                        <div class="mb-3">
                            <strong>Total Orders:</strong><br>
                            <span class="badge bg-primary"><?php echo count($customer_orders); ?> orders</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Addresses -->
            <?php if (!empty($customer_addresses)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Saved Addresses</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($customer_addresses as $address): ?>
                            <div class="mb-3 p-3 border rounded <?php echo $address['is_default'] ? 'border-success' : ''; ?>">
                                <?php if ($address['is_default']): ?>
                                    <span class="badge bg-success mb-2">Default</span>
                                <?php endif; ?>
                                <strong class="text-capitalize"><?php echo $address['address_type']; ?> Address</strong><br>
                                <?php echo $address['street_address']; ?><br>
                                <?php echo $address['city']; ?>, <?php echo $address['state']; ?>
                                <?php echo $address['zip_code']; ?><br>
                                <?php echo $address['country']; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Customer Orders -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Order History</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($customer_orders)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                            <h5>No Orders Yet</h5>
                            <p class="text-muted">This customer hasn't placed any orders yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customer_orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
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
                                            <td class="text-capitalize">
                                                <?php echo str_replace('_', ' ', $order['payment_method']); ?>
                                            </td>
                                            <td>
                                                <a href="../order-tracking.php?id=<?php echo $order['id']; ?>"
                                                    class="btn btn-sm btn-outline-primary">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Statistics -->
            <?php if (!empty($customer_orders)): ?>
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success">₱<?php
                                $total_spent = array_sum(array_column($customer_orders, 'total_amount'));
                                echo number_format($total_spent, 2);
                                ?></h3>
                                <p class="text-muted mb-0">Total Spent</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-primary"><?php echo count($customer_orders); ?></h3>
                                <p class="text-muted mb-0">Total Orders</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-warning">₱<?php
                                $avg_order = $total_spent / count($customer_orders);
                                echo number_format($avg_order, 2);
                                ?></h3>
                                <p class="text-muted mb-0">Average Order</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
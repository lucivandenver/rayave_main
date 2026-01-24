<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$order_id = $_GET['id'] ?? 0;

// Get order details
$stmt = $pdo->prepare("SELECT o.*, u.username, u.email, u.phone 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    redirect('orders.php');
}

// Get order items
$stmt = $pdo->prepare("SELECT oi.*, p.name, p.image 
                      FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order tracking history
$stmt = $pdo->prepare("SELECT * FROM order_tracking WHERE order_id = ? ORDER BY created_at DESC");
$stmt->execute([$order_id]);
$tracking_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $status = $_POST['status'];
    $tracking_number = $_POST['tracking_number'] ?? '';

    $stmt = $pdo->prepare("UPDATE orders SET status = ?, tracking_number = ? WHERE id = ?");
    if ($stmt->execute([$status, $tracking_number, $order_id])) {
        $descriptions = [
            'pending' => 'Order placed successfully',
            'confirmed' => 'Order confirmed and payment verified',
            'processing' => 'Order is being processed and prepared for shipment',
            'shipped' => 'Order has been shipped',
            'delivered' => 'Order has been delivered successfully',
            'cancelled' => 'Order has been cancelled'
        ];

        updateOrderStatus($order_id, $status, $descriptions[$status]);
        
        $_SESSION['success'] = "Order status updated successfully!";
        redirect('order-detail.php?id=' . $order_id);
    } else {
        $_SESSION['error'] = "Failed to update order status.";
    }
}

?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Order #<?php echo $order['id']; ?></h2>
        <a href="orders.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Orders
        </a>
    </div>

    <div class="row">
        <!-- Order Information -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="m-0 font-weight-bold">Order Information</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Customer Details</h6>
                            <p>
                                <strong>Name:</strong> <?php echo $order['username']; ?><br>
                                <strong>Email:</strong> <?php echo $order['email']; ?><br>
                                <strong>Phone:</strong> <?php echo $order['phone'] ?? 'N/A'; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Order Details</h6>
                            <p>
                                <strong>Order ID:</strong> #<?php echo $order['id']; ?><br>
                                <strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?><br>
                                <strong>Status:</strong> 
                                <span class="badge bg-<?php
                                echo $order['status'] === 'delivered' ? 'success' :
                                    ($order['status'] === 'cancelled' ? 'danger' :
                                        ($order['status'] === 'pending' ? 'warning' : 'info'));
                                ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Shipping Address</h6>
                            <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Payment Method</h6>
                            <p class="text-capitalize">
                                <?php echo str_replace('_', ' ', $order['payment_method']); ?>
                            </p>
                            <?php if ($order['tracking_number']): ?>
                                <h6>Tracking Number</h6>
                                <p><?php echo $order['tracking_number']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="m-0 font-weight-bold">Order Items</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td><?php echo $item['name']; ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                        <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tracking History -->
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h6 class="m-0 font-weight-bold">Tracking History</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php if ($tracking_history): ?>
                            <?php foreach ($tracking_history as $entry): ?>
                                <div class="timeline-item mb-3">
                                    <div class="d-flex">
                                        <div class="me-3">
                                            <div class="badge bg-success rounded-circle" style="width: 15px; height: 15px;"></div>
                                        </div>
                                        <div>
                                            <strong><?php echo ucfirst(str_replace('_', ' ', $entry['status'])); ?></strong>
                                            <p class="mb-0 text-muted small">
                                                <?php echo $entry['description']; ?>
                                            </p>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y g:i A', strtotime($entry['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No tracking history available.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Summary & Actions -->
        <div class="col-lg-4">
            <!-- Order Summary -->
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="m-0 font-weight-bold">Order Summary</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>₱<?php echo number_format($order['total_amount'] - 10 - (($order['total_amount'] - 10) * 0.1 / 1.1), 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span>₱10.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Tax:</span>
                        <span>₱<?php echo number_format(($order['total_amount'] - 10) * 0.1 / 1.1, 2); ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-5">
                        <span>Total:</span>
                        <span>₱<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Update Status -->
            <div class="card shadow">
                <div class="card-header bg-warning text-white">
                    <h6 class="m-0 font-weight-bold">Update Status</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tracking Number (Optional)</label>
                            <input type="text" name="tracking_number" class="form-control" 
                                   value="<?php echo $order['tracking_number'] ?? ''; ?>" 
                                   placeholder="e.g., TRK123456">
                        </div>
                        <button type="submit" name="update_status" class="btn btn-warning w-100">
                            <i class="fas fa-sync me-2"></i>Update Status
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

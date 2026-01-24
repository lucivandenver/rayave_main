<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$order_id = $_GET['id'] ?? null;
$order = null;
$tracking = [];

if ($order_id) {
    $stmt = $pdo->prepare("SELECT o.*, u.username FROM orders o 
                          JOIN users u ON o.user_id = u.id 
                          WHERE o.id = ? AND (o.user_id = ? OR ? = 'admin')");
    $stmt->execute([$order_id, $_SESSION['user_id'], $_SESSION['role']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        $tracking = getOrderTracking($order_id);

        // Get order items
        $stmt = $pdo->prepare("SELECT oi.*, p.name, p.image FROM order_items oi 
                              JOIN products p ON oi.product_id = p.id 
                              WHERE oi.order_id = ?");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <?php if (!$order): ?>
        <div class="text-center">
            <i class="fas fa-exclamation-circle fa-3x text-warning mb-3"></i>
            <h3>Order Not Found</h3>
            <p>The order you're looking for doesn't exist or you don't have permission to view it.</p>
            <a href="profile.php" class="btn btn-success">View My Orders</a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <!-- Order Tracking Timeline -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Order Tracking - #<?php echo $order['id']; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php
                            $status_flow = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
                            $current_status_index = array_search($order['status'], $status_flow);

                            foreach ($status_flow as $index => $status):
                                $is_completed = $index <= $current_status_index;
                                $is_current = $index === $current_status_index;
                                ?>
                                <div class="timeline-item <?php echo $is_completed ? 'completed' : ''; ?>">
                                    <div class="timeline-marker">
                                        <?php if ($is_completed): ?>
                                            <i class="fas fa-check-circle text-success"></i>
                                        <?php else: ?>
                                            <i class="far fa-circle text-muted"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1 text-capitalize"><?php echo $status; ?></h6>
                                        <?php if ($is_completed && isset($tracking[$index])): ?>
                                            <p class="text-muted mb-0 small">
                                                <?php echo date('M j, Y g:i A', strtotime($tracking[$index]['created_at'])); ?>
                                            </p>
                                            <?php if ($tracking[$index]['description']): ?>
                                                <p class="small mb-0"><?php echo $tracking[$index]['description']; ?></p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($order['tracking_number']): ?>
                            <div class="mt-4 p-3 bg-light rounded">
                                <strong>Tracking Number:</strong> <?php echo $order['tracking_number']; ?><br>
                                <?php if ($order['estimated_delivery']): ?>
                                    <strong>Estimated Delivery:</strong>
                                    <?php echo date('M j, Y', strtotime($order['estimated_delivery'])); ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Items</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($order_items as $item): ?>
                            <?php $item_feedback = getFeedbackForOrderItem($item['id'], $_SESSION['user_id']); ?>
                            <div class="row align-items-center mb-3 pb-3 border-bottom">
                                <div class="col-2">
                                    <img src="assets/images/products/<?php echo $item['image']; ?>"
                                        alt="<?php echo $item['name']; ?>" class="img-fluid rounded">
                                </div>
                                <div class="col-6">
                                    <h6 class="mb-1"><?php echo $item['name']; ?></h6>
                                    <p class="text-muted mb-0">Quantity: <?php echo $item['quantity']; ?></p>
                                    <?php if ($item_feedback): ?>
                                        <div class="mt-2">
                                            <span class="badge bg-light text-success">Rated
                                                <?php echo $item_feedback['rating']; ?>/5</span>
                                            <p class="small mb-0 text-muted">
                                                “<?php echo htmlspecialchars($item_feedback['comment']); ?>”</p>
                                            <small class="text-muted"><?php echo ucfirst($item_feedback['status']); ?>
                                                review</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-4 text-end">
                                    <strong>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                    <?php if ($order['status'] === 'delivered'): ?>
                                        <button class="btn btn-outline-success btn-sm mt-2" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#feedbackForm-<?php echo $item['id']; ?>">
                                            <?php echo $item_feedback ? 'Edit Feedback' : 'Leave Feedback'; ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($order['status'] === 'delivered'): ?>
                                <div class="collapse" id="feedbackForm-<?php echo $item['id']; ?>">
                                    <div class="card card-body bg-light mb-3">
                                        <form method="POST" action="submit-feedback.php">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="order_item_id" value="<?php echo $item['id']; ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                            <div class="row g-3">
                                                <div class="col-md-3">
                                                    <label class="form-label small">Rating</label>
                                                    <select name="rating" class="form-select form-select-sm" required>
                                                        <?php for ($r = 5; $r >= 1; $r--): ?>
                                                            <option value="<?php echo $r; ?>" <?php echo ($item_feedback['rating'] ?? 5) == $r ? 'selected' : ''; ?>><?php echo $r; ?> -
                                                                <?php echo str_repeat('★', $r); ?>
                                                            </option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-9">
                                                    <label class="form-label small">Comment</label>
                                                    <textarea name="comment" class="form-control form-control-sm" rows="2"
                                                        placeholder="Share how the <?php echo strtolower($item['name']); ?> made your day"
                                                        required><?php echo htmlspecialchars($item_feedback['comment'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-end mt-3">
                                                <button type="submit" class="btn btn-success btn-sm">Save Feedback</button>
                                            </div>
                                            <small class="text-muted d-block mt-2">Feedback status will be set to pending until
                                                approved by an admin.</small>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Order Status:</strong>
                            <span class="badge bg-<?php
                            echo $order['status'] === 'delivered' ? 'success' :
                                ($order['status'] === 'cancelled' ? 'danger' :
                                    ($order['status'] === 'pending' ? 'warning' : 'info'));
                            ?> text-capitalize float-end">
                                <?php echo $order['status']; ?>
                            </span>
                        </div>

                        <div class="mb-3">
                            <strong>Order Date:</strong>
                            <span
                                class="float-end"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></span>
                        </div>

                        <div class="mb-3">
                            <strong>Payment Method:</strong>
                            <span class="float-end text-capitalize">
                                <?php echo str_replace('_', ' ', $order['payment_method']); ?>
                            </span>
                        </div>

                        <hr>

                        <div class="mb-2">
                            <span>Subtotal:</span>
                            <span class="float-end">₱<?php echo number_format($order['total_amount'] / 1.1, 2); ?></span>
                        </div>

                        <div class="mb-2">
                            <span>Tax (10%):</span>
                            <span
                                class="float-end">₱<?php echo number_format($order['total_amount'] / 1.1 * 0.1, 2); ?></span>
                        </div>

                        <div class="mb-2">
                            <span>Shipping:</span>
                            <span class="float-end">₱10.00</span>
                        </div>

                        <hr>

                        <div class="mb-3 fw-bold fs-5">
                            <span>Total:</span>
                            <span class="float-end">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>

                        <hr>

                        <h6>Shipping Address</h6>
                        <p class="small"><?php echo nl2br($order['shipping_address']); ?></p>

                        <?php if ($order['status'] === 'pending' && $order['user_id'] == $_SESSION['user_id']): ?>
                            <button type="button" class="btn btn-outline-danger w-100 mt-3" data-bs-toggle="modal"
                                data-bs-target="#cancelOrderModal" data-order-id="<?php echo $order['id']; ?>"
                                data-redirect="order-tracking.php?id=<?php echo $order['id']; ?>">
                                Cancel Order
                            </button>
                            <small class="text-muted d-block mt-2">You can cancel while the order is still pending.
                                Once confirmed by admin, cancellation is disabled.</small>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isAdmin()): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-warning">
                            <h6 class="mb-0">Admin Actions</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="admin/update-order-status.php" class="mb-3">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <div class="mb-2">
                                    <label class="form-label small">Update Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>
                                            Pending</option>
                                        <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>
                                            Shipped</option>
                                        <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Tracking Number</label>
                                    <input type="text" name="tracking_number" class="form-control form-control-sm"
                                        value="<?php echo $order['tracking_number'] ?? ''; ?>">
                                </div>
                                <button type="submit" class="btn btn-warning btn-sm w-100">Update</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
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
                <p class="mb-0">Are you sure you want to cancel this order? This action cannot be undone and stock will
                    be returned to inventory.</p>
            </div>
            <div class="modal-footer">
                <input type="hidden" name="order_id" id="cancelOrderId">
                <input type="hidden" name="redirect_to" id="cancelRedirect">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-danger">Yes, Cancel Order</button>
            </div>
        </form>
    </div>
</div>

<style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }

    const cancelModal=document.getElementById('cancelOrderModal');

    if (cancelModal) {
        cancelModal.addEventListener('show.bs.modal', function (event) {
                const button=event.relatedTarget;
                const orderId=button.getAttribute('data-order-id');
                const redirect=button.getAttribute('data-redirect');
                document.getElementById('cancelOrderId').value=orderId;
                document.getElementById('cancelRedirect').value=redirect;
            });
    }

    </script>.timeline-item {
        position: relative;
        padding-bottom: 20px;
    }

    .timeline-item:last-child {
        padding-bottom: 0;
    }

    .timeline-marker {
        position: absolute;
        left: -30px;
        top: 0;
    }

    .timeline-item.completed .timeline-marker {
        color: #28a745;
    }

    .timeline-content {
        margin-left: 0;
    }
</style>

<?php include 'includes/footer.php'; ?>
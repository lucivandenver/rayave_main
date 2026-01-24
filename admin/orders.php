<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $tracking_number = $_POST['tracking_number'] ?? '';

    $stmt = $pdo->prepare("UPDATE orders SET status = ?, tracking_number = ? WHERE id = ?");
    if ($stmt->execute([$status, $tracking_number, $order_id])) {
        // Add tracking entry
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
    } else {
        $_SESSION['error'] = "Failed to update order status.";
    }
}

// Pagination setup
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders");
$count_stmt->execute();
$total_orders = (int) $count_stmt->fetchColumn();
$total_pages = max(1, (int) ceil($total_orders / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// Get paginated orders
$orders_stmt = $pdo->prepare("SELECT o.*, u.username, u.email 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      ORDER BY o.created_at DESC
                      LIMIT :limit OFFSET :offset");
$orders_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$orders_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$orders_stmt->execute();
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Order Management</h2>
    </div>

    <!-- Orders Table -->
    <div class="card shadow">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold">All Orders</h6>
            <small class="text-white-50">Showing <?php echo $total_orders ? ($offset + 1) : 0; ?>-
                <?php echo min($offset + count($orders), $total_orders); ?> of <?php echo $total_orders; ?></small>
        </div>
        <div class="card-body">
            <?php if (empty($orders)): ?>
                <div class="alert alert-info mb-0">No orders found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="ordersTable">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment Method</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td>
                                        <div><?php echo $order['username']; ?></div>
                                        <small class="text-muted"><?php echo $order['email']; ?></small>
                                    </td>
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
                                    <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="../order-tracking.php?id=<?php echo $order['id']; ?>"
                                                class="btn btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn btn-outline-warning" data-bs-toggle="modal"
                                                data-bs-target="#statusModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>

                                        <!-- Status Update Modal -->
                                        <div class="modal fade" id="statusModal<?php echo $order['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Update Order #<?php echo $order['id']; ?></h5>
                                                        <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="order_id"
                                                                value="<?php echo $order['id']; ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">Status</label>
                                                                <select name="status" class="form-select" required>
                                                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>
                                                                        Pending</option>
                                                                    <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>
                                                                        Confirmed</option>
                                                                    <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>
                                                                        Processing</option>
                                                                    <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>
                                                                        Shipped</option>
                                                                    <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>
                                                                        Delivered</option>
                                                                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>
                                                                        Cancelled</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Tracking Number</label>
                                                                <input type="text" name="tracking_number" class="form-control"
                                                                    value="<?php echo $order['tracking_number'] ?? ''; ?>"
                                                                    placeholder="Optional">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" name="update_status"
                                                                class="btn btn-warning">Update Status</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    ?>
                    <nav aria-label="Orders pagination">
                        <ul class="pagination pagination-sm justify-content-center">
                            <?php $prev_page = max(1, $page - 1); ?>
                            <li class="page-item <?php echo $page === 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $prev_page; ?>" tabindex="-1">Prev</a>
                            </li>
                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php $next_page = min($total_pages, $page + 1); ?>
                            <li class="page-item <?php echo $page === $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $next_page; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
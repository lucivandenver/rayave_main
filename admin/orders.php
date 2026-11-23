<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

ensureOrderArchiveColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_archive'])) {
    $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
    $archiveState = isset($_POST['new_archive_state']) && (int) $_POST['new_archive_state'] === 1;
    $redirect_to = $_POST['redirect_to'] ?? 'orders.php';
    if (!is_string($redirect_to) || strpos($redirect_to, 'orders.php') !== 0) {
        $redirect_to = 'orders.php';
    }

    if ($order_id && archiveOrder($order_id, $archiveState)) {
        $_SESSION['success'] = $archiveState ? "Order archived." : "Order restored to active list.";
    } else {
        $_SESSION['error'] = "Unable to update archive status for that order.";
    }

    redirect($redirect_to);
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
    $status = $_POST['status'] ?? '';
    $tracking_number = trim($_POST['tracking_number'] ?? '');

    $descriptions = getOrderStatusDescriptions($tracking_number);
    $description = $descriptions[$status] ?? '';

    if (updateOrderStatus($order_id, $status, $description, true, $tracking_number)) {
        $_SESSION['success'] = "Order status updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update order status.";
    }
}

$scope = isset($_GET['scope']) ? strtolower($_GET['scope']) : 'active';
$scopeLabels = [
    'active' => 'Active',
    'archived' => 'Archived',
    'all' => 'All'
];
if (!array_key_exists($scope, $scopeLabels)) {
    $scope = 'active';
}

$validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
$paymentMethodsStmt = $pdo->query("SELECT DISTINCT payment_method FROM orders ORDER BY payment_method");
$paymentMethods = $paymentMethodsStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

$searchQuery = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = '';
}

$paymentFilter = $_GET['payment_method'] ?? '';
if ($paymentFilter !== '' && !in_array($paymentFilter, $paymentMethods, true)) {
    $paymentFilter = '';
}

$sanitizeDate = function ($date) {
    if ($date === '' || $date === null) {
        return '';
    }
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return ($dt && $dt->format('Y-m-d') === $date) ? $date : '';
};

$dateFrom = $sanitizeDate($_GET['date_from'] ?? '');
$dateTo = $sanitizeDate($_GET['date_to'] ?? '');

if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
    [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
}

$conditions = [];
$params = [];
$joinClause = 'JOIN users u ON o.user_id = u.id';

if ($scope === 'archived') {
    $conditions[] = 'o.is_archived = 1';
} elseif ($scope === 'active') {
    $conditions[] = 'o.is_archived = 0';
}

if ($searchQuery !== '') {
    $conditions[] = '(CAST(o.id AS CHAR) LIKE :search OR u.username LIKE :search OR u.email LIKE :search)';
    $params[':search'] = '%' . $searchQuery . '%';
}

if ($statusFilter !== '') {
    $conditions[] = 'o.status = :status';
    $params[':status'] = $statusFilter;
}

if ($paymentFilter !== '') {
    $conditions[] = 'o.payment_method = :payment_method';
    $params[':payment_method'] = $paymentFilter;
}

if ($dateFrom !== '') {
    $conditions[] = 'DATE(o.created_at) >= :date_from';
    $params[':date_from'] = $dateFrom;
}

if ($dateTo !== '') {
    $conditions[] = 'DATE(o.created_at) <= :date_to';
    $params[':date_to'] = $dateTo;
}

$whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Pagination setup
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$count_sql = "SELECT COUNT(*) FROM orders o $joinClause $whereClause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_orders = (int) $count_stmt->fetchColumn();
$total_pages = max(1, (int) ceil($total_orders / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// Get paginated orders
$orders_sql = "SELECT o.*, u.username, u.full_name, u.email 
                      FROM orders o 
                      $joinClause
                      $whereClause
                      ORDER BY o.created_at DESC
                      LIMIT :limit OFFSET :offset";
$orders_stmt = $pdo->prepare($orders_sql);
$stringParams = $params;
foreach ($stringParams as $placeholder => $value) {
    $orders_stmt->bindValue($placeholder, $value);
}
$orders_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$orders_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$orders_stmt->execute();
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

$baseQueryParams = ['scope' => $scope];
if ($searchQuery !== '') {
    $baseQueryParams['search'] = $searchQuery;
}
if ($statusFilter !== '') {
    $baseQueryParams['status'] = $statusFilter;
}
if ($paymentFilter !== '') {
    $baseQueryParams['payment_method'] = $paymentFilter;
}
if ($dateFrom !== '') {
    $baseQueryParams['date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $baseQueryParams['date_to'] = $dateTo;
}

$currentListParams = $baseQueryParams;
$currentListParams['page'] = $page;
$currentQueryString = http_build_query($currentListParams);
$currentListUrl = 'orders.php' . ($currentQueryString ? '?' . $currentQueryString : '');
$resetFiltersUrl = 'orders.php?scope=' . $scope;
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
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                <div class="btn-group btn-group-sm" role="group" aria-label="Order filters">
                    <?php foreach ($scopeLabels as $scopeKey => $label): ?>
                        <?php $btnClass = $scope === $scopeKey ? 'btn-success' : 'btn-outline-success'; ?>
                        <?php
                        $scopeFilters = $baseQueryParams;
                        $scopeFilters['scope'] = $scopeKey;
                        $scopeFilters['page'] = 1;
                        $scopeUrl = 'orders.php?' . http_build_query($scopeFilters);
                        ?>
                        <a href="<?php echo $scopeUrl; ?>" class="btn <?php echo $btnClass; ?>">
                            <?php echo $label; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <small class="text-muted">Archive orders to keep the active list tidy.</small>
                    <a href="create-order.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>Create Order
                    </a>
                </div>
            </div>

            <form method="GET" class="row g-3 align-items-end mb-4">
                <input type="hidden" name="scope" value="<?php echo htmlspecialchars($scope); ?>">
                <div class="col-md-4">
                    <label class="form-label small text-uppercase text-muted">Search</label>
                    <input type="text" name="search" class="form-control"
                        placeholder="Order #, customer, email"
                        value="<?php echo htmlspecialchars($searchQuery); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-uppercase text-muted">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        <?php foreach ($validStatuses as $statusOption): ?>
                            <option value="<?php echo $statusOption; ?>" <?php echo $statusFilter === $statusOption ? 'selected' : ''; ?>>
                                <?php echo ucfirst($statusOption); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-uppercase text-muted">Payment</label>
                    <select name="payment_method" class="form-select">
                        <option value="">All methods</option>
                        <?php foreach ($paymentMethods as $method): ?>
                            <option value="<?php echo htmlspecialchars($method); ?>" <?php echo $paymentFilter === $method ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $method))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-uppercase text-muted">Date from</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-uppercase text-muted">Date to</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
                </div>
                <div class="col-12 col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-success flex-fill">Apply</button>
                    <a href="<?php echo $resetFiltersUrl; ?>" class="btn btn-outline-secondary flex-fill" title="Reset filters">Reset</a>
                </div>
            </form>

            <?php if (empty($orders)): ?>
                <div class="alert alert-info mb-0">
                    <?php echo $scope === 'archived' ? 'No archived orders found yet.' : 'No orders found for this view.'; ?>
                </div>
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
                                <tr class="<?php echo !empty($order['is_archived']) ? 'table-light' : ''; ?>">
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($order['full_name'] ?: $order['email']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
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
                                        <?php if (!empty($order['is_archived'])): ?>
                                            <span class="badge bg-secondary ms-1">Archived</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-capitalize">
                                        <?php echo str_replace('_', ' ', $order['payment_method']); ?>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <?php
                                        $archiveBtnClass = !empty($order['is_archived']) ? 'btn-outline-success' : 'btn-outline-secondary';
                                        $archiveBtnTitle = !empty($order['is_archived']) ? 'Restore to active list' : 'Archive order';
                                        ?>
                                        <div class="btn-group btn-group-sm">
                                            <a href="../order-tracking.php?id=<?php echo $order['id']; ?>"
                                                class="btn btn-outline-primary" title="View order">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn btn-outline-warning" data-bs-toggle="modal"
                                                data-bs-target="#statusModal<?php echo $order['id']; ?>" title="Update status">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="submit" class="btn <?php echo $archiveBtnClass; ?>"
                                                title="<?php echo $archiveBtnTitle; ?>"
                                                form="archiveForm<?php echo $order['id']; ?>">
                                                <i class="fas fa-box-archive"></i>
                                            </button>
                                        </div>

                                        <form method="POST" id="archiveForm<?php echo $order['id']; ?>" class="d-none">
                                            <input type="hidden" name="toggle_archive" value="1">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="new_archive_state" value="<?php echo !empty($order['is_archived']) ? 0 : 1; ?>">
                                            <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($currentListUrl); ?>">
                                        </form>

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
                            <?php
                            $prev_page = max(1, $page - 1);
                            $prevQuery = $baseQueryParams;
                            $prevQuery['page'] = $prev_page;
                            $prevUrl = 'orders.php?' . http_build_query($prevQuery);
                            ?>
                            <li class="page-item <?php echo $page === 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $prevUrl; ?>" tabindex="-1">Prev</a>
                            </li>
                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <?php
                                $pageQuery = $baseQueryParams;
                                $pageQuery['page'] = $i;
                                $pageUrl = 'orders.php?' . http_build_query($pageQuery);
                                ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo $pageUrl; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php
                            $next_page = min($total_pages, $page + 1);
                            $nextQuery = $baseQueryParams;
                            $nextQuery['page'] = $next_page;
                            $nextUrl = 'orders.php?' . http_build_query($nextQuery);
                            ?>
                            <li class="page-item <?php echo $page === $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $nextUrl; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
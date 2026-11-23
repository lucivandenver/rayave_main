<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

$validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
$searchQuery = trim($_GET['search'] ?? '');
$statusFilter = strtolower(trim($_GET['status'] ?? ''));
if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = '';
}

$sanitizeDate = function ($value) {
    if ($value === '' || $value === null) {
        return '';
    }
    $dt = DateTime::createFromFormat('Y-m-d', $value);
    return ($dt && $dt->format('Y-m-d') === $value) ? $value : '';
};

$dateFrom = $sanitizeDate($_GET['date_from'] ?? '');
$dateTo = $sanitizeDate($_GET['date_to'] ?? '');
if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
    [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
}

$conditions = ['o.user_id = :user_id'];
$params = [':user_id' => $user_id];

if ($searchQuery !== '') {
    $conditions[] = '(CAST(o.id AS CHAR) LIKE :search OR o.status LIKE :search)';
    $params[':search'] = '%' . $searchQuery . '%';
}

if ($statusFilter !== '') {
    $conditions[] = 'o.status = :status';
    $params[':status'] = $statusFilter;
}

if ($dateFrom !== '') {
    $conditions[] = 'DATE(o.created_at) >= :date_from';
    $params[':date_from'] = $dateFrom;
}

if ($dateTo !== '') {
    $conditions[] = 'DATE(o.created_at) <= :date_to';
    $params[':date_to'] = $dateTo;
}

$whereClause = 'WHERE ' . implode(' AND ', $conditions);

$ordersSql = "SELECT o.* FROM orders o $whereClause ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($ordersSql);
foreach ($params as $placeholder => $value) {
    $stmt->bindValue($placeholder, $value);
}
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filterParams = [];
if ($searchQuery !== '') {
    $filterParams['search'] = $searchQuery;
}
if ($statusFilter !== '') {
    $filterParams['status'] = $statusFilter;
}
if ($dateFrom !== '') {
    $filterParams['date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $filterParams['date_to'] = $dateTo;
}

$hasActiveFilters = !empty($filterParams);
$currentQueryString = $filterParams ? '?' . http_build_query($filterParams) : '';
$currentListUrl = 'my-orders.php' . $currentQueryString;
$resetFiltersUrl = 'my-orders.php';
$reorderEligibleStatuses = ['confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Orders</h5>
                </div>
                <div class="card-body">
                    <?php $resultsCount = count($orders); ?>

                    <?php if ($resultsCount > 0 || $hasActiveFilters): ?>
                        <form method="GET" class="row gy-2 gx-3 align-items-end mb-4">
                            <div class="col-md-4">
                                <label class="form-label small text-uppercase text-muted">Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Order # or status"
                                    value="<?php echo htmlspecialchars($searchQuery); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-uppercase text-muted">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All statuses</option>
                                    <?php foreach ($validStatuses as $statusOption): ?>
                                        <option value="<?php echo $statusOption; ?>"
                                            <?php echo $statusFilter === $statusOption ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($statusOption); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-6 col-md-2">
                                <label class="form-label small text-uppercase text-muted">From</label>
                                <input type="date" name="date_from" class="form-control"
                                    value="<?php echo htmlspecialchars($dateFrom); ?>">
                            </div>
                            <div class="col-sm-6 col-md-2">
                                <label class="form-label small text-uppercase text-muted">To</label>
                                <input type="date" name="date_to" class="form-control"
                                    value="<?php echo htmlspecialchars($dateTo); ?>">
                            </div>
                            <div class="col-12 col-md-auto d-flex gap-2">
                                <button type="submit" class="btn btn-success flex-fill">Apply</button>
                                <?php if ($hasActiveFilters): ?>
                                    <a href="<?php echo $resetFiltersUrl; ?>" class="btn btn-outline-secondary flex-fill">Reset</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    <?php endif; ?>

                    <?php if (empty($orders)): ?>
                        <?php if ($hasActiveFilters): ?>
                            <div class="alert alert-light border text-center py-5">
                                <i class="fas fa-filter fa-2x text-muted mb-3"></i>
                                <h5 class="mb-2">No matching orders</h5>
                                <p class="text-muted mb-3">Try adjusting your search or date range.</p>
                                <a href="<?php echo $resetFiltersUrl; ?>" class="btn btn-outline-success">Clear Filters</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                <h5>No Orders Yet</h5>
                                <p class="text-muted">You haven't placed any orders yet.</p>
                                <a href="products.php" class="btn btn-success">Start Shopping</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted small">Showing <?php echo $resultsCount; ?> order<?php echo $resultsCount === 1 ? '' : 's'; ?><?php echo $hasActiveFilters ? ' (filtered)' : ''; ?></span>
                            <a href="<?php echo $currentListUrl; ?>" class="text-decoration-none small">Refresh list</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
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
                                        <?php $canReorder = in_array($order['status'], $reorderEligibleStatuses, true); ?>
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
                                                            data-redirect="<?php echo htmlspecialchars($currentListUrl); ?>">
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
                <p class="mb-0">Cancelling will immediately stop processing and return stock to inventory. Are you sure?</p>
            </div>
            <div class="modal-footer">
                <input type="hidden" name="order_id" id="cancelOrderId">
                <input type="hidden" name="redirect_to" id="cancelRedirect" value="my-orders.php">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Order</button>
                <button type="submit" class="btn btn-danger">Cancel Order</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
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

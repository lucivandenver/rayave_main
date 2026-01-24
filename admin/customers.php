<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Pagination setup
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'customer'");
$count_stmt->execute();
$total_customers = (int) $count_stmt->fetchColumn();
$total_pages = max(1, (int) ceil($total_customers / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// Get paged customers with aggregates
$customers_stmt = $pdo->prepare("SELECT u.*, 
                         COUNT(o.id) as order_count,
                         COALESCE(SUM(o.total_amount), 0) as total_spent
                         FROM users u 
                         LEFT JOIN orders o ON u.id = o.user_id 
                         WHERE u.role = 'customer' 
                         GROUP BY u.id 
                         ORDER BY u.created_at DESC
                         LIMIT :limit OFFSET :offset");
$customers_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$customers_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$customers_stmt->execute();
$customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Customer Management</h2>
    </div>

    <!-- Customers Table -->
    <div class="card shadow">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold">All Customers</h6>
            <small class="text-white-50">Showing <?php echo $total_customers ? ($offset + 1) : 0; ?>-
                <?php echo min($offset + count($customers), $total_customers); ?> of
                <?php echo $total_customers; ?></small>
        </div>
        <div class="card-body">
            <?php if (empty($customers)): ?>
                <div class="alert alert-info mb-0">No customers found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="customersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Member Since</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                                <tr>
                                    <td><?php echo $customer['id']; ?></td>
                                    <td>
                                        <div class="fw-bold"><?php echo $customer['full_name'] ?: $customer['username']; ?>
                                        </div>
                                        <small class="text-muted">@<?php echo $customer['username']; ?></small>
                                    </td>
                                    <td><?php echo $customer['email']; ?></td>
                                    <td><?php echo $customer['phone'] ?: 'N/A'; ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $customer['order_count']; ?> orders</span>
                                    </td>
                                    <td>₱<?php echo number_format($customer['total_spent'], 2); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="customer-detail.php?id=<?php echo $customer['id']; ?>"
                                                class="btn btn-outline-primary" title="View profile">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="mailto:<?php echo $customer['email']; ?>" class="btn btn-outline-success"
                                                title="Email customer">
                                                <i class="fas fa-envelope"></i>
                                            </a>
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
                    <nav aria-label="Customer pagination">
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
<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get filters
$filters = [];
$filters['username'] = trim($_GET['username'] ?? '');
$filters['event'] = trim($_GET['event'] ?? '');
$filters['date_from'] = trim($_GET['date_from'] ?? '');
$filters['date_to'] = trim($_GET['date_to'] ?? '');

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

// Get total count
$total = countActivityLogsFiltered($filters);
$totalPages = ceil($total / $limit);

// Get logs
$logs = getActivityLogsFiltered($filters, $limit, $offset);

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Activity Logs</h3>
        <small class="text-muted">Total: <?php echo $total; ?> records</small>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0">Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control form-control-sm" id="username" name="username" 
                           value="<?php echo htmlspecialchars($filters['username']); ?>" placeholder="Search username">
                </div>
                <div class="col-md-3">
                    <label for="event" class="form-label">Activity Type</label>
                    <select class="form-select form-select-sm" id="event" name="event">
                        <option value="">All Events</option>
                        <option value="login" <?php echo $filters['event'] === 'login' ? 'selected' : ''; ?>>Login</option>
                        <option value="logout" <?php echo $filters['event'] === 'logout' ? 'selected' : ''; ?>>Logout</option>
                        <option value="login_failed" <?php echo $filters['event'] === 'login_failed' ? 'selected' : ''; ?>>Login Failed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control form-control-sm" id="date_from" name="date_from" 
                           value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control form-control-sm" id="date_to" name="date_to" 
                           value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    <a href="activity-logs.php" class="btn btn-sm btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <?php if (empty($logs)): ?>
        <div class="alert alert-info">No activity logs found.</div>
    <?php else: ?>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date/Time</th>
                            <th>User</th>
                            <th>Activity</th>
                            <th>IP Address</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <small><?php echo htmlspecialchars($log['created_at']); ?></small>
                                </td>
                                <td>
                                    <?php if ($log['user_id']): ?>
                                        <strong><?php echo htmlspecialchars($log['username'] ?? 'Unknown'); ?></strong>
                                        <br><small class="text-muted">ID: <?php echo (int)$log['user_id']; ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Guest</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $eventLabel = $log['event'];
                                    if ($log['event'] === 'login') {
                                        echo '<span class="badge bg-success">Login</span>';
                                    } elseif ($log['event'] === 'logout') {
                                        echo '<span class="badge bg-warning">Logout</span>';
                                    } elseif ($log['event'] === 'login_failed') {
                                        echo '<span class="badge bg-danger">Login Failed</span>';
                                    } else {
                                        echo '<span class="badge bg-info">' . htmlspecialchars($eventLabel) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <code class="small"><?php echo htmlspecialchars($log['ip'] ?? 'N/A'); ?></code>
                                </td>
                                <td>
                                    <?php 
                                    $meta = !empty($log['meta']) ? json_decode($log['meta'], true) : [];
                                    if (!empty($meta)): ?>
                                        <small class="text-muted">
                                            <?php 
                                            if (isset($meta['reason'])) {
                                                echo 'Reason: ' . htmlspecialchars($meta['reason']) . '<br>';
                                            }
                                            if (isset($meta['source'])) {
                                                echo 'Source: ' . htmlspecialchars($meta['source']);
                                            }
                                            ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1<?php echo !empty($filters['username']) ? '&username=' . urlencode($filters['username']) : ''; ?>">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($filters['username']) ? '&username=' . urlencode($filters['username']) : ''; ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($filters['username']) ? '&username=' . urlencode($filters['username']) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($filters['username']) ? '&username=' . urlencode($filters['username']) : ''; ?>">Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo !empty($filters['username']) ? '&username=' . urlencode($filters['username']) : ''; ?>">Last</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

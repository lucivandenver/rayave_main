<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_id'])) {
    $feedback_id = (int) $_POST['feedback_id'];
    $status = $_POST['status'] ?? 'pending';
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $comment = trim($_POST['comment'] ?? '');
    $notes = trim($_POST['admin_notes'] ?? '');
    $redirect_status = $_POST['status_filter'] ?? '';
    $redirect_page = max(1, (int) ($_POST['page'] ?? 1));

    try {
        $stmt = $pdo->prepare("UPDATE customer_feedback SET comment = ?, status = ?, is_featured = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$comment, $status, $is_featured, $notes, $feedback_id]);
        $_SESSION['success'] = 'Feedback updated successfully.';
    } catch (PDOException $e) {
        error_log('Admin feedback update error: ' . $e->getMessage());
        $_SESSION['error'] = 'Unable to update feedback.';
    }

    $query = [];
    if (!empty($redirect_status)) {
        $query['status'] = $redirect_status;
    }
    if ($redirect_page > 1) {
        $query['page'] = $redirect_page;
    }

    $redirect_url = 'feedback.php' . (!empty($query) ? '?' . http_build_query($query) : '');
    redirect($redirect_url);
}

$status_filter = $_GET['status'] ?? '';
$per_page = 8;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$filters = [];

$count_sql = "SELECT COUNT(*) FROM customer_feedback cf";
if (!empty($status_filter)) {
    $count_sql .= " WHERE cf.status = ?";
    $filters[] = $status_filter;
}

$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($filters);
$total_rows = (int) $count_stmt->fetchColumn();
$total_pages = max(1, (int) ceil($total_rows / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$sql = "SELECT cf.*, p.name AS product_name, u.full_name, u.username
    FROM customer_feedback cf
    JOIN products p ON cf.product_id = p.id
    JOIN users u ON cf.user_id = u.id";

$filters = [];
if (!empty($status_filter)) {
    $sql .= " WHERE cf.status = ?";
    $filters[] = $status_filter;
}

$sql .= " ORDER BY cf.created_at DESC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($filters);
$feedback_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
$page_query = [];
if (!empty($status_filter)) {
    $page_query['status'] = $status_filter;
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-0">Customer Feedback</h2>
            <small class="text-muted">Review, edit, and feature testimonials submitted by shoppers.</small>
        </div>
        <form class="d-flex align-items-center" method="GET">
            <label class="me-2 mb-0">Status:</label>
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published
                </option>
                <option value="hidden" <?php echo $status_filter === 'hidden' ? 'selected' : ''; ?>>Hidden</option>
            </select>
        </form>
    </div>

    <?php if (empty($feedback_entries)): ?>
        <div class="alert alert-info">No feedback found for the selected filter.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Rating</th>
                        <th>Moderation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feedback_entries as $entry): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($entry['full_name'] ?? $entry['username']); ?></strong><br>
                                <small class="text-muted">Order #<?php echo $entry['order_id']; ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($entry['product_name']); ?><br>
                                <small class="text-muted">Submitted:
                                    <?php echo date('M j, Y', strtotime($entry['created_at'])); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-success"><?php echo $entry['rating']; ?>/5</span>
                            </td>
                            <td style="min-width:300px;">
                                <form method="POST" class="row g-2 align-items-start">
                                    <input type="hidden" name="feedback_id" value="<?php echo $entry['id']; ?>">
                                    <input type="hidden" name="status_filter"
                                        value="<?php echo htmlspecialchars($status_filter); ?>">
                                    <input type="hidden" name="page" value="<?php echo $page; ?>">
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Customer Comment</label>
                                        <textarea name="comment" class="form-control" rows="2"
                                            required><?php echo htmlspecialchars($entry['comment']); ?></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small mb-1">Status</label>
                                        <select name="status" class="form-select form-select-sm">
                                            <option value="pending" <?php echo $entry['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="published" <?php echo $entry['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                                            <option value="hidden" <?php echo $entry['status'] === 'hidden' ? 'selected' : ''; ?>>
                                                Hidden</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small mb-1">Featured</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_featured" value="1" <?php echo $entry['is_featured'] ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Show on landing</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small mb-1">Admin Notes</label>
                                        <textarea name="admin_notes" class="form-control" rows="2"
                                            placeholder="Internal note"><?php echo htmlspecialchars($entry['admin_notes'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-success btn-sm">Save Changes</button>
                                    </div>
                                </form>
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
            <nav aria-label="Feedback pagination" class="mt-3">
                <ul class="pagination pagination-sm justify-content-center">
                    <?php $prev_page = max(1, $page - 1); ?>
                    <li class="page-item <?php echo $page === 1 ? 'disabled' : ''; ?>">
                        <a class="page-link"
                            href="?<?php echo http_build_query(array_merge($page_query, ['page' => $prev_page])); ?>"
                            tabindex="-1">Prev</a>
                    </li>
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link"
                                href="?<?php echo http_build_query(array_merge($page_query, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <?php $next_page = min($total_pages, $page + 1); ?>
                    <li class="page-item <?php echo $page === $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link"
                            href="?<?php echo http_build_query(array_merge($page_query, ['page' => $next_page])); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
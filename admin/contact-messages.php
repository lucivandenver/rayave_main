<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$statusFilter = $_GET['status'] ?? 'all';
$validStatuses = ['all', 'open', 'pending', 'closed'];
if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = 'all';
}

$threads = $statusFilter === 'all' ? getContactThreads() : getContactThreads($statusFilter);
$statusCounts = countContactThreadsByStatus();
$customerSearch = isset($_GET['customer_search']) ? trim($_GET['customer_search']) : '';
$customerResults = [];
$defaultSupportSubject = 'Support Assistance';

if ($customerSearch !== '') {
    $searchTerm = '%' . $customerSearch . '%';
    $stmt = $pdo->prepare("SELECT id, full_name, username, email FROM users
                            WHERE role != 'admin' AND (full_name LIKE :term OR username LIKE :term OR email LIKE :term)
                            ORDER BY full_name ASC LIMIT 10");
    $stmt->bindValue(':term', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    $customerResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include 'includes/header.php';
?>

<div class="card shadow-sm mt-4 mb-4">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h5 class="mb-1">Start a New Conversation</h5>
                <p class="text-muted mb-0">Search any customer and reach out proactively from here.</p>
            </div>
            <form method="GET" class="w-100" style="max-width: 420px;">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                <div class="input-group">
                    <input type="search" name="customer_search" class="form-control"
                        placeholder="Search by name, username, or email"
                        value="<?php echo htmlspecialchars($customerSearch); ?>">
                    <button class="btn btn-success" type="submit">Search</button>
                </div>
            </form>
        </div>

        <?php if ($customerSearch !== ''): ?>
            <div class="mt-3">
                <p class="text-muted small mb-2">Results for "<?php echo htmlspecialchars($customerSearch); ?>":</p>
                <?php if (!empty($customerResults)): ?>
                    <div class="list-group">
                        <?php foreach ($customerResults as $customer): ?>
                            <?php $existingThread = getContactThreadByUser($customer['id']); ?>
                            <div class="list-group-item"> 
                                <div class="row g-3 align-items-center">
                                    <div class="col-md">
                                        <strong><?php echo htmlspecialchars($customer['full_name'] ?: $customer['username']); ?></strong>
                                        <div class="text-muted small">@<?php echo htmlspecialchars($customer['username']); ?> ·
                                            <?php echo htmlspecialchars($customer['email']); ?></div>
                                        <?php if ($existingThread): ?>
                                            <span class="badge bg-secondary mt-2">Existing conversation</span>
                                        <?php else: ?>
                                            <span class="badge bg-success mt-2">New conversation</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-auto">
                                        <?php if ($existingThread): ?>
                                            <a href="contact-message-view.php?id=<?php echo $existingThread['id']; ?>"
                                                class="btn btn-outline-primary btn-sm">Open conversation</a>
                                        <?php else: ?>
                                            <form method="GET" action="contact-message-view.php"
                                                class="d-flex flex-column flex-md-row gap-2">
                                                <input type="hidden" name="user_id"
                                                    value="<?php echo (int) $customer['id']; ?>">
                                                <input type="text" name="subject"
                                                    class="form-control form-control-sm"
                                                    placeholder="Subject"
                                                    value="<?php echo htmlspecialchars($defaultSupportSubject); ?>">
                                                <button type="submit" class="btn btn-success btn-sm">Start chat</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">No customers found matching that search.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-4 mb-3">
    <div>
        <h2 class="mb-1">Contact Messages</h2>
        <p class="text-muted mb-0">Review customer conversations and keep replies in one place.</p>
    </div>
    <div class="btn-group" role="group">
        <a href="?status=all" class="btn btn-outline-secondary <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
            All (<?php echo $statusCounts['all']; ?>)
        </a>
        <a href="?status=open"
            class="btn btn-outline-secondary <?php echo $statusFilter === 'open' ? 'active' : ''; ?>">
            Open (<?php echo $statusCounts['open']; ?>)
        </a>
        <a href="?status=pending"
            class="btn btn-outline-secondary <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
            Pending (<?php echo $statusCounts['pending']; ?>)
        </a>
        <a href="?status=closed"
            class="btn btn-outline-secondary <?php echo $statusFilter === 'closed' ? 'active' : ''; ?>">
            Closed (<?php echo $statusCounts['closed']; ?>)
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Last Message</th>
                        <th>Unread</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($threads)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No conversations found for this filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($threads as $thread): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($thread['name']); ?></strong>
                                    <div class="small text-muted"><?php echo htmlspecialchars($thread['email']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($thread['subject']); ?></td>
                                <td>
                                    <span
                                        class="badge bg-<?php
                                        echo $thread['status'] === 'open' ? 'danger' : ($thread['status'] === 'pending' ? 'warning text-dark' : 'success'); ?>">
                                        <?php echo ucfirst($thread['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo $thread['last_message_at'] ? date('M d, Y h:i A', strtotime($thread['last_message_at'])) : '—'; ?></small>
                                </td>
                                <td>
                                    <?php if ((int) $thread['unread_for_admin'] > 0): ?>
                                        <span class="badge bg-danger"><?php echo (int) $thread['unread_for_admin']; ?> new</span>
                                    <?php else: ?>
                                        <span class="text-muted small">None</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="contact-message-view.php?id=<?php echo $thread['id']; ?>"
                                        class="btn btn-outline-primary btn-sm">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<?php
require_once '../includes/config.php';
ensureUserSecurityColumns();
ensureSecurityTables();

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'disable_user' && !empty($_POST['user_id'])) {
        $uid = (int) $_POST['user_id'];
        if (disableUser($uid)) {
            logUserActivity($_SESSION['user_id'], 'admin_disable_user', ['target' => $uid]);
            $_SESSION['success'] = 'User disabled successfully.';
        } else {
            $_SESSION['error'] = 'Unable to disable user.';
        }
        redirect('security.php');
    }

    if ($action === 'enable_user' && !empty($_POST['user_id'])) {
        $uid = (int) $_POST['user_id'];
        if (enableUser($uid)) {
            logUserActivity($_SESSION['user_id'], 'admin_enable_user', ['target' => $uid]);
            $_SESSION['success'] = 'User enabled successfully.';
        } else {
            $_SESSION['error'] = 'Unable to enable user.';
        }
        redirect('security.php');
    }

    if ($action === 'mark_flag_reviewed' && !empty($_POST['flag_id'])) {
        $fid = (int) $_POST['flag_id'];
        $stmt = $pdo->prepare("UPDATE flagged_messages SET review_state = 'reviewed' WHERE id = ?");
        if ($stmt->execute([$fid])) {
            $_SESSION['success'] = 'Flagged message marked as reviewed.';
        } else {
            $_SESSION['error'] = 'Unable to mark as reviewed.';
        }
        redirect('security.php');
    }
}

$pageActivity = max(1, (int)($_GET['page_activity'] ?? 1));
$perActivity = max(10, min(100, (int)($_GET['per_activity'] ?? 25)));
$pageFlagged = max(1, (int)($_GET['page_flagged'] ?? 1));
$perFlagged = max(10, min(100, (int)($_GET['per_flagged'] ?? 25)));

$filters = [];
$filters['username'] = trim($_GET['filter_user'] ?? '');
$filters['event'] = trim($_GET['filter_event'] ?? '');
$filters['date_from'] = trim($_GET['filter_from'] ?? '');
$filters['date_to'] = trim($_GET['filter_to'] ?? '');

$flagFilters = [];
$flagFilters['username'] = trim($_GET['filter_user'] ?? '');
$flagFilters['source'] = trim($_GET['filter_source'] ?? '');
$flagFilters['review_state'] = trim($_GET['filter_review_state'] ?? '');
$flagFilters['date_from'] = trim($_GET['filter_from'] ?? '');
$flagFilters['date_to'] = trim($_GET['filter_to'] ?? '');

$totalActivities = countActivityLogsFiltered($filters);
$totalFlagged = countFlaggedMessagesFiltered($flagFilters);

$activityOffset = ($pageActivity - 1) * $perActivity;
$flaggedOffset = ($pageFlagged - 1) * $perFlagged;

$activityLogs = getActivityLogsFiltered($filters, $perActivity, $activityOffset);
$flagged = getFlaggedMessagesFiltered($flagFilters, $perFlagged, $flaggedOffset);

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Security & Activity</h3>
        <small class="text-muted">Monitor logins, flagged content, and manage accounts</small>
    </div>
    <div class="mb-3">
        <form class="row g-2 align-items-center">
            <div class="col-auto">
                <input name="filter_user" value="<?php echo htmlspecialchars($_GET['filter_user'] ?? ''); ?>" class="form-control form-control-sm" placeholder="User">
            </div>
            <div class="col-auto">
                <input name="filter_event" value="<?php echo htmlspecialchars($_GET['filter_event'] ?? ''); ?>" class="form-control form-control-sm" placeholder="Event (login/logout)">
            </div>
            <div class="col-auto">
                <select name="filter_source" class="form-select form-select-sm">
                    <option value="">Source (any)</option>
                    <option value="feedback" <?php if (($_GET['filter_source'] ?? '') === 'feedback') echo 'selected'; ?>>Feedback</option>
                    <option value="chatbot" <?php if (($_GET['filter_source'] ?? '') === 'chatbot') echo 'selected'; ?>>Chatbot</option>
                    <option value="contact" <?php if (($_GET['filter_source'] ?? '') === 'contact') echo 'selected'; ?>>Contact</option>
                </select>
            </div>
            <div class="col-auto">
                <select name="filter_review_state" class="form-select form-select-sm">
                    <option value="">Review state</option>
                    <option value="new" <?php if (($_GET['filter_review_state'] ?? '') === 'new') echo 'selected'; ?>>New</option>
                    <option value="reviewed" <?php if (($_GET['filter_review_state'] ?? '') === 'reviewed') echo 'selected'; ?>>Reviewed</option>
                    <option value="false_positive" <?php if (($_GET['filter_review_state'] ?? '') === 'false_positive') echo 'selected'; ?>>False positive</option>
                </select>
            </div>
            <div class="col-auto">
                <input name="filter_from" type="date" value="<?php echo htmlspecialchars($_GET['filter_from'] ?? ''); ?>" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <input name="filter_to" type="date" value="<?php echo htmlspecialchars($_GET['filter_to'] ?? ''); ?>" class="form-control form-control-sm">
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary">Apply</button>
                <a href="security.php" class="btn btn-sm btn-outline-secondary">Clear</a>
                <a href="banned_words.php" class="btn btn-sm btn-outline-secondary">Manage banned words</a>
            </div>
        </form>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Recent Activity Logs</h6>
                    <small class="text-muted">Latest <?php echo count($activityLogs); ?></small>
                </div>
                <div class="card-body">
                    <?php if (empty($activityLogs)): ?>
                        <p class="text-muted">No activity recorded yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>When</th>
                                        <th>User</th>
                                        <th>Event</th>
                                        <th>IP</th>
                                        <th>Agent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activityLogs as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                            <td><?php echo htmlspecialchars($row['username'] ?? 'Guest'); ?></td>
                                            <td><?php echo htmlspecialchars($row['event']); ?></td>
                                            <td><?php echo htmlspecialchars($row['ip']); ?></td>
                                            <td style="max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($row['user_agent']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php
                        $totalPages = max(1, (int)ceil($totalActivities / $perActivity));
                        if ($totalPages > 1): ?>
                            <nav class="mt-2" aria-label="Activity pagination">
                                <ul class="pagination pagination-sm">
                                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                        <li class="page-item <?php echo $p === $pageActivity ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page_activity' => $p])); ?>"><?php echo $p; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Flagged Messages</h6>
                    <small class="text-muted">Inappropriate content detected</small>
                </div>
                <div class="card-body">
                    <?php if (empty($flagged)): ?>
                        <p class="text-muted">No flagged messages.</p>
                    <?php else: ?>
                        <?php foreach ($flagged as $f): ?>
                            <div class="mb-3 p-2 border rounded">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong><?php echo htmlspecialchars($f['username'] ?? 'Guest'); ?></strong>
                                        <small class="text-muted"> &middot; <?php echo htmlspecialchars($f['source']); ?> &middot; <?php echo htmlspecialchars($f['created_at']); ?></small>
                                    </div>
                                    <div>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="flag_id" value="<?php echo (int)$f['id']; ?>">
                                            <input type="hidden" name="action" value="mark_flag_reviewed">
                                            <button class="btn btn-sm btn-outline-success">Mark reviewed</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="mt-2 small text-muted">Detected: <?php echo htmlspecialchars($f['detected_words']); ?></div>
                                <div class="mt-2"><?php echo nl2br(htmlspecialchars($f['content'])); ?></div>
                                <?php if (!empty($f['user_id'])): ?>
                                    <div class="mt-2">
                                        <?php $uid = (int)$f['user_id'];
                                            $u = $pdo->prepare('SELECT id, username, is_disabled FROM users WHERE id = ?');
                                            $u->execute([$uid]);
                                            $userRow = $u->fetch(PDO::FETCH_ASSOC);
                                        ?>
                                        <?php if ($userRow): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $userRow['id']; ?>">
                                                <input type="hidden" name="action" value="<?php echo $userRow['is_disabled'] ? 'enable_user' : 'disable_user'; ?>">
                                                <button class="btn btn-sm <?php echo $userRow['is_disabled'] ? 'btn-outline-primary' : 'btn-outline-danger'; ?>">
                                                    <?php echo $userRow['is_disabled'] ? 'Enable account' : 'Disable account'; ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                            <?php
                            $totalFlagPages = max(1, (int)ceil($totalFlagged / $perFlagged));
                            if ($totalFlagPages > 1): ?>
                                <nav class="mt-2" aria-label="Flagged pagination">
                                    <ul class="pagination pagination-sm">
                                        <?php for ($p = 1; $p <= $totalFlagPages; $p++): ?>
                                            <li class="page-item <?php echo $p === $pageFlagged ? 'active' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page_flagged' => $p])); ?>"><?php echo $p; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Use these to investigate suspicious accounts or review flagged content.</p>
                    <a href="security.php" class="btn btn-sm btn-outline-secondary mb-2">Refresh</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
// client-side filtering for activity logs and flagged messages
(() => {
    const search = document.getElementById('securitySearch');
    if (!search) return;
    const activityRows = Array.from(document.querySelectorAll('table tbody tr'));
    const flaggedBlocks = Array.from(document.querySelectorAll('.card-body > div.mb-3, .card-body > div.border'));

    function matchesText(el, term) {
        return el.textContent && el.textContent.toLowerCase().indexOf(term) !== -1;
    }

    search.addEventListener('input', function () {
        const term = this.value.trim().toLowerCase();

        activityRows.forEach(r => {
            if (!term) { r.style.display = ''; return; }
            r.style.display = matchesText(r, term) ? '' : 'none';
        });

        flaggedBlocks.forEach(b => {
            if (!term) { b.style.display = ''; return; }
            b.style.display = matchesText(b, term) ? '' : 'none';
        });
    });
})();
</script>
<script>
// Lightweight auto-refresh polling for new security items
(function () {
    const activityBody = document.querySelector('.card .table tbody');
    const flaggedCardBody = document.querySelectorAll('.card .card-body')[1];
    if (!activityBody || !flaggedCardBody) {
        return;
    }

    let lastActivityId = <?php echo (int)(array_reduce($activityLogs, function($carry, $i){ return max($carry, (int)$i['id']); }, 0)); ?>;
    let lastFlaggedId = <?php echo (int)(array_reduce($flagged, function($carry, $i){ return max($carry, (int)$i['id']); }, 0)); ?>;
    let isFetching = false;

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

    function insertActivity(row) {
        const tr = document.createElement('tr');
        tr.innerHTML = '<td>' + escapeHtml(row.created_at || '') + '</td>' +
            '<td>' + escapeHtml(row.username || 'Guest') + '</td>' +
            '<td>' + escapeHtml(row.event || '') + '</td>' +
            '<td>' + escapeHtml(row.ip || '') + '</td>' +
            '<td style="max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">' + escapeHtml(row.user_agent || '') + '</td>';
        activityBody.insertBefore(tr, activityBody.firstChild);
    }

    function insertFlagged(item) {
        const div = document.createElement('div');
        div.className = 'mb-3 p-2 border rounded';
        div.innerHTML = '<div class="d-flex justify-content-between"><div><strong>' + escapeHtml(item.username || 'Guest') + '</strong> <small class="text-muted"> &middot; ' + escapeHtml(item.source || '') + ' &middot; ' + escapeHtml(item.created_at || '') + '</small></div></div>' +
            '<div class="mt-2 small text-muted">Detected: ' + escapeHtml(item.detected_words || '') + '</div>' +
            '<div class="mt-2">' + escapeHtml(item.content || '') + '</div>';
        flaggedCardBody.insertBefore(div, flaggedCardBody.firstChild);
    }

    async function pollUpdates() {
        if (isFetching) return;
        isFetching = true;
        try {
            const url = 'security-updates.php?last_activity_id=' + lastActivityId + '&last_flagged_id=' + lastFlaggedId;
            const response = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
            if (!response.ok) return;
            const data = await response.json();

            if (Array.isArray(data.activities) && data.activities.length) {
                data.activities.forEach(insertActivity);
                lastActivityId = Math.max(lastActivityId, ...data.activities.map(item => parseInt(item.id, 10) || 0));
            }

            if (Array.isArray(data.flagged) && data.flagged.length) {
                data.flagged.forEach(insertFlagged);
                lastFlaggedId = Math.max(lastFlaggedId, ...data.flagged.map(item => parseInt(item.id, 10) || 0));
            }
        } catch (error) {
            console.warn('Security update poll failed', error);
        } finally {
            isFetching = false;
        }
    }

    setInterval(pollUpdates, 3000);
    pollUpdates();
})();
</script>

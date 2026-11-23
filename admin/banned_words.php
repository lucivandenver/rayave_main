<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' && !empty($_POST['word'])) {
        $w = trim($_POST['word']);
        $adminId = $_SESSION['user_id'] ?? null;
        if (addBannedWord($w, $adminId)) {
            $_SESSION['success'] = 'Word added.';
        } else {
            $_SESSION['error'] = 'Unable to add word.';
        }
        redirect('banned_words.php');
    }

    if ($action === 'delete' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $adminId = $_SESSION['user_id'] ?? null;
        if (removeBannedWord($id, $adminId)) {
            $_SESSION['success'] = 'Word removed.';
        } else {
            $_SESSION['error'] = 'Unable to remove.';
        }
        redirect('banned_words.php');
    }
}

$words = getBannedWords();
$audits = getBannedWordsAudit(100);
include 'includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Banned Words</h3>
        <a href="security.php" class="btn btn-sm btn-outline-secondary">Back to Security</a>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <form method="POST" class="mb-3 d-flex gap-2">
                        <input type="hidden" name="action" value="add">
                        <input name="word" class="form-control form-control-sm" placeholder="Enter word to ban" required>
                        <button class="btn btn-sm btn-success">Add</button>
                    </form>

                    <?php if (empty($words)): ?>
                        <p class="text-muted">No banned words configured.</p>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($words as $w): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?php echo htmlspecialchars($w['word']); ?></span>
                                    <form method="POST" class="mb-0">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$w['id']; ?>">
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Audit Trail</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($audits)): ?>
                            <p class="text-muted">No audit entries yet.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($audits as $a): ?>
                                    <li class="list-group-item small d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo htmlspecialchars($a['action']); ?></strong>
                                            <div class="text-muted"><?php echo htmlspecialchars($a['word'] ?? ''); ?></div>
                                            <div class="text-muted small">By: <?php echo htmlspecialchars($a['username'] ?? 'System'); ?> at <?php echo htmlspecialchars($a['created_at']); ?></div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

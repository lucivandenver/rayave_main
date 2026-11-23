<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$action = $_REQUEST['action'] ?? '';
$pageId = isset($_REQUEST['page_id']) ? (int)$_REQUEST['page_id'] : null;

// Handle create/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'save' || $action === 'create')) {
    $pageData = [
        'title' => $_POST['title'] ?? '',
        'slug' => $_POST['slug'] ?? '',
        'content' => $_POST['content'] ?? '',
        'is_published' => !empty($_POST['is_published']) ? 1 : 0
    ];

    // Check for inappropriate content
    $contentCheck = checkInappropriateContent($pageData['content']);
    if ($contentCheck['has_inappropriate']) {
        $_SESSION['warning'] = 'Content contains inappropriate words: ' . implode(', ', $contentCheck['detected_words']);
    }

    $result = savePage($pageData, $pageId);
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
        redirect('pages.php');
    } else {
        $_SESSION['error'] = $result['message'];
    }
}

// Handle delete
if ($action === 'delete' && $pageId) {
    if (deletePage($pageId)) {
        $_SESSION['success'] = 'Page deleted successfully.';
    } else {
        $_SESSION['error'] = 'Failed to delete page.';
    }
    redirect('pages.php');
}

$page = null;
if ($action === 'edit' && $pageId) {
    $page = getPageById($pageId);
    if (!$page) {
        $_SESSION['error'] = 'Page not found.';
        redirect('pages.php');
    }
}

$allPages = getAllPages();

include 'includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Pages Manager</h3>
        <?php if ($action !== 'create' && $action !== 'edit'): ?>
            <a href="?action=create" class="btn btn-sm btn-primary">+ New Page</a>
        <?php endif; ?>
    </div>

    <!-- Create/Edit Form -->
    <?php if ($action === 'create' || $action === 'edit'): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><?php echo $action === 'create' ? 'Create New Page' : 'Edit Page'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="save">
                    <?php if ($page): ?>
                        <input type="hidden" name="page_id" value="<?php echo (int)$page['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="title" class="form-label">Page Title</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo htmlspecialchars($page['title'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="slug" class="form-label">URL Slug</label>
                        <small class="text-muted d-block mb-2">Auto-generated from title if left blank</small>
                        <input type="text" class="form-control" id="slug" name="slug" 
                               value="<?php echo htmlspecialchars($page['slug'] ?? ''); ?>" 
                               placeholder="page-title">
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($page['content'] ?? ''); ?></textarea>
                        <small class="text-muted d-block mt-2">
                            ⚠️ Any inappropriate content will be flagged in the system for review.
                        </small>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_published" name="is_published" 
                               value="1" <?php echo (!isset($page) || $page['is_published'] ? 'checked' : ''); ?>>
                        <label class="form-check-label" for="is_published">Publish this page</label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action === 'create' ? 'Create Page' : 'Update Page'; ?>
                        </button>
                        <a href="pages.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Pages List -->
    <?php if ($action !== 'create' && $action !== 'edit'): ?>
        <?php if (empty($allPages)): ?>
            <div class="alert alert-info">No pages yet. <a href="?action=create">Create one now</a></div>
        <?php else: ?>
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>URL Slug</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allPages as $p): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($p['title']); ?></strong>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($p['slug']); ?></code></td>
                                    <td>
                                        <?php if ($p['is_published']): ?>
                                            <span class="badge bg-success">Published</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($p['created_by_username'] ?? 'System'); ?></td>
                                    <td><small class="text-muted"><?php echo htmlspecialchars($p['updated_at']); ?></small></td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="?action=edit&page_id=<?php echo (int)$p['id']; ?>" class="btn btn-outline-primary">Edit</a>
                                            <a href="<?php echo appUrl(htmlspecialchars($p['slug'])); ?>" class="btn btn-outline-secondary" target="_blank">View</a>
                                            <form method="POST" class="d-inline" style="display:inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="page_id" value="<?php echo (int)$p['id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" 
                                                        onclick="return confirm('Are you sure?')">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

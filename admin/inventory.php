<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

ensureLowStockThresholdColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_threshold'])) {
    $product_id = (int) $_POST['product_id'];
    $low_stock_threshold = max(0, (int) $_POST['low_stock_threshold']);

    $stmt = $pdo->prepare("UPDATE products SET low_stock_threshold = ? WHERE id = ?");
    if ($stmt->execute([$low_stock_threshold, $product_id])) {
        $_SESSION['success'] = "Low stock alert updated.";
    } else {
        $_SESSION['error'] = "Failed to update low stock alert.";
    }
    redirect('inventory.php');
}

$overview = getInventoryOverview();
$categoriesList = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$categoryFilter = isset($_GET['category']) && $_GET['category'] !== '' ? (int) $_GET['category'] : null;
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$perPage = 10;
$currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

$filters = [];
$params = [];

if ($categoryFilter) {
    $filters[] = 'p.category_id = :category_id';
    $params['category_id'] = $categoryFilter;
}

if ($searchTerm !== '') {
    $filters[] = '(p.name LIKE :search OR p.description LIKE :search OR p.tags LIKE :search)';
    $params['search'] = '%' . $searchTerm . '%';
}

$whereSql = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p $whereSql");
$countStmt->execute($params);
$totalProducts = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalProducts / $perPage));

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}

$offset = ($currentPage - 1) * $perPage;

$productsStmt = $pdo->prepare("SELECT p.*, c.name AS category_name
                             FROM products p
                             LEFT JOIN categories c ON p.category_id = c.id
                             $whereSql
                             ORDER BY c.name ASC, p.name ASC
                             LIMIT :limit OFFSET :offset");
foreach ($params as $key => $value) {
    $productsStmt->bindValue(':' . $key, $value);
}
$productsStmt->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
$productsStmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
$productsStmt->execute();
$allProducts = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

$baseQuery = [];
if ($categoryFilter) {
    $baseQuery['category'] = $categoryFilter;
}
if ($searchTerm !== '') {
    $baseQuery['search'] = $searchTerm;
}
$buildPaginationLink = function ($pageNumber) use ($baseQuery) {
    $params = $baseQuery;
    $params['page'] = $pageNumber;
    return 'inventory.php?' . http_build_query($params);
};

$showingFrom = $totalProducts ? $offset + 1 : 0;
$showingTo = min($offset + count($allProducts), $totalProducts);
$lowStockProducts = getLowStockProducts();
$outOfStockProducts = getOutOfStockProducts();
$restockLogs = getInventoryLogsByReason('restock', 8);
$spoilageLogs = getInventoryLogsByReason('spoilage', 8);
?>

<?php include 'includes/header.php'; ?>

<style>
    .inventory-metric {
        border: 0;
        border-radius: 18px;
        color: #fff;
        padding: 1.25rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .inventory-metric-link {
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .inventory-metric-link:hover .inventory-metric {
        transform: translateY(-3px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }

    .inventory-metric .metric-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.2);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        margin-bottom: 0.5rem;
    }

    .metric-primary {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
    }

    .metric-success {
        background: linear-gradient(135deg, #16a34a, #15803d);
    }

    .metric-warning {
        background: linear-gradient(135deg, #f97316, #ea580c);
    }

    .metric-danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
    }

    .inventory-table th {
        font-size: 0.85rem;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: #6b7280;
        border-top: 0;
    }

    .inventory-table td {
        vertical-align: middle;
    }

    .status-pill {
        border-radius: 999px;
        padding: 0.25rem 0.85rem;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .inventory-empty {
        border: 1px dashed #d1d5db;
        border-radius: 14px;
        padding: 1.5rem;
        text-align: center;
        color: #6b7280;
    }

    .inventory-empty i {
        font-size: 1.5rem;
        color: #d1d5db;
        margin-bottom: 0.5rem;
    }

    .log-pill {
        font-weight: 600;
        padding: 0.15rem 0.75rem;
        border-radius: 999px;
        font-size: 0.85rem;
    }

    .inline-threshold input[type="number"] {
        border-radius: 999px;
        text-align: center;
    }

    .inline-threshold button {
        border-radius: 999px;
        padding-inline: 1.25rem;
    }
</style>

<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <div>
            <h2 class="mb-0">Inventory Management</h2>
            <p class="text-muted mb-0">Monitor live stock, adjust alerts, and track restock/spoilage activity.</p>
        </div>
        <a href="products.php" class="btn btn-outline-success">
            <i class="fas fa-boxes me-2"></i>Manage Products
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <a href="#live-stock" class="inventory-metric-link">
                <div class="inventory-metric metric-primary h-100">
                <div class="metric-icon">
                    <i class="fas fa-cubes"></i>
                </div>
                <small class="text-white-50 text-uppercase">Active SKUs</small>
                <h3 class="fw-semibold mb-0"><?php echo (int) ($overview['total_skus'] ?? 0); ?></h3>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="#live-stock" class="inventory-metric-link">
                <div class="inventory-metric metric-success h-100">
                <div class="metric-icon">
                    <i class="fas fa-warehouse"></i>
                </div>
                <small class="text-white-50 text-uppercase">Units On Hand</small>
                <h3 class="fw-semibold mb-0"><?php echo number_format((int) ($overview['total_units'] ?? 0)); ?></h3>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="#low-stock-section" class="inventory-metric-link">
                <div class="inventory-metric metric-warning h-100">
                <div class="metric-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <small class="text-white-50 text-uppercase">Low Stock Alerts</small>
                <h3 class="fw-semibold mb-0"><?php echo (int) ($overview['low_stock'] ?? 0); ?></h3>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a href="#out-of-stock-section" class="inventory-metric-link">
                <div class="inventory-metric metric-danger h-100">
                <div class="metric-icon">
                    <i class="fas fa-ban"></i>
                </div>
                <small class="text-white-50 text-uppercase">Out of Stock</small>
                <h3 class="fw-semibold mb-0"><?php echo (int) ($overview['out_of_stock'] ?? 0); ?></h3>
                </div>
            </a>
        </div>
    </div>

    <div class="card shadow-sm mb-4" id="live-stock">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Live Stock Overview</h5>
            <small class="text-muted">Adjust alert thresholds inline as needed.</small>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end mb-4">
                <div class="col-md-4">
                    <label for="categoryFilter" class="form-label">Category</label>
                    <select name="category" id="categoryFilter" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categoriesList as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $categoryFilter === (int) $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="searchInput" class="form-label">Search</label>
                    <input type="text" name="search" id="searchInput" class="form-control" placeholder="Product name, description, or tags" value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-success flex-grow-1">Apply Filters</button>
                    <?php if ($categoryFilter || $searchTerm !== ''): ?>
                        <a href="inventory.php" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 text-muted small">
                <span>
                    <?php if ($totalProducts): ?>
                        Showing <?php echo $showingFrom; ?>-<?php echo $showingTo; ?> of <?php echo $totalProducts; ?> products
                    <?php else: ?>
                        No products match the current filters
                    <?php endif; ?>
                </span>
                <span><?php echo $perPage; ?> per page</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle inventory-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="text-center">On Hand</th>
                            <th style="width: 180px;">Alert Threshold</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$allProducts): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="inventory-empty">
                                        <i class="fas fa-box-open"></i>
                                        <p class="mb-0">No products available yet.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $currentCategory = null; ?>
                            <?php foreach ($allProducts as $product): ?>
                                <?php $categoryName = $product['category_name'] ? htmlspecialchars($product['category_name']) : 'Uncategorized'; ?>
                                <?php if ($categoryName !== $currentCategory): ?>
                                    <?php $currentCategory = $categoryName; ?>
                                    <tr class="table-light">
                                        <td colspan="5" class="text-uppercase small fw-semibold text-muted">
                                            <?php echo $currentCategory; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php
                            $statusLabel = 'Healthy';
                            $badgeClass = 'success';
                            if ($product['stock_quantity'] <= 0) {
                                $statusLabel = 'Out of Stock';
                                $badgeClass = 'danger';
                            } elseif ($product['stock_quantity'] <= $product['low_stock_threshold']) {
                                $statusLabel = 'Low Stock';
                                $badgeClass = 'warning';
                            }
                            ?>
                                <tr
                                class="<?php echo $badgeClass === 'danger' ? 'table-danger' : ($badgeClass === 'warning' ? 'table-warning' : ''); ?>">
                                <td class="fw-semibold"><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo $categoryName; ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo $badgeClass; ?>">
                                        <?php echo (int) $product['stock_quantity']; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="d-flex align-items-center gap-2 inline-threshold">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="update_threshold" value="1">
                                        <input type="number" name="low_stock_threshold" min="0"
                                            class="form-control form-control-sm"
                                            value="<?php echo (int) $product['low_stock_threshold']; ?>">
                                        <button class="btn btn-outline-secondary btn-sm" type="submit">Save</button>
                                    </form>
                                </td>
                                <td>
                                    <span class="status-pill bg-<?php echo $badgeClass; ?> bg-opacity-75 text-white">
                                        <?php echo $statusLabel; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav aria-label="Inventory pagination">
                    <ul class="pagination justify-content-center mt-3">
                        <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $currentPage <= 1 ? '#' : htmlspecialchars($buildPaginationLink($currentPage - 1)); ?>" aria-disabled="<?php echo $currentPage <= 1 ? 'true' : 'false'; ?>">Previous</a>
                        </li>
                        <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                            <li class="page-item <?php echo $page === $currentPage ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo htmlspecialchars($buildPaginationLink($page)); ?>"><?php echo $page; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $currentPage >= $totalPages ? '#' : htmlspecialchars($buildPaginationLink($currentPage + 1)); ?>" aria-disabled="<?php echo $currentPage >= $totalPages ? 'true' : 'false'; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4" id="low-stock-section">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Low Stock Alerts</h5>
                </div>
                <div class="card-body">
                    <?php if (!$lowStockProducts): ?>
                        <div class="inventory-empty">
                            <i class="fas fa-check-circle"></i>
                            <p class="mb-0">All products are above their alert thresholds.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle inventory-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">On Hand</th>
                                        <th class="text-center">Alert Trigger</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStockProducts as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td class="text-center fw-semibold text-warning">
                                                <?php echo (int) $product['stock_quantity']; ?>
                                            </td>
                                            <td class="text-center text-muted">≤
                                                <?php echo (int) $product['low_stock_threshold']; ?>
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
        <div class="col-lg-6" id="out-of-stock-section">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Out of Stock</h5>
                </div>
                <div class="card-body">
                    <?php if (!$outOfStockProducts): ?>
                        <div class="inventory-empty">
                            <i class="fas fa-box"></i>
                            <p class="mb-0">No items are currently out of stock.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle inventory-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th class="text-center">Last Known Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($outOfStockProducts as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                            <td class="text-center text-danger fw-semibold">
                                                <?php echo (int) $product['stock_quantity']; ?>
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

    <div class="row g-4 mt-1">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Restock Logs</h5>
                    <small class="text-muted">Last 8 entries</small>
                </div>
                <div class="card-body">
                    <?php if (!$restockLogs): ?>
                        <div class="inventory-empty">
                            <i class="fas fa-history"></i>
                            <p class="mb-0">No restock activity recorded yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle inventory-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Change</th>
                                        <th>New Qty</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($restockLogs as $log): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($log['product_name']); ?></td>
                                            <td class="text-center">
                                                <span
                                                    class="log-pill bg-success bg-opacity-10 text-success">+<?php echo (int) $log['change_quantity']; ?></span>
                                            </td>
                                            <td><?php echo (int) $log['new_quantity']; ?></td>
                                            <td class="text-muted small"><?php echo htmlspecialchars($log['notes']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Spoilage Logs</h5>
                    <small class="text-muted">Last 8 entries</small>
                </div>
                <div class="card-body">
                    <?php if (!$spoilageLogs): ?>
                        <div class="inventory-empty">
                            <i class="fas fa-recycle"></i>
                            <p class="mb-0">No spoilage adjustments recorded.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle inventory-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Change</th>
                                        <th>New Qty</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($spoilageLogs as $log): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($log['product_name']); ?></td>
                                            <td class="text-center">
                                                <span
                                                    class="log-pill bg-danger bg-opacity-10 text-danger"><?php echo (int) $log['change_quantity']; ?></span>
                                            </td>
                                            <td><?php echo (int) $log['new_quantity']; ?></td>
                                            <td class="text-muted small"><?php echo htmlspecialchars($log['notes']); ?></td>
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

<?php include 'includes/footer.php'; ?>
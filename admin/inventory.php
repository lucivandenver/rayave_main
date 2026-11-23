<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

ensureInventoryCoreTables();
$inventoryUnitsCatalog = getInventoryUnitsCatalog();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_inventory_item'])) {
    $itemId = (int) ($_POST['item_id'] ?? 0);
    if ($itemId <= 0) {
        $_SESSION['error'] = 'Invalid inventory item reference.';
        redirect('inventory.php');
    }

    $reorderPointRaw = trim((string) ($_POST['item_reorder_point'] ?? '0'));
    $unitCostRaw = trim((string) ($_POST['item_unit_cost'] ?? ''));

    $payload = [
        'name' => trim((string) ($_POST['item_name'] ?? '')),
        'sku_code' => trim((string) ($_POST['item_sku'] ?? '')),
        'category' => trim((string) ($_POST['item_category'] ?? '')),
        'unit' => strtolower(trim((string) ($_POST['item_unit'] ?? 'pc'))),
        'reorder_point' => $reorderPointRaw === '' ? 0 : (float) $reorderPointRaw,
        'unit_cost' => $unitCostRaw,
        'notes' => trim((string) ($_POST['item_notes'] ?? '')),
        'is_active' => isset($_POST['item_is_active']) ? 1 : 0
    ];

    $imageUpload = $_FILES['inventory_item_image'] ?? null;
    if ($imageUpload && $imageUpload['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($imageUpload['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Unable to upload the inventory image.';
            redirect('inventory.php');
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($imageUpload['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            $_SESSION['error'] = 'Unsupported image format. Use JPG, PNG, GIF, or WebP.';
            redirect('inventory.php');
        }

        $normalizedExtension = $extension === 'jpeg' ? 'jpg' : $extension;
        $uploadDirectory = realpath(__DIR__ . '/../assets/images/inventory');
        if ($uploadDirectory === false) {
            $uploadDirectory = __DIR__ . '/../assets/images/inventory';
        }
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0755, true);
        }

        $fileName = 'inventory_' . $itemId . '_' . time() . '.' . $normalizedExtension;
        $destination = rtrim($uploadDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($imageUpload['tmp_name'], $destination)) {
            $_SESSION['error'] = 'Failed to save the inventory image.';
            redirect('inventory.php');
        }

        $payload['image_path'] = 'inventory/' . $fileName;
    }

    if ($payload['name'] === '') {
        $_SESSION['error'] = 'Item name is required.';
        redirect('inventory.php');
    }

    if (!array_key_exists($payload['unit'], $inventoryUnitsCatalog)) {
        $payload['unit'] = 'pc';
    }

    if (!saveInventoryItem($payload, $itemId)) {
        if (empty($_SESSION['error'])) {
            $_SESSION['error'] = 'Unable to update the inventory item right now.';
        }
    } else {
        $_SESSION['success'] = 'Inventory item updated.';
    }

    redirect('inventory.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_inventory_item'])) {
    $itemId = (int) ($_POST['item_id'] ?? 0);

    if ($itemId <= 0) {
        $_SESSION['error'] = 'Invalid inventory item reference.';
        redirect('inventory.php');
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM inventory_items WHERE id = ? LIMIT 1');
        $stmt->execute([$itemId]);
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = 'Inventory item deleted.';
        } else {
            $_SESSION['error'] = 'Inventory item was not found.';
        }
    } catch (PDOException $e) {
        error_log('Inventory delete failed: ' . $e->getMessage());
        $_SESSION['error'] = 'Unable to delete the inventory item.';
    }

    redirect('inventory.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_item_product_links'])) {
    $itemId = (int) ($_POST['item_id'] ?? 0);
    if ($itemId <= 0) {
        $_SESSION['error'] = 'Invalid inventory item reference.';
        redirect('inventory.php');
    }

    $productIds = $_POST['linked_product_id'] ?? [];
    $quantities = $_POST['linked_quantity'] ?? [];
    $preparedLinks = [];

    foreach ($productIds as $index => $productIdRaw) {
        $productId = (int) $productIdRaw;
        $quantity = isset($quantities[$index]) ? (float) $quantities[$index] : 0;
        if ($productId <= 0 || $quantity <= 0) {
            continue;
        }
        $preparedLinks[$productId] = $quantity;
    }

    $links = [];
    foreach ($preparedLinks as $productId => $quantity) {
        $links[] = [
            'product_id' => $productId,
            'quantity' => $quantity
        ];
    }

    if (saveInventoryItemProductLinks($itemId, $links)) {
        $_SESSION['success'] = 'Linked products updated.';
    } else {
        if (empty($_SESSION['error'])) {
            $_SESSION['error'] = 'Unable to update linked products right now.';
        }
    }

    $returnQuery = trim($_POST['return_to_query'] ?? '');
    $redirectTarget = $returnQuery !== '' ? 'inventory.php?' . $returnQuery : 'inventory.php';
    redirect($redirectTarget);
}

$inventorySummary = getInventorySummary();
$inventoryHasReorderPoint = inventoryItemsTableHasColumn('reorder_point');

$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryFilter = isset($_GET['per_piece_category']) ? trim($_GET['per_piece_category']) : '';
$statusFilter = $_GET['status'] ?? 'active';
$hideEmpty = isset($_GET['hide_empty']) && $_GET['hide_empty'] === '1';
$perPage = 10;
$currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

$inventoryFilters = [];
if ($searchTerm !== '') {
    $inventoryFilters['search'] = $searchTerm;
}
if ($categoryFilter !== '') {
    $inventoryFilters['category'] = $categoryFilter;
}
if ($statusFilter === 'inactive') {
    $inventoryFilters['status'] = 'inactive';
} elseif ($statusFilter === 'all') {
    $inventoryFilters['include_inactive'] = true;
}
if ($hideEmpty) {
    $inventoryFilters['hide_empty'] = true;
}

$allInventoryItems = getInventoryItems($inventoryFilters);
$totalInventoryItems = count($allInventoryItems);
$totalPages = max(1, (int) ceil($totalInventoryItems / $perPage));
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
$offset = ($currentPage - 1) * $perPage;
$visibleInventoryItems = array_slice($allInventoryItems, $offset, $perPage);
$showingFrom = $totalInventoryItems ? $offset + 1 : 0;
$showingTo = min($offset + count($visibleInventoryItems), $totalInventoryItems);

$buildPaginationLink = function ($pageNumber) use ($categoryFilter, $searchTerm, $statusFilter, $hideEmpty) {
    $params = [];
    if ($categoryFilter !== '') {
        $params['per_piece_category'] = $categoryFilter;
    }
    if ($searchTerm !== '') {
        $params['search'] = $searchTerm;
    }
    if ($statusFilter !== 'active') {
        $params['status'] = $statusFilter;
    }
    if ($hideEmpty) {
        $params['hide_empty'] = 1;
    }
    $params['page'] = $pageNumber;
    $query = http_build_query($params);
    return $query ? 'inventory.php?' . $query : 'inventory.php?page=' . $pageNumber;
};

$categoryStmt = $pdo->query("SELECT DISTINCT category FROM inventory_items WHERE category IS NOT NULL AND category <> '' ORDER BY category ASC");
$perPieceCategories = $categoryStmt ? $categoryStmt->fetchAll(PDO::FETCH_COLUMN) : [];

$lowStockItems = getLowStockInventoryItems(6);
$outOfStockItems = getOutOfStockInventoryItems(6);
$restockLogs = getInventoryItemLogsByType('restock', 8);
$spoilageLogs = getInventoryItemLogsByType('waste', 8);
$productCatalogStmt = $pdo->query("SELECT p.id, p.name, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.name ASC");
$productCatalog = $productCatalogStmt ? $productCatalogStmt->fetchAll(PDO::FETCH_ASSOC) : [];
$inventoryItemProductLinks = getInventoryItemLinkedProducts();

$displayOverview = [
    'total_skus' => (int) ($inventorySummary['total_items'] ?? 0),
    'total_units' => (float) ($inventorySummary['total_units'] ?? 0),
    'low_stock' => (int) ($inventorySummary['at_risk'] ?? 0),
    'out_of_stock' => (int) ($inventorySummary['out_of_stock'] ?? 0)
];
$hasPerPieceInventory = $displayOverview['total_skus'] > 0;
$hasActiveFilters = $categoryFilter !== '' || $searchTerm !== '' || $statusFilter !== 'active' || $hideEmpty;

$formatQuantity = function ($value) {
    $formatted = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    return $formatted === '' ? '0' : $formatted;
};

$inventoryEditModals = [];
$inventoryLinkModals = [];
$currentRequestUri = $_SERVER['REQUEST_URI'] ?? 'inventory.php';
$currentQueryString = $_SERVER['QUERY_STRING'] ?? '';

include 'includes/header.php';
?>

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

    .inventory-thumb {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        object-fit: cover;
        border: 1px solid #e5e7eb;
        background: #fff;
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

    <div class="row g-3 mb-2">
        <div class="col-sm-6 col-lg-3">
            <div class="inventory-metric metric-primary h-100">
                <div class="metric-icon">
                    <i class="fas fa-cubes"></i>
                </div>
                <small class="text-white-50 text-uppercase">Active SKUs</small>
                <h3 class="fw-semibold mb-0"><?php echo number_format($displayOverview['total_skus']); ?></h3>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="inventory-metric metric-success h-100">
                <div class="metric-icon">
                    <i class="fas fa-warehouse"></i>
                </div>
                <small class="text-white-50 text-uppercase">Units On Hand</small>
                <h3 class="fw-semibold mb-0"><?php echo number_format($displayOverview['total_units']); ?></h3>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="inventory-metric metric-warning h-100">
                <div class="metric-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <small class="text-white-50 text-uppercase">Low Stock Alerts</small>
                <h3 class="fw-semibold mb-0"><?php echo number_format($displayOverview['low_stock']); ?></h3>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="inventory-metric metric-danger h-100">
                <div class="metric-icon">
                    <i class="fas fa-ban"></i>
                </div>
                <small class="text-white-50 text-uppercase">Out of Stock</small>
                <h3 class="fw-semibold mb-0"><?php echo number_format($displayOverview['out_of_stock']); ?></h3>
            </div>
        </div>
    </div>
    <?php if (!$hasPerPieceInventory): ?>
        <div class="alert alert-info mb-4">
            Per-piece metrics will stay at zero until you add items via “Add Per-Piece Product.”
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4" id="live-stock">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Live Stock Overview</h5>
            <small class="text-muted">Adjust alert thresholds inline as needed.</small>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end mb-4">
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="per_piece_category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($perPieceCategories as $categoryName): ?>
                            <option value="<?php echo htmlspecialchars($categoryName); ?>" <?php echo $categoryFilter === $categoryName ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoryName); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Name, SKU, or notes" value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active only</option>
                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive only</option>
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All items</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Visibility & Actions</label>
                    <div class="d-flex flex-column flex-md-row align-items-md-center gap-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="hideEmptySwitch" name="hide_empty" value="1" <?php echo $hideEmpty ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="hideEmptySwitch">Hide zero stock</label>
                        </div>
                        <div class="d-flex gap-2 ms-md-auto">
                            <button type="submit" class="btn btn-success">Apply Filters</button>
                            <?php if ($hasActiveFilters): ?>
                                <a href="inventory.php" class="btn btn-outline-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 text-muted small">
                <span>
                    <?php if ($totalInventoryItems): ?>
                        Showing <?php echo $showingFrom; ?>–<?php echo $showingTo; ?> of <?php echo $totalInventoryItems; ?> items
                    <?php else: ?>
                        No per-piece inventory matches the current filters
                    <?php endif; ?>
                </span>
                <span><?php echo $perPage; ?> per page</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle inventory-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Image</th>
                            <th>Item</th>
                            <th>Category</th>
                            <th class="text-center">On Hand</th>
                            <?php if ($inventoryHasReorderPoint): ?>
                                <th style="width: 200px;">Alert Threshold</th>
                            <?php endif; ?>
                            <th>Status</th>
                            <th class="text-center">Linked Products</th>
                            <th class="text-center" style="width: 190px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($visibleInventoryItems)): ?>
                            <tr>
                                <td colspan="<?php echo $inventoryHasReorderPoint ? '8' : '7'; ?>" class="text-center text-muted py-4">
                                    No per-piece inventory items match the current filters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($visibleInventoryItems as $item): ?>
                                <?php
                                $currentQty = (float) ($item['current_quantity'] ?? 0);
                                $reorderPoint = (float) ($item['reorder_point'] ?? 0);
                                $unitLabel = htmlspecialchars($item['unit'] ?? 'pc');
                                $statusLabel = 'Healthy';
                                $badgeClass = 'success';
                                if ($currentQty <= 0) {
                                    $statusLabel = 'Out of Stock';
                                    $badgeClass = 'danger';
                                } elseif ($inventoryHasReorderPoint && $reorderPoint > 0 && $currentQty <= $reorderPoint) {
                                    $statusLabel = 'Low Stock';
                                    $badgeClass = 'warning';
                                }
                                $quantityDisplay = $formatQuantity($currentQty);
                                $defaultImagePath = '../assets/images/products/default.jpg';
                                $imagePath = $defaultImagePath;
                                if (!empty($item['image_path'])) {
                                    $candidate = '../assets/images/' . ltrim($item['image_path'], '/\\');
                                    $imagePath = file_exists($candidate) ? $candidate : $defaultImagePath;
                                }
                                $linkModalId = 'manageItemLinks' . $item['id'];
                                $linkedProductsForItem = $inventoryItemProductLinks[$item['id']] ?? [];
                                $linkCountDisplay = count($linkedProductsForItem);
                                if (!$linkCountDisplay && !empty($item['linked_products'])) {
                                    $linkCountDisplay = (int) $item['linked_products'];
                                }
                                $productLinkRows = !empty($linkedProductsForItem) ? $linkedProductsForItem : [];
                                ?>
                                <tr class="<?php echo $badgeClass === 'danger' ? 'table-danger' : ($badgeClass === 'warning' ? 'table-warning' : ''); ?>">
                                    <td class="text-center">
                                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($item['name']); ?> image" class="inventory-thumb" onerror="this.onerror=null; this.src='../assets/images/products/default.jpg';">
                                    </td>
                                    <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="text-muted small">
                                        <?php if (!empty($item['sku_code'])): ?>
                                            SKU: <?php echo htmlspecialchars($item['sku_code']); ?> ·
                                        <?php endif; ?>
                                        Unit: <?php echo $unitLabel; ?>
                                    </div>
                                    <?php if (!empty($item['notes'])): ?>
                                        <div class="small text-muted"><?php echo htmlspecialchars($item['notes']); ?></div>
                                    <?php endif; ?>
                                    </td>
                                    <td><?php echo $item['category'] ? htmlspecialchars($item['category']) : '<span class="text-muted">Uncategorized</span>'; ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo $badgeClass; ?>">
                                            <?php echo $quantityDisplay; ?> <?php echo $unitLabel; ?>
                                        </span>
                                    </td>
                                    <?php if ($inventoryHasReorderPoint): ?>
                                        <td>
                                            <form method="POST" class="d-flex align-items-center gap-2 inline-threshold">
                                                <input type="hidden" name="update_item_threshold" value="1">
                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                <input type="number" step="0.01" min="0" name="item_reorder_point" class="form-control form-control-sm" value="<?php echo htmlspecialchars($item['reorder_point']); ?>">
                                                <button class="btn btn-outline-secondary btn-sm" type="submit">Save</button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <span class="status-pill bg-<?php echo $badgeClass; ?> bg-opacity-75 text-white">
                                            <?php echo $statusLabel; ?>
                                        </span>
                                    </td>
                                    <td class="text-center fw-semibold">
                                        <?php echo $linkCountDisplay; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-info link-inventory-btn" data-bs-toggle="modal" data-bs-target="#<?php echo $linkModalId; ?>" title="Edit linked products">
                                                <i class="fab fa-bluetooth-b"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editInventoryItem<?php echo $item['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="d-inline-block"
                                                  data-confirm="Delete <?php echo htmlspecialchars($item['name']); ?>? Components using it will also be removed."
                                                  data-confirm-variant="danger"
                                                  data-confirm-accept="Delete">
                                                <input type="hidden" name="delete_inventory_item" value="1">
                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                ob_start();
                                $hasLinkedProducts = !empty($productLinkRows);
                                ?>
                                    <div class="modal fade inventory-link-modal product-link-modal" id="<?php echo $linkModalId; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <form method="POST" action="<?php echo htmlspecialchars($currentRequestUri); ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Manage Linked Products</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="save_item_product_links" value="1">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                        <input type="hidden" name="return_to_query" value="<?php echo htmlspecialchars($currentQueryString); ?>">
                                                        <p class="text-muted small mb-3">
                                                            Review every catalog product that consumes this per-piece item. Remove any row below to unlink it instantly before saving.
                                                        </p>
                                                        <div class="inventory-search mb-3">
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                                <input type="search" class="form-control" placeholder="Search linked products" data-product-filter-input <?php echo $hasLinkedProducts ? '' : 'disabled'; ?>>
                                                                <button type="button" class="btn btn-outline-secondary" data-trigger-product-filter <?php echo $hasLinkedProducts ? '' : 'disabled'; ?>>Search</button>
                                                                <button type="button" class="btn btn-outline-secondary" data-clear-product-filter title="Clear search" <?php echo $hasLinkedProducts ? '' : 'disabled'; ?>>
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </div>
                                                            <div class="small text-muted mt-2 d-none" data-product-filter-feedback></div>
                                                        </div>
                                                        <div class="product-link-collection <?php echo $hasLinkedProducts ? '' : 'd-none'; ?>" id="productLinkRows<?php echo $item['id']; ?>" data-product-link-rows>
                                                            <?php foreach ($productLinkRows as $linkRow): ?>
                                                                <?php
                                                                $selectedProductId = (int) ($linkRow['product_id'] ?? 0);
                                                                $productTitle = trim($linkRow['product_name'] ?? ('Product #' . $selectedProductId));
                                                                $productCategory = trim($linkRow['category_name'] ?? '');
                                                                $rawQty = isset($linkRow['quantity_per_unit']) ? (float) $linkRow['quantity_per_unit'] : 0;
                                                                $formattedQty = $rawQty > 0 ? rtrim(rtrim(sprintf('%.4f', $rawQty), '0'), '.') : '0';
                                                                $filterLabel = strtolower($productTitle . ' ' . $productCategory);
                                                                ?>
                                                                <div class="product-link-row border rounded p-3 mb-3" data-product-link-row data-product-name="<?php echo htmlspecialchars($filterLabel); ?>">
                                                                    <input type="hidden" name="linked_product_id[]" value="<?php echo $selectedProductId; ?>">
                                                                    <input type="hidden" name="linked_quantity[]" value="<?php echo htmlspecialchars($rawQty); ?>">
                                                                    <div class="row g-3 align-items-center">
                                                                        <div class="col-md-7">
                                                                            <div class="fw-semibold"><?php echo htmlspecialchars($productTitle); ?></div>
                                                                            <?php if ($productCategory): ?>
                                                                                <div class="text-muted small">Category: <?php echo htmlspecialchars($productCategory); ?></div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="col-md-3">
                                                                            <div class="text-muted small">Qty per build</div>
                                                                            <div class="fw-semibold"><?php echo $formattedQty; ?></div>
                                                                        </div>
                                                                        <div class="col-md-2 text-end">
                                                                            <button type="button" class="btn btn-outline-danger btn-sm" data-remove-product-link-row title="Unlink product">
                                                                                <i class="fas fa-unlink me-1"></i> Remove
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <div class="alert alert-info mb-0 <?php echo $hasLinkedProducts ? 'd-none' : ''; ?>" data-no-product-links>
                                                            No products are currently tied to this per-piece item. Add links from the Products screen when you need to build new recipes.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-success" data-product-link-save>Save Links</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                $inventoryLinkModals[] = ob_get_clean();

                                ob_start();
                                ?>
                                    <div class="modal fade" id="editInventoryItem<?php echo $item['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <form method="POST" enctype="multipart/form-data">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Inventory Item</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="edit_inventory_item" value="1">
                                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Item Name</label>
                                                                <input type="text" name="item_name" class="form-control" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">SKU / Code</label>
                                                                <input type="text" name="item_sku" class="form-control" value="<?php echo htmlspecialchars($item['sku_code'] ?? ''); ?>">
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Category Label</label>
                                                                <input type="text" name="item_category" class="form-control" value="<?php echo htmlspecialchars($item['category'] ?? ''); ?>" placeholder="e.g., Stems">
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Unit</label>
                                                                <select name="item_unit" class="form-select">
                                                                    <?php foreach ($inventoryUnitsCatalog as $unitKey => $unitLabel): ?>
                                                                        <option value="<?php echo htmlspecialchars($unitKey); ?>" <?php echo ($item['unit'] ?? 'pc') === $unitKey ? 'selected' : ''; ?>>
                                                                            <?php echo htmlspecialchars($unitLabel); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Low Stock Threshold</label>
                                                                <input type="number" name="item_reorder_point" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($item['reorder_point']); ?>">
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                            <label class="form-label">Unit Cost (₱)</label>
                                                            <input type="number" name="item_unit_cost" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($item['unit_cost'] ?? ''); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Notes</label>
                                                        <textarea name="item_notes" class="form-control" rows="2" placeholder="Any sourcing or prep reminders."><?php echo htmlspecialchars($item['notes'] ?? ''); ?></textarea>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Item Image</label>
                                                        <input type="file" name="inventory_item_image" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                                        <div class="form-text">Leave blank to keep the current image. Current file: <?php echo htmlspecialchars($item['image_path'] ?? 'default'); ?></div>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="item_is_active" id="itemActive<?php echo $item['id']; ?>" <?php echo !empty($item['is_active']) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="itemActive<?php echo $item['id']; ?>">Active</label>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                $inventoryEditModals[] = ob_get_clean();
                                ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($inventoryLinkModals)): ?>
                <?php foreach ($inventoryLinkModals as $modalMarkup): ?>
                    <?php echo $modalMarkup; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($inventoryEditModals)): ?>
                <?php foreach ($inventoryEditModals as $modalMarkup): ?>
                    <?php echo $modalMarkup; ?>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
                <nav aria-label="Per-piece pagination">
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
                    <?php if (!$inventoryHasReorderPoint): ?>
                        <div class="inventory-empty">
                            <i class="fas fa-sliders-h"></i>
                            <p class="mb-0">Add the <strong>reorder_point</strong> column to show low-stock items.</p>
                        </div>
                    <?php elseif (!$lowStockItems): ?>
                        <div class="inventory-empty">
                            <i class="fas fa-check-circle"></i>
                            <p class="mb-0">All items are above their alert thresholds.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle inventory-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-center">On Hand</th>
                                        <th class="text-center">Alert Trigger</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStockItems as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td class="text-center fw-semibold text-warning"><?php echo $formatQuantity($item['current_quantity']); ?> <?php echo htmlspecialchars($item['unit'] ?? 'pc'); ?></td>
                                            <td class="text-center text-muted">≤ <?php echo $formatQuantity($item['reorder_point']); ?> <?php echo htmlspecialchars($item['unit'] ?? 'pc'); ?></td>
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
                    <?php if (!$outOfStockItems): ?>
                        <div class="inventory-empty">
                            <i class="fas fa-box"></i>
                            <p class="mb-0">No per-piece items are currently out of stock.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle inventory-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Category</th>
                                        <th class="text-center">Last Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($outOfStockItems as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo $item['category'] ? htmlspecialchars($item['category']) : '<span class="text-muted">Uncategorized</span>'; ?></td>
                                            <td class="text-center text-danger fw-semibold">0</td>
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
                                        <th>Item</th>
                                        <th class="text-center">Change</th>
                                        <th>New Qty</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($restockLogs as $log): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($log['item_name']); ?></td>
                                            <td class="text-center">
                                                <span class="log-pill bg-success bg-opacity-10 text-success">+<?php echo $formatQuantity($log['change_quantity']); ?> <?php echo htmlspecialchars($log['unit']); ?></span>
                                            </td>
                                            <td><?php echo $formatQuantity($log['resulting_quantity']); ?> <?php echo htmlspecialchars($log['unit']); ?></td>
                                            <td class="text-muted small"><?php echo htmlspecialchars($log['notes'] ?? ''); ?></td>
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
                                        <th>Item</th>
                                        <th class="text-center">Change</th>
                                        <th>New Qty</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($spoilageLogs as $log): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($log['item_name']); ?></td>
                                            <td class="text-center">
                                                <span class="log-pill bg-danger bg-opacity-10 text-danger"><?php echo $formatQuantity($log['change_quantity']); ?> <?php echo htmlspecialchars($log['unit']); ?></span>
                                            </td>
                                            <td><?php echo $formatQuantity($log['resulting_quantity']); ?> <?php echo htmlspecialchars($log['unit']); ?></td>
                                            <td class="text-muted small"><?php echo htmlspecialchars($log['notes'] ?? ''); ?></td>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    var updateModalState = function (modal) {
        if (!modal) {
            return;
        }
        var rowsWrapper = modal.querySelector('[data-product-link-rows]');
        var emptyState = modal.querySelector('[data-no-product-links]');
        var searchInput = modal.querySelector('[data-product-filter-input]');
        var triggerButton = modal.querySelector('[data-trigger-product-filter]');
        var clearButton = modal.querySelector('[data-clear-product-filter]');
        var rowCount = rowsWrapper ? rowsWrapper.querySelectorAll('[data-product-link-row]').length : 0;
        var hasRows = rowCount > 0;

        if (rowsWrapper) {
            rowsWrapper.classList.toggle('d-none', !hasRows);
        }
        if (emptyState) {
            emptyState.classList.toggle('d-none', hasRows);
        }

        [searchInput, triggerButton, clearButton].forEach(function (control) {
            if (!control) {
                return;
            }
            control.disabled = !hasRows;
            if (!hasRows && control === searchInput) {
                control.value = '';
            }
        });

        if (!hasRows && searchInput) {
            var feedback = modal.querySelector('[data-product-filter-feedback]');
            if (feedback) {
                feedback.classList.add('d-none');
                feedback.textContent = '';
            }
        }
    };

    var filterProductsInModal = function (input) {
        if (!input || input.disabled) {
            return;
        }
        var modal = input.closest('.product-link-modal');
        if (!modal) {
            return;
        }
        var query = input.value.trim().toLowerCase();
        var rows = modal.querySelectorAll('[data-product-link-row]');
        var matchCount = 0;

        rows.forEach(function (row) {
            var label = (row.getAttribute('data-product-name') || '').toLowerCase();
            var matches = !query || label.indexOf(query) !== -1;
            row.classList.toggle('d-none', !matches);
            if (matches) {
                matchCount++;
            }
        });

        var feedback = modal.querySelector('[data-product-filter-feedback]');
        if (!feedback) {
            return;
        }
        if (!query) {
            feedback.classList.add('d-none');
            feedback.textContent = '';
            return;
        }

        feedback.classList.remove('d-none');
        if (!rows.length) {
            feedback.textContent = 'No linked products available.';
        } else {
            feedback.textContent = matchCount
                ? matchCount + ' matching product' + (matchCount === 1 ? '' : 's')
                : 'No linked products match that search.';
        }
    };

    var scheduleProductFilter = function (input) {
        if (!input) {
            return;
        }
        clearTimeout(input._productFilterTimer);
        input._productFilterTimer = setTimeout(function () {
            filterProductsInModal(input);
        }, 200);
    };

    document.querySelectorAll('.product-link-modal').forEach(function (modal) {
        updateModalState(modal);
    });

    document.addEventListener('click', function (event) {
        var removeBtn = event.target.closest('[data-remove-product-link-row]');
        if (!removeBtn) {
            return;
        }
        var modal = removeBtn.closest('.product-link-modal');
        var row = removeBtn.closest('[data-product-link-row]');
        if (!modal || !row) {
            return;
        }
        row.remove();
        updateModalState(modal);
    });

    document.querySelectorAll('[data-product-filter-input]').forEach(function (input) {
        input.addEventListener('input', function () {
            scheduleProductFilter(input);
        });
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                filterProductsInModal(input);
            }
        });
    });

    document.querySelectorAll('[data-trigger-product-filter]').forEach(function (button) {
        button.addEventListener('click', function () {
            var wrapper = button.closest('.inventory-search');
            var input = wrapper ? wrapper.querySelector('[data-product-filter-input]') : null;
            if (!input) {
                return;
            }
            filterProductsInModal(input);
        });
    });

    document.querySelectorAll('[data-clear-product-filter]').forEach(function (button) {
        button.addEventListener('click', function () {
            var wrapper = button.closest('.inventory-search');
            var input = wrapper ? wrapper.querySelector('[data-product-filter-input]') : null;
            if (!input) {
                return;
            }
            input.value = '';
            filterProductsInModal(input);
            input.focus();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>

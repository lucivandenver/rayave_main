<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

ensureProductAvailabilityColumn();
ensureCustomizeProductColumns();

$category_id = isset($_GET['category']) ? $_GET['category'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$perPage = 6;
$currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$categories = getCategories();
$isLoggedIn = isLoggedIn();
$allowOutOfStockOrders = isOutOfStockOrderingEnabled();

$defaultCategoryId = null;
foreach ($categories as $cat) {
    if (isset($cat['name']) && strtolower(trim($cat['name'])) === 'arrangements') {
        $defaultCategoryId = (int) $cat['id'];
        break;
    }
}
if ($defaultCategoryId === null && !empty($categories)) {
    $defaultCategoryId = (int) $categories[0]['id'];
}

if ($category_id === null && $defaultCategoryId !== null) {
    $category_id = $defaultCategoryId;
}

$customizeCategoryId = null;
foreach ($categories as $cat) {
    if (isset($cat['name']) && strtolower(trim($cat['name'])) === 'customize') {
        $customizeCategoryId = $cat['id'];
        break;
    }
}

$isSearchMode = $search !== '';
$isCustomizeCategoryView = !$isSearchMode && $category_id && $customizeCategoryId && (int) $category_id === (int) $customizeCategoryId;
$customizeStepsData = [];
$customizeFilters = [];
$customizePreviewImage = 'assets/images/products/wrapper.png';

$conditions = [];
$params = [];

if ($category_id && !$isSearchMode) {
    $conditions[] = 'p.category_id = :category_id';
    $params['category_id'] = $category_id;
}

if ($search !== '') {
    $conditions[] = '(p.name LIKE :search_name OR p.description LIKE :search_description OR p.tags LIKE :search_tags)';
    $params['search_name'] = '%' . $search . '%';
    $params['search_description'] = '%' . $search . '%';
    $params['search_tags'] = '%' . $search . '%';
}

$whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$countSql = "SELECT COUNT(*) FROM products p $whereSql";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalProducts = (int) $countStmt->fetchColumn();
$applyPagination = !$isCustomizeCategoryView;
$totalPages = $applyPagination ? max(1, (int) ceil($totalProducts / $perPage)) : 1;

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}

$offset = $applyPagination ? ($currentPage - 1) * $perPage : 0;

$dataSql = "SELECT p.*, c.name as category_name FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            $whereSql 
            ORDER BY p.created_at DESC";

if ($applyPagination) {
    $dataSql .= " LIMIT :limit OFFSET :offset";
}

$dataStmt = $pdo->prepare($dataSql);
foreach ($params as $key => $value) {
    $dataStmt->bindValue(':' . $key, $value);
}
if ($applyPagination) {
    $dataStmt->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
}
$dataStmt->execute();

$products = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

if ($isCustomizeCategoryView) {
    $totalProducts = count($products);
    $totalPages = 1;
    $currentPage = 1;
}

if ($isCustomizeCategoryView && !empty($products)) {
    $customizeStepsData = [
        'container' => [
            'step' => 1,
            'title' => 'Container / Wrapper',
            'subtitle' => 'Pick a basket, pot, or floral wrapper as your bouquet base',
            'short_label' => 'Container',
            'keywords' => ['basket', 'pot', 'box', 'planter', 'handle', 'ceramic', 'gift', 'wrapper', 'wrap', 'sheet'],
            'items' => []
        ],
        'flowers' => [
            'step' => 2,
            'title' => 'Flowers & Colors',
            'subtitle' => 'Add your hero blooms onto the live bouquet canvas',
            'short_label' => 'Flowers',
            'keywords' => ['chrysanthemum', 'tulip', 'ranunculus', 'pom', 'daisy', 'rose', 'bloom'],
            'items' => []
        ],
        'fillers' => [
            'step' => 3,
            'title' => 'Leaves & Fillers',
            'subtitle' => 'Layer eucalyptus, ruscus, and baby\'s breath around your blooms',
            'short_label' => 'Fillers',
            'keywords' => ['eucalyptus', 'ruscus', 'filler', 'foliage', 'green', 'baby'],
            'items' => []
        ],
        'ribbon' => [
            'step' => 4,
            'title' => 'Ribbon (Optional)',
            'subtitle' => 'Add a silk ribbon or bow as the finishing touch',
            'short_label' => 'Ribbon',
            'keywords' => ['ribbon', 'bow', 'satin', 'silk'],
            'optional' => true,
            'items' => []
        ]
    ];
    $customizeStepMap = [
        1 => 'container',
        2 => 'flowers',
        3 => 'fillers',
        4 => 'ribbon'
    ];

    $customizeFilters = [
        'container' => [
            'pot' => [
                'label' => 'Pot',
                'keywords' => ['pot', 'planter', 'ceramic', 'vase', 'jar']
            ],
            'bouquet' => [
                'label' => 'Wrapper / Bouquet',
                'keywords' => ['bouquet', 'hand', 'tied', 'wrap', 'wrapper', 'paper', 'sheet']
            ],
            'basket' => [
                'label' => 'Basket / Box',
                'keywords' => ['basket', 'box', 'crate', 'hamper']
            ]
        ],
        'flowers' => [
            'warm' => [
                'label' => 'Warm',
                'keywords' => ['red', 'orange', 'yellow', 'gold', 'sun', 'marigold', 'scarlet', 'amber', 'coral']
            ],
            'cool' => [
                'label' => 'Cool',
                'keywords' => ['blue', 'teal', 'green', 'aqua', 'violet', 'purple', 'indigo', 'lavender', 'lilac']
            ],
            'pastel' => [
                'label' => 'Pastel',
                'keywords' => ['pastel', 'blush', 'peach', 'pink', 'soft', 'powder', 'baby', 'pale', 'light', 'mint']
            ],
            'neutral' => [
                'label' => 'White / Neutral',
                'keywords' => ['white', 'cream', 'ivory', 'beige', 'champagne', 'vanilla']
            ]
        ]
    ];

    foreach ($products as $product) {
        if (!empty($product['customize_archived'])) {
            continue;
        }

        $haystack = strtolower(($product['name'] ?? '') . ' ' . ($product['description'] ?? '') . ' ' . ($product['tags'] ?? ''));
        $matchedGroup = null;

        $customizeStepNumber = isset($product['customize_step']) ? (int) $product['customize_step'] : null;
        if ($customizeStepNumber && isset($customizeStepMap[$customizeStepNumber])) {
            $matchedGroup = $customizeStepMap[$customizeStepNumber];
        }

        if (!$matchedGroup) {
            foreach ($customizeStepsData as $groupKey => $groupMeta) {
                foreach ($groupMeta['keywords'] as $keyword) {
                    if (strpos($haystack, $keyword) !== false) {
                        $matchedGroup = $groupKey;
                        break 2;
                    }
                }
            }
        }

        if (!$matchedGroup) {
            $matchedGroup = 'flowers';
        }

        $customizeStepsData[$matchedGroup]['items'][] = $product;
    }
}

$baseQueryParams = [];
if ($category_id && !$isSearchMode) {
    $baseQueryParams['category'] = $category_id;
}
if ($search !== '') {
    $baseQueryParams['search'] = $search;
}

$buildPaginationLink = function ($pageNumber) use ($baseQueryParams) {
    $params = $baseQueryParams;
    $params['page'] = $pageNumber;
    $query = http_build_query($params);
    return $query ? 'products.php?' . $query : 'products.php';
};
?>

<?php include 'includes/header.php'; ?>

<?php if ($isCustomizeCategoryView): ?>
    <link rel="stylesheet" href="assets/css/bouquet-canvas.css">
<?php endif; ?>

<div class="container py-5">
    <?php if ($isCustomizeCategoryView): ?>
        <!-- Compact Category Navigation Bar so the Live Bouquet Studio gets full width -->
        <div class="bouquet-category-bar">
            <span class="bouquet-category-bar-label"><i class="fas fa-th-large me-1"></i>Categories:</span>
            <?php foreach ($categories as $cat): ?>
                <a href="products.php?category=<?php echo $cat['id']; ?>"
                    class="bouquet-category-pill <?php echo $category_id == $cat['id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($products)): ?>
            <div class="alert alert-info">No customizable components found for this category.</div>
        <?php else: ?>
            <section data-bouquet-studio
                data-is-auth="<?php echo $isLoggedIn ? '1' : '0'; ?>"
                data-allow-preorder="<?php echo $allowOutOfStockOrders ? '1' : '0'; ?>"
                data-required-steps="container,flowers,fillers">

                <div class="bouquet-studio-header">
                    <div>
                        <div class="bouquet-studio-kicker"><i class="fas fa-palette me-1"></i>Live Bouquet Studio</div>
                        <h3 class="mb-1 fw-bold">Design Your Custom Bouquet</h3>
                        <p class="text-muted mb-0 small">Pick items from any tab on the left—they drop straight onto the live canvas where you can drag, resize, rotate, and layer your arrangement.</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-outline-success btn-sm" data-studio-reset>
                            <i class="fas fa-redo me-1"></i>Start Over
                        </button>
                    </div>
                </div>

                <div class="bouquet-studio-grid">
                    <!-- LEFT: Free-Switching Component Picker -->
                    <div class="bouquet-picker-panel">
                        <div class="bouquet-step-tabs" role="tablist">
                            <?php foreach (['container', 'flowers', 'fillers', 'ribbon'] as $idx => $stepKey):
                                $step = $customizeStepsData[$stepKey] ?? null;
                                if (!$step) continue;
                            ?>
                                <button type="button"
                                    class="bouquet-step-tab <?php echo $idx === 0 ? 'is-active' : ''; ?>"
                                    data-step-tab="<?php echo $stepKey; ?>"
                                    role="tab">
                                    <span class="bouquet-step-tab-num">Step <?php echo (int) $step['step']; ?><?php echo !empty($step['optional']) ? ' · Opt' : ''; ?></span>
                                    <span class="bouquet-step-tab-title"><?php echo htmlspecialchars($step['short_label']); ?></span>
                                    <span class="bouquet-step-tab-count is-empty" data-step-tab-count="<?php echo $stepKey; ?>">0</span>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <?php foreach (['container', 'flowers', 'fillers', 'ribbon'] as $idx => $stepKey):
                            $step = $customizeStepsData[$stepKey] ?? null;
                            if (!$step) continue;
                            $hasFilters = isset($customizeFilters[$stepKey]) && !empty($customizeFilters[$stepKey]);
                        ?>
                            <div class="bouquet-step-pane <?php echo $idx === 0 ? 'is-active' : ''; ?>" data-step-pane="<?php echo $stepKey; ?>">
                                <div class="bouquet-step-heading">
                                    <div>
                                        <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($step['title']); ?></h5>
                                        <small class="text-muted"><?php echo htmlspecialchars($step['subtitle']); ?></small>
                                    </div>
                                </div>

                                <?php if (!empty($step['items']) && $hasFilters): ?>
                                    <div class="bouquet-filter-bar">
                                        <button type="button" class="bouquet-filter-btn active"
                                            data-filter-step="<?php echo $stepKey; ?>"
                                            data-filter-value="all">All</button>
                                        <?php foreach ($customizeFilters[$stepKey] as $filterKey => $filterMeta): ?>
                                            <button type="button" class="bouquet-filter-btn"
                                                data-filter-step="<?php echo $stepKey; ?>"
                                                data-filter-value="<?php echo htmlspecialchars($filterKey); ?>">
                                                <?php echo htmlspecialchars($filterMeta['label']); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (empty($step['items'])): ?>
                                    <div class="alert alert-warning mb-0">No options available for this tab yet.</div>
                                <?php else: ?>
                                    <div class="bouquet-items-grid">
                                        <?php foreach ($step['items'] as $product):
                                            $imagePath = 'assets/images/products/' . ($product['image'] ?? '');
                                            if (empty($product['image']) || !file_exists($imagePath)) {
                                                $imagePath = 'assets/images/products/wrapper.png';
                                            }
                                            $canvasImagePath = '';
                                            $hasExplicitCutout = false;
                                            if (!empty($product['canvas_image'])) {
                                                $candidateCutout = 'assets/images/products/' . $product['canvas_image'];
                                                if (file_exists($candidateCutout)) {
                                                    $canvasImagePath = $candidateCutout;
                                                    $hasExplicitCutout = true;
                                                }
                                            }
                                            $stockCount = isset($product['stock_quantity']) ? (int) $product['stock_quantity'] : 0;
                                            $isZeroStock = $stockCount <= 0;
                                            $isPreorderOnly = $allowOutOfStockOrders && $isZeroStock;
                                            $isOutOfStock = !$allowOutOfStockOrders && $isZeroStock;
                                            $isManuallyUnavailable = ($product['availability_status'] ?? 'available') !== 'available';
                                            $isUnavailable = $isOutOfStock || $isManuallyUnavailable;

                                            $filterValue = '';
                                            if ($hasFilters) {
                                                $haystack = strtolower(($product['name'] ?? '') . ' ' . ($product['description'] ?? '') . ' ' . ($product['tags'] ?? ''));
                                                foreach ($customizeFilters[$stepKey] as $fKey => $fMeta) {
                                                    foreach ($fMeta['keywords'] as $kw) {
                                                        if ($kw !== '' && strpos($haystack, $kw) !== false) {
                                                            $filterValue = $fKey;
                                                            break 2;
                                                        }
                                                    }
                                                }
                                            }
                                        ?>
                                            <div class="bouquet-item-card <?php echo $isUnavailable ? 'is-unavailable' : ''; ?>"
                                                data-bouquet-item-card
                                                data-product-id="<?php echo (int) $product['id']; ?>"
                                                data-step="<?php echo $stepKey; ?>"
                                                data-filter-value="<?php echo htmlspecialchars($filterValue); ?>"
                                                data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                data-price="<?php echo number_format((float) $product['price'], 2, '.', ''); ?>"
                                                data-image-src="<?php echo htmlspecialchars($imagePath); ?>"
                                                data-canvas-image-src="<?php echo htmlspecialchars($canvasImagePath ?: $imagePath); ?>"
                                                data-has-cutout="<?php echo $hasExplicitCutout ? '1' : '0'; ?>"
                                                data-stock="<?php echo $stockCount; ?>"
                                                data-unavailable="<?php echo $isUnavailable ? '1' : '0'; ?>">

                                                <div class="bouquet-item-thumb">
                                                    <img src="<?php echo htmlspecialchars($canvasImagePath ?: $imagePath); ?>"
                                                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                        onerror="this.onerror=null;this.src='assets/images/products/wrapper.png';">
                                                    <?php if ($isOutOfStock): ?>
                                                        <span class="badge bg-danger bouquet-item-badge">Out of Stock</span>
                                                    <?php elseif ($isPreorderOnly): ?>
                                                        <span class="badge bg-warning text-dark bouquet-item-badge">Preorder</span>
                                                    <?php elseif ($isManuallyUnavailable): ?>
                                                        <span class="badge bg-secondary bouquet-item-badge">Unavailable</span>
                                                    <?php endif; ?>
                                                    <span class="bouquet-item-canvas-badge d-none" data-item-canvas-badge>×0 on canvas</span>
                                                </div>

                                                <div class="bouquet-item-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                                <div class="bouquet-item-desc"><?php echo htmlspecialchars($product['description'] ?? ''); ?></div>

                                                <div class="bouquet-item-footer">
                                                    <div class="bouquet-item-price-row">
                                                        <span class="bouquet-item-price">₱<?php echo number_format((float) $product['price'], 2); ?></span>
                                                        <a href="product-detail.php?id=<?php echo (int) $product['id']; ?>" class="bouquet-item-details-link" target="_blank" rel="noopener">Details</a>
                                                    </div>

                                                    <div class="bouquet-item-actions">
                                                        <button type="button"
                                                            class="bouquet-add-btn"
                                                            data-item-add-btn
                                                            <?php echo $isUnavailable ? 'disabled' : ''; ?>>
                                                            <i class="fas fa-plus-circle"></i>
                                                            <?php echo $isUnavailable ? 'Unavailable' : 'Add to Canvas'; ?>
                                                        </button>
                                                        <div class="bouquet-stepper d-none" data-item-stepper>
                                                            <button type="button" class="bouquet-stepper-btn" data-item-minus aria-label="Remove one from canvas">−</button>
                                                            <span class="bouquet-stepper-count" data-item-stepper-count>1</span>
                                                            <button type="button" class="bouquet-stepper-btn" data-item-plus aria-label="Add another to canvas">+</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- RIGHT: Sticky Live Bouquet Canvas -->
                    <div class="bouquet-canvas-panel">
                        <div class="bouquet-canvas-topbar">
                            <div>
                                <h5 class="mb-0 fw-bold text-success"><i class="fas fa-spa me-1"></i>Live Bouquet Canvas</h5>
                                <small class="text-muted" data-canvas-item-count>0 pieces on canvas</small>
                            </div>
                            <div class="bouquet-bg-swatches" title="Canvas backdrop">
                                <button type="button" class="bouquet-bg-swatch is-active" data-bg-swatch="#ffffff" style="background:#ffffff;" aria-label="White studio background"></button>
                                <button type="button" class="bouquet-bg-swatch" data-bg-swatch="#fdf9f1" style="background:#fdf9f1;" aria-label="Warm cream background"></button>
                                <button type="button" class="bouquet-bg-swatch" data-bg-swatch="#f1faf4" style="background:#f1faf4;" aria-label="Soft sage background"></button>
                                <button type="button" class="bouquet-bg-swatch" data-bg-swatch="#efe4d4" style="background:#efe4d4;" aria-label="Kraft paper background"></button>
                            </div>
                        </div>

                        <!-- Canva-Style Toolbar -->
                        <div class="bouquet-toolbar" role="toolbar" aria-label="Canvas controls">
                            <button type="button" class="bouquet-tool-btn" data-tool="forward" title="Bring Forward" disabled>
                                <i class="fas fa-arrow-up"></i>
                            </button>
                            <button type="button" class="bouquet-tool-btn" data-tool="backward" title="Send Backward" disabled>
                                <i class="fas fa-arrow-down"></i>
                            </button>
                            <button type="button" class="bouquet-tool-btn" data-tool="flip" title="Flip Horizontally" disabled>
                                <i class="fas fa-arrows-alt-h"></i>
                            </button>
                            <button type="button" class="bouquet-tool-btn" data-tool="duplicate" title="Duplicate Selected (+1)" disabled>
                                <i class="fas fa-clone"></i>
                            </button>
                            <button type="button" class="bouquet-tool-btn bouquet-tool-btn--danger" data-tool="delete" title="Remove Selected" disabled>
                                <i class="fas fa-trash-alt"></i>
                            </button>
                            <span class="bouquet-tool-divider" aria-hidden="true"></span>
                            <button type="button" class="bouquet-tool-btn" data-tool="undo" title="Undo (Ctrl+Z)" disabled>
                                <i class="fas fa-undo"></i>
                            </button>
                            <button type="button" class="bouquet-tool-btn" data-tool="redo" title="Redo (Ctrl+Y)" disabled>
                                <i class="fas fa-redo"></i>
                            </button>
                            <span class="bouquet-tool-divider" aria-hidden="true"></span>
                            <button type="button" class="bouquet-tool-btn bouquet-tool-btn--accent" data-tool="auto-arrange" title="Auto-Arrange Bouquet" disabled>
                                <i class="fas fa-magic"></i> Auto-Arrange
                            </button>
                            <button type="button" class="bouquet-tool-btn bouquet-tool-btn--danger ms-auto" data-tool="clear" title="Clear Canvas" disabled>
                                <i class="fas fa-eraser"></i>
                            </button>
                        </div>

                        <!-- Interactive Fabric.js Canvas Stage -->
                        <div class="bouquet-stage-wrap" data-stage-wrap>
                            <canvas id="bouquetLiveCanvas" width="480" height="500"></canvas>
                            <div class="bouquet-stage-empty" data-stage-empty>
                                <div class="bouquet-stage-empty-icon"><i class="fas fa-seedling"></i></div>
                                <h6 class="fw-bold mb-1">Your bouquet canvas is ready</h6>
                                <p class="small mb-0">Click <strong>Add to Canvas</strong> on any item to place it here, then drag, resize, rotate, or hit <strong>Auto-Arrange</strong>.</p>
                            </div>
                        </div>

                        <div class="bouquet-stage-hint">
                            <span><i class="fas fa-hand-pointer me-1 text-success"></i>Drag to move · Corners to resize · Top handle to rotate</span>
                        </div>

                        <!-- Required Step Checklist Pills -->
                        <div class="bouquet-checklist">
                            <button type="button" class="bouquet-check-pill" data-check-step="container">
                                <i class="far fa-circle"></i> Container *
                            </button>
                            <button type="button" class="bouquet-check-pill" data-check-step="flowers">
                                <i class="far fa-circle"></i> Flowers *
                            </button>
                            <button type="button" class="bouquet-check-pill" data-check-step="fillers">
                                <i class="far fa-circle"></i> Fillers *
                            </button>
                            <button type="button" class="bouquet-check-pill" data-check-step="ribbon">
                                <i class="far fa-circle"></i> Ribbon
                            </button>
                        </div>

                        <!-- Live Summary + Florist Note + Submit -->
                        <div class="bouquet-summary-box">
                            <div class="bouquet-summary-items" data-canvas-summary-list></div>

                            <div class="mb-3">
                                <label for="bouquetDesignNotes" class="form-label small fw-semibold text-muted mb-1">
                                    Optional Note for Florist
                                </label>
                                <textarea id="bouquetDesignNotes"
                                    class="form-control bouquet-notes-input"
                                    rows="2"
                                    maxlength="600"
                                    placeholder="e.g., Make the pink roses stand slightly taller in the center..."
                                    data-design-notes></textarea>
                            </div>

                            <div class="bouquet-total-bar">
                                <div>
                                    <div class="small text-muted text-uppercase fw-bold">Estimated Total</div>
                                    <small class="text-muted">Syncs live with your canvas</small>
                                </div>
                                <div class="bouquet-total-amount" data-canvas-total>₱0.00</div>
                            </div>

                            <?php if ($isLoggedIn): ?>
                                <button type="button" class="btn btn-success bouquet-submit-btn" data-studio-submit>
                                    <i class="fas fa-shopping-bag me-2"></i>Add Custom Bouquet to Cart
                                </button>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-outline-success bouquet-submit-btn">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login to Save &amp; Order Bouquet
                                </a>
                            <?php endif; ?>

                            <div class="alert d-none mt-3 mb-0" data-studio-feedback></div>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    <?php else: ?>
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Categories</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($categories as $cat): ?>
                            <a href="products.php?category=<?php echo $cat['id']; ?>" class="list-group-item list-group-item-action 
                           <?php echo $category_id == $cat['id'] ? 'active' : ''; ?>">
                                <?php echo $cat['name']; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Products -->
            <div class="col-lg-9">
                <!-- Search Bar -->
                <form method="GET" class="mb-4">
                    <?php if ($category_id): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars((string) $category_id); ?>">
                    <?php endif; ?>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search products..."
                            value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-success" type="submit">Search</button>
                    </div>
                </form>

                <div class="row">
                    <?php if (empty($products)): ?>
                        <div class="col-12 text-center">
                            <p class="lead">No products found.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $product):
                            $stockLevel = isset($product['stock_quantity']) ? (int) $product['stock_quantity'] : 0;
                            $isZeroStock = $stockLevel <= 0;
                            $isPreorderOnly = $allowOutOfStockOrders && $isZeroStock;
                            $isManuallyUnavailable = ($product['availability_status'] ?? 'available') !== 'available';
                            $isOutOfStock = !$allowOutOfStockOrders && $isZeroStock;
                            $statusLabel = $isPreorderOnly ? 'Preorder' : ($isOutOfStock ? 'Out of Stock' : ($isManuallyUnavailable ? 'Unavailable' : ''));
                            $statusClass = $isPreorderOnly ? 'bg-warning text-dark' : ($isOutOfStock ? 'bg-danger' : 'bg-secondary');
                            $quickAddMaxAttr = $allowOutOfStockOrders ? '' : 'max="' . $stockLevel . '"';
                        ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card product-card h-100">
                                    <img src="assets/images/products/<?php echo htmlspecialchars($product['image']); ?>"
                                        class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>"
                                        style="height: 200px; object-fit: cover;"
                                        onerror="this.onerror=null;this.src='assets/images/products/wrapper.png';">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $product['name']; ?></h5>
                                        <p class="card-text text-muted"><?php echo $product['category_name']; ?></p>
                                        <?php if ($statusLabel): ?>
                                            <span class="badge <?php echo $statusClass; ?> mb-2"><?php echo $statusLabel; ?></span>
                                        <?php endif; ?>
                                        <p class="card-text">₱<?php echo $product['price']; ?></p>
                                        <p class="card-text small"><?php echo substr($product['description'], 0, 100); ?>...</p>

                                        <?php if ($isLoggedIn && !$isOutOfStock && !$isManuallyUnavailable): ?>
                                            <form method="POST" action="add-to-cart.php">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <div class="input-group mb-2">
                                                    <input type="number" name="quantity" class="form-control" value="1" min="1"
                                                        <?php echo $quickAddMaxAttr; ?>>
                                                    <button type="submit" class="btn btn-success">Add to Cart</button>
                                                </div>
                                            </form>
                                        <?php elseif ($isOutOfStock || $isManuallyUnavailable): ?>
                                            <div class="alert alert-light border <?php echo $isOutOfStock ? 'border-warning text-warning' : 'border-secondary text-muted'; ?> py-2">
                                                <?php echo $isOutOfStock ? 'Currently out of stock' : 'Temporarily unavailable'; ?>
                                            </div>
                                        <?php endif; ?>

                                        <a href="product-detail.php?id=<?php echo $product['id']; ?>"
                                            class="btn btn-outline-success w-100">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Products pagination">
                        <ul class="pagination justify-content-center mt-3">
                            <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $currentPage <= 1 ? '#' : $buildPaginationLink($currentPage - 1); ?>" aria-disabled="<?php echo $currentPage <= 1 ? 'true' : 'false'; ?>">Previous</a>
                            </li>
                            <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                                <li class="page-item <?php echo $page === $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo $buildPaginationLink($page); ?>"><?php echo $page; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $currentPage >= $totalPages ? '#' : $buildPaginationLink($currentPage + 1); ?>" aria-disabled="<?php echo $currentPage >= $totalPages ? 'true' : 'false'; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($isCustomizeCategoryView && !empty($products)): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
    <script src="assets/js/bouquet-canvas.js"></script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

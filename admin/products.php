<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

ensureInventoryCoreTables();
ensureCustomizeProductColumns();
ensureCustomizeFiltersTable();

$customizeStepDefinitions = getCustomizeStepDefinitions();
$customizeStepKeyMap = [];
$customizeStepKeyToNumber = [];
foreach ($customizeStepDefinitions as $stepNumber => $meta) {
    $customizeStepKeyMap[$stepNumber] = $meta['key'];
    $customizeStepKeyToNumber[$meta['key']] = $stepNumber;
}

$customizeFiltersByStep = getCustomizeFiltersGrouped();
$customizeFiltersById = [];
foreach ($customizeFiltersByStep as $stepKey => $filters) {
    foreach ($filters as $filter) {
        $customizeFiltersById[$filter['id']] = $filter;
    }
}

$sanitizeCustomizeStep = function ($value) use ($customizeStepDefinitions) {
    $step = (int) ($value ?? 0);
    return array_key_exists($step, $customizeStepDefinitions) ? $step : null;
};

$sanitizeCustomizeFilter = function ($value, $step) use ($customizeStepKeyMap, $customizeFiltersByStep) {
    $step = (int) $step;
    if (!$step || !isset($customizeStepKeyMap[$step])) {
        return null;
    }

    $stepKey = $customizeStepKeyMap[$step];
    if (empty($customizeFiltersByStep[$stepKey])) {
        return null;
    }

    $filterId = (int) ($value ?? 0);
    foreach ($customizeFiltersByStep[$stepKey] as $filter) {
        if ((int) $filter['id'] === $filterId) {
            return $filterId;
        }
    }

    return null;
};

$inventoryItems = getInventoryItems(['include_inactive' => true]);
$hasInventoryItems = !empty($inventoryItems);
$inventoryUnitsCatalog = getInventoryUnitsCatalog();

$processImageUpload = function ($fallbackImage = 'default.jpg') {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] === 4) {
        return $fallbackImage;
    }

    $uploadErrors = [
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
        3 => 'The uploaded file was only partially uploaded.',
        6 => 'Missing a temporary folder.',
        7 => 'Failed to write file to disk.',
        8 => 'A PHP extension stopped the file upload.'
    ];

    if ($_FILES['image']['error'] !== 0) {
        $errorMessage = $uploadErrors[$_FILES['image']['error']] ?? 'Unknown upload error.';
        $_SESSION['error'] = 'Image upload failed: ' . $errorMessage;
        redirect('products.php');
    }

    $uploadDir = '../assets/images/products/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        $_SESSION['error'] = 'Failed to create upload directory.';
        redirect('products.php');
    }

    if (!is_writable($uploadDir)) {
        $_SESSION['error'] = 'Upload directory is not writable. Please check permissions.';
        redirect('products.php');
    }

    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = $_FILES['image']['type'];
    $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

    if (!in_array($fileType, $allowedTypes, true) || !in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        $_SESSION['error'] = 'Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.';
        redirect('products.php');
    }

    $maxFileSize = 5 * 1024 * 1024;
    if ($_FILES['image']['size'] > $maxFileSize) {
        $_SESSION['error'] = 'File too large. Maximum size allowed is 5MB.';
        redirect('products.php');
    }

    $originalName = basename($_FILES['image']['name']);
    $rawBase = pathinfo($originalName, PATHINFO_FILENAME);
    $safeBase = preg_replace('/[^a-zA-Z0-9-]+/', '_', $rawBase);
    $safeBase = trim(preg_replace('/_+/', '_', $safeBase), '_');
    if ($safeBase === '') {
        $safeBase = 'product';
    }
    $safeBase = substr($safeBase, 0, 40);
    $imageName = time() . '_' . $safeBase . '.' . $fileExtension;
    $targetFile = $uploadDir . $imageName;

    if (file_exists($targetFile)) {
        $imageName = time() . '_' . uniqid() . '_' . $safeBase . '.' . $fileExtension;
        $targetFile = $uploadDir . $imageName;
    }

    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
        if (!file_exists($targetFile) || !getimagesize($targetFile)) {
            $_SESSION['error'] = 'Uploaded file is not a valid image.';
            if (file_exists($targetFile)) {
                unlink($targetFile);
            }
            return $fallbackImage;
        }

        return $imageName;
    }

    $_SESSION['error'] = 'Sorry, there was an error uploading your file. Please try again.';
    return $fallbackImage;
};

$processCanvasImageUpload = function ($fallbackImage = null) {
    if (!isset($_FILES['canvas_image']) || $_FILES['canvas_image']['error'] === 4) {
        return $fallbackImage;
    }

    if ($_FILES['canvas_image']['error'] !== 0) {
        $_SESSION['error'] = 'Canvas cutout upload failed (error code ' . (int) $_FILES['canvas_image']['error'] . ').';
        return $fallbackImage;
    }

    $uploadDir = '../assets/images/products/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0775, true);
    }

    $allowedExts = ['png', 'webp', 'jpg', 'jpeg', 'gif'];
    $ext = strtolower(pathinfo($_FILES['canvas_image']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
        $_SESSION['error'] = 'Invalid canvas cutout file type. Allowed: PNG, JPG, JPEG, WebP, GIF.';
        return $fallbackImage;
    }

    if ($_FILES['canvas_image']['size'] > 10 * 1024 * 1024) {
        $_SESSION['error'] = 'Canvas cutout image is too large (max 10MB).';
        return $fallbackImage;
    }

    $rawBase = pathinfo($_FILES['canvas_image']['name'], PATHINFO_FILENAME);
    $safeBase = preg_replace('/[^a-zA-Z0-9-]+/', '_', $rawBase);
    $safeBase = trim(preg_replace('/_+/', '_', $safeBase), '_');
    if ($safeBase === '') {
        $safeBase = 'item';
    }
    $safeBase = substr($safeBase, 0, 40);
    $fileName = 'cutout_' . time() . '_' . uniqid() . '_' . $safeBase . '.png';
    $targetFile = $uploadDir . $fileName;

    $tmpPath = $_FILES['canvas_image']['tmp_name'];
    $imgInfo = @getimagesize($tmpPath);
    if (!$imgInfo) {
        $_SESSION['error'] = 'Uploaded canvas cutout is not a valid image.';
        return $fallbackImage;
    }

    // If already PNG, move directly (preserves full alpha transparency from browser AI cutout)
    if (($imgInfo[2] ?? 0) === IMAGETYPE_PNG) {
        if (move_uploaded_file($tmpPath, $targetFile)) {
            return $fileName;
        }
        return $fallbackImage;
    }

    // Convert non-PNG uploads to PNG via GD if available, otherwise store with original extension
    if (function_exists('imagecreatefromstring') && function_exists('imagepng')) {
        $raw = @file_get_contents($tmpPath);
        $gdImg = $raw !== false ? @imagecreatefromstring($raw) : false;
        if ($gdImg !== false) {
            imagesavealpha($gdImg, true);
            $saved = @imagepng($gdImg, $targetFile, 6);
            imagedestroy($gdImg);
            if ($saved) {
                return $fileName;
            }
        }
    }

    $fallbackFileName = 'cutout_' . time() . '_' . uniqid() . '_' . $safeBase . '.' . $ext;
    if (move_uploaded_file($tmpPath, $uploadDir . $fallbackFileName)) {
        return $fallbackFileName;
    }

    return $fallbackImage;
};

$processPerPieceImageUpload = function () {
    if (!isset($_FILES['per_piece_image']) || $_FILES['per_piece_image']['error'] === 4) {
        return null;
    }

    $uploadErrors = [
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
        3 => 'The uploaded file was only partially uploaded.',
        6 => 'Missing a temporary folder.',
        7 => 'Failed to write file to disk.',
        8 => 'A PHP extension stopped the file upload.'
    ];

    if ($_FILES['per_piece_image']['error'] !== 0) {
        $errorMessage = $uploadErrors[$_FILES['per_piece_image']['error']] ?? 'Unknown upload error.';
        $_SESSION['error'] = 'Per-piece image upload failed: ' . $errorMessage;
        redirect('products.php');
    }

    $uploadDir = '../assets/images/inventory/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        $_SESSION['error'] = 'Failed to create per-piece upload directory.';
        redirect('products.php');
    }

    if (!is_writable($uploadDir)) {
        $_SESSION['error'] = 'Per-piece upload directory is not writable.';
        redirect('products.php');
    }

    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = $_FILES['per_piece_image']['type'];
    $fileExtension = strtolower(pathinfo($_FILES['per_piece_image']['name'], PATHINFO_EXTENSION));

    if (!in_array($fileType, $allowedTypes, true) || !in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        $_SESSION['error'] = 'Invalid per-piece image. Only JPG, PNG, GIF, and WebP are allowed.';
        redirect('products.php');
    }

    $maxFileSize = 5 * 1024 * 1024;
    if ($_FILES['per_piece_image']['size'] > $maxFileSize) {
        $_SESSION['error'] = 'Per-piece image is too large. Maximum size is 5MB.';
        redirect('products.php');
    }

    $originalName = basename($_FILES['per_piece_image']['name']);
    $safeName = preg_replace("/[^a-zA-Z0-9\._-]/", '_', $originalName);
    $imageName = time() . '_' . $safeName;
    $targetFile = $uploadDir . $imageName;

    if (file_exists($targetFile)) {
        $imageName = time() . '_' . uniqid() . '_' . $safeName;
        $targetFile = $uploadDir . $imageName;
    }

    if (!move_uploaded_file($_FILES['per_piece_image']['tmp_name'], $targetFile) || !getimagesize($targetFile)) {
        if (file_exists($targetFile)) {
            unlink($targetFile);
        }
        $_SESSION['error'] = 'Unable to save the per-piece image. Please try again.';
        redirect('products.php');
    }

    return 'inventory/' . $imageName;
};

if (isset($_POST['add_per_piece_product'])) {
    $perPieceData = [
        'name' => trim($_POST['per_piece_name'] ?? ''),
        'sku_code' => trim($_POST['per_piece_sku'] ?? ''),
        'category' => trim($_POST['per_piece_category'] ?? ''),
        'unit' => $_POST['per_piece_unit'] ?? 'pc',
        'reorder_point' => isset($_POST['per_piece_reorder_point']) ? (float) $_POST['per_piece_reorder_point'] : 0,
        'unit_cost' => ($_POST['per_piece_unit_cost'] ?? '') === '' ? '' : (float) ($_POST['per_piece_unit_cost'] ?? 0),
        'notes' => trim($_POST['per_piece_notes'] ?? ''),
        'initial_quantity' => isset($_POST['per_piece_initial_quantity']) ? (float) $_POST['per_piece_initial_quantity'] : 0,
        'is_active' => 1
    ];

    $perPieceImagePath = $processPerPieceImageUpload();
    if ($perPieceImagePath !== null) {
        $perPieceData['image_path'] = $perPieceImagePath;
    }

    if (!array_key_exists($perPieceData['unit'], $inventoryUnitsCatalog)) {
        $perPieceData['unit'] = 'pc';
    }

    if ($perPieceData['name'] === '') {
        $_SESSION['error'] = 'Per-piece items need a name.';
        redirect('products.php');
    }

    if (saveInventoryItem($perPieceData)) {
        $_SESSION['success'] = 'Per-piece inventory product added!';
    } else {
        if (empty($_SESSION['error'])) {
            $_SESSION['error'] = 'Unable to save the per-piece entry right now.';
        }
    }

    redirect('products.php');
}

if (isset($_POST['save_inventory_components'])) {
    $productId = (int) ($_POST['product_id'] ?? 0);
    if ($productId <= 0) {
        $_SESSION['error'] = 'Invalid product reference.';
        redirect('products.php');
    }

    $itemIds = $_POST['component_item_id'] ?? [];
    $quantities = $_POST['component_quantity'] ?? [];
    $components = [];

    foreach ($itemIds as $index => $itemIdRaw) {
        $itemId = (int) $itemIdRaw;
        $quantity = isset($quantities[$index]) ? (float) $quantities[$index] : 0;
        if ($itemId > 0 && $quantity > 0) {
            $components[] = ['item_id' => $itemId, 'quantity' => $quantity];
        }
    }

    if (saveProductInventoryComponents($productId, $components)) {
        $_SESSION['success'] = 'Inventory components updated!';
    } else {
        $_SESSION['error'] = 'Failed to save inventory components.';
    }

    $returnQuery = trim($_POST['return_to_query'] ?? '');
    $redirectTarget = $returnQuery !== '' ? 'products.php?' . $returnQuery : 'products.php';
    redirect($redirectTarget);
}

if (isset($_POST['add_step_filter'])) {
    $stepKey = trim($_POST['filter_step_key'] ?? '');
    $label = trim($_POST['filter_label'] ?? '');
    $order = max(1, (int) ($_POST['filter_order'] ?? 1));

    if ($stepKey === '' || !isset($customizeStepKeyToNumber[$stepKey])) {
        $_SESSION['error'] = 'Invalid step selection.';
        redirect('products.php');
    }

    if ($label === '') {
        $_SESSION['error'] = 'Category label is required.';
        redirect('products.php');
    }

    $slug = generateSlug($label);

    try {
        $stmt = $pdo->prepare("INSERT INTO customize_step_filters (step_key, label, slug, display_order) VALUES (?, ?, ?, ?)");
        $stmt->execute([$stepKey, $label, $slug, $order]);
        $_SESSION['success'] = 'Category added.';
    } catch (PDOException $e) {
        $message = $e->getCode() === '23000' ? 'A category with that slug already exists for this step.' : 'Unable to add category right now.';
        $_SESSION['error'] = $message;
    }

    redirect('products.php');
}

if (isset($_POST['edit_step_filter'])) {
    $filterId = (int) ($_POST['filter_id'] ?? 0);
    $stepKey = trim($_POST['filter_step_key'] ?? '');
    $label = trim($_POST['filter_label'] ?? '');
    $order = max(1, (int) ($_POST['filter_order'] ?? 1));

    if ($filterId <= 0 || $stepKey === '' || !isset($customizeStepKeyToNumber[$stepKey])) {
        $_SESSION['error'] = 'Invalid category update request.';
        redirect('products.php');
    }

    if ($label === '') {
        $_SESSION['error'] = 'Category label is required.';
        redirect('products.php');
    }

    $slug = generateSlug($label);

    try {
        $stmt = $pdo->prepare("UPDATE customize_step_filters SET step_key = ?, label = ?, slug = ?, display_order = ? WHERE id = ?");
        $stmt->execute([$stepKey, $label, $slug, $order, $filterId]);
        $_SESSION['success'] = 'Category updated.';
    } catch (PDOException $e) {
        $message = $e->getCode() === '23000' ? 'Another category already uses that slug in this step.' : 'Unable to update category right now.';
        $_SESSION['error'] = $message;
    }

    redirect('products.php');
}

if (isset($_POST['delete_step_filter'])) {
    $filterId = (int) ($_POST['filter_id'] ?? 0);

    if ($filterId <= 0) {
        $_SESSION['error'] = 'Invalid category reference.';
        redirect('products.php');
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM customize_step_filters WHERE id = ?");
        $stmt->execute([$filterId]);
        $pdo->prepare("UPDATE products SET customize_filter_id = NULL WHERE customize_filter_id = ?")->execute([$filterId]);
        $_SESSION['success'] = 'Category deleted.';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Unable to delete category right now.';
    }

    redirect('products.php');
}

if (isset($_POST['edit_product'])) {
    $product_id = (int) ($_POST['product_id'] ?? 0);
    if ($product_id <= 0) {
        $_SESSION['error'] = 'Invalid product reference.';
        redirect('products.php');
    }

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$product_id]);
    $existingProduct = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingProduct) {
        $_SESSION['error'] = 'Product not found.';
        redirect('products.php');
    }

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $weight = isset($_POST['weight']) && $_POST['weight'] !== '' ? (float) $_POST['weight'] : null;
    $dimensions = trim($_POST['dimensions'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $customize_step = $sanitizeCustomizeStep($_POST['customize_step'] ?? null);
    $customize_filter_id = $sanitizeCustomizeFilter($_POST['customize_filter_id'] ?? null, $customize_step);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    if ($name === '' || $description === '' || $category_id <= 0) {
        $_SESSION['error'] = 'Name, description, and category are required.';
        redirect('products.php');
    }

    $image = $processImageUpload($existingProduct['image'] ?? 'default.jpg');
    $canvas_image = !empty($_POST['remove_canvas_image'])
        ? null
        : $processCanvasImageUpload($existingProduct['canvas_image'] ?? null);
    if ($canvas_image && ($image === 'default.jpg' || !file_exists('../assets/images/products/' . $image))) {
        $image = $canvas_image;
    }
    $dimensions = $dimensions !== '' ? $dimensions : null;
    $tags = $tags !== '' ? $tags : null;

    $updateStmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, image = ?, canvas_image = ?, category_id = ?, weight = ?, dimensions = ?, tags = ?, customize_step = ?, customize_filter_id = ?, is_featured = ? WHERE id = ?");

    if ($updateStmt->execute([$name, $description, $price, $image, $canvas_image, $category_id, $weight, $dimensions, $tags, $customize_step, $customize_filter_id, $is_featured, $product_id])) {
        $_SESSION['success'] = 'Product updated successfully!';
    } else {
        $_SESSION['error'] = 'Failed to update product.';
    }

    redirect('products.php');
}

if (isset($_POST['add_product'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $category_id = (int) ($_POST['category_id'] ?? 0);
    $weight = isset($_POST['weight']) && $_POST['weight'] !== '' ? (float) $_POST['weight'] : null;
    $dimensions = trim($_POST['dimensions'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $customize_step = $sanitizeCustomizeStep($_POST['customize_step'] ?? null);
    $customize_filter_id = $sanitizeCustomizeFilter($_POST['customize_filter_id'] ?? null, $customize_step);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $availability_status = 'available';

    if ($name === '' || $description === '' || $category_id <= 0) {
        $_SESSION['error'] = 'Name, description, and category are required.';
        redirect('products.php');
    }

    $image = $processImageUpload('default.jpg');
    $canvas_image = $processCanvasImageUpload(null);
    if ($canvas_image && ($image === 'default.jpg' || !file_exists('../assets/images/products/' . $image))) {
        $image = $canvas_image;
    }
    $dimensions = $dimensions !== '' ? $dimensions : null;
    $tags = $tags !== '' ? $tags : null;

    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image, canvas_image, category_id, stock_quantity, weight, dimensions, tags, customize_step, customize_filter_id, customize_archived, is_featured, availability_status, low_stock_threshold) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$name, $description, $price, $image, $canvas_image, $category_id, 0, $weight, $dimensions, $tags, $customize_step, $customize_filter_id, 0, $is_featured, $availability_status, 0])) {
        $_SESSION['success'] = 'Product added successfully!';
        redirect('products.php');
    } else {
        $_SESSION['error'] = 'Failed to add product to database.';
        redirect('products.php');
    }
}

if (isset($_POST['toggle_customize_archive'])) {
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $newStatus = isset($_POST['new_archive_status']) && (int) $_POST['new_archive_status'] === 1 ? 1 : 0;

    if ($product_id <= 0) {
        $_SESSION['error'] = 'Invalid product reference.';
        redirect('products.php');
    }

    $stmt = $pdo->prepare("UPDATE products SET customize_archived = ? WHERE id = ?");
    if ($stmt->execute([$newStatus, $product_id])) {
        $_SESSION['success'] = $newStatus ? 'Product archived from Customize builder.' : 'Product restored to Customize builder.';
    } else {
        $_SESSION['error'] = 'Unable to update Customize archive state.';
    }
    redirect('products.php');
}

// Handle delete product
if (isset($_GET['delete'])) {
    $product_id = (int) $_GET['delete'];

    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Product deleted successfully!";
        } else {
            $_SESSION['error'] = "Product not found or already removed.";
        }
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $_SESSION['error'] = "Cannot delete this product because it is linked to past orders. Please edit it instead.";
        } else {
            $_SESSION['error'] = "Failed to delete product.";
        }
    }
    redirect('products.php');
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$customizeCategoryId = null;
foreach ($categories as $category) {
    if (isset($category['name']) && strtolower(trim($category['name'])) === 'customize') {
        $customizeCategoryId = (int) $category['id'];
        break;
    }
}

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

$dataSql = "SELECT p.*, c.name as category_name FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            $whereSql 
            ORDER BY c.name ASC, p.created_at DESC 
            LIMIT :limit OFFSET :offset";

$dataStmt = $pdo->prepare($dataSql);
foreach ($params as $key => $value) {
    $dataStmt->bindValue(':' . $key, $value);
}
$dataStmt->bindValue(':limit', (int) $perPage, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
$dataStmt->execute();
$products = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

$baseQueryParams = [];
if ($categoryFilter) {
    $baseQueryParams['category'] = $categoryFilter;
}
if ($searchTerm !== '') {
    $baseQueryParams['search'] = $searchTerm;
}

$buildPaginationLink = function ($pageNumber) use ($baseQueryParams) {
    $params = $baseQueryParams;
    $params['page'] = $pageNumber;
    $query = http_build_query($params);
    return $query ? 'products.php?' . $query : 'products.php?page=' . $pageNumber;
};

$showingFrom = $totalProducts ? $offset + 1 : 0;
$showingTo = min($offset + count($products), $totalProducts);
$currentRequestUri = $_SERVER['REQUEST_URI'] ?? 'products.php';
$currentQueryString = $_SERVER['QUERY_STRING'] ?? '';
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h2 class="mb-0">Product Management</h2>
        <div class="d-flex gap-2">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus"></i> Add New Product
            </button>
            <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#addPerPieceProductModal">
                <i class="fas fa-layer-group"></i> Add Per-Piece Product
            </button>
        </div>
    </div>

    <!-- Display Messages -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error']; ?>
            <?php unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success']; ?>
            <?php unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Debug Info (Remove after testing) -->
    <div class="card mb-4 bg-light">
        <div class="card-body">
            <h6 class="card-title">Upload Directory Info</h6>
            <?php
            $upload_dir = '../assets/images/products/';
            $dir_exists = is_dir($upload_dir);
            $dir_writable = is_writable($upload_dir);
            $default_image_exists = file_exists($upload_dir . 'default.jpg');
            ?>
            <small>
                Directory exists: <span
                    class="badge bg-<?php echo $dir_exists ? 'success' : 'danger'; ?>"><?php echo $dir_exists ? 'Yes' : 'No'; ?></span><br>
                Directory writable: <span
                    class="badge bg-<?php echo $dir_writable ? 'success' : 'danger'; ?>"><?php echo $dir_writable ? 'Yes' : 'No'; ?></span><br>
                Default image exists: <span
                    class="badge bg-<?php echo $default_image_exists ? 'success' : 'danger'; ?>"><?php echo $default_image_exists ? 'Yes' : 'No'; ?></span><br>
                Upload max filesize: <?php echo ini_get('upload_max_filesize'); ?>
            </small>
        </div>
    </div>

    <!-- Customize Filters Management -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="m-0">Customize Step Categories</h6>
                <small class="text-muted">Compact view for Pot/Bouquet-style labels.</small>
            </div>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#customizeFiltersPanel">
                Toggle Panel
            </button>
        </div>
        <div class="collapse" id="customizeFiltersPanel">
            <div class="card-body">
                <div class="accordion" id="customizeFiltersAccordion">
                    <?php foreach ($customizeStepDefinitions as $stepNumber => $stepMeta): ?>
                        <?php
                        $stepKey = $stepMeta['key'];
                        $filtersForStep = $customizeFiltersByStep[$stepKey] ?? [];
                        $accordionId = 'stepFilters' . $stepNumber;
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-<?php echo $accordionId; ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $accordionId; ?>" aria-expanded="false" aria-controls="collapse-<?php echo $accordionId; ?>">
                                    <div class="d-flex justify-content-between align-items-center w-100 pe-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars($stepMeta['label']); ?></strong>
                                            <span class="text-muted small ms-2">Step <?php echo $stepNumber; ?> • Key: <?php echo htmlspecialchars($stepKey); ?></span>
                                        </div>
                                        <span class="badge bg-success">
                                            <?php echo count($filtersForStep); ?>
                                        </span>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse-<?php echo $accordionId; ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo $accordionId; ?>" data-bs-parent="#customizeFiltersAccordion">
                                <div class="accordion-body">
                                    <?php if (!empty($filtersForStep)): ?>
                                        <div class="table-responsive mb-3">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 40%;">Label</th>
                                                        <th style="width: 25%;">Slug</th>
                                                        <th style="width: 15%;">Order</th>
                                                        <th class="text-end" style="width: 20%;">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($filtersForStep as $filter): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($filter['label']); ?></td>
                                                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($filter['slug']); ?></span></td>
                                                            <td><?php echo (int) $filter['display_order']; ?></td>
                                                            <td class="text-end">
                                                                <div class="btn-group btn-group-sm">
                                                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editFilterModal<?php echo $filter['id']; ?>">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <form method="POST" class="d-inline"
                                                                          data-confirm="Delete this category?"
                                                                          data-confirm-variant="danger"
                                                                          data-confirm-accept="Delete">
                                                                        <input type="hidden" name="delete_step_filter" value="1">
                                                                        <input type="hidden" name="filter_id" value="<?php echo $filter['id']; ?>">
                                                                        <button type="submit" class="btn btn-outline-danger">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted small mb-3">No categories yet for this step.</p>
                                    <?php endif; ?>

                                    <?php foreach ($filtersForStep as $filter): ?>
                                        <div class="modal fade" id="editFilterModal<?php echo $filter['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" data-step-filter-form="edit" data-filter-id="<?php echo $filter['id']; ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Category</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="edit_step_filter" value="1">
                                                            <input type="hidden" name="filter_id" value="<?php echo $filter['id']; ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">Assign to Step</label>
                                                                <select name="filter_step_key" class="form-select" required data-edit-step-select>
                                                                    <?php foreach ($customizeStepDefinitions as $optionStepNumber => $optionMeta): ?>
                                                                        <option value="<?php echo $optionMeta['key']; ?>" <?php echo $optionMeta['key'] === $filter['step_key'] ? 'selected' : ''; ?>>
                                                                            <?php echo htmlspecialchars($optionMeta['label']); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Category Label</label>
                                                                <input type="text" name="filter_label" class="form-control" value="<?php echo htmlspecialchars($filter['label']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Display Order</label>
                                                                <input type="number" name="filter_order" class="form-control" min="1" value="<?php echo (int) $filter['display_order']; ?>" required data-display-order-input>
                                                                <div class="invalid-feedback" data-order-feedback>Display order already exists for this step.</div>
                                                                <div class="form-text">Each step must have unique display orders.</div>
                                                            </div>
                                                            <small class="text-muted">Slugs update automatically based on the label.</small>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-success">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                    <?php endforeach; ?>

                                    <form method="POST" class="row g-2 align-items-end" data-step-filter-form="add" data-step-key="<?php echo $stepKey; ?>">
                                        <input type="hidden" name="add_step_filter" value="1">
                                        <input type="hidden" name="filter_step_key" value="<?php echo $stepKey; ?>">
                                        <div class="col-md-6">
                                            <label class="form-label">New Category Name</label>
                                            <input type="text" name="filter_label" class="form-control" placeholder="e.g., Pot" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Display Order</label>
                                            <input type="number" name="filter_order" class="form-control" min="1" value="<?php echo count($filtersForStep) + 1; ?>" required data-display-order-input>
                                            <div class="invalid-feedback" data-order-feedback>Display order already exists for this step.</div>
                                            <div class="form-text">Keep this number unique within the step.</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label invisible">Add</label>
                                            <button type="submit" class="btn btn-success w-100">
                                                <i class="fas fa-plus"></i> Add
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-muted small mb-0">Tip: keep labels short—slugs are generated automatically.</p>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h6 class="m-0 font-weight-bold">All Products</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end mb-4">
                <div class="col-md-4">
                    <label for="categoryFilter" class="form-label">Category</label>
                    <select name="category" id="categoryFilter" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                <?php echo $categoryFilter === (int) $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="searchInput" class="form-label">Search</label>
                    <input type="text" name="search" id="searchInput" class="form-control"
                        placeholder="Name, description, or tags"
                        value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-success flex-grow-1">Apply Filters</button>
                    <?php if ($categoryFilter || $searchTerm !== ''): ?>
                        <a href="products.php" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <div class="text-muted small">
                    <?php if ($totalProducts): ?>
                        Showing <?php echo $showingFrom; ?>-<?php echo $showingTo; ?> of <?php echo $totalProducts; ?> products
                    <?php else: ?>
                        No products found
                    <?php endif; ?>
                </div>
                <div class="text-muted small">
                    <?php echo $perPage; ?> per page
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="productsTable">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price (₱)</th>
                            <th class="text-center featured-col">Featured</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No products match the current filters.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                            <?php
                            $has_cutout = !empty($product['canvas_image']) && file_exists('../assets/images/products/' . $product['canvas_image']);
                            $display_image = $has_cutout ? $product['canvas_image'] : ($product['image'] ?? '');
                            $image_path = '../assets/images/products/' . $display_image;
                            $image_exists = $display_image !== '' && file_exists($image_path);
                            $productComponents = getProductInventoryComponents($product['id']);
                            $componentCount = count($productComponents);
                            ?>
                            <tr>
                                <td>
                                    <div style="width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; border-radius: 8px; overflow: hidden; <?php echo $has_cutout ? 'background-image: linear-gradient(45deg, #e9ecef 25%, transparent 25%), linear-gradient(-45deg, #e9ecef 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #e9ecef 75%), linear-gradient(-45deg, transparent 75%, #e9ecef 75%); background-size: 12px 12px; background-position: 0 0, 0 6px, 6px -6px, -6px 0; border: 1px solid #cce5d8;' : ''; ?>">
                                        <img src="../assets/images/products/<?php echo htmlspecialchars($display_image); ?>"
                                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                                            style="width: 50px; height: 50px; object-fit: <?php echo $has_cutout ? 'contain' : 'cover'; ?>;"
                                            class="rounded <?php echo !$image_exists ? 'border border-danger' : ''; ?>"
                                            onerror="this.onerror=null; this.src='../assets/images/products/wrapper.png'">
                                    </div>
                                    <?php if ($has_cutout): ?>
                                        <span class="badge bg-success mt-1" style="font-size: 0.65rem;">Cutout PNG</span>
                                    <?php elseif (!$image_exists): ?>
                                        <small class="text-danger d-block">File missing</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($product['name']); ?></div>
                                    <div class="text-muted small">
                                        Buildable units available:
                                        <span class="fw-semibold text-success"><?php echo (int) $product['stock_quantity']; ?></span>
                                    </div>
                                    <?php if (!empty($product['tags'])): ?>
                                        <div class="small text-muted"><?php echo htmlspecialchars($product['tags']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($product['category_name']); ?></div>
                                    <?php
                                    $stepLabel = null;
                                    $filterLabel = null;
                                    if (!empty($product['customize_step'])) {
                                        $stepKey = (int) $product['customize_step'];
                                        if (isset($customizeStepDefinitions[$stepKey])) {
                                            $stepLabel = $customizeStepDefinitions[$stepKey]['label'];
                                            if (!empty($product['customize_filter_id']) && isset($customizeFiltersById[$product['customize_filter_id']])) {
                                                $filterLabel = $customizeFiltersById[$product['customize_filter_id']]['label'];
                                            }
                                        }
                                    }
                                    ?>
                                    <?php if ($stepLabel): ?>
                                        <span class="badge bg-info text-dark mt-1"><?php echo htmlspecialchars($stepLabel); ?></span>
                                        <?php if ($filterLabel): ?>
                                            <span class="badge bg-light text-dark border mt-1"><?php echo htmlspecialchars($filterLabel); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($product['customize_archived'])): ?>
                                            <span class="badge bg-secondary mt-1">Archived</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                <td class="text-center featured-col">
                                    <?php if ($product['is_featured']): ?>
                                        <i class="fas fa-star text-warning" aria-label="Featured product"></i>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="align-top">
                                    <div class="btn-group btn-group-sm flex-wrap">
                                        <button type="button"
                                            class="btn btn-outline-info link-inventory-btn"
                                            data-bs-target="#linkInventoryModal<?php echo $product['id']; ?>"
                                            data-bs-toggle="modal"
                                            title="Link per-piece inventory">
                                            <i class="fab fa-bluetooth-b"></i>
                                        </button>
                                        <a href="../product-detail.php?id=<?php echo $product['id']; ?>"
                                            class="btn btn-outline-primary" title="Preview product">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-outline-secondary" data-bs-toggle="modal"
                                            data-bs-target="#editProductModal<?php echo $product['id']; ?>"
                                            title="Edit product">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-outline-danger"
                                            data-confirm="Delete <?php echo htmlspecialchars($product['name']); ?>? This cannot be undone."
                                            data-confirm-variant="danger"
                                            data-confirm-accept="Delete Product" title="Delete product">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                    <?php if ($componentCount > 0): ?>
                                        <div class="inventory-link-meta mt-1">
                                            <i class="fas fa-link me-1"></i>
                                            <?php echo $componentCount; ?> linked item<?php echo $componentCount === 1 ? '' : 's'; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($product['customize_step'])): ?>
                                        <form method="POST" class="d-inline-block mt-2">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="toggle_customize_archive" value="1">
                                            <input type="hidden" name="new_archive_status"
                                                value="<?php echo $product['customize_archived'] ? 0 : 1; ?>">
                                            <button type="submit"
                                                class="btn btn-sm <?php echo $product['customize_archived'] ? 'btn-outline-success' : 'btn-outline-secondary'; ?>">
                                                <i class="fas fa-box-archive"></i>
                                                <?php echo $product['customize_archived'] ? 'Restore to builder' : 'Archive from builder'; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Link Inventory Modal -->
                                    <div class="modal fade inventory-link-modal" id="linkInventoryModal<?php echo $product['id']; ?>"
                                        tabindex="-1" aria-labelledby="linkInventoryModalLabel<?php echo $product['id']; ?>"
                                        aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <form method="POST" action="<?php echo htmlspecialchars($currentRequestUri); ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="linkInventoryModalLabel<?php echo $product['id']; ?>">Link per-piece inventory</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <input type="hidden" name="save_inventory_components" value="1">
                                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                        <input type="hidden" name="return_to_query" value="<?php echo htmlspecialchars($currentQueryString); ?>">
                                                        <?php if (!$hasInventoryItems): ?>
                                                            <div class="alert alert-warning mb-0">
                                                                <?php if ($componentCount > 0): ?>
                                                                    This product is linked to <?php echo $componentCount; ?> item<?php echo $componentCount === 1 ? '' : 's'; ?>, but there are no per-piece inventory records available right now. Add or restore the related inventory items to edit the mapping.
                                                                    <ul class="mt-2 mb-0 small">
                                                                        <?php foreach ($productComponents as $component): ?>
                                                                            <li>
                                                                                <strong><?php echo htmlspecialchars($component['item_name'] ?? 'Unlisted item'); ?></strong>
                                                                                &middot; <?php echo (float) ($component['quantity_per_unit'] ?? 0); ?> <?php echo htmlspecialchars($component['unit'] ?? 'unit'); ?> per order
                                                                            </li>
                                                                        <?php endforeach; ?>
                                                                    </ul>
                                                                <?php else: ?>
                                                                    You need at least one per-piece inventory item before you can create links. Use the “Add Per-Piece Product” button to seed your raw materials.
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <p class="text-muted small mb-3">
                                                                Choose every per-piece ingredient this product consumes so inventory can auto-deduct per order.
                                                            </p>
                                                            <div class="inventory-search mb-3">
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                                    <input type="search" class="form-control" placeholder="Search or filter per-piece items" data-inventory-filter-input>
                                                                    <button type="button" class="btn btn-outline-secondary" data-trigger-inventory-filter>
                                                                        Search
                                                                    </button>
                                                                    <button type="button" class="btn btn-outline-secondary" data-clear-inventory-filter title="Clear search">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                </div>
                                                                <div class="small text-muted mt-2 d-none" data-inventory-filter-feedback></div>
                                                            </div>
                                                            <?php $componentRowsData = $componentCount ? $productComponents : [['item_id' => null, 'quantity_per_unit' => null]]; ?>
                                                            <div id="componentRows<?php echo $product['id']; ?>">
                                                                <?php foreach ($componentRowsData as $componentRow): ?>
                                                                    <?php
                                                                    $selectedItemId = (int) ($componentRow['item_id'] ?? 0);
                                                                    $rawQuantity = isset($componentRow['quantity_per_unit']) ? (float) $componentRow['quantity_per_unit'] : null;
                                                                    $quantityDisplay = $rawQuantity === null ? '' : rtrim(rtrim(sprintf('%.4f', $rawQuantity), '0'), '.');
                                                                    ?>
                                                                    <div class="component-row border rounded p-3 mb-3">
                                                                        <div class="row g-3 align-items-end">
                                                                            <div class="col-md-7">
                                                                                <label class="form-label">Per-piece item</label>
                                                                                <select name="component_item_id[]" class="form-select" required>
                                                                                    <option value="">Choose an inventory item</option>
                                                                                    <?php foreach ($inventoryItems as $inventoryItem): ?>
                                                                                        <option value="<?php echo (int) $inventoryItem['id']; ?>" <?php echo $selectedItemId === (int) $inventoryItem['id'] ? 'selected' : ''; ?>>
                                                                                            <?php echo htmlspecialchars($inventoryItem['name']); ?>
                                                                                            (<?php echo number_format((float) ($inventoryItem['current_quantity'] ?? 0), 2); ?> <?php echo htmlspecialchars($inventoryItem['unit'] ?? ''); ?> available)
                                                                                        </option>
                                                                                    <?php endforeach; ?>
                                                                                </select>
                                                                            </div>
                                                                            <div class="col-md-4">
                                                                                <label class="form-label">Qty per order</label>
                                                                                <input type="number" name="component_quantity[]" class="form-control" min="0.01" step="0.01" value="<?php echo htmlspecialchars($quantityDisplay); ?>" placeholder="0.00" required>
                                                                            </div>
                                                                            <div class="col-md-1 d-flex">
                                                                                <button type="button" class="btn btn-outline-danger" data-remove-component-row title="Remove row">
                                                                                    <i class="fas fa-times"></i>
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                                <button type="button" class="btn btn-outline-secondary btn-sm" data-add-component-row data-target="#componentRows<?php echo $product['id']; ?>">
                                                                    <i class="fas fa-plus"></i> Add Item
                                                                </button>
                                                                <span class="text-muted small">Inventory deducts by the quantities set here.</span>
                                                            </div>
                                                            <div class="component-row-template d-none">
                                                                <div class="component-row border rounded p-3 mb-3">
                                                                    <div class="row g-3 align-items-end">
                                                                        <div class="col-md-7">
                                                                            <label class="form-label">Per-piece item</label>
                                                                            <select name="component_item_id[]" class="form-select" required disabled>
                                                                                <option value="">Choose an inventory item</option>
                                                                                <?php foreach ($inventoryItems as $inventoryItem): ?>
                                                                                    <option value="<?php echo (int) $inventoryItem['id']; ?>">
                                                                                        <?php echo htmlspecialchars($inventoryItem['name']); ?>
                                                                                        (<?php echo number_format((float) ($inventoryItem['current_quantity'] ?? 0), 2); ?> <?php echo htmlspecialchars($inventoryItem['unit'] ?? ''); ?> available)
                                                                                    </option>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label class="form-label">Qty per order</label>
                                                                            <input type="number" name="component_quantity[]" class="form-control" min="0.01" step="0.01" placeholder="0.00" required disabled>
                                                                        </div>
                                                                        <div class="col-md-1 d-flex">
                                                                            <button type="button" class="btn btn-outline-danger" data-remove-component-row title="Remove row" disabled>
                                                                                <i class="fas fa-times"></i>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" class="btn btn-success" <?php echo $hasInventoryItems ? '' : 'disabled'; ?>>Save Links</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Edit Product Modal -->
                                    <div class="modal fade" id="editProductModal<?php echo $product['id']; ?>"
                                        tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Product: <?php echo $product['name']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" enctype="multipart/form-data">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="product_id"
                                                            value="<?php echo $product['id']; ?>">
                                                        <input type="hidden" name="edit_product" value="1">
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Product Name</label>
                                                                <input type="text" name="name" class="form-control"
                                                                    value="<?php echo htmlspecialchars($product['name']); ?>"
                                                                    required>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Category</label>
                                                                <select name="category_id" class="form-select" required data-category-select>
                                                                    <option value="">Select Category</option>
                                                                    <?php foreach ($categories as $category): ?>
                                                                        <option value="<?php echo $category['id']; ?>"
                                                                            <?php echo $category['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                                                            <?php echo htmlspecialchars($category['name']); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Description</label>
                                                            <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Price (₱)</label>
                                                                <input type="number" name="price" class="form-control" step="0.01"
                                                                    value="<?php echo htmlspecialchars($product['price']); ?>" required>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Weight (kg)</label>
                                                                <input type="number" name="weight" class="form-control" step="0.01"
                                                                    value="<?php echo htmlspecialchars($product['weight'] ?? ''); ?>">
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Dimensions (LxWxH)</label>
                                                                <input type="text" name="dimensions" class="form-control"
                                                                    value="<?php echo htmlspecialchars($product['dimensions'] ?? ''); ?>">
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Tags (comma separated)</label>
                                                                <input type="text" name="tags" class="form-control"
                                                                    value="<?php echo htmlspecialchars($product['tags'] ?? ''); ?>">
                                                            </div>
                                                        </div>

                                                        <?php
                                                        $showCustomizeFields = (int) $product['category_id'] === (int) $customizeCategoryId;
                                                        $currentStepId = (int) ($product['customize_step'] ?? 0);
                                                        $currentFilterId = (int) ($product['customize_filter_id'] ?? 0);
                                                        $currentStepKey = ($currentStepId && isset($customizeStepKeyMap[$currentStepId])) ? $customizeStepKeyMap[$currentStepId] : null;
                                                        $currentFilterOptions = $currentStepKey && isset($customizeFiltersByStep[$currentStepKey]) ? $customizeFiltersByStep[$currentStepKey] : [];
                                                        ?>
                                                        <div class="row">
                                                            <div class="col-md-12 mb-3 <?php echo $showCustomizeFields ? '' : 'd-none'; ?>"
                                                                data-customize-step-wrapper>
                                                                <label class="form-label">Customize Builder Step</label>
                                                                <select name="customize_step" class="form-select" data-customize-step-select
                                                                    <?php echo $showCustomizeFields ? '' : 'disabled'; ?>>
                                                                    <option value="">Not part of Customize builder</option>
                                                                    <?php foreach ($customizeStepDefinitions as $stepNumber => $stepMeta): ?>
                                                                        <option value="<?php echo $stepNumber; ?>"
                                                                            <?php echo $currentStepId === $stepNumber ? 'selected' : ''; ?>>
                                                                            <?php echo htmlspecialchars($stepMeta['label']); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <div class="form-text">Assign the step instantly so the item lands in the correct Customize stage.</div>
                                                            </div>
                                                            <div class="col-md-12 mb-3 <?php echo ($showCustomizeFields && $currentStepId) ? '' : 'd-none'; ?>"
                                                                data-customize-filter-wrapper>
                                                                <label class="form-label">Step Category</label>
                                                                <select name="customize_filter_id" class="form-select" data-customize-filter-select
                                                                    data-selected-filter="<?php echo $currentFilterId ?: ''; ?>"
                                                                    <?php echo ($showCustomizeFields && $currentStepId && !empty($currentFilterOptions)) ? '' : 'disabled'; ?>>
                                                                    <option value="">No specific category</option>
                                                                    <?php foreach ($currentFilterOptions as $filterOption): ?>
                                                                        <option value="<?php echo $filterOption['id']; ?>"
                                                                            <?php echo $currentFilterId === (int) $filterOption['id'] ? 'selected' : ''; ?>>
                                                                            <?php echo htmlspecialchars($filterOption['label']); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                                <div class="form-text" data-customize-filter-helper>
                                                                    <?php if ($currentStepId && !empty($currentFilterOptions)): ?>
                                                                        Optional: narrow this product to a specific sub-category within the step.
                                                                    <?php elseif ($currentStepId): ?>
                                                                        No categories yet for this step. Add one above to start using them.
                                                                    <?php else: ?>
                                                                        Select a step first to unlock category filters.
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="small text-muted mt-2 <?php echo ($currentStepId && !empty($currentFilterOptions)) ? '' : 'd-none'; ?>"
                                                                    data-customize-filter-preview>
                                                                    <?php if ($currentStepId && !empty($currentFilterOptions)): ?>
                                                                        <div class="d-flex flex-wrap gap-2">
                                                                            <?php foreach ($currentFilterOptions as $filterOption): ?>
                                                                                <span class="badge bg-light text-dark border">
                                                                                    <?php echo htmlspecialchars($filterOption['label']); ?>
                                                                                    <small class="text-muted ms-1">&bull; <?php echo htmlspecialchars($filterOption['slug']); ?></small>
                                                                                </span>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Product Image</label>
                                                            <input type="file" name="image" class="form-control"
                                                                accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                                            <div class="form-text">Leave blank to keep current image.
                                                                <br>Current file: <strong><?php echo htmlspecialchars($product['image']); ?></strong>
                                                            </div>
                                                        </div>

                                                        <div class="mb-3 p-3 border rounded bg-light" data-canvas-cutout-widget>
                                                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                                                <label class="form-label fw-semibold mb-0">
                                                                    <i class="fas fa-magic text-success me-1"></i>Live Canvas Cutout Image
                                                                    <span class="badge bg-success ms-1">Auto-Transparent AI</span>
                                                                </label>
                                                                <?php if (!empty($product['image']) && file_exists('../assets/images/products/' . $product['image'])): ?>
                                                                    <button type="button"
                                                                        class="btn btn-outline-success btn-sm"
                                                                        data-cutout-from-current
                                                                        data-current-image-url="../assets/images/products/<?php echo htmlspecialchars($product['image']); ?>"
                                                                        data-product-name="<?php echo htmlspecialchars($product['name']); ?>">
                                                                        <i class="fas fa-wand-magic-sparkles me-1"></i>Auto-Cutout Current Photo
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                            <input type="file" name="canvas_image" class="form-control"
                                                                accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                                                data-canvas-cutout-input>
                                                            <div class="form-check mt-2">
                                                                <input class="form-check-input" type="checkbox" id="auto_bg_edit_<?php echo (int) $product['id']; ?>" data-auto-bg-toggle checked>
                                                                <label class="form-check-label small" for="auto_bg_edit_<?php echo (int) $product['id']; ?>">
                                                                    Automatically remove background &amp; convert into clean transparent PNG on select
                                                                </label>
                                                            </div>
                                                            <div class="alert d-none py-2 px-3 small mt-2 mb-0" data-cutout-status>
                                                                <div data-cutout-status-text></div>
                                                                <div class="progress mt-1 d-none" style="height: 6px;">
                                                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 15%" data-cutout-progress></div>
                                                                </div>
                                                            </div>
                                                            <div class="mt-2 <?php echo !empty($product['canvas_image']) ? '' : 'd-none'; ?>" data-cutout-preview-wrap>
                                                                <div class="small fw-semibold text-muted mb-1">Transparent Cutout Preview:</div>
                                                                <div class="d-inline-block p-2 border rounded" style="background-image: linear-gradient(45deg, #e9ecef 25%, transparent 25%), linear-gradient(-45deg, #e9ecef 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #e9ecef 75%), linear-gradient(-45deg, transparent 75%, #e9ecef 75%); background-size: 16px 16px; background-position: 0 0, 0 8px, 8px -8px, -8px 0;">
                                                                    <img src="<?php echo !empty($product['canvas_image']) ? '../assets/images/products/' . htmlspecialchars($product['canvas_image']) : ''; ?>"
                                                                        alt="Transparent cutout preview"
                                                                        style="max-height: 120px; max-width: 160px; object-fit: contain; display: block;"
                                                                        data-cutout-preview-img>
                                                                </div>
                                                            </div>
                                                            <div class="form-text mt-1">
                                                                Upload any JPG, PNG, or WebP photo—AI removes the background automatically into a clean transparent PNG for the Live Bouquet Canvas.
                                                                <?php if (!empty($product['canvas_image'])): ?>
                                                                    <br>Current cutout: <strong><?php echo htmlspecialchars($product['canvas_image']); ?></strong>
                                                                    <label class="ms-2 text-danger">
                                                                        <input type="checkbox" name="remove_canvas_image" value="1"> Remove cutout
                                                                    </label>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>

                                                        <div class="form-check mb-3">
                                                            <input class="form-check-input" type="checkbox" name="is_featured"
                                                                id="is_featured_<?php echo $product['id']; ?>"
                                                                <?php echo $product['is_featured'] ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="is_featured_<?php echo $product['id']; ?>">
                                                                Feature this product
                                                            </label>
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

                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
</div>

<!-- Add Per-Piece Product Modal -->
<div class="modal fade" id="addPerPieceProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Per-Piece Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="add_per_piece_product" value="1">
                    <div class="mb-3">
                        <label class="form-label">Item Name</label>
                        <input type="text" name="per_piece_name" class="form-control" placeholder="e.g., Single Rose Stem" required>
                        <div class="form-text">Appears inside the per-piece inventory workspace.</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SKU / Code</label>
                            <input type="text" name="per_piece_sku" class="form-control" placeholder="Optional unique code">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category Label</label>
                            <input type="text" name="per_piece_category" class="form-control" placeholder="e.g., Stems">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unit</label>
                            <select name="per_piece_unit" class="form-select">
                                <?php foreach ($inventoryUnitsCatalog as $unitKey => $unitLabel): ?>
                                    <option value="<?php echo htmlspecialchars($unitKey); ?>" <?php echo $unitKey === 'pc' ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($unitLabel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unit Cost (₱)</label>
                            <input type="number" name="per_piece_unit_cost" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Initial Quantity</label>
                            <input type="number" name="per_piece_initial_quantity" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Low Stock Threshold</label>
                            <input type="number" name="per_piece_reorder_point" class="form-control" step="0.01" min="0" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="per_piece_notes" class="form-control" rows="3" placeholder="Any sourcing or prep reminders"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Item Image</label>
                        <input type="file" name="per_piece_image" class="form-control" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                        <div class="form-text">Allowed formats: JPG, PNG, GIF, WebP. Max size: 5MB.</div>
                    </div>
                    <div class="alert alert-info small mb-0">
                        Saving here also seeds the upcoming per-piece view inside Inventory. Use this for stems, wraps, ribbons, and similar raw materials.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Save Per-Piece Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required data-category-select>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (₱)</label>
                            <input type="number" name="price" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Weight (kg)</label>
                            <input type="number" name="weight" class="form-control" step="0.01">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Dimensions (LxWxH)</label>
                            <input type="text" name="dimensions" class="form-control" placeholder="e.g., 10x5x8">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tags (comma separated)</label>
                            <input type="text" name="tags" class="form-control"
                                placeholder="e.g., bouquet, roses, anniversary">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3 d-none" data-customize-step-wrapper>
                            <label class="form-label">Customize Builder Step</label>
                            <select name="customize_step" class="form-select" data-customize-step-select disabled>
                                <option value="">Not part of Customize builder</option>
                                <?php foreach ($customizeStepDefinitions as $stepNumber => $stepMeta): ?>
                                    <option value="<?php echo $stepNumber; ?>"><?php echo htmlspecialchars($stepMeta['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Assign the step instantly so the item lands in the correct Customize stage.</div>
                        </div>
                        <div class="col-md-12 mb-3 d-none" data-customize-filter-wrapper>
                            <label class="form-label">Step Category</label>
                            <select name="customize_filter_id" class="form-select" data-customize-filter-select disabled data-selected-filter="">
                                <option value="">No specific category</option>
                            </select>
                            <div class="form-text" data-customize-filter-helper>Select a step first to unlock category filters.</div>
                            <div class="small text-muted mt-2 d-none" data-customize-filter-preview></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Product Image</label>
                        <input type="file" name="image" class="form-control"
                            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                        <div class="form-text">Allowed formats: JPG, PNG, GIF, WebP. Max size: 5MB</div>
                    </div>

                    <div class="mb-3 p-3 border rounded bg-light" data-canvas-cutout-widget>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <label class="form-label fw-semibold mb-0">
                                <i class="fas fa-magic text-success me-1"></i>Live Canvas Cutout Image
                                <span class="badge bg-success ms-1">Auto-Transparent AI</span>
                            </label>
                            <button type="button" class="btn btn-outline-success btn-sm" data-cutout-from-main-upload>
                                <i class="fas fa-wand-magic-sparkles me-1"></i>Auto-Cutout Product Image Above
                            </button>
                        </div>
                        <input type="file" name="canvas_image" class="form-control"
                            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                            data-canvas-cutout-input>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="auto_bg_add" data-auto-bg-toggle checked>
                            <label class="form-check-label small" for="auto_bg_add">
                                Automatically remove background &amp; convert into clean transparent PNG on select
                            </label>
                        </div>
                        <div class="alert d-none py-2 px-3 small mt-2 mb-0" data-cutout-status>
                            <div data-cutout-status-text></div>
                            <div class="progress mt-1 d-none" style="height: 6px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 15%" data-cutout-progress></div>
                            </div>
                        </div>
                        <div class="mt-2 d-none" data-cutout-preview-wrap>
                            <div class="small fw-semibold text-muted mb-1">Transparent Cutout Preview:</div>
                            <div class="d-inline-block p-2 border rounded" style="background-image: linear-gradient(45deg, #e9ecef 25%, transparent 25%), linear-gradient(-45deg, #e9ecef 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #e9ecef 75%), linear-gradient(-45deg, transparent 75%, #e9ecef 75%); background-size: 16px 16px; background-position: 0 0, 0 8px, 8px -8px, -8px 0;">
                                <img src="" alt="Transparent cutout preview" style="max-height: 120px; max-width: 160px; object-fit: contain; display: block;" data-cutout-preview-img>
                            </div>
                        </div>
                        <div class="form-text mt-1">Upload any JPG, PNG, or WebP photo—AI removes the background automatically into a clean transparent PNG (max 10MB).</div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured">
                        <label class="form-check-label" for="is_featured">
                            Feature this product
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_product" class="btn btn-success">Add Product</button>
                </div>
            </form>
        </div>
    </div>
    </div>

    <?php if ($customizeCategoryId): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var customizeCategoryId = '<?php echo $customizeCategoryId; ?>';
        var stepKeyMap = <?php echo json_encode($customizeStepKeyMap, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?> || {};
        var filtersByStep = <?php echo json_encode($customizeFiltersByStep, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?> || {};
        var helperCopy = {
            prompt: 'Select a step first to unlock category filters.',
            empty: 'No categories yet for this step. Add one above to start using them.',
            ready: 'Optional: narrow this product to a specific sub-category within the step.'
        };

        var stepFiltersState = {};
        Object.keys(filtersByStep || {}).forEach(function (key) {
            var records = filtersByStep[key] || [];
            var list = Array.isArray(records) ? records : Object.values(records);
            stepFiltersState[key] = list.map(function (item) {
                return {
                    id: item && item.id !== undefined ? String(item.id) : '',
                    order: item && item.display_order !== undefined ? parseInt(item.display_order, 10) : null
                };
            });
        });

        var isDisplayOrderTaken = function (stepKey, order, excludeId) {
            if (!stepKey || order === null || isNaN(order)) {
                return false;
            }
            var list = stepFiltersState[stepKey] || [];
            var exclude = excludeId !== undefined && excludeId !== null ? String(excludeId) : '';
            return list.some(function (item) {
                if (!item || item.order === null) {
                    return false;
                }
                if (exclude && item.id === exclude) {
                    return false;
                }
                return Number(item.order) === Number(order);
            });
        };

        var renderFilterPreview = function (previewEl, items, stepValue) {
            if (!previewEl) {
                return;
            }

            previewEl.innerHTML = '';

            if (!stepValue) {
                previewEl.classList.add('d-none');
                return;
            }

            previewEl.classList.remove('d-none');

            if (!items || !items.length) {
                previewEl.textContent = helperCopy.empty;
                return;
            }

            var list = document.createElement('div');
            list.className = 'd-flex flex-wrap gap-2';

            items.forEach(function (filter) {
                var badge = document.createElement('span');
                badge.className = 'badge bg-light text-dark border';
                badge.title = 'Slug: ' + (filter.slug || '');

                var labelNode = document.createElement('span');
                labelNode.textContent = filter.label || 'Unnamed';
                badge.appendChild(labelNode);

                if (filter.slug) {
                    var slugNode = document.createElement('small');
                    slugNode.className = 'text-muted ms-1';
                    slugNode.textContent = '• ' + filter.slug;
                    badge.appendChild(slugNode);
                }

                list.appendChild(badge);
            });

            previewEl.appendChild(list);
        };

        var updateFilterFieldState = function (form, stepValue) {
            var filterWrapper = form.querySelector('[data-customize-filter-wrapper]');
            var filterSelect = form.querySelector('[data-customize-filter-select]');
            var helper = form.querySelector('[data-customize-filter-helper]');
            var preview = form.querySelector('[data-customize-filter-preview]');
            if (!filterWrapper || !filterSelect) {
                return;
            }
            var storedSelected = filterSelect.getAttribute('data-selected-filter') || '';

            if (!stepValue) {
                filterWrapper.classList.add('d-none');
                filterSelect.disabled = true;
                filterSelect.innerHTML = '<option value="">No specific category</option>';
                filterSelect.value = '';
                if (helper) {
                    helper.textContent = helperCopy.prompt;
                }
                renderFilterPreview(preview, [], null);
                return;
            }

            var stepKey = stepKeyMap[stepValue] || null;
            var rawOptions = stepKey && filtersByStep[stepKey] ? filtersByStep[stepKey] : [];
            var options = Array.isArray(rawOptions) ? rawOptions : Object.values(rawOptions || {});
            filterWrapper.classList.remove('d-none');
            filterSelect.innerHTML = '';

            var placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = options.length ? 'No specific category' : 'No categories defined yet';
            filterSelect.appendChild(placeholder);

            if (options.length) {
                options.forEach(function (filter) {
                    var option = document.createElement('option');
                    option.value = filter.id;
                    option.textContent = filter.label;
                    if (storedSelected && String(storedSelected) === String(filter.id)) {
                        option.selected = true;
                    }
                    filterSelect.appendChild(option);
                });
                filterSelect.disabled = false;
                if (helper) {
                    helper.textContent = helperCopy.ready;
                }
            } else {
                filterSelect.disabled = true;
                if (helper) {
                    helper.textContent = helperCopy.empty;
                }
            }

            renderFilterPreview(preview, options, stepValue);
        };

        var updateFormState = function (categorySelect) {
            if (!categorySelect) {
                return;
            }
            var form = categorySelect.closest('form');
            if (!form) {
                return;
            }
            var stepWrapper = form.querySelector('[data-customize-step-wrapper]');
            var stepSelect = form.querySelector('[data-customize-step-select]');
            if (!stepWrapper || !stepSelect) {
                return;
            }
            var isCustomize = categorySelect.value === customizeCategoryId;
            stepWrapper.classList.toggle('d-none', !isCustomize);
            stepSelect.disabled = !isCustomize;
            if (!isCustomize) {
                stepSelect.value = '';
            }
            updateFilterFieldState(form, isCustomize ? stepSelect.value : null);
        };

        document.querySelectorAll('[data-category-select]').forEach(function (select) {
            updateFormState(select);
            select.addEventListener('change', function () {
                updateFormState(select);
            });

            var form = select.closest('form');
            if (!form) {
                return;
            }
            var stepSelect = form.querySelector('[data-customize-step-select]');
            if (stepSelect && !stepSelect.dataset.customizeEnhanced) {
                stepSelect.addEventListener('change', function () {
                    var filterSelect = form.querySelector('[data-customize-filter-select]');
                    if (filterSelect) {
                        filterSelect.setAttribute('data-selected-filter', '');
                    }
                    updateFilterFieldState(form, stepSelect.value);
                });
                stepSelect.dataset.customizeEnhanced = '1';
            }

            var filterSelect = form.querySelector('[data-customize-filter-select]');
            if (filterSelect && !filterSelect.dataset.customizeEnhanced) {
                filterSelect.addEventListener('change', function () {
                    filterSelect.setAttribute('data-selected-filter', filterSelect.value || '');
                });
                filterSelect.dataset.customizeEnhanced = '1';
            }
        });

        var bindStepFilterValidation = function (form) {
            if (!form) {
                return;
            }

            var orderInput = form.querySelector('[data-display-order-input]');
            if (!orderInput) {
                return;
            }

            var submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (!submitBtn) {
                return;
            }

            var feedbackEl = form.querySelector('[data-order-feedback]');
            var stepSelect = form.querySelector('[data-edit-step-select]');
            var staticStepKey = form.getAttribute('data-step-key') || '';
            var filterId = form.getAttribute('data-filter-id') || '';

            var resolveStepKey = function () {
                if (stepSelect) {
                    return stepSelect.value;
                }
                return staticStepKey;
            };

            var setValidity = function (isValid) {
                if (isValid) {
                    orderInput.classList.remove('is-invalid');
                    if (feedbackEl) {
                        feedbackEl.style.display = 'none';
                    }
                    submitBtn.disabled = false;
                } else {
                    orderInput.classList.add('is-invalid');
                    if (feedbackEl) {
                        feedbackEl.style.display = 'block';
                    }
                    submitBtn.disabled = true;
                }
            };

            var evaluate = function () {
                var stepKey = resolveStepKey();
                var orderValue = parseInt(orderInput.value, 10);

                if (!stepKey || isNaN(orderValue)) {
                    setValidity(false);
                    return;
                }

                var taken = isDisplayOrderTaken(stepKey, orderValue, filterId);
                setValidity(!taken);
            };

            orderInput.addEventListener('input', evaluate);
            orderInput.addEventListener('change', evaluate);
            if (stepSelect) {
                stepSelect.addEventListener('change', evaluate);
            }

            form.addEventListener('submit', function (event) {
                var stepKey = resolveStepKey();
                var orderValue = parseInt(orderInput.value, 10);
                if (!stepKey || isNaN(orderValue) || isDisplayOrderTaken(stepKey, orderValue, filterId)) {
                    event.preventDefault();
                    event.stopPropagation();
                    evaluate();
                }
            });

            evaluate();
        };

        document.querySelectorAll('[data-step-filter-form]').forEach(bindStepFilterValidation);
    });
    </script>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var isTemplateRow = function (node) {
            return node && node.closest('.component-row-template');
        };

        var attachRemoveHandler = function (scope) {
            if (!scope || isTemplateRow(scope)) {
                return;
            }
            var targets = scope.querySelectorAll('[data-remove-component-row]');
            targets.forEach(function (removeBtn) {
                removeBtn.addEventListener('click', function () {
                    var row = removeBtn.closest('.component-row');
                    if (!row) {
                        return;
                    }
                    var container = row.parentElement;
                    if (container && container.querySelectorAll('.component-row').length > 1) {
                        row.remove();
                    } else {
                        row.querySelectorAll('select, input').forEach(function (input) {
                            input.value = '';
                        });
                    }
                });
            });
        };

        var filterInventoryOptions = function (input) {
            if (!input) {
                return;
            }
            var modal = input.closest('.inventory-link-modal');
            if (!modal) {
                return;
            }

            var query = input.value.trim().toLowerCase();
            var selects = Array.prototype.filter.call(
                modal.querySelectorAll('select[name="component_item_id[]"]'),
                function (select) {
                    return !select.closest('.component-row-template');
                }
            );

            var primarySelect = selects[0] || null;
            var matchCount = 0;

            selects.forEach(function (select) {
                Array.prototype.forEach.call(select.options, function (option) {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }
                    var isMatch = !query || option.text.toLowerCase().indexOf(query) !== -1;
                    option.hidden = !isMatch;
                    if (primarySelect && select === primarySelect && isMatch) {
                        matchCount++;
                    }
                });

                var selectedOption = select.selectedOptions[0];
                if (selectedOption && selectedOption.hidden) {
                    select.value = '';
                }
            });

            var feedback = modal.querySelector('[data-inventory-filter-feedback]');
            if (!feedback) {
                return;
            }
            if (!query) {
                feedback.classList.add('d-none');
                feedback.textContent = '';
            } else {
                feedback.classList.remove('d-none');
                feedback.textContent = matchCount
                    ? matchCount + ' matching item' + (matchCount === 1 ? '' : 's')
                    : 'No per-piece items match that search.';
            }
        };

        var refreshFilterState = function (modalContent) {
            if (!modalContent) {
                return;
            }
            var input = modalContent.querySelector('[data-inventory-filter-input]');
            if (input && input.value.trim()) {
                filterInventoryOptions(input);
            }
        };

        document.querySelectorAll('.component-row').forEach(function (row) {
            attachRemoveHandler(row);
        });

        document.querySelectorAll('[data-add-component-row]').forEach(function (button) {
            button.addEventListener('click', function () {
                var targetSelector = button.getAttribute('data-target');
                var target = document.querySelector(targetSelector);
                if (!target) {
                    return;
                }

                var modalContent = button.closest('.modal-content');
                if (!modalContent) {
                    return;
                }

                var template = modalContent.querySelector('.component-row-template .component-row');
                if (!template) {
                    return;
                }

                var clone = template.cloneNode(true);
                clone.querySelectorAll('select, input').forEach(function (input) {
                    input.value = '';
                    input.disabled = false;
                });
                clone.querySelectorAll('[data-remove-component-row]').forEach(function (btn) {
                    btn.disabled = false;
                });

                target.appendChild(clone);
                attachRemoveHandler(clone);
                refreshFilterState(modalContent);
            });
        });

        var scheduleFilter = function (input) {
            if (!input) {
                return;
            }
            clearTimeout(input._inventoryFilterTimer);
            input._inventoryFilterTimer = setTimeout(function () {
                filterInventoryOptions(input);
            }, 200);
        };

        document.querySelectorAll('[data-inventory-filter-input]').forEach(function (input) {
            input.addEventListener('input', function () {
                scheduleFilter(input);
            });
            input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    filterInventoryOptions(input);
                }
            });
        });

        document.querySelectorAll('[data-trigger-inventory-filter]').forEach(function (button) {
            button.addEventListener('click', function () {
                var wrapper = button.closest('.inventory-search');
                var input = wrapper ? wrapper.querySelector('[data-inventory-filter-input]') : null;
                if (!input) {
                    return;
                }
                filterInventoryOptions(input);
            });
        });

        document.querySelectorAll('[data-clear-inventory-filter]').forEach(function (button) {
            button.addEventListener('click', function () {
                var wrapper = button.closest('.inventory-search');
                var input = wrapper ? wrapper.querySelector('[data-inventory-filter-input]') : null;
                if (!input) {
                    return;
                }
                input.value = '';
                filterInventoryOptions(input);
                input.focus();
            });
        });
    });
    </script>
    <script type="module" src="../assets/js/admin-bg-remover.js"></script>

    <?php include 'includes/footer.php'; ?>
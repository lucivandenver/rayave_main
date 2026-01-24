<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

ensureLowStockThresholdColumn();
ensureProductAvailabilityColumn();

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_product'])) {
        $product_id = (int) $_POST['product_id'];

        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
        $stmt->execute([$product_id]);
        $existingProduct = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingProduct) {
            $_SESSION['error'] = "Product not found.";
            redirect('products.php');
        }

        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];
        $stock_quantity = (int) $_POST['stock_quantity'];
        $weight = $_POST['weight'] !== '' ? $_POST['weight'] : null;
        $dimensions = $_POST['dimensions'];
        $tags = $_POST['tags'];
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $low_stock_threshold = max(0, (int) $_POST['low_stock_threshold']);
        $availability_status_input = $_POST['availability_status'] ?? 'available';
        $availability_status = in_array($availability_status_input, ['available', 'unavailable']) ? $availability_status_input : 'available';

        $image = $existingProduct['image'];
        $newImageUploaded = false;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload_dir = '../assets/images/products/';

            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    $_SESSION['error'] = "Failed to create upload directory.";
                    redirect('products.php');
                }
            }

            if (!is_writable($upload_dir)) {
                $_SESSION['error'] = "Upload directory is not writable. Please check permissions.";
                redirect('products.php');
            }

            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['image']['type'];
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            if (!in_array($file_type, $allowed_types) || !in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $_SESSION['error'] = "Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.";
                redirect('products.php');
            }

            $max_file_size = 5 * 1024 * 1024;
            if ($_FILES['image']['size'] > $max_file_size) {
                $_SESSION['error'] = "File too large. Maximum size allowed is 5MB.";
                redirect('products.php');
            }

            $original_name = basename($_FILES['image']['name']);
            $safe_name = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $original_name);
            $newFilename = time() . '_' . $safe_name;
            $target_file = $upload_dir . $newFilename;

            if (file_exists($target_file)) {
                $newFilename = time() . '_' . uniqid() . '_' . $safe_name;
                $target_file = $upload_dir . $newFilename;
            }

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                if (!file_exists($target_file) || !getimagesize($target_file)) {
                    $_SESSION['error'] = "Uploaded file is not a valid image.";
                    if (file_exists($target_file)) {
                        unlink($target_file);
                    }
                    redirect('products.php');
                }
                $image = $newFilename;
                $newImageUploaded = true;
            } else {
                $_SESSION['error'] = "Sorry, there was an error uploading your file. Please try again.";
                redirect('products.php');
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== 4) {
            $upload_errors = [
                1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                3 => 'The uploaded file was only partially uploaded.',
                6 => 'Missing a temporary folder.',
                7 => 'Failed to write file to disk.',
                8 => 'A PHP extension stopped the file upload.'
            ];
            $error_code = $_FILES['image']['error'];
            $error_message = $upload_errors[$error_code] ?? 'Unknown upload error.';
            $_SESSION['error'] = "Image upload failed: " . $error_message;
            redirect('products.php');
        }

        $stmt = $pdo->prepare("UPDATE products 
                       SET name = ?, description = ?, price = ?, category_id = ?, weight = ?, dimensions = ?, tags = ?, is_featured = ?, availability_status = ?, low_stock_threshold = ?, image = ? 
                       WHERE id = ?");

        if ($stmt->execute([$name, $description, $price, $category_id, $weight, $dimensions, $tags, $is_featured, $availability_status, $low_stock_threshold, $image, $product_id])) {
            if ($newImageUploaded && $existingProduct['image'] && $existingProduct['image'] !== 'default.jpg') {
                $oldPath = '../assets/images/products/' . $existingProduct['image'];
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $stockDifference = $stock_quantity - $existingProduct['stock_quantity'];
            if ($stockDifference != 0) {
                updateInventory($product_id, $stockDifference, 'adjustment', 'Stock edited via product details');
            }

            $_SESSION['success'] = "Product updated successfully.";
        } else {
            $_SESSION['error'] = "Failed to update product.";
        }

        redirect('products.php');
    }

    if (isset($_POST['add_product'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];
        $stock_quantity = $_POST['stock_quantity'];
        $weight = $_POST['weight'];
        $dimensions = $_POST['dimensions'];
        $tags = $_POST['tags'];
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $low_stock_threshold = max(0, (int) ($_POST['low_stock_threshold'] ?? 5));
        $availability_status_input = $_POST['availability_status'] ?? 'available';
        $availability_status = in_array($availability_status_input, ['available', 'unavailable']) ? $availability_status_input : 'available';

        // Enhanced image upload with error handling
        $image = 'default.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $upload_dir = '../assets/images/products/';

            // Create upload directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    $_SESSION['error'] = "Failed to create upload directory.";
                    redirect('products.php');
                }
            }

            // Check if directory is writable
            if (!is_writable($upload_dir)) {
                $_SESSION['error'] = "Upload directory is not writable. Please check permissions.";
                redirect('products.php');
            }

            // Validate file type
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['image']['type'];
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            // Double check file type
            if (!in_array($file_type, $allowed_types) || !in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $_SESSION['error'] = "Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.";
                redirect('products.php');
            }

            // Check file size (5MB max)
            $max_file_size = 5 * 1024 * 1024; // 5MB in bytes
            if ($_FILES['image']['size'] > $max_file_size) {
                $_SESSION['error'] = "File too large. Maximum size allowed is 5MB.";
                redirect('products.php');
            }

            // Generate safe filename
            $original_name = basename($_FILES['image']['name']);
            $safe_name = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $original_name);
            $image = time() . '_' . $safe_name;
            $target_file = $upload_dir . $image;

            // Check if file already exists (unlikely with timestamp, but just in case)
            if (file_exists($target_file)) {
                $image = time() . '_' . uniqid() . '_' . $safe_name;
                $target_file = $upload_dir . $image;
            }

            // Attempt to move uploaded file
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                // Verify the file was actually uploaded and is an image
                if (!file_exists($target_file) || !getimagesize($target_file)) {
                    $_SESSION['error'] = "Uploaded file is not a valid image.";
                    $image = 'default.jpg'; // Fallback to default
                    // Remove the invalid file
                    if (file_exists($target_file)) {
                        unlink($target_file);
                    }
                }
            } else {
                $_SESSION['error'] = "Sorry, there was an error uploading your file. Please try again.";
                $image = 'default.jpg'; // Fallback to default
            }
        } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== 4) { // Error 4 = No file uploaded
            // Handle specific upload errors
            $upload_errors = [
                1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                3 => 'The uploaded file was only partially uploaded.',
                6 => 'Missing a temporary folder.',
                7 => 'Failed to write file to disk.',
                8 => 'A PHP extension stopped the file upload.'
            ];
            $error_code = $_FILES['image']['error'];
            $error_message = $upload_errors[$error_code] ?? 'Unknown upload error.';
            $_SESSION['error'] = "Image upload failed: " . $error_message;
        }

        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image, category_id, stock_quantity, weight, dimensions, tags, is_featured, availability_status, low_stock_threshold) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $description, $price, $image, $category_id, $stock_quantity, $weight, $dimensions, $tags, $is_featured, $availability_status, $low_stock_threshold])) {
            $_SESSION['success'] = "Product added successfully!";

            // Log inventory change
            $product_id = $pdo->lastInsertId();
            updateInventory($product_id, $stock_quantity, 'restock', 'Initial stock');

            redirect('products.php');
        } else {
            $_SESSION['error'] = "Failed to add product to database.";
            redirect('products.php');
        }
    }

    if (isset($_POST['update_stock'])) {
        $product_id = $_POST['product_id'];
        $change_quantity = $_POST['change_quantity'];
        $reason = $_POST['reason'];
        $notes = $_POST['notes'];

        if (updateInventory($product_id, $change_quantity, $reason, $notes)) {
            $_SESSION['success'] = "Inventory updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update inventory.";
        }
        redirect('products.php');
    }

    if (isset($_POST['update_threshold'])) {
        $product_id = (int) $_POST['product_id'];
        $low_stock_threshold = max(0, (int) $_POST['low_stock_threshold']);

        $stmt = $pdo->prepare("UPDATE products SET low_stock_threshold = ? WHERE id = ?");
        if ($stmt->execute([$low_stock_threshold, $product_id])) {
            $_SESSION['success'] = "Low stock alert updated.";
        } else {
            $_SESSION['error'] = "Failed to update low stock alert.";
        }
        redirect('products.php');
    }
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
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Product Management</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="fas fa-plus"></i> Add New Product
        </button>
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
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price (₱)</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Low Stock Alert</th>
                            <th>Featured</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No products match the current filters.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                            <?php
                            $image_path = '../assets/images/products/' . $product['image'];
                            $image_exists = file_exists($image_path);
                            ?>
                            <tr>
                                <td>
                                    <img src="../assets/images/products/<?php echo $product['image']; ?>"
                                        alt="<?php echo $product['name']; ?>"
                                        style="width: 50px; height: 50px; object-fit: cover;"
                                        class="rounded <?php echo !$image_exists ? 'border border-danger' : ''; ?>"
                                        onerror="this.onerror=null; this.src='../assets/images/products/default.jpg'">
                                    <?php if (!$image_exists): ?>
                                        <small class="text-danger d-block">File missing</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['category_name']; ?></td>
                                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <?php
                                    $stockClass = 'success';
                                    if ($product['stock_quantity'] <= 0) {
                                        $stockClass = 'danger';
                                    } elseif ($product['stock_quantity'] <= $product['low_stock_threshold']) {
                                        $stockClass = 'warning';
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $stockClass; ?>">
                                        <?php echo $product['stock_quantity']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $isManuallyUnavailable = ($product['availability_status'] ?? 'available') !== 'available';
                                    if ($product['stock_quantity'] <= 0) {
                                        $statusLabel = 'Out of Stock';
                                        $statusClass = 'danger';
                                    } elseif ($isManuallyUnavailable) {
                                        $statusLabel = 'Unavailable';
                                        $statusClass = 'secondary';
                                    } else {
                                        $statusLabel = 'Available';
                                        $statusClass = 'success';
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo $statusLabel; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="update_threshold" value="1">
                                        <input type="number" name="low_stock_threshold" class="form-control form-control-sm"
                                            min="0" style="width: 80px;"
                                            value="<?php echo (int) $product['low_stock_threshold']; ?>">
                                        <button type="submit" class="btn btn-outline-secondary btn-sm">Save</button>
                                    </form>
                                    <small class="text-muted">Alert when ≤
                                        <?php echo $product['low_stock_threshold']; ?></small>
                                </td>
                                <td>
                                    <?php if ($product['is_featured']): ?>
                                        <i class="fas fa-star text-warning"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="../product-detail.php?id=<?php echo $product['id']; ?>"
                                            class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-outline-secondary" data-bs-toggle="modal"
                                            data-bs-target="#editProductModal<?php echo $product['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-warning" data-bs-toggle="modal"
                                            data-bs-target="#stockModal<?php echo $product['id']; ?>">
                                            <i class="fas fa-boxes"></i>
                                        </button>
                                        <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-outline-danger"
                                            onclick="return confirm('Are you sure you want to delete this product?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>

                                    <!-- Stock Update Modal -->
                                    <div class="modal fade" id="stockModal<?php echo $product['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Update Stock: <?php echo $product['name']; ?>
                                                    </h5>
                                                    <button type="button" class="btn-close"
                                                        data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="product_id"
                                                            value="<?php echo $product['id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Current Stock:
                                                                <?php echo $product['stock_quantity']; ?></label>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Change Quantity</label>
                                                            <input type="number" name="change_quantity" class="form-control"
                                                                required
                                                                placeholder="Positive for restock, negative for adjustment">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Reason</label>
                                                            <select name="reason" class="form-select" required>
                                                                <option value="restock">Restock</option>
                                                                <option value="spoilage">Spoilage</option>
                                                                <option value="adjustment">Adjustment</option>
                                                                <option value="return">Return</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Notes</label>
                                                            <textarea name="notes" class="form-control" rows="3"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                            data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="update_stock"
                                                            class="btn btn-warning">Update Stock</button>
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
                                                                <select name="category_id" class="form-select" required>
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
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Price (₱)</label>
                                                                <input type="number" name="price" class="form-control" step="0.01"
                                                                    value="<?php echo htmlspecialchars($product['price']); ?>" required>
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Stock Quantity</label>
                                                                <input type="number" name="stock_quantity" class="form-control"
                                                                    value="<?php echo (int) $product['stock_quantity']; ?>" required>
                                                            </div>
                                                            <div class="col-md-4 mb-3">
                                                                <label class="form-label">Weight (kg)</label>
                                                                <input type="number" name="weight" class="form-control" step="0.01"
                                                                    value="<?php echo htmlspecialchars($product['weight'] ?? ''); ?>">
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Availability</label>
                                                            <select name="availability_status" class="form-select">
                                                                <option value="available"
                                                                    <?php echo ($product['availability_status'] ?? 'available') === 'available' ? 'selected' : ''; ?>>Available</option>
                                                                <option value="unavailable"
                                                                    <?php echo ($product['availability_status'] ?? 'available') === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                                                            </select>
                                                            <div class="form-text">Unavailable items remain visible but customers cannot add them to the cart.</div>
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

                                                        <div class="mb-3">
                                                            <label class="form-label">Low Stock Alert Threshold</label>
                                                            <input type="number" name="low_stock_threshold" class="form-control" min="0"
                                                                value="<?php echo (int) $product['low_stock_threshold']; ?>" required>
                                                            <div class="form-text">Alerts trigger when stock is at or below this amount.</div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Product Image</label>
                                                            <input type="file" name="image" class="form-control"
                                                                accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                                            <div class="form-text">Leave blank to keep current image.
                                                                <br>Current file: <strong><?php echo htmlspecialchars($product['image']); ?></strong>
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
                            <select name="category_id" class="form-select" required>
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
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price (₱)</label>
                            <input type="number" name="price" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" name="stock_quantity" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
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

                    <div class="mb-3">
                        <label class="form-label">Availability</label>
                        <select name="availability_status" class="form-select">
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                        <div class="form-text">Mark products as unavailable to temporarily stop orders without deleting them.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Low Stock Alert Threshold</label>
                        <input type="number" name="low_stock_threshold" class="form-control" min="0" value="5" required>
                        <div class="form-text">Inventory alerts will trigger when stock is at or below this number.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Product Image</label>
                        <input type="file" name="image" class="form-control"
                            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                        <div class="form-text">Allowed formats: JPG, PNG, GIF, WebP. Max size: 5MB</div>
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

<?php include 'includes/footer.php'; ?>
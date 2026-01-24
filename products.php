<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

ensureProductAvailabilityColumn();

$category_id = isset($_GET['category']) ? $_GET['category'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$perPage = 6;
$currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$categories = getCategories();

$conditions = [];
$params = [];

if ($category_id) {
    $conditions[] = 'p.category_id = :category_id';
    $params['category_id'] = $category_id;
}

if ($search !== '') {
    $conditions[] = '(p.name LIKE :search_name OR p.description LIKE :search_description)';
    $params['search_name'] = '%' . $search . '%';
    $params['search_description'] = '%' . $search . '%';
}

$whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$countSql = "SELECT COUNT(*) FROM products p $whereSql";
$countStmt = $pdo->prepare($countSql);
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
            ORDER BY p.created_at DESC 
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
if ($category_id) {
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

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Categories</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="products.php" class="list-group-item list-group-item-action 
                       <?php echo !$category_id ? 'active' : ''; ?>">All Products</a>
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
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search products..."
                        value="<?php echo $search; ?>">
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
                        $isOutOfStock = $product['stock_quantity'] <= 0;
                        $isManuallyUnavailable = ($product['availability_status'] ?? 'available') !== 'available';
                        $statusLabel = $isOutOfStock ? 'Out of Stock' : ($isManuallyUnavailable ? 'Unavailable' : '');
                        $statusClass = $isOutOfStock ? 'bg-danger' : 'bg-secondary';
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

                                    <?php if (isLoggedIn() && !$isOutOfStock && !$isManuallyUnavailable): ?>
                                        <form method="POST" action="add-to-cart.php">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <div class="input-group mb-2">
                                                <input type="number" name="quantity" class="form-control" value="1" min="1"
                                                    max="<?php echo $product['stock_quantity']; ?>">
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
</div>

<?php include 'includes/footer.php'; ?>
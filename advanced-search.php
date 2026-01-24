<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

ensureProductAvailabilityColumn();

$categories = getCategories();
$search_query = $_GET['q'] ?? '';
$category_id = $_GET['category'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$sort = $_GET['sort'] ?? 'name';
$rating = $_GET['rating'] ?? '';

// Build search parameters
$params = [];
if (!empty($search_query)) $params['q'] = $search_query;
if (!empty($category_id)) $params['category'] = $category_id;
if (!empty($min_price)) $params['min_price'] = $min_price;
if (!empty($max_price)) $params['max_price'] = $max_price;
if (!empty($sort)) $params['sort'] = $sort;
if (!empty($rating)) $params['rating'] = $rating;

// Perform search
$products = searchProducts($search_query, $category_id, $min_price, $max_price, $sort);

// Filter by rating if specified
if (!empty($rating)) {
    $products = array_filter($products, function($product) use ($rating) {
        $rating_info = getAverageRating($product['id']);
        return $rating_info['avg_rating'] >= $rating;
    });
}

// Get popular search tags
$popular_tags = $pdo->query("SELECT tags FROM products WHERE tags IS NOT NULL AND tags != ''")->fetchAll(PDO::FETCH_COLUMN);
$all_tags = [];
foreach ($popular_tags as $tag_string) {
    $tags = explode(',', $tag_string);
    foreach ($tags as $tag) {
        $tag = trim($tag);
        if (!empty($tag)) {
            $all_tags[$tag] = isset($all_tags[$tag]) ? $all_tags[$tag] + 1 : 1;
        }
    }
}
arsort($all_tags);
$popular_tags = array_slice(array_keys($all_tags), 0, 10);
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <div class="row">
        <!-- Search Filters -->
        <div class="col-lg-3">
            <div class="card search-filter mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">Search Filters</h6>
                </div>
                <div class="card-body">
                    <form method="GET" id="searchForm">
                        <!-- Search Query -->
                        <div class="mb-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="q" class="form-control" 
                                   value="<?php echo htmlspecialchars($search_query); ?>" 
                                   placeholder="Search products...">
                        </div>

                        <!-- Category Filter -->
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo $category['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Price Range -->
                        <div class="mb-3">
                            <label class="form-label">Price Range</label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="number" name="min_price" class="form-control" 
                                           placeholder="Min" value="<?php echo $min_price; ?>" step="0.01">
                                </div>
                                <div class="col-6">
                                    <input type="number" name="max_price" class="form-control" 
                                           placeholder="Max" value="<?php echo $max_price; ?>" step="0.01">
                                </div>
                            </div>
                        </div>

                        <!-- Rating Filter -->
                        <div class="mb-3">
                            <label class="form-label">Minimum Rating</label>
                            <select name="rating" class="form-select">
                                <option value="">Any Rating</option>
                                <option value="5" <?php echo $rating == '5' ? 'selected' : ''; ?>>5 Stars</option>
                                <option value="4" <?php echo $rating == '4' ? 'selected' : ''; ?>>4+ Stars</option>
                                <option value="3" <?php echo $rating == '3' ? 'selected' : ''; ?>>3+ Stars</option>
                                <option value="2" <?php echo $rating == '2' ? 'selected' : ''; ?>>2+ Stars</option>
                                <option value="1" <?php echo $rating == '1' ? 'selected' : ''; ?>>1+ Stars</option>
                            </select>
                        </div>

                        <!-- Sort Options -->
                        <div class="mb-3">
                            <label class="form-label">Sort By</label>
                            <select name="sort" class="form-select">
                                <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                                <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Name Z-A</option>
                                <option value="price" <?php echo $sort == 'price' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <button type="button" onclick="resetFilters()" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="fas fa-times"></i> Reset Filters
                        </button>
                    </form>
                </div>
            </div>

            <!-- Popular Tags -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Popular Tags</h6>
                </div>
                <div class="card-body">
                    <div class="tag-cloud">
                        <?php foreach ($popular_tags as $tag): ?>
                        <a href="advanced-search.php?q=<?php echo urlencode($tag); ?>" 
                           class="badge bg-light text-dark text-decoration-none me-1 mb-1">
                            <?php echo $tag; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Results -->
        <div class="col-lg-9">
            <!-- Search Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Search Results</h2>
                    <p class="text-muted mb-0">
                        Found <?php echo count($products); ?> product(s)
                        <?php if (!empty($search_query)): ?>
                            for "<?php echo htmlspecialchars($search_query); ?>"
                        <?php endif; ?>
                    </p>
                </div>
                <div class="d-none d-md-block">
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-success active" onclick="setView('grid')">
                            <i class="fas fa-th"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success" onclick="setView('list')">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Active Filters -->
            <?php if (!empty(array_filter($params))): ?>
            <div class="mb-4">
                <strong>Active Filters:</strong>
                <?php foreach ($params as $key => $value): ?>
                    <?php if (!empty($value)): ?>
                    <span class="badge bg-success me-1 mb-1">
                        <?php 
                        $display_key = [
                            'q' => 'Search',
                            'category' => 'Category',
                            'min_price' => 'Min Price',
                            'max_price' => 'Max Price',
                            'sort' => 'Sort By',
                            'rating' => 'Rating'
                        ][$key] ?? $key;
                        
                        $display_value = $value;
                        if ($key === 'category') {
                            foreach ($categories as $cat) {
                                if ($cat['id'] == $value) {
                                    $display_value = $cat['name'];
                                    break;
                                }
                            }
                        } elseif ($key === 'sort') {
                            $display_value = [
                                'name' => 'Name A-Z',
                                'name_desc' => 'Name Z-A',
                                'price' => 'Price: Low to High',
                                'price_desc' => 'Price: High to Low',
                                'newest' => 'Newest First',
                                'rating' => 'Highest Rated'
                            ][$value] ?? $value;
                        } elseif ($key === 'rating') {
                            $display_value = $value . '+ Stars';
                        }
                        ?>
                        <?php echo $display_key; ?>: <?php echo $display_value; ?>
                        <a href="<?php echo removeParam($key); ?>" class="text-white ms-1">
                            <i class="fas fa-times"></i>
                        </a>
                    </span>
                    <?php endif; ?>
                <?php endforeach; ?>
                <a href="advanced-search.php" class="btn btn-sm btn-outline-danger ms-2">
                    Clear All
                </a>
            </div>
            <?php endif; ?>

            <!-- Products Grid -->
            <div id="productsGrid" class="view-grid">
                <?php if (empty($products)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4>No products found</h4>
                        <p class="text-muted">Try adjusting your search criteria or browse all products.</p>
                        <a href="products.php" class="btn btn-success">Browse All Products</a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($products as $product): 
                            $rating_info = getAverageRating($product['id']);
                            $isOutOfStock = $product['stock_quantity'] <= 0;
                            $isManuallyUnavailable = ($product['availability_status'] ?? 'available') !== 'available';
                            $statusLabel = $isOutOfStock ? 'Out of Stock' : ($isManuallyUnavailable ? 'Unavailable' : '');
                            $statusClass = $isOutOfStock ? 'bg-danger' : 'bg-secondary';
                        ?>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                            <div class="card product-card h-100">
                                <img src="assets/images/products/<?php echo $product['image']; ?>" 
                                     class="card-img-top" alt="<?php echo $product['name']; ?>"
                                     style="height: 200px; object-fit: cover;">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo $product['name']; ?></h6>
                                    <p class="card-text text-muted small"><?php echo $product['category_name']; ?></p>
                                    <?php if ($statusLabel): ?>
                                        <span class="badge <?php echo $statusClass; ?> mb-2"><?php echo $statusLabel; ?></span>
                                    <?php endif; ?>
                                    
                                    <!-- Rating -->
                                    <?php if ($rating_info['avg_rating']): ?>
                                    <div class="mb-2">
                                        <div class="rating-stars small">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= round($rating_info['avg_rating']) ? 'text-warning' : 'text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                            <small class="text-muted">(<?php echo $rating_info['review_count']; ?>)</small>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <p class="card-text text-success fw-bold">₱<?php echo $product['price']; ?></p>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-outline-success btn-sm">View Details</a>
                                        <?php if (isLoggedIn() && !$isOutOfStock && !$isManuallyUnavailable): ?>
                                        <form method="POST" action="add-to-cart.php" class="d-grid">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-cart-plus"></i> Add to Cart
                                            </button>
                                        </form>
                                        <?php elseif ($isOutOfStock || $isManuallyUnavailable): ?>
                                        <div class="alert alert-light border <?php echo $isOutOfStock ? 'border-warning text-warning' : 'border-secondary text-muted'; ?> py-2 mb-0">
                                            <?php echo $isOutOfStock ? 'Currently out of stock' : 'Temporarily unavailable'; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Products List View (Hidden by default) -->
            <div id="productsList" class="view-list d-none">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): 
                        $rating_info = getAverageRating($product['id']);
                        $isOutOfStock = $product['stock_quantity'] <= 0;
                        $isManuallyUnavailable = ($product['availability_status'] ?? 'available') !== 'available';
                        $statusLabel = $isOutOfStock ? 'Out of Stock' : ($isManuallyUnavailable ? 'Unavailable' : '');
                        $statusClass = $isOutOfStock ? 'badge bg-danger' : 'badge bg-secondary';
                    ?>
                    <div class="card mb-3">
                        <div class="row g-0">
                            <div class="col-md-3">
                                <img src="assets/images/products/<?php echo $product['image']; ?>" 
                                     class="img-fluid rounded-start h-100" alt="<?php echo $product['name']; ?>"
                                     style="object-fit: cover;">
                            </div>
                            <div class="col-md-9">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $product['name']; ?></h5>
                                    <p class="card-text text-muted"><?php echo $product['category_name']; ?></p>
                                    <?php if ($statusLabel): ?>
                                        <span class="<?php echo $statusClass; ?> mb-2"><?php echo $statusLabel; ?></span>
                                    <?php endif; ?>
                                    <p class="card-text"><?php echo substr($product['description'], 0, 150); ?>...</p>
                                    
                                    <?php if ($rating_info['avg_rating']): ?>
                                    <div class="mb-2">
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= round($rating_info['avg_rating']) ? 'text-warning' : 'text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                            <span class="text-muted">(<?php echo $rating_info['review_count']; ?> reviews)</span>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h4 class="text-success mb-0">₱<?php echo $product['price']; ?></h4>
                                        <div class="btn-group">
                                            <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                                               class="btn btn-outline-success">View Details</a>
                                            <?php if (isLoggedIn() && !$isOutOfStock && !$isManuallyUnavailable): ?>
                                            <form method="POST" action="add-to-cart.php" class="d-inline">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                                </button>
                                            </form>
                                            <?php elseif ($isOutOfStock || $isManuallyUnavailable): ?>
                                            <span class="badge <?php echo $isOutOfStock ? 'bg-warning text-dark' : 'bg-secondary'; ?> align-self-center"><?php echo $statusLabel; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function resetFilters() {
    document.getElementById('searchForm').reset();
    window.location.href = 'advanced-search.php';
}

function setView(view) {
    if (view === 'grid') {
        document.getElementById('productsGrid').classList.remove('d-none');
        document.getElementById('productsList').classList.add('d-none');
        document.querySelector('[onclick="setView(\'grid\')"]').classList.add('active');
        document.querySelector('[onclick="setView(\'list\')"]').classList.remove('active');
    } else {
        document.getElementById('productsGrid').classList.add('d-none');
        document.getElementById('productsList').classList.remove('d-none');
        document.querySelector('[onclick="setView(\'grid\')"]').classList.remove('active');
        document.querySelector('[onclick="setView(\'list\')"]').classList.add('active');
    }
}

// View toggle buttons
document.querySelectorAll('.btn-group .btn').forEach(btn => {
    btn.addEventListener('click', function() {
        this.classList.add('active');
        this.parentElement.querySelectorAll('.btn').forEach(otherBtn => {
            if (otherBtn !== this) {
                otherBtn.classList.remove('active');
            }
        });
    });
});
</script>

<style>
.view-grid, .view-list {
    transition: opacity 0.3s ease;
}

.tag-cloud {
    line-height: 2;
}

.product-card {
    transition: all 0.3s ease;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.btn-group .btn.active {
    background-color: #198754;
    border-color: #198754;
    color: white;
}
</style>

<?php
// Helper function to remove parameters from URL
function removeParam($param_to_remove) {
    $params = $_GET;
    unset($params[$param_to_remove]);
    return 'advanced-search.php?' . http_build_query($params);
}
?>

<?php include 'includes/footer.php'; ?>
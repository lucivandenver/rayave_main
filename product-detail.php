<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isset($_GET['id'])) {
    redirect('products.php');
}

$product_id = $_GET['id'];
$product = getProduct($product_id);

if (!$product) {
    redirect('products.php');
}

$reviews = getProductReviews($product_id);
$rating_info = getAverageRating($product_id);

$isOutOfStock = $product['stock_quantity'] <= 0;
$isManuallyUnavailable = ($product['availability_status'] ?? 'available') !== 'available';
$statusLabel = $isOutOfStock ? 'Out of Stock' : ($isManuallyUnavailable ? 'Unavailable' : 'In Stock');
$statusClass = $isOutOfStock ? 'text-danger' : ($isManuallyUnavailable ? 'text-warning' : 'text-success');

// Safe related products with error handling
try {
    $related_products = getRelatedProducts($product_id, $product['category_id']);
} catch (Exception $e) {
    $related_products = []; // Fallback to empty array
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please login to submit a review";
        redirect('login.php');
    }

    $rating = $_POST['rating'];
    $review_text = trim($_POST['review_text']);

    if (addProductReview($product_id, $_SESSION['user_id'], $rating, $review_text)) {
        $_SESSION['success'] = "Review submitted successfully!";
        redirect("product-detail.php?id=$product_id");
    } else {
        $_SESSION['error'] = "You have already reviewed this product or an error occurred.";
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <div class="row">
        <!-- Product Images -->
        <div class="col-lg-6">
            <?php
            $product_img = 'assets/images/products/' . ($product['image'] ?? '');
            if (!file_exists($product_img) || empty($product['image'])) {
                $product_img = 'assets/images/products/wrapper.png';
            }
            ?>
            <img src="<?php echo $product_img; ?>" class="img-fluid rounded"
                alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.onerror=null;this.src='assets/images/products/wrapper.png'">
        </div>

        <!-- Product Info -->
        <div class="col-lg-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                    <li class="breadcrumb-item active"><?php echo $product['name']; ?></li>
                </ol>
            </nav>

            <h1 class="display-5"><?php echo $product['name']; ?></h1>

            <!-- Rating -->
            <div class="mb-3">
                <?php if ($rating_info['avg_rating']): ?>
                    <div class="d-flex align-items-center">
                        <div class="rating-stars me-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i
                                    class="fas fa-star <?php echo $i <= round($rating_info['avg_rating']) ? 'text-warning' : 'text-muted'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="text-muted">(<?php echo $rating_info['review_count']; ?> reviews)</span>
                    </div>
                <?php else: ?>
                    <span class="text-muted">No reviews yet</span>
                <?php endif; ?>
            </div>

            <h3 class="text-success mb-4">₱<?php echo $product['price']; ?></h3>

            <p class="lead"><?php echo $product['description']; ?></p>

            <div class="mb-4">
                <strong>Category:</strong> <?php echo $product['category_name']; ?><br>
                <strong>Status:</strong>
                <span class="<?php echo $statusClass; ?>">
                    <?php echo $statusLabel; ?>
                </span>
                <?php if (!$isOutOfStock): ?>
                    <small class="text-muted">&mdash; <?php echo $product['stock_quantity']; ?> pcs available</small>
                <?php endif; ?>
            </div>

            <?php if (isLoggedIn() && !$isOutOfStock && !$isManuallyUnavailable): ?>
                <form method="POST" action="add-to-cart.php" class="mb-4">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <label for="quantity" class="form-label">Quantity:</label>
                        </div>
                        <div class="col-auto">
                            <input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1"
                                max="<?php echo $product['stock_quantity']; ?>" style="width: 80px;">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                        </div>
                    </div>
                </form>
            <?php elseif (!isLoggedIn() && !$isOutOfStock && !$isManuallyUnavailable): ?>
                <a href="login.php" class="btn btn-success btn-lg">Login to Purchase</a>
            <?php else: ?>
                <div class="alert <?php echo $isOutOfStock ? 'alert-warning' : 'alert-info'; ?>">
                    <?php echo $isOutOfStock ? 'This product is currently out of stock.' : 'This product is temporarily unavailable.'; ?>
                </div>
            <?php endif; ?>

            <!-- Product Tags -->
            <?php if (!empty($product['tags'])): ?>
                <div class="mt-4">
                    <strong>Tags:</strong>
                    <?php
                    $tags = explode(',', $product['tags']);
                    foreach ($tags as $tag):
                        ?>
                        <span class="badge bg-light text-dark"><?php echo trim($tag); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Customer Reviews</h5>
                </div>
                <div class="card-body">
                    <!-- Review Form -->
                    <?php if (isLoggedIn()): ?>
                        <div class="mb-5">
                            <h6>Write a Review</h6>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Rating</label>
                                    <div class="rating-input">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="rating"
                                                    id="rating<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                                <label class="form-check-label" for="rating<?php echo $i; ?>">
                                                    <?php echo $i; ?> <i class="fas fa-star text-warning"></i>
                                                </label>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="review_text" class="form-label">Review</label>
                                    <textarea class="form-control" id="review_text" name="review_text" rows="4"
                                        required></textarea>
                                </div>
                                <button type="submit" name="submit_review" class="btn btn-success">Submit Review</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <p>Please <a href="login.php">login</a> to write a review.</p>
                    <?php endif; ?>

                    <!-- Reviews List -->
                    <div class="reviews-list">
                        <?php if (empty($reviews)): ?>
                            <p class="text-muted">No reviews yet. Be the first to review this product!</p>
                        <?php else: ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo $review['full_name'] ?: $review['username']; ?></h6>
                                            <div class="rating-stars mb-2">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i
                                                        class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                        </small>
                                    </div>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
        <div class="row mt-5">
            <div class="col-12">
                <h3 class="mb-4">Related Products</h3>
                <div class="row">
                    <?php foreach ($related_products as $related): ?>
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="card product-card h-100">
                                <?php
                                $rel_img = 'assets/images/products/' . ($related['image'] ?? '');
                                if (!file_exists($rel_img) || empty($related['image'])) {
                                    $rel_img = 'assets/images/products/wrapper.png';
                                }
                                ?>
                                <img src="<?php echo $rel_img; ?>" class="card-img-top"
                                    alt="<?php echo htmlspecialchars($related['name']); ?>" style="height: 200px; object-fit: cover;"
                                    onerror="this.onerror=null;this.src='assets/images/products/wrapper.png'">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo $related['name']; ?></h6>
                                    <p class="card-text text-success">₱<?php echo $related['price']; ?></p>
                                    <a href="product-detail.php?id=<?php echo $related['id']; ?>"
                                        class="btn btn-outline-success btn-sm">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$featured_products = getProducts(null, true);
$categories = getCategories();
$featured_feedback = getCustomerFeedback(3, true);
?>

<?php include 'includes/header.php'; ?>

<style>

    .testimonial-card {
        border-radius: 20px;
        padding: 1.75rem;
        background: #ffffff;
        border: 1px solid #e3efe7;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
        height: 100%;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .testimonial-card::after {
        content: "“";
        position: absolute;
        font-size: 4rem;
        color: rgba(21, 128, 74, 0.08);
        top: -10px;
        right: 20px;
        font-family: Georgia, serif;
    }

    .testimonial-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 25px 55px rgba(15, 23, 42, 0.12);
    }

    .testimonial-rating {
        color: #ffaf2d;
        letter-spacing: 2px;
        font-size: 0.9rem;
    }

    .testimonial-avatar {
        height: 56px;
        width: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #dff8ed, #f0fffa);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #15804a;
        box-shadow: inset 0 0 0 2px rgba(33, 165, 108, 0.15);
    }

    .testimonial-card p {
        font-style: italic;
        font-size: 1rem;
        color: #1f352a;
    }

    .newsletter-card {
        background: #ffffff;
        border-radius: 22px;
        padding: 2rem;
        box-shadow: 0 15px 45px rgba(15, 40, 30, 0.08);
        height: 100%;
    }

    .newsletter-card form .form-control {
        border-radius: 999px;
        padding: 0.85rem 1.2rem;
        border: 1px solid #ced4da;
    }

    .newsletter-card form .btn {
        border-radius: 999px;
        padding: 0.85rem 2rem;
        font-weight: 600;
    }

    @media (max-width: 991px) {
        .newsletter-card {
            border-radius: 16px;
            padding: 1.5rem;
        }
    }
</style>

<!-- Hero Section -->
<!-- Hero Section -->
<section class="hero-section text-center"
    style="background-image: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)), url('assets/images/products/Screenshot_20251113-001342.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; padding: 120px 0; color: #fff;">
    <div class="container">
        <h1 class="display-4 fw-bold text-white" style="text-shadow: 0 2px 6px rgba(0,0,0,0.6);">Beautiful Flowers &
            Handcrafted Gifts</h1>
        <p class="lead text-white">Spread love and happiness with our exquisite floral arrangements</p>
        <a href="products.php" class="btn btn-light btn-lg mt-3">Shop Now</a>
    </div>
</section>

<!-- Featured Products -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Featured Products</h2>
        <div class="row">
            <?php foreach ($featured_products as $product): ?>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card product-card h-100">
                        <?php
                        $prod_img = 'assets/images/products/' . ($product['image'] ?? '');
                        if (!file_exists($prod_img) || empty($product['image'])) {
                            $prod_img = 'assets/images/products/wrapper.png';
                        }
                        ?>
                        <img src="<?php echo $prod_img; ?>" class="card-img-top"
                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                            style="height: 200px; object-fit: cover;"
                            onerror="this.onerror=null;this.src='assets/images/products/wrapper.png'">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $product['name']; ?></h5>
                            <p class="card-text">₱<?php echo $product['price']; ?></p>
                            <a href="product-detail.php?id=<?php echo $product['id']; ?>"
                                class="btn btn-outline-success">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Categories -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Shop by Category</h2>
        <div class="row">
            <?php foreach ($categories as $category): ?>
                <div class="col-md-4 mb-4">
                    <div class="card category-card">
                        <?php
                        $cat_img = 'assets/images/categories/' . ($category['image'] ?? '');
                        $cat_name_lower = isset($category['name']) ? strtolower(trim($category['name'])) : '';

                        // Custom images for specific categories
                        if ($cat_name_lower === 'arrangements') {
                            $cat_img = 'assets/images/products/istockphoto-1351158595-612x612.jpg';
                        } elseif ($cat_name_lower === 'best sellers') {
                            $cat_img = 'assets/images/products/our-best-seller-designers-choice-PDC11100420.425.jpg';
                        } elseif ($cat_name_lower === 'color themes') {
                            $cat_img = 'assets/images/products/Spring-Color-Palette-24-1024x1024.webp';
                        } elseif ($cat_name_lower === 'customize') {
                            $cat_img = 'assets/images/products/istockphoto-690258636-612x612.jpg';
                        } elseif ($cat_name_lower === 'flower types') {
                            $cat_img = 'assets/images/products/Untitled-design-1-1080x608.jpg';
                        } elseif ($cat_name_lower === 'occasions') {
                            $cat_img = 'assets/images/products/1CA0A562-630E-4795-A337-F0B8FFECA939_yh2eee.jpg';
                        } else {
                            // Fallback to a product wrapper image if category image missing
                            if (!file_exists($cat_img) || empty($category['image'])) {
                                $cat_img = 'assets/images/products/wrapper2.png';
                            }
                        }
                        ?>
                        <img src="<?php echo $cat_img; ?>" class="card-img-top category-img"
                            alt="<?php echo htmlspecialchars($category['name']); ?>"
                            onerror="this.onerror=null;this.src='assets/images/products/wrapper2.png'">
                        <div class="card-body text-center">
                            <h5 class="card-title"><?php echo $category['name']; ?></h5>
                            <a href="products.php?category=<?php echo $category['id']; ?>"
                                class="btn btn-success">Explore</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($featured_feedback)): ?>
    <!-- Customer Feedback -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <p class="text-success fw-semibold mb-1">Customer Feedback</p>
                <h2 class="fw-bold">Stories from Roxas & Beyond</h2>
                <p class="text-muted">Real notes from shoppers who brought RaymundoAvenue blooms into their celebrations.
                </p>
            </div>
            <div class="row g-4">
                <?php foreach ($featured_feedback as $feedback): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="testimonial-card h-100">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="testimonial-avatar">
                                        <?php echo strtoupper(substr($feedback['full_name'] ?? $feedback['username'], 0, 1)); ?>
                                    </div>
                                    <div class="mt-3">
                                        <h6 class="mb-0">
                                            <?php echo htmlspecialchars($feedback['full_name'] ?? $feedback['username']); ?>
                                        </h6>
                                        <small class="text-muted">Ordered
                                            <?php echo htmlspecialchars($feedback['product_name']); ?></small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="testimonial-rating"><?php echo str_repeat('★', (int) $feedback['rating']); ?>
                                    </div>
                                    <small
                                        class="text-muted"><?php echo date('M j, Y', strtotime($feedback['created_at'])); ?></small>
                                </div>
                            </div>
                            <p class="mt-3 mb-0">“<?php echo htmlspecialchars($feedback['comment']); ?>”</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Get the successful order ID from session
$order_id = $_SESSION['order_success'] ?? null;

if (!$order_id) {
    redirect('products.php');
}

// Clear the session
unset($_SESSION['order_success']);

// Get order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    redirect('products.php');
}

// Get order items
$stmt = $pdo->prepare("SELECT oi.*, p.name, p.image FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get best selling products for recommendations
$recommended_products = getBestSellingProducts(4);
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Success Message -->
            <div class="text-center mb-5">
                <div class="success-icon mb-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                </div>
                <h1 class="display-5 fw-bold text-success mb-3">Order Confirmed!</h1>
                <p class="lead mb-4">Thank you for your purchase. Your order has been received and is being processed.
                </p>
                <div class="alert alert-info">
                    <strong>Order Number:</strong> #<?php echo $order_id; ?><br>
                    <strong>Order Date:</strong>
                    <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?><br>
                    <strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($order_items as $item): ?>
                        <div class="row align-items-center mb-3">
                            <div class="col-2">
                                <img src="assets/images/products/<?php echo $item['image']; ?>"
                                    alt="<?php echo $item['name']; ?>" class="img-fluid rounded">
                            </div>
                            <div class="col-6">
                                <h6 class="mb-1"><?php echo $item['name']; ?></h6>
                                <p class="text-muted mb-0">Quantity: <?php echo $item['quantity']; ?></p>
                            </div>
                            <div class="col-4 text-end">
                                <strong>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <strong>Subtotal:</strong>
                        </div>
                        <div class="col-6 text-end">
                            $<?php echo number_format($order['total_amount'] / 1.1, 2); ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <strong>Tax:</strong>
                        </div>
                        <div class="col-6 text-end">
                            $<?php echo number_format($order['total_amount'] / 1.1 * 0.1, 2); ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <strong>Shipping:</strong>
                        </div>
                        <div class="col-6 text-end">
                            ₱10.00
                        </div>
                    </div>
                    <hr>
                    <div class="row fw-bold fs-5">
                        <div class="col-6">
                            Total:
                        </div>
                        <div class="col-6 text-end text-success">
                            $<?php echo number_format($order['total_amount'], 2); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipping Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Shipping Information</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Shipping Address:</strong></p>
                    <p class="text-muted"><?php echo nl2br($order['shipping_address']); ?></p>

                    <p class="mb-2"><strong>Payment Method:</strong></p>
                    <p class="text-muted text-capitalize">
                        <i class="fas fa-credit-card me-2"></i>
                        <?php echo str_replace('_', ' ', $order['payment_method']); ?>
                    </p>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">What's Next?</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4 mb-3">
                            <div class="step-icon mb-2">
                                <i class="fas fa-envelope fa-2x text-primary"></i>
                            </div>
                            <h6>Order Confirmation</h6>
                            <p class="small text-muted">You'll receive an email confirmation shortly</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="step-icon mb-2">
                                <i class="fas fa-truck fa-2x text-warning"></i>
                            </div>
                            <h6>Order Processing</h6>
                            <p class="small text-muted">We'll prepare your order for shipment</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="step-icon mb-2">
                                <i class="fas fa-home fa-2x text-success"></i>
                            </div>
                            <h6>Delivery</h6>
                            <p class="small text-muted">Your order will be delivered within 3-5 business days</p>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <a href="order-tracking.php?id=<?php echo $order_id; ?>" class="btn btn-success me-3">
                            <i class="fas fa-map-marker-alt"></i> Track Your Order
                        </a>
                        <a href="products.php" class="btn btn-outline-success">
                            <i class="fas fa-shopping-bag"></i> Continue Shopping
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recommended Products -->
            <?php if (!empty($recommended_products)): ?>
                <div class="mt-5">
                    <h3 class="mb-4">You Might Also Like</h3>
                    <div class="row">
                        <?php foreach ($recommended_products as $product): ?>
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="card product-card h-100">
                                    <img src="assets/images/products/<?php echo $product['image']; ?>" class="card-img-top"
                                        alt="<?php echo $product['name']; ?>" style="height: 200px; object-fit: cover;">
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo $product['name']; ?></h6>
                                        <p class="card-text text-success">₱<?php echo $product['price']; ?></p>
                                        <a href="product-detail.php?id=<?php echo $product['id']; ?>"
                                            class="btn btn-outline-success btn-sm">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .success-icon {
        animation: bounce 1s ease-in-out;
    }

    .step-icon {
        width: 80px;
        height: 80px;
        background: #f8f9fa;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
    }

    @keyframes bounce {

        0%,
        20%,
        60%,
        100% {
            transform: translateY(0);
        }

        40% {
            transform: translateY(-10px);
        }

        80% {
            transform: translateY(-5px);
        }
    }
</style>

<?php include 'includes/footer.php'; ?>
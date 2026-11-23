<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

ensureOrderRecipientColumns();

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

$isAlternateRecipient = !empty($order['recipient_is_alternate']);
$alternateRecipientName = trim((string) ($order['recipient_name'] ?? ''));
$alternateRecipientContact = trim((string) ($order['recipient_contact'] ?? ''));
$alternateRecipientAddress = trim((string) ($order['recipient_address'] ?? ''));
$alternateRecipientNotes = trim((string) ($order['recipient_notes'] ?? ''));

// Get order items
$stmt = $pdo->prepare("SELECT oi.*, p.name, p.image, p.category_id AS product_category_id FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$customizeCategoryId = getCustomizeCategoryId();
$customizeItems = [];
$regularItems = [];

foreach ($order_items as $item) {
    $itemCategoryId = isset($item['product_category_id']) ? (int) $item['product_category_id'] : null;
    if ($customizeCategoryId && $itemCategoryId === (int) $customizeCategoryId) {
        $customizeItems[] = $item;
    } else {
        $regularItems[] = $item;
    }
}

$customizeBundleTotal = array_reduce($customizeItems, function ($carry, $item) {
    return $carry + ((float) $item['price'] * (int) $item['quantity']);
}, 0.0);
$hasCustomizeBundle = !empty($customizeItems);
$bundleCollageLayout = [
    ['top' => 0, 'left' => 0, 'z' => 4, 'rotate' => '-5deg'],
    ['top' => 18, 'left' => 70, 'z' => 3, 'rotate' => '3deg'],
    ['top' => 60, 'left' => 10, 'z' => 2, 'rotate' => '2deg'],
    ['top' => 40, 'left' => 110, 'z' => 1, 'rotate' => '-2deg'],
];
$bundleCollageItems = array_slice($customizeItems, 0, count($bundleCollageLayout));
$bundleCollageExtra = max(0, count($customizeItems) - count($bundleCollageItems));

// Get best selling products for recommendations
$recommended_products = getBestSellingProducts(4);

$shippingFee = isset($order['shipping_fee']) ? (float) $order['shipping_fee'] : 0;
$shippingDetails = [];
if (!empty($order['shipping_details'])) {
    $decodedDetails = json_decode($order['shipping_details'], true);
    if (is_array($decodedDetails)) {
        $shippingDetails = $decodedDetails;
    }
}
$bundleStatus = $shippingDetails['bundle_status'] ?? [];
$isBundleFinalized = !empty($bundleStatus['finalized']);
$bundleFinalizedAt = $bundleStatus['finalized_at'] ?? null;

$bundleStatusLabel = $isBundleFinalized ? 'Final Bundle Total' : 'Estimated Total';
$subtotalLabel = $hasCustomizeBundle ? ($isBundleFinalized ? 'Final Arrangement Total' : 'Estimated Arrangement Total') : 'Subtotal';
$totalLabel = $hasCustomizeBundle ? ($isBundleFinalized ? 'Final Order Total' : 'Estimated Order Total') : 'Total';

$itemsSubtotal = max(0, $order['total_amount'] - $shippingFee);
$distanceText = $shippingDetails['distance_text'] ?? '';
$durationText = $shippingDetails['duration_text'] ?? '';
$shippingMessage = '';
if (!empty($shippingDetails['messages']) && is_array($shippingDetails['messages'])) {
    $shippingMessage = $shippingDetails['messages'][0];
}
$fulfillmentMethodLabel = 'Delivery';
if (($shippingDetails['method'] ?? '') === 'pickup' || $shippingFee <= 0) {
    $fulfillmentMethodLabel = 'Store Pickup';
}
?>

<?php include 'includes/header.php'; ?>

<?php if ($hasCustomizeBundle): ?>
<style>
    .bundle-collage-wrapper {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        align-items: center;
    }
    .bundle-collage {
        position: relative;
        width: 200px;
        height: 150px;
    }
    .bundle-collage img {
        position: absolute;
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 20px;
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.18);
        border: 4px solid #fff;
    }
    .bundle-collage-more {
        position: absolute;
        right: 8px;
        bottom: 8px;
        background: rgba(25, 135, 84, 0.9);
        color: #fff;
        border-radius: 999px;
        padding: 0.3rem 0.75rem;
        font-weight: 600;
        font-size: 0.85rem;
        box-shadow: 0 6px 14px rgba(25, 135, 84, 0.35);
    }
    .bundle-component-list {
        list-style: none;
        padding-left: 0;
        margin-bottom: 0;
    }
    .bundle-component-list li {
        display: flex;
        justify-content: space-between;
        font-size: 0.9rem;
        padding: 0.1rem 0;
        border-bottom: 1px dashed rgba(0, 0, 0, 0.05);
    }
    .bundle-component-list li:last-child {
        border-bottom: none;
    }
</style>
<?php endif; ?>

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
                    <strong>Total Amount:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <?php if ($hasCustomizeBundle): ?>
                        <div class="card shadow-sm border border-success mb-4">
                            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <h6 class="mb-0">Your Custom Bundle</h6>
                                    <small class="text-white-50">All stems will be arranged together.</small>
                                </div>
                                <div class="text-end">
                                        <span class="text-uppercase small d-block"><?php echo htmlspecialchars($bundleStatusLabel); ?></span>
                                    <strong>₱<?php echo number_format($customizeBundleTotal, 2); ?></strong>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="bundle-collage-wrapper">
                                    <div class="bundle-collage" aria-label="Bundle collage preview">
                                        <?php foreach ($bundleCollageItems as $index => $component): ?>
                                            <?php
                                                $layout = $bundleCollageLayout[$index];
                                                $imageFile = !empty($component['image']) ? $component['image'] : 'wrapper.png';
                                            ?>
                                            <img src="assets/images/products/<?php echo htmlspecialchars($imageFile); ?>"
                                                alt="Bundle component"
                                                style="top: <?php echo (int) $layout['top']; ?>px; left: <?php echo (int) $layout['left']; ?>px; z-index: <?php echo (int) $layout['z']; ?>; transform: rotate(<?php echo $layout['rotate']; ?>);">
                                        <?php endforeach; ?>
                                        <?php if ($bundleCollageExtra > 0): ?>
                                            <span class="bundle-collage-more">+<?php echo $bundleCollageExtra; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="mb-1 fw-semibold text-success"><?php echo count($customizeItems); ?> curated pieces locked in</p>
                                        <p class="text-muted small mb-2">
                                            <?php if ($isBundleFinalized): ?>
                                                Final price confirmed<?php echo $bundleFinalizedAt ? ' on ' . date('M j, Y g:i A', strtotime($bundleFinalizedAt)) : ''; ?>.
                                            <?php else: ?>
                                                Our florist will finalize the price and post it on your tracking page.
                                            <?php endif; ?>
                                        </p>
                                        <ul class="bundle-component-list">
                                            <?php foreach ($customizeItems as $component): ?>
                                                <li>
                                                    <span><?php echo htmlspecialchars($component['name']); ?></span>
                                                    <span class="text-muted">×<?php echo (int) $component['quantity']; ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($regularItems)): ?>
                        <?php foreach ($regularItems as $item): ?>
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
                    <?php endif; ?>

                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <strong><?php echo $subtotalLabel; ?>:</strong>
                        </div>
                        <div class="col-6 text-end">
                            ₱<?php echo number_format($itemsSubtotal, 2); ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <strong>Shipping:</strong>
                        </div>
                        <div class="col-6 text-end">
                            ₱<?php echo number_format($shippingFee, 2); ?>
                        </div>
                    </div>
                    <hr>
                    <div class="row fw-bold fs-5">
                        <div class="col-6">
                            <?php echo $totalLabel; ?>:
                        </div>
                        <div class="col-6 text-end text-success">
                            ₱<?php echo number_format($order['total_amount'], 2); ?>
                        </div>
                    </div>
                    <?php if ($hasCustomizeBundle): ?>
                        <div class="alert alert-<?php echo $isBundleFinalized ? 'success' : 'warning'; ?> small mt-3 mb-0">
                            <?php if ($isBundleFinalized): ?>
                                Custom bundle total locked in. You will also see this final amount inside order tracking.
                            <?php else: ?>
                                Final pricing will appear on your order tracking page once our florist completes the arrangement.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
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

                    <?php if ($isAlternateRecipient): ?>
                        <div class="alert alert-light border">
                            <div class="fw-semibold text-success mb-1"><i class="fas fa-user-check me-2"></i>Alternate Recipient</div>
                            <div><?php echo htmlspecialchars($alternateRecipientName); ?></div>
                            <?php if ($alternateRecipientContact !== ''): ?>
                                <div class="small text-muted">Contact: <?php echo htmlspecialchars($alternateRecipientContact); ?></div>
                            <?php endif; ?>
                            <?php if ($alternateRecipientAddress !== ''): ?>
                                <div class="small text-muted mt-1"><?php echo nl2br(htmlspecialchars($alternateRecipientAddress)); ?></div>
                            <?php endif; ?>
                            <?php if ($alternateRecipientNotes !== ''): ?>
                                <div class="small text-muted mt-2">Notes: <?php echo nl2br(htmlspecialchars($alternateRecipientNotes)); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <p class="mb-2"><strong>Fulfillment:</strong> <?php echo htmlspecialchars($fulfillmentMethodLabel); ?></p>
                    <?php if ($distanceText): ?>
                        <p class="mb-2">
                            <strong>Route Distance:</strong>
                            <?php echo htmlspecialchars($distanceText); ?>
                            <?php if ($durationText): ?>
                                <span class="text-muted">• <?php echo htmlspecialchars($durationText); ?></span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($shippingMessage): ?>
                        <p class="small text-muted mb-2"><?php echo htmlspecialchars($shippingMessage); ?></p>
                    <?php endif; ?>

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
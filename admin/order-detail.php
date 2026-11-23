<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

ensureOrderRecipientColumns();

$order_id = $_GET['id'] ?? 0;

// Get order details
$stmt = $pdo->prepare("SELECT o.*, u.username, u.full_name, u.email, u.phone 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    redirect('orders.php');
}

$isAlternateRecipient = !empty($order['recipient_is_alternate']);
$alternateRecipientName = trim((string) ($order['recipient_name'] ?? ''));
$alternateRecipientContact = trim((string) ($order['recipient_contact'] ?? ''));
$alternateRecipientAddress = trim((string) ($order['recipient_address'] ?? ''));
$alternateRecipientNotes = trim((string) ($order['recipient_notes'] ?? ''));

$shippingFee = isset($order['shipping_fee']) ? (float) $order['shipping_fee'] : 0;
$shippingDetails = [];
if (!empty($order['shipping_details'])) {
    $decodedDetails = json_decode($order['shipping_details'], true);
    if (is_array($decodedDetails)) {
        $shippingDetails = $decodedDetails;
    }
}

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

// Get order items
$stmt = $pdo->prepare("SELECT oi.*, p.name, p.image, p.category_id AS product_category_id 
                      FROM order_items oi 
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
$hasCustomizeBundle = !empty($customizeItems);
$orderCustomDesign = $hasCustomizeBundle ? getOrderCustomDesign($order_id) : null;
$orderCustomDesignImage = null;
if ($orderCustomDesign && !empty($orderCustomDesign['preview_image']) && file_exists(dirname(__DIR__) . '/' . $orderCustomDesign['preview_image'])) {
    $orderCustomDesignImage = '../' . $orderCustomDesign['preview_image'];
}
$customizeBundleTotal = array_reduce($customizeItems, function ($carry, $item) {
    return $carry + ((float) $item['price'] * (int) $item['quantity']);
}, 0.0);
$bundleCollageLayout = [
    ['top' => 0, 'left' => 0, 'z' => 4, 'rotate' => '-5deg'],
    ['top' => 18, 'left' => 70, 'z' => 3, 'rotate' => '3deg'],
    ['top' => 60, 'left' => 10, 'z' => 2, 'rotate' => '2deg'],
    ['top' => 40, 'left' => 110, 'z' => 1, 'rotate' => '-2deg'],
];
$bundleCollageItems = array_slice($customizeItems, 0, count($bundleCollageLayout));
$bundleCollageExtra = max(0, count($customizeItems) - count($bundleCollageItems));
$bundleStatus = $shippingDetails['bundle_status'] ?? [];
$isBundleFinalized = !empty($bundleStatus['finalized']);
$bundleFinalizedAt = $bundleStatus['finalized_at'] ?? null;

// Get order tracking history
$stmt = $pdo->prepare("SELECT * FROM order_tracking WHERE order_id = ? ORDER BY created_at DESC");
$stmt->execute([$order_id]);
$tracking_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle custom bundle pricing updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_custom_prices'])) {
    $customPriceInputs = $_POST['custom_price'] ?? [];
    if (!$customizeCategoryId || empty($customizeItems) || !is_array($customPriceInputs)) {
        $_SESSION['error'] = 'No customize items detected for this order.';
        redirect('order-detail.php?id=' . $order_id);
    }

    $updatedCount = 0;
    foreach ($customizeItems as $item) {
        $itemId = (int) $item['id'];
        if (!array_key_exists($itemId, $customPriceInputs)) {
            continue;
        }
        $newPrice = (float) $customPriceInputs[$itemId];
        if ($newPrice < 0) {
            $newPrice = 0;
        }
        if (abs($newPrice - (float) $item['price']) < 0.00001) {
            continue;
        }

        $stmt = $pdo->prepare("UPDATE order_items SET price = ? WHERE id = ?");
        $stmt->execute([$newPrice, $itemId]);
        $updatedCount++;
    }

    if ($updatedCount === 0) {
        $_SESSION['error'] = 'No price changes were detected.';
        redirect('order-detail.php?id=' . $order_id);
    }

    $stmt = $pdo->prepare("SELECT SUM(price * quantity) FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $itemsSubtotal = (float) $stmt->fetchColumn();
    $newTotalAmount = $itemsSubtotal + $shippingFee;

    $stmt = $pdo->prepare("SELECT SUM(oi.price * oi.quantity) FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ? AND p.category_id = ?");
    $stmt->execute([$order_id, $customizeCategoryId]);
    $finalBundleTotal = (float) $stmt->fetchColumn();

    $bundleStatusUpdate = $shippingDetails['bundle_status'] ?? [];
    $bundleStatusUpdate['has_custom_bundle'] = true;
    $bundleStatusUpdate['finalized'] = true;
    $bundleStatusUpdate['finalized_at'] = date('c');
    $bundleStatusUpdate['final_total'] = $finalBundleTotal;
    $shippingDetails['bundle_status'] = $bundleStatusUpdate;

    $stmt = $pdo->prepare("UPDATE orders SET total_amount = ?, shipping_details = ? WHERE id = ?");
    $stmt->execute([
        $newTotalAmount,
        json_encode($shippingDetails, JSON_UNESCAPED_UNICODE),
        $order_id
    ]);

    $_SESSION['success'] = 'Custom bundle pricing updated successfully.';
    redirect('order-detail.php?id=' . $order_id);
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $status = $_POST['status'] ?? '';
    $tracking_number = trim($_POST['tracking_number'] ?? '');

    $descriptions = getOrderStatusDescriptions($tracking_number);
    $description = $descriptions[$status] ?? '';

    if (updateOrderStatus($order_id, $status, $description, true, $tracking_number)) {
        $_SESSION['success'] = "Order status updated successfully!";
        redirect('order-detail.php?id=' . $order_id);
    } else {
        $_SESSION['error'] = "Failed to update order status.";
    }
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
</style>
<?php endif; ?>

<div class="container-fluid py-4">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Order #<?php echo $order['id']; ?></h2>
        <a href="orders.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Orders
        </a>
    </div>

    <div class="row">
        <!-- Order Information -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="m-0 font-weight-bold">Order Information</h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Customer Details</h6>
                            <p>
                                <strong>Name:</strong> <?php echo htmlspecialchars($order['full_name'] ?: $order['email']); ?><br>
                                <strong>Email:</strong> <?php echo $order['email']; ?><br>
                                <strong>Phone:</strong> <?php echo $order['phone'] ?? 'N/A'; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6>Order Details</h6>
                            <p>
                                <strong>Order ID:</strong> #<?php echo $order['id']; ?><br>
                                <strong>Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?><br>
                                <strong>Status:</strong> 
                                <span class="badge bg-<?php
                                echo $order['status'] === 'delivered' ? 'success' :
                                    ($order['status'] === 'cancelled' ? 'danger' :
                                        ($order['status'] === 'pending' ? 'warning' : 'info'));
                                ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Shipping Address</h6>
                            <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
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
                            <p class="mb-1"><strong>Fulfillment:</strong> <?php echo htmlspecialchars($fulfillmentMethodLabel); ?></p>
                            <?php if ($distanceText): ?>
                                <p class="mb-1">
                                    <strong>Route Distance:</strong> <?php echo htmlspecialchars($distanceText); ?>
                                    <?php if ($durationText): ?>
                                        <span class="text-muted">• <?php echo htmlspecialchars($durationText); ?></span>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($shippingMessage): ?>
                                <p class="small text-muted mb-0"><?php echo htmlspecialchars($shippingMessage); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6>Payment Method</h6>
                            <p class="text-capitalize">
                                <?php echo str_replace('_', ' ', $order['payment_method']); ?>
                            </p>
                            <?php if ($order['tracking_number']): ?>
                                <h6>Tracking Number</h6>
                                <p><?php echo $order['tracking_number']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="m-0 font-weight-bold">Order Items</h6>
                </div>
                <div class="card-body">
                    <?php if ($hasCustomizeBundle): ?>
                        <div class="card shadow-sm border border-success mb-4">
                            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <h6 class="mb-0">Customize Bundle</h6>
                                    <small class="text-white-50">
                                        <?php echo $isBundleFinalized ? 'Final price locked in' : 'Set the final payout before arranging'; ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="text-uppercase small d-block"><?php echo $isBundleFinalized ? 'Final Bundle Total' : 'Current Estimate'; ?></span>
                                    <strong>₱<?php echo number_format($customizeBundleTotal, 2); ?></strong>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="bundle-collage-wrapper mb-4">
                                    <?php if ($orderCustomDesignImage): ?>
                                        <div class="border rounded-3 p-2 bg-light text-center" style="width: 220px; flex-shrink: 0;">
                                            <a href="#" data-bs-toggle="modal" data-bs-target="#adminDesignZoomModal" title="Click to enlarge florist reference">
                                                <img src="<?php echo htmlspecialchars($orderCustomDesignImage); ?>"
                                                    alt="Customer's custom bouquet canvas design"
                                                    class="img-fluid rounded"
                                                    style="max-height: 220px; object-fit: contain;">
                                            </a>
                                            <div class="d-flex gap-1 mt-2">
                                                <button type="button" class="btn btn-outline-success btn-sm flex-fill" data-bs-toggle="modal" data-bs-target="#adminDesignZoomModal">
                                                    <i class="fas fa-search-plus me-1"></i>Zoom
                                                </button>
                                                <a href="<?php echo htmlspecialchars($orderCustomDesignImage); ?>"
                                                    download="order-<?php echo (int) $order['id']; ?>-bouquet-design.png"
                                                    class="btn btn-success btn-sm flex-fill">
                                                    <i class="fas fa-download me-1"></i>Save
                                                </a>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="bundle-collage" aria-label="Bundle preview">
                                            <?php foreach ($bundleCollageItems as $index => $component): ?>
                                                <?php
                                                    $layout = $bundleCollageLayout[$index];
                                                    $imageFile = !empty($component['image']) ? $component['image'] : 'wrapper.png';
                                                ?>
                                                <img src="../assets/images/products/<?php echo htmlspecialchars($imageFile); ?>"
                                                    alt="Bundle item"
                                                    style="top: <?php echo (int) $layout['top']; ?>px; left: <?php echo (int) $layout['left']; ?>px; z-index: <?php echo (int) $layout['z']; ?>; transform: rotate(<?php echo $layout['rotate']; ?>);">
                                            <?php endforeach; ?>
                                            <?php if ($bundleCollageExtra > 0): ?>
                                                <span class="bundle-collage-more">+<?php echo $bundleCollageExtra; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1">
                                        <p class="mb-1 fw-semibold text-success">
                                            <?php echo count($customizeItems); ?> curated components
                                            <?php if ($orderCustomDesignImage): ?>
                                                <span class="badge bg-success ms-1"><i class="fas fa-palette me-1"></i>Live Canvas Design</span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-muted small mb-2">
                                            <?php if ($isBundleFinalized && $bundleFinalizedAt): ?>
                                                Finalized on <?php echo date('M j, Y g:i A', strtotime($bundleFinalizedAt)); ?> — you can still adjust the numbers below if needed.
                                            <?php elseif ($isBundleFinalized): ?>
                                                Final price already confirmed — editing will refresh the customer total instantly.
                                            <?php else: ?>
                                                Enter the final unit prices to publish the official bundle total to the customer portal.
                                            <?php endif; ?>
                                        </p>
                                        <?php if ($orderCustomDesign && !empty($orderCustomDesign['design_notes'])): ?>
                                            <div class="alert alert-info py-2 px-3 small mb-0">
                                                <strong><i class="fas fa-sticky-note me-1"></i>Customer Florist Note:</strong><br>
                                                <?php echo nl2br(htmlspecialchars($orderCustomDesign['design_notes'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($orderCustomDesignImage): ?>
                                    <div class="modal fade" id="adminDesignZoomModal" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><i class="fas fa-palette text-success me-2"></i>Order #<?php echo (int) $order['id']; ?> — Customer Bouquet Design Reference</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-center bg-light">
                                                    <img src="<?php echo htmlspecialchars($orderCustomDesignImage); ?>"
                                                        alt="Full-size bouquet design reference"
                                                        class="img-fluid rounded shadow-sm"
                                                        style="max-height: 72vh; object-fit: contain;">
                                                </div>
                                                <div class="modal-footer">
                                                    <a href="<?php echo htmlspecialchars($orderCustomDesignImage); ?>"
                                                        download="order-<?php echo (int) $order['id']; ?>-bouquet-design.png"
                                                        class="btn btn-success">
                                                        <i class="fas fa-download me-1"></i>Download Reference PNG
                                                    </a>
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <form method="POST">
                                    <input type="hidden" name="update_custom_prices" value="1">
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Component</th>
                                                    <th class="text-center">Qty</th>
                                                    <th style="width: 180px;">Unit Price (₱)</th>
                                                    <th class="text-end">Line Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($customizeItems as $component): ?>
                                                    <?php $lineTotal = (float) $component['price'] * (int) $component['quantity']; ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($component['name']); ?></td>
                                                        <td class="text-center"><?php echo (int) $component['quantity']; ?></td>
                                                        <td>
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text">₱</span>
                                                                <input type="number"
                                                                       class="form-control text-end"
                                                                       min="0"
                                                                       step="0.01"
                                                                       name="custom_price[<?php echo (int) $component['id']; ?>]"
                                                                       value="<?php echo htmlspecialchars(number_format((float) $component['price'], 2, '.', '')); ?>">
                                                            </div>
                                                        </td>
                                                        <td class="text-end">₱<?php echo number_format($lineTotal, 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                                        <div class="fw-semibold">Bundle total: ₱<?php echo number_format($customizeBundleTotal, 2); ?></div>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save me-2"></i>Save Bundle Pricing
                                        </button>
                                    </div>
                                    <p class="small text-muted mt-2 mb-0">Saving updates the order totals and the customer's tracking page immediately.</p>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($regularItems)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($regularItems as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo (int) $item['quantity']; ?></td>
                                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                            <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif (!$hasCustomizeBundle): ?>
                        <p class="text-muted mb-0">No order items found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tracking History -->
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h6 class="m-0 font-weight-bold">Tracking History</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php if ($tracking_history): ?>
                            <?php foreach ($tracking_history as $entry): ?>
                                <div class="timeline-item mb-3">
                                    <div class="d-flex">
                                        <div class="me-3">
                                            <div class="badge bg-success rounded-circle" style="width: 15px; height: 15px;"></div>
                                        </div>
                                        <div>
                                            <strong><?php echo ucfirst(str_replace('_', ' ', $entry['status'])); ?></strong>
                                            <p class="mb-0 text-muted small">
                                                <?php echo $entry['description']; ?>
                                            </p>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y g:i A', strtotime($entry['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No tracking history available.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Summary & Actions -->
        <div class="col-lg-4">
            <!-- Order Summary -->
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="m-0 font-weight-bold">Order Summary</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>₱<?php echo number_format($itemsSubtotal, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span>₱<?php echo number_format($shippingFee, 2); ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-5">
                        <span>Total:</span>
                        <span>₱<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>

            <!-- Update Status -->
            <div class="card shadow">
                <div class="card-header bg-warning text-white">
                    <h6 class="m-0 font-weight-bold">Update Status</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tracking Number (Optional)</label>
                            <input type="text" name="tracking_number" class="form-control" 
                                   value="<?php echo $order['tracking_number'] ?? ''; ?>" 
                                   placeholder="e.g., TRK123456">
                        </div>
                        <button type="submit" name="update_status" class="btn btn-warning w-100">
                            <i class="fas fa-sync me-2"></i>Update Status
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

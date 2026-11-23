<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$order_id = $_GET['id'] ?? null;
$order = null;
$tracking = [];
$customizeItems = [];
$regularItems = [];
$hasCustomizeBundle = false;
$customizeBundleTotal = 0.0;
$bundleCollageLayout = [
    ['top' => 0, 'left' => 0, 'z' => 4, 'rotate' => '-5deg'],
    ['top' => 18, 'left' => 70, 'z' => 3, 'rotate' => '3deg'],
    ['top' => 60, 'left' => 10, 'z' => 2, 'rotate' => '2deg'],
    ['top' => 40, 'left' => 110, 'z' => 1, 'rotate' => '-2deg'],
];
$bundleCollageItems = [];
$bundleCollageExtra = 0;
$bundleStatus = [];
$isBundleFinalized = false;
$bundleFinalizedAt = null;
$bundleStatusLabel = 'Current Estimate';

if ($order_id) {
    $stmt = $pdo->prepare("SELECT o.*, u.username FROM orders o 
                          JOIN users u ON o.user_id = u.id 
                          WHERE o.id = ? AND (o.user_id = ? OR ? = 'admin')");
    $stmt->execute([$order_id, $_SESSION['user_id'], $_SESSION['role']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        $tracking = getOrderTracking($order_id);

        // Get order items
        $stmt = $pdo->prepare("SELECT oi.*, p.name, p.image, p.category_id AS product_category_id FROM order_items oi 
                              JOIN products p ON oi.product_id = p.id 
                              WHERE oi.order_id = ?");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $customizeCategoryId = getCustomizeCategoryId();
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
        $orderCustomDesign = $hasCustomizeBundle ? getOrderCustomDesign($order_id) : null;
        $orderCustomDesignImage = null;
        if ($orderCustomDesign && !empty($orderCustomDesign['preview_image']) && file_exists(__DIR__ . '/' . $orderCustomDesign['preview_image'])) {
            $orderCustomDesignImage = $orderCustomDesign['preview_image'];
        }
        $bundleCollageItems = array_slice($customizeItems, 0, count($bundleCollageLayout));
        $bundleCollageExtra = max(0, count($customizeItems) - count($bundleCollageItems));

        $shippingFee = isset($order['shipping_fee']) ? (float) $order['shipping_fee'] : 0;
        $shippingDetails = [];
        if (!empty($order['shipping_details'])) {
            $decoded = json_decode($order['shipping_details'], true);
            if (is_array($decoded)) {
                $shippingDetails = $decoded;
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
        $bundleStatus = $shippingDetails['bundle_status'] ?? [];
        $isBundleFinalized = !empty($bundleStatus['finalized']);
        $bundleFinalizedAt = $bundleStatus['finalized_at'] ?? null;
        $bundleStatusLabel = $isBundleFinalized ? 'Final Bundle Total' : 'Current Estimate';
    }
}
?>

<?php include 'includes/header.php'; ?>

<?php if ($order && $hasCustomizeBundle): ?>
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
    <?php if (!$order): ?>
        <div class="text-center">
            <i class="fas fa-exclamation-circle fa-3x text-warning mb-3"></i>
            <h3>Order Not Found</h3>
            <p>The order you're looking for doesn't exist or you don't have permission to view it.</p>
            <a href="my-orders.php" class="btn btn-success">View Orders</a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <!-- Order Tracking Timeline -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Order Tracking - #<?php echo $order['id']; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php
                            $status_flow = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
                            $current_status_index = array_search($order['status'], $status_flow);

                            foreach ($status_flow as $index => $status):
                                $is_completed = $index <= $current_status_index;
                                $is_current = $index === $current_status_index;
                                ?>
                                <div class="timeline-item <?php echo $is_completed ? 'completed' : ''; ?>">
                                    <div class="timeline-marker">
                                        <?php if ($is_completed): ?>
                                            <i class="fas fa-check-circle text-success"></i>
                                        <?php else: ?>
                                            <i class="far fa-circle text-muted"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1 text-capitalize"><?php echo $status; ?></h6>
                                        <?php if ($is_completed && isset($tracking[$index])): ?>
                                            <p class="text-muted mb-0 small">
                                                <?php echo date('M j, Y g:i A', strtotime($tracking[$index]['created_at'])); ?>
                                            </p>
                                            <?php if ($tracking[$index]['description']): ?>
                                                <p class="small mb-0"><?php echo $tracking[$index]['description']; ?></p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($order['tracking_number']): ?>
                            <div class="mt-4 p-3 bg-light rounded">
                                <strong>Tracking Number:</strong> <?php echo $order['tracking_number']; ?><br>
                                <?php if ($order['estimated_delivery']): ?>
                                    <strong>Estimated Delivery:</strong>
                                    <?php echo date('M j, Y', strtotime($order['estimated_delivery'])); ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Items</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($hasCustomizeBundle): ?>
                            <div class="card shadow-sm border border-success mb-4">
                                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <h6 class="mb-0">Your Custom Bundle</h6>
                                        <small class="text-white-50">Florist-adjusted arrangement</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="text-uppercase small d-block"><?php echo htmlspecialchars($bundleStatusLabel); ?></span>
                                        <strong>₱<?php echo number_format($customizeBundleTotal, 2); ?></strong>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="bundle-collage-wrapper">
                                        <?php if (!empty($orderCustomDesignImage)): ?>
                                            <div class="border rounded-3 p-2 bg-light text-center" style="width: 195px; flex-shrink: 0;">
                                                <a href="#" data-bs-toggle="modal" data-bs-target="#customerDesignZoomModal" title="View full size design">
                                                    <img src="<?php echo htmlspecialchars($orderCustomDesignImage); ?>"
                                                        alt="Your custom bouquet design"
                                                        class="img-fluid rounded"
                                                        style="max-height: 200px; object-fit: contain;">
                                                </a>
                                                <div class="mt-2">
                                                    <button type="button" class="btn btn-outline-success btn-sm w-100" data-bs-toggle="modal" data-bs-target="#customerDesignZoomModal">
                                                        <i class="fas fa-search-plus me-1"></i>View Design
                                                    </button>
                                                </div>
                                            </div>
                                        <?php else: ?>
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
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <p class="mb-1 fw-semibold text-success"><?php echo count($customizeItems); ?> curated pieces locked in</p>
                                            <p class="text-muted small mb-2">
                                                <?php if ($isBundleFinalized): ?>
                                                    Finalized<?php echo $bundleFinalizedAt ? ' on ' . date('M j, Y g:i A', strtotime($bundleFinalizedAt)) : ''; ?> — any changes will update this total instantly.
                                                <?php else: ?>
                                                    This figure will refresh automatically once our team confirms the final price.
                                                <?php endif; ?>
                                            </p>
                                            <?php if (!empty($orderCustomDesign['design_notes'])): ?>
                                                <div class="alert alert-light border py-2 px-3 small mb-2">
                                                    <strong>Florist Note:</strong> <?php echo nl2br(htmlspecialchars($orderCustomDesign['design_notes'])); ?>
                                                </div>
                                            <?php endif; ?>
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
                            <?php if (!empty($orderCustomDesignImage)): ?>
                                <div class="modal fade" id="customerDesignZoomModal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="fas fa-palette text-success me-2"></i>Your Custom Bouquet Design</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-center bg-light">
                                                <img src="<?php echo htmlspecialchars($orderCustomDesignImage); ?>"
                                                    alt="Full-size custom bouquet design"
                                                    class="img-fluid rounded shadow-sm"
                                                    style="max-height: 70vh; object-fit: contain;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (!empty($regularItems)): ?>
                            <?php foreach ($regularItems as $item): ?>
                                <?php $item_feedback = getFeedbackForOrderItem($item['id'], $_SESSION['user_id']); ?>
                                <div class="row align-items-center mb-3 pb-3 border-bottom">
                                    <div class="col-2">
                                        <img src="assets/images/products/<?php echo $item['image']; ?>"
                                            alt="<?php echo $item['name']; ?>" class="img-fluid rounded">
                                    </div>
                                    <div class="col-6">
                                        <h6 class="mb-1"><?php echo $item['name']; ?></h6>
                                        <p class="text-muted mb-0">Quantity: <?php echo $item['quantity']; ?></p>
                                        <?php if ($item_feedback): ?>
                                            <div class="mt-2">
                                                <span class="badge bg-light text-success">Rated
                                                    <?php echo $item_feedback['rating']; ?>/5</span>
                                                <p class="small mb-0 text-muted">
                                                    “<?php echo htmlspecialchars($item_feedback['comment']); ?>”</p>
                                                <small class="text-muted"><?php echo ucfirst($item_feedback['status']); ?>
                                                    review</small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-4 text-end">
                                        <strong>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                        <?php if ($order['status'] === 'delivered'): ?>
                                            <div class="d-flex flex-column align-items-end gap-2 mt-2">
                                                <div class="d-flex gap-2 flex-wrap justify-content-end">
                                                    <form method="POST" action="buy-again.php" class="d-inline"
                                                        data-confirm="Add this item to a fresh checkout?"
                                                        data-confirm-title="Buy Again"
                                                        data-confirm-variant="success"
                                                        data-confirm-accept="Checkout">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="order_item_id" value="<?php echo $item['id']; ?>">
                                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                        <input type="hidden" name="redirect_to" value="cart.php">
                                                        <button type="submit" class="btn btn-outline-success btn-sm">Buy Again</button>
                                                    </form>
                                                    <button class="btn btn-outline-secondary btn-sm" type="button"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#feedbackForm-<?php echo $item['id']; ?>">
                                                        <?php echo $item_feedback ? 'Edit Feedback' : 'Leave Feedback'; ?>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($order['status'] === 'delivered'): ?>
                                    <div class="collapse" id="feedbackForm-<?php echo $item['id']; ?>">
                                        <div class="card card-body bg-light mb-3">
                                            <form method="POST" action="submit-feedback.php">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="order_item_id" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                <div class="row g-3">
                                                    <div class="col-md-3">
                                                        <label class="form-label small">Rating</label>
                                                        <select name="rating" class="form-select form-select-sm" required>
                                                            <?php for ($r = 5; $r >= 1; $r--): ?>
                                                                <option value="<?php echo $r; ?>" <?php echo ($item_feedback['rating'] ?? 5) == $r ? 'selected' : ''; ?>><?php echo $r; ?> -
                                                                    <?php echo str_repeat('★', $r); ?>
                                                                </option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-9">
                                                        <label class="form-label small">Comment</label>
                                                        <textarea name="comment" class="form-control form-control-sm" rows="2"
                                                            placeholder="Share how the <?php echo strtolower($item['name']); ?> made your day"
                                                            required><?php echo htmlspecialchars($item_feedback['comment'] ?? ''); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-end mt-3">
                                                    <button type="submit" class="btn btn-success btn-sm">Save Feedback</button>
                                                </div>
                                                <small class="text-muted d-block mt-2">Feedback status will be set to pending until
                                                    approved by an admin.</small>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php elseif (!$hasCustomizeBundle): ?>
                            <p class="text-muted mb-0">No order items were found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Order Status:</strong>
                            <span class="badge bg-<?php
                            echo $order['status'] === 'delivered' ? 'success' :
                                ($order['status'] === 'cancelled' ? 'danger' :
                                    ($order['status'] === 'pending' ? 'warning' : 'info'));
                            ?> text-capitalize float-end">
                                <?php echo $order['status']; ?>
                            </span>
                        </div>

                        <div class="mb-3">
                            <strong>Order Date:</strong>
                            <span
                                class="float-end"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></span>
                        </div>

                        <div class="mb-3">
                            <strong>Payment Method:</strong>
                            <span class="float-end text-capitalize">
                                <?php echo str_replace('_', ' ', $order['payment_method']); ?>
                            </span>
                        </div>

                        <hr>

                        <div class="mb-2">
                            <span>Subtotal:</span>
                            <span class="float-end">₱<?php echo number_format($itemsSubtotal, 2); ?></span>
                        </div>

                        <div class="mb-2">
                            <span>Shipping:</span>
                            <span class="float-end">₱<?php echo number_format($shippingFee, 2); ?></span>
                        </div>

                        <hr>

                        <div class="mb-3 fw-bold fs-5">
                            <span>Total:</span>
                            <span class="float-end">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>

                        <hr>

                        <h6>Shipping Address</h6>
                        <p class="small mb-2"><?php echo nl2br($order['shipping_address']); ?></p>
                        <p class="small mb-1"><strong>Fulfillment:</strong> <?php echo htmlspecialchars($fulfillmentMethodLabel); ?></p>
                        <?php if ($distanceText): ?>
                            <p class="small mb-1">
                                <strong>Route Distance:</strong> <?php echo htmlspecialchars($distanceText); ?>
                                <?php if ($durationText): ?>
                                    <span class="text-muted">• <?php echo htmlspecialchars($durationText); ?></span>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($shippingMessage): ?>
                            <p class="small text-muted mb-0"><?php echo htmlspecialchars($shippingMessage); ?></p>
                        <?php endif; ?>

                        <?php if ($order['status'] === 'pending' && $order['user_id'] == $_SESSION['user_id']): ?>
                            <button type="button" class="btn btn-outline-danger w-100 mt-3" data-bs-toggle="modal"
                                data-bs-target="#cancelOrderModal" data-order-id="<?php echo $order['id']; ?>"
                                data-redirect="order-tracking.php?id=<?php echo $order['id']; ?>">
                                Cancel Order
                            </button>
                            <small class="text-muted d-block mt-2">You can cancel while the order is still pending.
                                Once confirmed by admin, cancellation is disabled.</small>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isAdmin()): ?>
                    <div class="card mt-4">
                        <div class="card-header bg-warning">
                            <h6 class="mb-0">Admin Actions</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="admin/update-order-status.php" class="mb-3">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <div class="mb-2">
                                    <label class="form-label small">Update Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>
                                            Pending</option>
                                        <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>
                                            Shipped</option>
                                        <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">Tracking Number</label>
                                    <input type="text" name="tracking_number" class="form-control form-control-sm"
                                        value="<?php echo $order['tracking_number'] ?? ''; ?>">
                                </div>
                                <button type="submit" class="btn btn-warning btn-sm w-100">Update</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Cancel Order Modal -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="cancel-order.php" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelOrderLabel">Cancel Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Are you sure you want to cancel this order? This action cannot be undone and stock will
                    be returned to inventory.</p>
            </div>
            <div class="modal-footer">
                <input type="hidden" name="order_id" id="cancelOrderId">
                <input type="hidden" name="redirect_to" id="cancelRedirect">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-danger">Yes, Cancel Order</button>
            </div>
        </form>
    </div>
</div>

<style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }

    const cancelModal=document.getElementById('cancelOrderModal');

    if (cancelModal) {
        cancelModal.addEventListener('show.bs.modal', function (event) {
                const button=event.relatedTarget;
                const orderId=button.getAttribute('data-order-id');
                const redirect=button.getAttribute('data-redirect');
                document.getElementById('cancelOrderId').value=orderId;
                document.getElementById('cancelRedirect').value=redirect;
            });
    }

    </script>.timeline-item {
        position: relative;
        padding-bottom: 20px;
    }

    .timeline-item:last-child {
        padding-bottom: 0;
    }

    .timeline-marker {
        position: absolute;
        left: -30px;
        top: 0;
    }

    .timeline-item.completed .timeline-marker {
        color: #28a745;
    }

    .timeline-content {
        margin-left: 0;
    }
</style>

<?php include 'includes/footer.php'; ?>
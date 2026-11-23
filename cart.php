<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$cart_items = getCartItems($userId);
$cart_total = getCartTotal($userId);
$estimated_total = $cart_total;

$categories = getCategories();
$customizeCategoryId = null;
foreach ($categories as $category) {
    if (isset($category['name']) && strtolower(trim($category['name'])) === 'customize') {
        $customizeCategoryId = (int) $category['id'];
        break;
    }
}

$customize_items = [];
$regular_items = [];

foreach ($cart_items as $item) {
    if ($customizeCategoryId && isset($item['category_id']) && (int) $item['category_id'] === $customizeCategoryId) {
        $customize_items[] = $item;
    } else {
        $regular_items[] = $item;
    }
}

$customize_bundle_total = array_reduce($customize_items, function ($carry, $item) {
    return $carry + ((float) $item['price'] * (int) $item['quantity']);
}, 0);
$hasCustomizeBundle = !empty($customize_items);
$customizeCategoryLink = $customizeCategoryId ? 'products.php?category=' . $customizeCategoryId : 'products.php';
$bundleCollageLayout = [
    ['top' => 0, 'left' => 0, 'z' => 4, 'rotate' => '-5deg'],
    ['top' => 18, 'left' => 70, 'z' => 3, 'rotate' => '3deg'],
    ['top' => 60, 'left' => 10, 'z' => 2, 'rotate' => '2deg'],
    ['top' => 40, 'left' => 110, 'z' => 1, 'rotate' => '-2deg'],
];
$bundleCollageItems = array_slice($customize_items, 0, count($bundleCollageLayout));
$bundleCollageExtra = max(0, count($customize_items) - count($bundleCollageItems));
$allowOutOfStockOrders = isOutOfStockOrderingEnabled();

$cartEstimatedStartDate = null;
foreach ($cart_items as $item) {
    if (!empty($item['created_at'])) {
        $createdAt = DateTime::createFromFormat('Y-m-d H:i:s', $item['created_at']);
        if ($createdAt !== false) {
            if ($cartEstimatedStartDate === null || $createdAt < $cartEstimatedStartDate) {
                $cartEstimatedStartDate = $createdAt;
            }
        }
    }
}
if ($cartEstimatedStartDate === null) {
    $cartEstimatedStartDate = new DateTimeImmutable('now');
}
$cartDeliveryEstimateRange = getSequentialDeliveryDateRange($cartEstimatedStartDate, 3);
$cartHasBlockedItems = !$allowOutOfStockOrders && count(array_filter($cart_items, function ($item) {
    return isset($item['stock_quantity']) && (int) $item['stock_quantity'] <= 0;
})) > 0;

function cart_gcd_positive($a, $b)
{
    $a = abs((int) $a);
    $b = abs((int) $b);

    if ($b === 0) {
        return $a;
    }

    return cart_gcd_positive($b, $a % $b);
}

function calculateCustomizeBundleSets(array $items)
{
    $quantities = [];
    foreach ($items as $item) {
        $qty = isset($item['quantity']) ? (int) $item['quantity'] : 0;
        if ($qty > 0) {
            $quantities[] = $qty;
        }
    }

    if (empty($quantities)) {
        return 0;
    }

    $gcd = array_shift($quantities);
    foreach ($quantities as $qty) {
        $gcd = cart_gcd_positive($gcd, $qty);
    }

    return max(1, $gcd);
}

function deleteCustomizeBundleItems($pdo, array $items, $userId)
{
    foreach ($items as $component) {
        if (!isset($component['id'])) {
            continue;
        }

        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->execute([$component['id'], $userId]);
    }
}

function updateCustomizeBundleQuantities($pdo, array $items, $userId, $currentSets, $desiredSets)
{
    $currentSets = max(1, (int) $currentSets);
    $desiredSets = max(1, (int) $desiredSets);

    foreach ($items as $component) {
        if (!isset($component['id'])) {
            continue;
        }

        $componentQty = max(1, (int) ($component['quantity'] ?? 1));
        $baseQuantity = $currentSets > 0 ? intdiv($componentQty, $currentSets) : $componentQty;
        if ($baseQuantity < 1) {
            $baseQuantity = 1;
        }

        $newQuantity = $baseQuantity * $desiredSets;

        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$newQuantity, $component['id'], $userId]);
    }
}

$customizeBundleSets = $hasCustomizeBundle ? calculateCustomizeBundleSets($customize_items) : 0;
$customizeBundleQuantityDisplay = $customizeBundleSets > 0 ? $customizeBundleSets : 1;
$activeCustomDesign = $hasCustomizeBundle ? getActiveCartCustomDesign($userId) : null;
$activeCustomDesignImage = null;
if ($activeCustomDesign && !empty($activeCustomDesign['preview_image']) && file_exists(__DIR__ . '/' . $activeCustomDesign['preview_image'])) {
    $activeCustomDesignImage = $activeCustomDesign['preview_image'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isUpdateRequest = isset($_POST['update_cart']) || isset($_POST['update_custom_bundle']);

    if ($hasCustomizeBundle && isset($_POST['remove_customize_bundle'])) {
        deleteCustomizeBundleItems($pdo, $customize_items, $userId);
        discardActiveCartCustomDesign($userId);
        redirect('cart.php');
    }

    if ($isUpdateRequest) {
        if ($hasCustomizeBundle && isset($_POST['custom_bundle_quantity'])) {
            $bundleQuantity = (int) $_POST['custom_bundle_quantity'];
            if ($bundleQuantity <= 0) {
                deleteCustomizeBundleItems($pdo, $customize_items, $userId);
                discardActiveCartCustomDesign($userId);
            } else {
                $currentSets = max(1, $customizeBundleSets);
                updateCustomizeBundleQuantities($pdo, $customize_items, $userId, $currentSets, $bundleQuantity);
            }
        }

        if (!empty($_POST['quantities']) && is_array($_POST['quantities'])) {
            foreach ($_POST['quantities'] as $cart_id => $quantity) {
                if ($quantity <= 0) {
                    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                    $stmt->execute([$cart_id, $userId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                    $stmt->execute([$quantity, $cart_id, $userId]);
                }
            }
        }

        redirect('cart.php');
    }
}

if (isset($_GET['remove'])) {
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['remove'], $userId]);
    redirect('cart.php');
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
    .bundle-collage-copy p:last-child {
        margin-bottom: 0;
    }
</style>
<?php endif; ?>

<div class="container py-5">
    <h2 class="mb-4">Shopping Cart</h2>

    <?php if (empty($cart_items)): ?>
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
            <h4>Your cart is empty</h4>
            <p>Start shopping to add items to your cart</p>
            <a href="products.php" class="btn btn-success">Continue Shopping</a>
        </div>
    <?php else: ?>
        <form method="POST">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <?php if ($hasCustomizeBundle): ?>
                                <div class="card shadow-sm border border-success mb-4">
                                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-start flex-wrap gap-3">
                                        <div>
                                            <h5 class="mb-0">Your Custom Bundle</h5>
                                            <small class="text-white-50">All components ship together as one arrangement.</small>
                                        </div>
                                        <div class="text-end">
                                            <span class="text-uppercase small d-block">Estimated Total</span>
                                            <strong>₱<?php echo number_format($customize_bundle_total, 2); ?></strong>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="bundle-collage-wrapper">
                                            <?php if ($activeCustomDesignImage): ?>
                                                <div class="border rounded-3 p-2 bg-light text-center" style="width: 190px; flex-shrink: 0;">
                                                    <img src="<?php echo htmlspecialchars($activeCustomDesignImage); ?>"
                                                        alt="Your custom bouquet design"
                                                        class="img-fluid rounded"
                                                        style="max-height: 195px; object-fit: contain;">
                                                    <span class="badge bg-success mt-2"><i class="fas fa-palette me-1"></i>Your Live Design</span>
                                                </div>
                                            <?php else: ?>
                                                <div class="bundle-collage" aria-label="Bundle collage preview">
                                                    <?php foreach ($bundleCollageItems as $index => $item): ?>
                                                        <?php
                                                            $layout = $bundleCollageLayout[$index] ?? ['top' => 0, 'left' => 0, 'z' => 1, 'rotate' => '0deg'];
                                                            $imageFile = !empty($item['image']) ? $item['image'] : 'wrapper.png';
                                                            $imageName = htmlspecialchars($imageFile);
                                                        ?>
                                                        <img src="assets/images/products/<?php echo $imageName; ?>"
                                                            alt="Bundle component"
                                                            style="top: <?php echo (int) $layout['top']; ?>px; left: <?php echo (int) $layout['left']; ?>px; z-index: <?php echo (int) $layout['z']; ?>; transform: rotate(<?php echo $layout['rotate']; ?>);">
                                                    <?php endforeach; ?>
                                                    <?php if ($bundleCollageExtra > 0): ?>
                                                        <span class="bundle-collage-more">+<?php echo $bundleCollageExtra; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="bundle-collage-copy flex-grow-1">
                                                <p class="mb-1 fw-semibold text-success"><?php echo count($customize_items); ?> curated pieces locked in</p>
                                                <ul class="list-unstyled small mb-2">
                                                    <?php foreach ($customize_items as $comp): ?>
                                                        <li class="d-flex justify-content-between border-bottom py-1">
                                                            <span><?php echo htmlspecialchars($comp['name']); ?></span>
                                                            <span class="text-muted">×<?php echo (int) $comp['quantity']; ?></span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                                <?php if ($activeCustomDesign && !empty($activeCustomDesign['design_notes'])): ?>
                                                    <p class="small text-muted mb-1">
                                                        <strong>Florist Note:</strong> <?php echo htmlspecialchars($activeCustomDesign['design_notes']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <p class="text-muted small mb-0">
                                                    Our florist will receive your bouquet design preview once you place your order.
                                                </p>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap align-items-end gap-3 mt-4">
                                            <div>
                                                <label class="form-label small text-uppercase text-muted mb-1">Bundle quantity</label>
                                                <input type="number"
                                                       class="form-control"
                                                       min="1"
                                                       name="custom_bundle_quantity"
                                                       value="<?php echo (int) $customizeBundleQuantityDisplay; ?>"
                                                       aria-label="Custom bundle quantity">
                                            </div>
        
                                            <div class="ms-auto d-flex flex-wrap gap-2">
                                                <button type="submit" name="update_custom_bundle" value="1" class="btn btn-success">
                                                    Update Bundle
                                                </button>
                                                <button type="submit"
                                                        name="remove_customize_bundle"
                                                        value="1"
                                                        class="btn btn-outline-danger"
                                                        data-confirm="Remove your custom bundle from the cart?"
                                                        data-confirm-variant="danger"
                                                        data-confirm-accept="Delete Bundle">
                                                    Delete Bundle
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($regular_items)): ?>
                                <?php foreach ($regular_items as $item): 
                                    $itemStockQuantity = isset($item['stock_quantity']) ? (int) $item['stock_quantity'] : 0;
                                    $isPreorderItem = $allowOutOfStockOrders && $itemStockQuantity <= 0;
                                    $maxQuantityAttr = $allowOutOfStockOrders ? '' : 'max="' . $itemStockQuantity . '"';
                                ?>
                                    <div class="row align-items-center mb-4 pb-4 border-bottom">
                                        <div class="col-md-2">
                                            <img src="assets/images/products/<?php echo $item['image']; ?>"
                                                alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-fluid rounded">
                                        </div>
                                        <div class="col-md-4">
                                            <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                            <p class="text-muted">₱<?php echo number_format($item['price'], 2); ?></p>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="number" name="quantities[<?php echo $item['id']; ?>]"
                                                value="<?php echo (int) $item['quantity']; ?>" min="1"
                                                <?php echo $maxQuantityAttr; ?> class="form-control">
                                            <?php if ($isPreorderItem): ?>
                                                <small class="text-warning d-block mt-1">Preorder item while inventory sync is in progress.</small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-2">
                                            <h5>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></h5>
                                        </div>
                                        <div class="col-md-1">
                                            <a href="cart.php?remove=<?php echo $item['id']; ?>"
                                                class="btn btn-outline-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php elseif (!$hasCustomizeBundle): ?>
                                <p class="text-muted">No standard products in your cart yet.</p>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between flex-wrap gap-2">
                                <a href="products.php" class="btn btn-outline-success">
                                    Continue Shopping
                                </a>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php if ($hasCustomizeBundle): ?>
                                        <a href="<?php echo $customizeCategoryLink; ?>" class="btn btn-outline-success">
                                            Build Another Bundle
                                        </a>
                                    <?php endif; ?>
                                    <button type="submit" name="update_cart" class="btn btn-success">
                                        Update Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <span>Subtotal:</span>
                                <span>₱<?php echo number_format($cart_total, 2); ?></span>
                            </div>
                            <?php if ($hasCustomizeBundle): ?>
                                <div class="d-flex justify-content-between mb-3 text-success">
                                    <span>Bundle estimate:</span>
                                    <span>₱<?php echo number_format($customize_bundle_total, 2); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Shipping:</span>
                                <span class="text-muted">Calculated at checkout</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3 fw-bold">
                                <span>Estimated total (before shipping):</span>
                                <span>₱<?php echo number_format($estimated_total, 2); ?></span>
                            </div>

                            <?php if ($hasCustomizeBundle): ?>
                                <div class="small text-muted mb-2">
                                    Your custom bundle price above already reflects every selected component.
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <div class="small text-uppercase text-success mb-2">Estimated delivery</div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($cartDeliveryEstimateRange); ?></div>
                            </div>

                            <div class="small text-muted mb-3">
                                Delivery fees now depend on distance. Choose your address at checkout to see the exact amount.
                            </div>

                            <?php if ($cartHasBlockedItems): ?>
                                <div class="alert alert-warning small mb-3">
                                    Some items in your cart are now out of stock and cannot be checked out while Maintenance is OFF.
                                </div>
                            <?php endif; ?>

                            <a href="<?php echo $cartHasBlockedItems ? '#' : 'checkout.php'; ?>" class="btn btn-success w-100 <?php echo $cartHasBlockedItems ? 'disabled' : ''; ?>" <?php echo $cartHasBlockedItems ? 'aria-disabled="true"' : ''; ?>>Proceed to Checkout</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
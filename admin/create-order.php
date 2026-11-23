<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

ensureProductAvailabilityColumn();
ensureCustomizeProductColumns();

// Start session for admin cart
if (!isset($_SESSION['admin_cart'])) {
    $_SESSION['admin_cart'] = [];
}

$step = isset($_GET['step']) ? (int) $_GET['step'] : 1;
$maxStep = 5; // 1: Container, 2: Flowers, 3: Fillers, 4: Ribbon, 5: Customer & Finalize

$categories = getCategories();
$customizeCategoryId = null;
foreach ($categories as $cat) {
    if (isset($cat['name']) && strtolower(trim($cat['name'])) === 'customize') {
        $customizeCategoryId = $cat['id'];
        break;
    }
}

$customizeStepsData = [
    'container' => [
        'step' => 1,
        'title' => 'Container Type',
        'subtitle' => 'Choose the base that fits your look',
        'short_label' => 'Container',
        'keywords' => ['basket', 'pot', 'box', 'planter', 'handle', 'ceramic', 'gift'],
        'items' => []
    ],
    'flowers' => [
        'step' => 2,
        'title' => 'Flower Type + Color',
        'subtitle' => 'Pick the hero blooms',
        'short_label' => 'Flowers',
        'keywords' => ['rose', 'lily', 'tulip', 'daisy', 'carnation', 'chrysanthemum', 'gerbera', 'peony', 'sunflower', 'orchid', 'iris', 'lavender', 'baby breath', 'hydrangea', 'anemone', 'delphinium', 'gladiolus', 'lilac', 'magnolia', 'poppy', 'violet', 'zinnia'],
        'items' => []
    ],
    'fillers' => [
        'step' => 3,
        'title' => 'Fillers & Greens',
        'subtitle' => 'Add texture and volume',
        'short_label' => 'Fillers',
        'keywords' => ['greenery', 'fern', 'eucalyptus', 'ivy', 'palm', 'ruscus', 'salal', 'leatherleaf', 'bupleurum', 'veronica', 'statice', 'solidago', 'yarrow', 'limonium', 'hypericum', 'genista', 'artemisia', 'dusty miller', 'pittosporum', 'saxifraga'],
        'items' => []
    ],
    'ribbon' => [
        'step' => 4,
        'title' => 'Ribbon / Wrap',
        'subtitle' => 'Finish with the perfect touch',
        'short_label' => 'Ribbon',
        'keywords' => ['ribbon', 'bow', 'wrap', 'paper', 'cellophane', 'tulle', 'raffia', 'twine', 'lace', 'satin', 'silk', 'organza', 'velvet', 'metallic', 'printed', 'plain'],
        'items' => []
    ]
];

// Load products for customize steps
$customizeProducts = [];
if ($customizeCategoryId) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND customize_archived = 0 ORDER BY name");
    $stmt->execute([$customizeCategoryId]);
    $customizeProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$customizeStepMap = [
    1 => 'container',
    2 => 'flowers',
    3 => 'fillers',
    4 => 'ribbon'
];

foreach ($customizeProducts as $product) {
    $haystack = strtolower(($product['name'] ?? '') . ' ' . ($product['description'] ?? ''));
    $matchedGroup = null;

    $customizeStepNumber = isset($product['customize_step']) ? (int) $product['customize_step'] : null;
    if ($customizeStepNumber && isset($customizeStepMap[$customizeStepNumber])) {
        $matchedGroup = $customizeStepMap[$customizeStepNumber];
    }

    if (!$matchedGroup) {
        foreach ($customizeStepsData as $groupKey => $groupMeta) {
            foreach ($groupMeta['keywords'] as $keyword) {
                if (strpos($haystack, $keyword) !== false) {
                    $matchedGroup = $groupKey;
                    break 2;
                }
            }
        }
    }

    if (!$matchedGroup) {
        $matchedGroup = 'flowers';
    }

    $customizeStepsData[$matchedGroup]['items'][] = $product;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['quantity']) && is_array($_POST['quantity'])) {
        foreach ($_POST['quantity'] as $productId => $quantity) {
            $id = (int) $productId;
            $qty = max(0, (int) $quantity);
            if ($id > 0) {
                if ($qty > 0) {
                    $_SESSION['admin_cart'][$id] = $qty;
                } else {
                    unset($_SESSION['admin_cart'][$id]);
                }
            }
        }
    }

    if (!empty($_POST['price']) && is_array($_POST['price'])) {
        foreach ($_POST['price'] as $productId => $price) {
            $id = (int) $productId;
            $value = trim($price);
            if ($id > 0 && is_numeric($value) && (float) $value >= 0) {
                $_SESSION['admin_cart_prices'][$id] = round((float) $value, 2);
            } else {
                unset($_SESSION['admin_cart_prices'][$id]);
            }
        }
    }

    if (isset($_POST['override_total'])) {
        $overrideTotal = trim($_POST['override_total']);
        if ($overrideTotal !== '' && is_numeric($overrideTotal) && (float) $overrideTotal >= 0) {
            $_SESSION['admin_cart_total_override'] = round((float) $overrideTotal, 2);
        } else {
            unset($_SESSION['admin_cart_total_override']);
        }
    }

    if (isset($_POST['add_to_cart'])) {
        $productId = (int) $_POST['product_id'];
        $quantity = (int) ($_POST['quantity'] ?? 1);
        if ($productId > 0 && $quantity > 0) {
            if (!isset($_SESSION['admin_cart'][$productId])) {
                $_SESSION['admin_cart'][$productId] = 0;
            }
            $_SESSION['admin_cart'][$productId] += $quantity;
        }
        redirect('create-order.php?step=' . $step);
    } elseif (isset($_POST['remove_from_cart'])) {
        $productId = (int) $_POST['product_id'];
        unset($_SESSION['admin_cart'][$productId]);
        unset($_SESSION['admin_cart_prices'][$productId]);
        redirect('create-order.php?step=' . $step);
    } elseif (isset($_POST['update_cart'])) {
        redirect('create-order.php?step=' . $step);
    } elseif (isset($_POST['create_order'])) {
        $customerId = (int) $_POST['customer_id'];
        if ($customerId > 0 && !empty($_SESSION['admin_cart'])) {
            // Calculate total
            $total = 0;
            $orderItems = [];
            foreach ($_SESSION['admin_cart'] as $productId => $quantity) {
                $product = getProduct($productId);
                if ($product) {
                    $price = isset($_SESSION['admin_cart_prices'][$productId]) ? floatval($_SESSION['admin_cart_prices'][$productId]) : $product['price'];
                    $subtotal = $price * $quantity;
                    $total += $subtotal;
                    $orderItems[] = [
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'price' => $price,
                        'subtotal' => $subtotal
                    ];
                }
            }

            if (!empty($orderItems)) {
                if (isset($_SESSION['admin_cart_total_override']) && $_SESSION['admin_cart_total_override'] >= 0) {
                    $total = $_SESSION['admin_cart_total_override'];
                }

                // Create order
                $pdo->beginTransaction();
                try {
                    $orderSql = "INSERT INTO orders (user_id, total_amount, status, payment_method, created_at) VALUES (?, ?, 'confirmed', 'walk_in', NOW())";
                    $orderStmt = $pdo->prepare($orderSql);
                    $orderStmt->execute([$customerId, $total]);
                    $orderId = $pdo->lastInsertId();

                    // Insert order items
                    $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                    $itemStmt = $pdo->prepare($itemSql);
                    foreach ($orderItems as $item) {
                        $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
                    }

                    // Update inventory
                    foreach ($orderItems as $item) {
                        $updateStockSql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
                        $updateStmt = $pdo->prepare($updateStockSql);
                        $updateStmt->execute([$item['quantity'], $item['product_id']]);
                    }

                    $pdo->commit();
                    unset($_SESSION['admin_cart']);
                    unset($_SESSION['admin_cart_prices']);
                    unset($_SESSION['admin_cart_total_override']);

                    // Mark as delivered immediately so the order is recorded in revenue reports.
                    updateOrderStatus($orderId, 'delivered', 'Walk-in order completed and recorded automatically by admin.');
                    awardRewardPointsForOrder($customerId, $orderId);

                    $_SESSION['success'] = "Order created successfully for customer.";
                    redirect('orders.php');
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['error'] = "Failed to create order: " . $e->getMessage();
                }
            }
        } else {
            $_SESSION['error'] = "Please select a customer and add items to cart.";
        }
        redirect('create-order.php?step=5');
    }
}

// Get cart items
$cartItems = [];
$cartTotal = 0;
if (!empty($_SESSION['admin_cart'])) {
    foreach ($_SESSION['admin_cart'] as $productId => $quantity) {
        $product = getProduct($productId);
        if ($product) {
            $price = isset($_SESSION['admin_cart_prices'][$productId]) ? floatval($_SESSION['admin_cart_prices'][$productId]) : $product['price'];
            $subtotal = $price * $quantity;
            $cartTotal += $subtotal;
            $cartItems[] = array_merge($product, ['quantity' => $quantity, 'price' => $price, 'subtotal' => $subtotal]);
        }
    }
}

// Get customers for step 5
$customers = [];
if ($step == 5) {
    $customers = $pdo->query("SELECT id, full_name, username, email FROM users WHERE role = 'customer' ORDER BY COALESCE(NULLIF(full_name,''), username)")->fetchAll(PDO::FETCH_ASSOC);
}

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Create Order</h2>
        <a href="orders.php" class="btn btn-outline-secondary">Back to Orders</a>
    </div>

    <!-- Progress Bar -->
    <div class="mb-4">
        <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($step / $maxStep) * 100; ?>%"></div>
        </div>
        <div class="d-flex justify-content-between mt-2">
            <?php foreach ($customizeStepsData as $key => $stepData): ?>
                <small class="text-<?php echo $step > $stepData['step'] ? 'success' : ($step == $stepData['step'] ? 'primary' : 'muted'); ?>">
                    Step <?php echo $stepData['step']; ?>: <?php echo $stepData['short_label']; ?>
                </small>
            <?php endforeach; ?>
            <small class="text-<?php echo $step == 5 ? 'primary' : 'muted'; ?>">Finalize</small>
        </div>
    </div>

    <?php if ($step <= 4): ?>
        <!-- Product Selection Steps -->
        <?php $currentStepData = array_values($customizeStepsData)[$step - 1]; ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5><?php echo $currentStepData['title']; ?></h5>
                        <p class="mb-0 text-muted"><?php echo $currentStepData['subtitle']; ?></p>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($currentStepData['items'] as $product): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card h-100">
                                        <img src="../assets/images/products/<?php echo htmlspecialchars($product['image'] ?? 'default.jpg'); ?>" class="card-img-top" alt="<?php echo $product['name']; ?>" style="height: 150px; object-fit: cover;" onerror="this.onerror=null;this.src='../assets/images/products/default.jpg';">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo $product['name']; ?></h6>
                                            <p class="card-text text-success fw-bold">₱<?php echo number_format($product['price'], 2); ?></p>
                                            <form method="POST" class="d-flex gap-2">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="number" name="quantity" value="1" min="1" class="form-control form-control-sm" style="width: 60px;">
                                                <button type="submit" name="add_to_cart" class="btn btn-success btn-sm">Add</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h6>Current Cart</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($cartItems)): ?>
                            <p class="text-muted">No items added yet.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($cartItems as $item): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo $item['name']; ?></h6>
                                                <small class="text-muted">Qty: <?php echo $item['quantity']; ?> × ₱<?php echo number_format($item['price'], 2); ?></small>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-bold">₱<?php echo number_format($item['subtotal'], 2); ?></div>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" name="remove_from_cart" class="btn btn-sm btn-outline-danger">Remove</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total:</strong>
                                <strong>₱<?php echo number_format($cartTotal, 2); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="d-flex justify-content-between mt-4">
            <?php if ($step > 1): ?>
                <a href="?step=<?php echo $step - 1; ?>" class="btn btn-outline-secondary">Previous</a>
            <?php else: ?>
                <div></div>
            <?php endif; ?>
            <?php if ($step < 4): ?>
                <a href="?step=<?php echo $step + 1; ?>" class="btn btn-primary">Next</a>
            <?php else: ?>
                <a href="?step=5" class="btn btn-success">Finalize Order</a>
            <?php endif; ?>
        </div>

    <?php elseif ($step == 5): ?>
        <!-- Finalize Order -->
        <form method="POST">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Qty</th>
                                            <th>Price</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cartItems as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td>
                                                    <input type="number" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" class="form-control form-control-sm" style="width: 80px;">
                                                </td>
                                                <td>
                                                    <input type="text" name="price[<?php echo $item['id']; ?>]" value="<?php echo number_format($item['price'], 2, '.', ''); ?>" class="form-control form-control-sm" style="width: 100px;">
                                                </td>
                                                <td>₱<?php echo number_format($item['subtotal'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3">Order Total</th>
                                            <th>
                                                <input type="text" name="override_total" value="<?php echo isset($_SESSION['admin_cart_total_override']) ? number_format($_SESSION['admin_cart_total_override'], 2, '.', '') : number_format($cartTotal, 2, '.', ''); ?>" class="form-control form-control-sm text-end" style="max-width: 140px;">
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <button type="submit" name="update_cart" class="btn btn-outline-secondary">Update Cart</button>
                                <span class="text-muted">Edit quantities or prices, then update before creating the order.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6>Assign to Customer</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Select Customer</label>
                                <select name="customer_id" id="customer_id" class="form-select" required>
                                    <option value="">Choose customer...</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?php echo $customer['id']; ?>"><?php echo htmlspecialchars($customer['full_name'] ?: $customer['email']); ?> (<?php echo htmlspecialchars($customer['email']); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="create_order" class="btn btn-success w-100">Create Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Navigation -->
        <div class="d-flex justify-content-between mt-4">
            <a href="?step=4" class="btn btn-outline-secondary">Back to Selection</a>
            <div></div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
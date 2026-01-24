<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$cart_items = getCartItems($userId);
$cart_total = getCartTotal($userId);
$shipping_fee = 10;
$tax_rate = 0.10;

if (empty($cart_items)) {
    redirect('cart.php');
}

$user_addresses = getUserAddresses($userId);

$couponSuccess = $_SESSION['coupon_success'] ?? '';
$couponError = $_SESSION['coupon_error'] ?? '';
unset($_SESSION['coupon_success'], $_SESSION['coupon_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['apply_coupon'])) {
        $code = trim($_POST['coupon_code'] ?? '');
        if ($code === '') {
            $_SESSION['coupon_error'] = 'Please enter a coupon code.';
        } else {
            $validation = validateCouponForOrder($code, $userId, $cart_total);
            $validatedCoupon = $validation['coupon'] ?? null;
            if ($validation['valid'] && is_array($validatedCoupon)) {
                $_SESSION['applied_coupon'] = [
                    'id' => (int) $validatedCoupon['id'],
                    'code' => $validatedCoupon['code'],
                    'discount_amount' => $validation['discount_amount']
                ];
                $_SESSION['coupon_success'] = 'Coupon applied successfully!';
            } else {
                unset($_SESSION['applied_coupon']);
                $_SESSION['coupon_error'] = $validation['error'];
            }
        }
        redirect('checkout.php');
    }

    if (isset($_POST['remove_coupon'])) {
        unset($_SESSION['applied_coupon']);
        $_SESSION['coupon_success'] = 'Coupon removed.';
        redirect('checkout.php');
    }

    if (isset($_POST['place_order'])) {
        $shipping_address_id = $_POST['shipping_address'] ?? null;
        $payment_method = $_POST['payment_method'] ?? 'credit_card';

        $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
        $stmt->execute([$shipping_address_id, $userId]);
        $address = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($address) {
            $shipping_address = "{$address['street_address']}, {$address['city']}, {$address['state']} {$address['zip_code']}, {$address['country']}";

            $couponData = null;
            $discountAmount = 0;

            if (!empty($_SESSION['applied_coupon'])) {
                $couponSession = $_SESSION['applied_coupon'];
                $couponCodeFromSession = $couponSession['code'] ?? '';

                if ($couponCodeFromSession === '') {
                    unset($_SESSION['applied_coupon']);
                    $_SESSION['coupon_error'] = 'Stored coupon could not be read. Please re-apply it.';
                    redirect('checkout.php');
                }

                $validation = validateCouponForOrder($couponCodeFromSession, $userId, $cart_total);
                $validatedCoupon = $validation['coupon'] ?? null;

                if (!$validation['valid'] || !is_array($validatedCoupon)) {
                    unset($_SESSION['applied_coupon']);
                    $_SESSION['coupon_error'] = $validation['error'];
                    redirect('checkout.php');
                }

                $couponData = [
                    'id' => (int) $validatedCoupon['id'],
                    'code' => $validatedCoupon['code'],
                    'discount_amount' => $validation['discount_amount']
                ];
                $_SESSION['applied_coupon'] = $couponData;
                $discountAmount = $couponData['discount_amount'];
            }

            $discountedSubtotal = max(0, $cart_total - $discountAmount);
            $tax_amount = $discountedSubtotal * $tax_rate;
            $total_amount = $discountedSubtotal + $shipping_fee + $tax_amount;

            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, payment_method) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $total_amount, $shipping_address, $payment_method]);
            $order_id = $pdo->lastInsertId();

            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);

                updateInventory($item['product_id'], -$item['quantity'], 'purchase', "Order #$order_id");
            }

            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);

            updateOrderStatus($order_id, 'pending', 'Order placed successfully');

            if ($couponData) {
                ensureOrderCouponColumns();
                $stmt = $pdo->prepare("UPDATE orders SET coupon_code = ?, coupon_discount = ? WHERE id = ?");
                $stmt->execute([$couponData['code'], $couponData['discount_amount'], $order_id]);
                recordCouponUsage($couponData['id'], $userId, $order_id);
                unset($_SESSION['applied_coupon']);
            }

            notifyAdmins('New order placed', 'Order #' . $order_id . ' needs review.', 'order', $order_id);

            $_SESSION['order_success'] = $order_id;
            redirect('order-success.php');
        }
    } elseif (isset($_POST['add_address'])) {
        $address_data = [
            'address_type' => $_POST['address_type'],
            'street_address' => $_POST['street_address'],
            'city' => $_POST['city'],
            'state' => $_POST['state'],
            'zip_code' => $_POST['zip_code'],
            'country' => $_POST['country'],
            'is_default' => isset($_POST['is_default'])
        ];

        if (addUserAddress($userId, $address_data)) {
            $_SESSION['success'] = "Address added successfully!";
            redirect('checkout.php');
        } else {
            $_SESSION['error'] = "Failed to add address.";
        }
    }
}

$appliedCoupon = null;
if (!empty($_SESSION['applied_coupon'])) {
    $sessionCoupon = $_SESSION['applied_coupon'];
    $sessionCode = $sessionCoupon['code'] ?? '';

    if ($sessionCode !== '') {
        $validation = validateCouponForOrder($sessionCode, $userId, $cart_total);
        $validatedCoupon = $validation['coupon'] ?? null;
    } else {
        $validation = ['valid' => false, 'error' => 'Stored coupon code is missing.'];
        $validatedCoupon = null;
    }

    if ($validation['valid'] && is_array($validatedCoupon)) {
        $appliedCoupon = [
            'id' => (int) $validatedCoupon['id'],
            'code' => $validatedCoupon['code'],
            'discount_amount' => $validation['discount_amount']
        ];
        $_SESSION['applied_coupon'] = $appliedCoupon;
    } else {
        $couponError = $couponError ?: $validation['error'];
        unset($_SESSION['applied_coupon']);
    }
}

$discountAmount = $appliedCoupon['discount_amount'] ?? 0;
$discountedSubtotal = max(0, $cart_total - $discountAmount);
$tax_amount = $discountedSubtotal * $tax_rate;
$grand_total = $discountedSubtotal + $shipping_fee + $tax_amount;
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <h2 class="mb-4">Checkout</h2>
    
    <form method="POST">
    <div class="row">
        <div class="col-lg-8">
            <!-- Shipping Address -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Shipping Address</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($user_addresses)): ?>
                        <div class="alert alert-info">
                            <p>No addresses found. Please add a shipping address.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($user_addresses as $address): ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" 
                                   name="shipping_address" 
                                   id="address<?php echo $address['id']; ?>" 
                                   value="<?php echo $address['id']; ?>" 
                                   <?php echo $address['is_default'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="address<?php echo $address['id']; ?>">
                                <strong><?php echo ucfirst($address['address_type']); ?> Address</strong><br>
                                <?php echo $address['street_address']; ?>, <?php echo $address['city']; ?>, 
                                <?php echo $address['state']; ?> <?php echo $address['zip_code']; ?>, 
                                <?php echo $address['country']; ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="alert alert-light border mt-4">
                        Need to ship somewhere else? Use the "Add Address" form below to save a new destination before placing your order.
                    </div>
                </div>
            </div>
            
            <!-- Payment Method -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Payment Method</h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="payment_method" 
                               id="credit_card" value="credit_card" checked>
                        <label class="form-check-label" for="credit_card">
                            <i class="fas fa-credit-card me-2"></i>Gcash
                        </label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="payment_method" 
                               id="paypal" value="paypal">
                        <label class="form-check-label" for="paypal">
                            <i class="fab fa-paypal me-2"></i>Paymaya
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" 
                               id="cod" value="cash_on_delivery">
                        <label class="form-check-label" for="cod">
                            <i class="fas fa-money-bill-wave me-2"></i>Cash on Delivery
                        </label>
                    </div>
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
                    <?php if ($couponSuccess): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($couponSuccess); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($couponError): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($couponError); ?>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Have a coupon?</label>
                        <?php if ($appliedCoupon): ?>
                            <div class="alert alert-success d-flex justify-content-between align-items-center mb-0">
                                <div>
                                    <strong><?php echo htmlspecialchars($appliedCoupon['code']); ?></strong>
                                    <div class="small">Discount: ₱<?php echo number_format($appliedCoupon['discount_amount'], 2); ?></div>
                                </div>
                                <button type="submit" name="remove_coupon" class="btn btn-link text-danger p-0">Remove</button>
                            </div>
                        <?php else: ?>
                            <div class="input-group">
                                <input type="text" name="coupon_code" class="form-control" placeholder="Enter coupon code">
                                <button class="btn btn-outline-secondary" type="submit" name="apply_coupon">Apply</button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php foreach ($cart_items as $item): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                            <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                        </div>
                        <span>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                    
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>₱<?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    <?php if ($discountAmount > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Coupon Discount:</span>
                            <span>-₱<?php echo number_format($discountAmount, 2); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Discounted Subtotal:</span>
                        <span>₱<?php echo number_format($discountedSubtotal, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span>₱<?php echo number_format($shipping_fee, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax:</span>
                        <span>₱<?php echo number_format($tax_amount, 2); ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3 fw-bold fs-5">
                        <span>Total:</span>
                        <span>₱<?php echo number_format($grand_total, 2); ?></span>
                    </div>
                    
                    <?php if (!empty($user_addresses)): ?>
                    <button type="submit" name="place_order" class="btn btn-success w-100 btn-lg" formnovalidate>
                        Place Order
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    </form>
</div>

<!-- Add New Address Card -->
<div class="container pb-5">
    <div class="row justify-content-start">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Add New Address</h5>
                </div>
                <form method="POST" class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Address Type</label>
                            <select name="address_type" class="form-select" required>
                                <option value="home">Home</option>
                                <option value="work">Work</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-md-4">
                                <input class="form-check-input" type="checkbox" name="is_default" id="new_is_default">
                                <label class="form-check-label" for="new_is_default">
                                    Set as default address
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Street Address</label>
                        <input type="text" name="street_address" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">State / Province</label>
                            <input type="text" name="state" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ZIP Code</label>
                            <input type="text" name="zip_code" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" value="Philippines" required>
                        </div>
                    </div>
                    <button type="submit" name="add_address" class="btn btn-outline-success">
                        Add Address
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
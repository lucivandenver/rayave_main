<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

ensureRewardsSystem();

$userId = $_SESSION['user_id'];
$cart_items = getCartItems($userId);
$cart_total = getCartTotal($userId);
$pickup_shipping_fee = 0;
$customizeCategoryId = getCustomizeCategoryId();
$customizeCartItems = [];
$regularCartItems = [];
$customizeBundleTotal = 0.0;
$hasCustomizeCartItems = false;

foreach ($cart_items as $cartItem) {
    $itemCategoryId = (int) ($cartItem['category_id'] ?? 0);
    if ($customizeCategoryId && $itemCategoryId === (int) $customizeCategoryId) {
        $customizeCartItems[] = $cartItem;
        $customizeBundleTotal += ((float) $cartItem['price'] * (int) $cartItem['quantity']);
    } else {
        $regularCartItems[] = $cartItem;
    }
}

if (!empty($customizeCartItems)) {
    $hasCustomizeCartItems = true;
}

$activeCustomDesign = $hasCustomizeCartItems ? getActiveCartCustomDesign($userId) : null;
$activeCustomDesignImage = null;
if ($activeCustomDesign && !empty($activeCustomDesign['preview_image']) && file_exists(__DIR__ . '/' . $activeCustomDesign['preview_image'])) {
    $activeCustomDesignImage = $activeCustomDesign['preview_image'];
}

$allowOutOfStockOrders = isOutOfStockOrderingEnabled();

// Prevent checkout when orders are disallowed (maintenance mode disabled)
// Set an error so the customer-side modal appears, but do NOT redirect here
// (redirecting to the same page causes a redirect loop). The POST handler
// below still performs a PRG redirect after placing the error in the session.
if (!$allowOutOfStockOrders) {
    $_SESSION['checkout_error'] = 'Orders are currently disallowed. The system is under maintenance for inventory updates. You can add items to your cart, but cannot place orders at this time. Please try again later.';
}

$checkoutHasBlockedItems = !$allowOutOfStockOrders && count(array_filter($cart_items, function ($item) {
    return isset($item['stock_quantity']) && (int) $item['stock_quantity'] <= 0;
})) > 0;

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
$checkoutDeliveryEstimateRange = getSequentialDeliveryDateRange($cartEstimatedStartDate, 3);

$validFulfillmentMethods = ['delivery', 'pickup'];
$fulfillmentMethod = $_SESSION['checkout_fulfillment'] ?? 'delivery';
if (!in_array($fulfillmentMethod, $validFulfillmentMethods, true)) {
    $fulfillmentMethod = 'delivery';
}

if (empty($cart_items)) {
    redirect('cart.php');
}

$user_addresses = getUserAddresses($userId);
$selectedShippingAddressId = $_SESSION['checkout_shipping_address'] ?? null;
if (isset($_POST['shipping_address'])) {
    $selectedShippingAddressId = (int) $_POST['shipping_address'];
}

if (!$selectedShippingAddressId && !empty($user_addresses)) {
    $defaultAddressId = null;
    foreach ($user_addresses as $address) {
        if (!empty($address['is_default'])) {
            $defaultAddressId = $address['id'];
            break;
        }
    }
    $selectedShippingAddressId = $defaultAddressId ?: $user_addresses[0]['id'];
}

if ($selectedShippingAddressId) {
    $_SESSION['checkout_shipping_address'] = $selectedShippingAddressId;
}

$activeShippingAddress = null;
if ($selectedShippingAddressId) {
    foreach ($user_addresses as $address) {
        if ((int) $address['id'] === (int) $selectedShippingAddressId) {
            $activeShippingAddress = $address;
            break;
        }
    }

    if (!$activeShippingAddress) {
        $activeShippingAddress = getUserAddressById($selectedShippingAddressId, $userId);
    }
}

$alternateRecipientDefaults = [
    'enabled' => false,
    'name' => '',
    'contact' => '',
    'address' => '',
    'notes' => ''
];
$alternateRecipientForm = array_merge($alternateRecipientDefaults, $_SESSION['checkout_recipient'] ?? []);
$recipientAddressPlaceholder = $activeShippingAddress ? formatUserAddressLine($activeShippingAddress) : '';

$couponSuccess = $_SESSION['coupon_success'] ?? '';
$couponError = $_SESSION['coupon_error'] ?? '';
unset($_SESSION['coupon_success'], $_SESSION['coupon_error']);

$rewardSuccess = $_SESSION['reward_success'] ?? '';
$rewardError = $_SESSION['reward_error'] ?? '';
unset($_SESSION['reward_success'], $_SESSION['reward_error']);

$checkoutError = $_SESSION['checkout_error'] ?? '';
unset($_SESSION['checkout_error']);

$isAddAddressAction = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address']);
$isPrimaryCheckoutPost = $_SERVER['REQUEST_METHOD'] === 'POST' && !$isAddAddressAction;

if ($isPrimaryCheckoutPost) {
    $alternateRecipientForm['enabled'] = isset($_POST['alternate_recipient_enabled']);
    $alternateRecipientForm['name'] = trim($_POST['alternate_recipient_name'] ?? '');
    $alternateRecipientForm['contact'] = trim($_POST['alternate_recipient_contact'] ?? '');
    $alternateRecipientForm['address'] = trim($_POST['alternate_recipient_address'] ?? '');
    $alternateRecipientForm['notes'] = trim($_POST['alternate_recipient_notes'] ?? '');
    $_SESSION['checkout_recipient'] = $alternateRecipientForm;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedMethod = $_POST['fulfillment_method'] ?? null;
    if ($postedMethod && in_array($postedMethod, $validFulfillmentMethods, true)) {
        $fulfillmentMethod = $postedMethod;
        $_SESSION['checkout_fulfillment'] = $fulfillmentMethod;
    }

    if (isset($_POST['apply_coupon'])) {
        if (!empty($_SESSION['applied_reward'])) {
            $_SESSION['coupon_error'] = 'Remove your applied reward first before using a coupon.';
            redirect('checkout.php');
        }

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

    if (isset($_POST['apply_reward'])) {
        if (!empty($_SESSION['applied_coupon'])) {
            $_SESSION['reward_error'] = 'Remove your applied coupon first before redeeming rewards.';
            redirect('checkout.php');
        }

        $tierId = isset($_POST['reward_tier_id']) ? (int) $_POST['reward_tier_id'] : 0;
        if ($tierId <= 0) {
            $_SESSION['reward_error'] = 'Please choose a reward tier to redeem.';
            redirect('checkout.php');
        }

        $rewardValidation = validateRewardTierForUser($tierId, $userId, $cart_total);
        if (!$rewardValidation['valid'] || empty($rewardValidation['tier'])) {
            unset($_SESSION['applied_reward']);
            $_SESSION['reward_error'] = $rewardValidation['error'] ?: 'Reward tier could not be applied.';
            redirect('checkout.php');
        }

        $tier = $rewardValidation['tier'];
        $_SESSION['applied_reward'] = [
            'tier_id' => (int) $tier['id'],
            'required_orders' => (int) $tier['required_orders'],
            'discount_percent' => (float) $tier['discount_percent'],
            'points_cost' => (int) $tier['points_cost'],
            'discount_amount' => (float) $rewardValidation['discount_amount']
        ];
        $_SESSION['reward_success'] = 'Reward tier applied successfully!';
        redirect('checkout.php');
    }

    if (isset($_POST['remove_reward'])) {
        unset($_SESSION['applied_reward']);
        $_SESSION['reward_success'] = 'Reward removed.';
        redirect('checkout.php');
    }

    if (isset($_POST['place_order'])) {
        // Final security check: ensure orders are allowed during order creation
        if (!isOutOfStockOrderingEnabled()) {
            $_SESSION['checkout_error'] = 'Orders are currently disallowed. Order placement is not permitted at this time.';
            redirect('checkout.php');
        }

        $shipping_address_id = isset($_POST['shipping_address']) ? (int) $_POST['shipping_address'] : 0;
        $payment_method = $_POST['payment_method'] ?? 'credit_card';
        $activeFulfillmentMethod = in_array($fulfillmentMethod, $validFulfillmentMethods, true) ? $fulfillmentMethod : 'delivery';
        $shippingFeeForOrder = $pickup_shipping_fee;
        $shipping_address = '';
        $shippingDistanceKm = null;
        $shippingDistanceMiles = null;
        $shippingDetailsPayload = [];
        $alternateRecipientEnabled = !empty($alternateRecipientForm['enabled']);
        $recipientNameForOrder = trim($alternateRecipientForm['name']);
        $recipientContactForOrder = trim($alternateRecipientForm['contact']);
        $recipientAddressForOrder = trim($alternateRecipientForm['address']);
        $recipientNotesForOrder = trim($alternateRecipientForm['notes']);

        if ($alternateRecipientEnabled) {
            if ($recipientNameForOrder === '') {
                $_SESSION['checkout_error'] = 'Please enter the name of the person who will receive the order.';
                redirect('checkout.php');
            }

            if ($activeFulfillmentMethod === 'delivery' && $recipientAddressForOrder === '') {
                $_SESSION['checkout_error'] = 'Please add the delivery address or drop-off instructions for the alternate recipient.';
                redirect('checkout.php');
            }
        }

        if ($activeFulfillmentMethod === 'delivery') {
            if ($shipping_address_id <= 0) {
                $_SESSION['checkout_error'] = 'Please select a shipping address for delivery orders.';
                redirect('checkout.php');
            }

            $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE id = ? AND user_id = ?");
            $stmt->execute([$shipping_address_id, $userId]);
            $address = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$address) {
                $_SESSION['checkout_error'] = 'We could not find the selected shipping address.';
                redirect('checkout.php');
            }

            $shipping_address = formatUserAddressLine($address);
            $quoteForOrder = calculateShippingQuote($address, 'delivery');

            if (empty($quoteForOrder['success'])) {
                $_SESSION['checkout_error'] = $quoteForOrder['messages'][0] ?? 'Unable to calculate the shipping fee. Please try again.';
                redirect('checkout.php');
            }

            $shippingFeeForOrder = (float) ($quoteForOrder['fee'] ?? 0);
            $shippingDistanceKm = $quoteForOrder['distance_km'] ?? null;
            $shippingDistanceMiles = $quoteForOrder['distance_miles'] ?? null;
            $shippingDetailsPayload = [
                'method' => 'delivery',
                'address' => $shipping_address,
                'distance_text' => $quoteForOrder['distance_text'] ?? null,
                'duration_text' => $quoteForOrder['duration_text'] ?? null,
                'messages' => $quoteForOrder['messages'] ?? [],
                'unit' => $quoteForOrder['unit'] ?? null,
                'rate_used' => $quoteForOrder['rate_used'] ?? null,
                'base_fee' => $quoteForOrder['base_fee'] ?? null,
                'minimum_fee' => $quoteForOrder['minimum_fee'] ?? null,
                'bundle_status' => [
                    'has_custom_bundle' => $hasCustomizeCartItems,
                    'finalized' => false,
                    'finalized_at' => null,
                    'final_total' => null
                ]
            ];
        } else {
            $pickupLocation = 'Store Pickup - Bloomify Flower Studio, Raymundo Avenue, Gloria, Oriental Mindoro (No delivery required)';
            $shipping_address = $pickupLocation;
            $shippingDetailsPayload = [
                'method' => 'pickup',
                'address' => $pickupLocation,
                'messages' => ['Store pickup selected.'],
                'bundle_status' => [
                    'has_custom_bundle' => $hasCustomizeCartItems,
                    'finalized' => false,
                    'finalized_at' => null,
                    'final_total' => null
                ]
            ];
        }

        if ($alternateRecipientEnabled) {
            $shippingDetailsPayload['recipient'] = [
                'mode' => 'alternate',
                'name' => $recipientNameForOrder,
                'contact' => $recipientContactForOrder,
                'address' => $recipientAddressForOrder,
                'notes' => $recipientNotesForOrder
            ];
        } else {
            $shippingDetailsPayload['recipient'] = [
                'mode' => 'self'
            ];
        }

        $couponData = null;
        $rewardData = null;
        $discountAmount = 0;

        if (!empty($_SESSION['applied_coupon'])) {
            if (!empty($_SESSION['applied_reward'])) {
                unset($_SESSION['applied_reward']);
            }

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
        } elseif (!empty($_SESSION['applied_reward'])) {
            $rewardSession = $_SESSION['applied_reward'];
            $rewardTierId = (int) ($rewardSession['tier_id'] ?? 0);

            if ($rewardTierId <= 0) {
                unset($_SESSION['applied_reward']);
                $_SESSION['reward_error'] = 'Stored reward tier is invalid. Please redeem again.';
                redirect('checkout.php');
            }

            $rewardValidation = validateRewardTierForUser($rewardTierId, $userId, $cart_total);
            if (!$rewardValidation['valid'] || empty($rewardValidation['tier'])) {
                unset($_SESSION['applied_reward']);
                $_SESSION['reward_error'] = $rewardValidation['error'] ?: 'Reward tier is no longer available.';
                redirect('checkout.php');
            }

            $tier = $rewardValidation['tier'];
            $rewardData = [
                'tier_id' => (int) $tier['id'],
                'required_orders' => (int) $tier['required_orders'],
                'discount_percent' => (float) $tier['discount_percent'],
                'points_cost' => (int) $rewardValidation['points_to_spend'],
                'discount_amount' => (float) $rewardValidation['discount_amount']
            ];

            $_SESSION['applied_reward'] = $rewardData;
            $discountAmount = $rewardData['discount_amount'];
        }

        $discountedSubtotal = max(0, $cart_total - $discountAmount);
        $total_amount = $discountedSubtotal + $shippingFeeForOrder;

        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, payment_method) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $total_amount, $shipping_address, $payment_method]);
        $order_id = $pdo->lastInsertId();

        ensureOrderShippingColumns();
        ensureOrderRecipientColumns();
        $stmt = $pdo->prepare("UPDATE orders SET shipping_fee = ?, shipping_distance_km = ?, shipping_distance_miles = ?, shipping_details = ?, recipient_is_alternate = ?, recipient_name = ?, recipient_contact = ?, recipient_address = ?, recipient_notes = ? WHERE id = ?");
        $stmt->execute([
            $shippingFeeForOrder,
            $shippingDistanceKm,
            $shippingDistanceMiles,
            json_encode($shippingDetailsPayload, JSON_UNESCAPED_UNICODE),
            $alternateRecipientEnabled ? 1 : 0,
            $alternateRecipientEnabled ? $recipientNameForOrder : null,
            $alternateRecipientEnabled && $recipientContactForOrder !== '' ? $recipientContactForOrder : null,
            $alternateRecipientEnabled && $recipientAddressForOrder !== '' ? $recipientAddressForOrder : null,
            $alternateRecipientEnabled && $recipientNotesForOrder !== '' ? $recipientNotesForOrder : null,
            $order_id
        ]);

        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
        }

        $inventoryResult = consumeInventoryForOrder($order_id);
        if (empty($inventoryResult['success'])) {
            $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$order_id]);
            $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$order_id]);
            $_SESSION['checkout_error'] = $inventoryResult['message'] ?? 'We could not reserve enough inventory to fulfill this order. Please adjust the quantity or try again later.';
            redirect('checkout.php');
        }

        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);

        if ($hasCustomizeCartItems) {
            attachCustomDesignToOrder($userId, $order_id);
        }

        updateOrderStatus($order_id, 'pending', 'Order placed successfully');

        if ($couponData) {
            ensureOrderCouponColumns();
            $stmt = $pdo->prepare("UPDATE orders SET coupon_code = ?, coupon_discount = ? WHERE id = ?");
            $stmt->execute([$couponData['code'], $couponData['discount_amount'], $order_id]);
            recordCouponUsage($couponData['id'], $userId, $order_id);
            unset($_SESSION['applied_coupon']);
        }

        if ($rewardData) {
            $pointsSpent = (int) ($rewardData['points_cost'] ?? 0);
            $rewardSpendOk = true;
            if ($pointsSpent > 0) {
                $rewardSpendOk = spendRewardPointsForOrder($userId, $order_id, $pointsSpent, [
                    'reward_tier_id' => (int) $rewardData['tier_id'],
                    'required_orders' => (int) $rewardData['required_orders'],
                    'discount_percent' => (float) $rewardData['discount_percent']
                ]);
            }

            if (!$rewardSpendOk) {
                error_log('Reward points deduction failed for order #' . $order_id . ' and user #' . $userId);
                $pointsSpent = 0;
            }

            $stmt = $pdo->prepare("UPDATE orders SET reward_discount_percent = ?, reward_discount_amount = ?, reward_points_spent = ? WHERE id = ?");
            $stmt->execute([
                (float) $rewardData['discount_percent'],
                (float) $rewardData['discount_amount'],
                $pointsSpent,
                $order_id
            ]);

            unset($_SESSION['applied_reward']);
        }

        $pointsEarned = awardRewardPointsForOrder($userId, $order_id);
        if ($pointsEarned > 0) {
            $stmt = $pdo->prepare("UPDATE orders SET reward_points_earned = ? WHERE id = ?");
            $stmt->execute([$pointsEarned, $order_id]);
        }

        notifyAdmins('New order placed', 'Order #' . $order_id . ' needs review.', 'order', $order_id);
        unset($_SESSION['checkout_fulfillment'], $_SESSION['checkout_shipping_address'], $_SESSION['checkout_recipient']);

        $_SESSION['order_success'] = $order_id;
        redirect('order-success.php');
    } elseif ($isAddAddressAction) {
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

$rewardPointsBalance = getUserRewardPoints($userId);
$userQualifyingOrders = getUserQualifyingOrderCount($userId);
$availableRewardTiers = [];
$appliedReward = null;

foreach (getRewardTiers(true) as $tier) {
    $tierDiscountAmount = calculateRewardDiscountAmount($tier, $cart_total);
    if ($tierDiscountAmount <= 0) {
        continue;
    }

    $requiredOrders = (int) ($tier['required_orders'] ?? 0);
    $pointsCost = (int) ($tier['points_cost'] ?? 0);
    $availableRewardTiers[] = [
        'id' => (int) $tier['id'],
        'required_orders' => $requiredOrders,
        'discount_percent' => (float) $tier['discount_percent'],
        'points_cost' => $pointsCost,
        'discount_amount' => $tierDiscountAmount,
        'is_unlocked' => $userQualifyingOrders >= $requiredOrders,
        'has_points' => $rewardPointsBalance >= $pointsCost
    ];
}

if (!empty($_SESSION['applied_reward'])) {
    if (!empty($_SESSION['applied_coupon'])) {
        unset($_SESSION['applied_reward']);
    } else {
        $rewardSession = $_SESSION['applied_reward'];
        $rewardTierId = (int) ($rewardSession['tier_id'] ?? 0);
        if ($rewardTierId > 0) {
            $rewardValidation = validateRewardTierForUser($rewardTierId, $userId, $cart_total);
            if ($rewardValidation['valid'] && !empty($rewardValidation['tier'])) {
                $tier = $rewardValidation['tier'];
                $appliedReward = [
                    'tier_id' => (int) $tier['id'],
                    'required_orders' => (int) $tier['required_orders'],
                    'discount_percent' => (float) $tier['discount_percent'],
                    'points_cost' => (int) $rewardValidation['points_to_spend'],
                    'discount_amount' => (float) $rewardValidation['discount_amount']
                ];
                $_SESSION['applied_reward'] = $appliedReward;
            } else {
                $rewardError = $rewardError ?: ($rewardValidation['error'] ?: 'Reward is no longer available.');
                unset($_SESSION['applied_reward']);
            }
        } else {
            unset($_SESSION['applied_reward']);
        }
    }
}

$couponDiscountAmount = $appliedCoupon['discount_amount'] ?? 0;
$rewardDiscountAmount = $appliedReward['discount_amount'] ?? 0;
$discountAmount = $couponDiscountAmount + $rewardDiscountAmount;
$discountedSubtotal = max(0, $cart_total - $discountAmount);
$shippingStatusMessage = '';
$shippingQuote = [
    'success' => $fulfillmentMethod === 'pickup',
    'fee' => $fulfillmentMethod === 'pickup' ? $pickup_shipping_fee : null,
    'messages' => [],
    'distance_text' => null,
    'duration_text' => null
];

if ($fulfillmentMethod === 'delivery') {
    if ($activeShippingAddress) {
        $shippingQuote = calculateShippingQuote($activeShippingAddress, 'delivery');
    } elseif (empty($user_addresses)) {
        $shippingQuote['messages'][] = 'Add a shipping address to calculate delivery fees.';
    } else {
        $shippingQuote['messages'][] = 'Select a shipping address to calculate delivery fees.';
    }
}

$shippingQuoteReady = $fulfillmentMethod === 'pickup' ? true : !empty($shippingQuote['success']);
$current_shipping_fee = $shippingQuoteReady ? (float) ($shippingQuote['fee'] ?? 0) : 0;
$deliveryStatus = 'pickup';
if ($fulfillmentMethod === 'delivery') {
    if ($shippingQuoteReady) {
        $deliveryStatus = 'ready';
    } elseif ($shippingStatusMessage !== '') {
        $deliveryStatus = 'failed';
    } else {
        $deliveryStatus = 'pending';
    }
}

if ($fulfillmentMethod === 'pickup') {
    $shippingLabel = 'Pickup';
} else {
    $shippingLabelSuffix = $deliveryStatus === 'pending' ? ' (pending)' : ($deliveryStatus === 'failed' ? ' (unavailable)' : '');
    $shippingLabel = 'Shipping' . $shippingLabelSuffix;
}
$grand_total = $discountedSubtotal + ($fulfillmentMethod === 'pickup' ? 0 : $current_shipping_fee);
$requiresAddress = empty($user_addresses);
$needsAddressSelection = $fulfillmentMethod === 'delivery' && !$selectedShippingAddressId;
$placeOrderDisabled = $fulfillmentMethod === 'delivery' ? ($needsAddressSelection || !$shippingQuoteReady) : false;
if ($checkoutHasBlockedItems) {
    $placeOrderDisabled = true;
}
$shippingStatusMessage = $shippingQuote['messages'][0] ?? '';
$shippingDistanceSummary = '';
if ($shippingQuoteReady && !empty($shippingQuote['distance_text'])) {
    $shippingDistanceSummary = trim($shippingQuote['distance_text'] . ' • ' . ($shippingQuote['duration_text'] ?? ''));
}
$addressNoticeShouldShow = $fulfillmentMethod === 'delivery' && ($requiresAddress || $needsAddressSelection || !$shippingQuoteReady);
$shippingAlertVisible = $fulfillmentMethod === 'delivery' && !$shippingQuoteReady;
$shippingDisplayValue = $shippingQuoteReady ? '₱' . number_format($current_shipping_fee, 2) : ($deliveryStatus === 'failed' ? 'Unavailable' : 'Calculating...');
$orderSummarySubtotalLabel = $hasCustomizeCartItems ? 'Estimated Arrangement Total' : 'Subtotal';
$orderSummaryDiscountedLabel = $hasCustomizeCartItems ? 'Estimated After Discount' : 'Discounted Subtotal';
$orderSummaryGrandLabel = $hasCustomizeCartItems ? 'Estimated Order Total' : 'Total';
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <h2 class="mb-4">Checkout</h2>
    <?php if ($checkoutError): ?>
        <div class="alert alert-danger d-none" data-modal-alert data-alert-title="Checkout Issue">
            <?php echo htmlspecialchars($checkoutError); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
    <div class="row">
        <div class="col-lg-8">
            <!-- Fulfillment Method -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Fulfillment Method</h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="fulfillment_method"
                               id="fulfillment_delivery" value="delivery"
                               <?php echo $fulfillmentMethod === 'delivery' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="fulfillment_delivery">
                            <i class="fas fa-truck me-2"></i>Delivery to my saved address
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="fulfillment_method"
                               id="fulfillment_pickup" value="pickup"
                               <?php echo $fulfillmentMethod === 'pickup' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="fulfillment_pickup">
                            <i class="fas fa-store me-2"></i>Store Pickup <span class="badge bg-success ms-1">No shipping fee</span>
                        </label>
                    </div>
                    <p class="small text-muted mt-3 mb-0">Switch to pickup if you prefer to collect your flowers in person and skip the shipping fee.</p>
                </div>
            </div>

            <!-- Shipping Address -->
            <div class="card mb-4 <?php echo $fulfillmentMethod === 'pickup' ? 'd-none' : ''; ?>" id="shippingAddressCard">
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
                        <?php $isAddressSelected = (int) $address['id'] === (int) $selectedShippingAddressId; ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" 
                                   name="shipping_address" 
                                   id="address<?php echo $address['id']; ?>" 
                                   value="<?php echo $address['id']; ?>" 
                                   <?php echo $isAddressSelected ? 'checked' : ''; ?>>
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

            <!-- Pickup Details -->
            <div class="card mb-4 <?php echo $fulfillmentMethod === 'pickup' ? '' : 'd-none'; ?>" id="pickupInfoCard">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Pickup Details</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Collect your bouquet from our Raymundo Avenue floral studio. We will notify you as soon as your order is ready.</p>
                    <ul class="list-unstyled mb-3">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2 text-success"></i>Raymundo Avenue, Gloria, Oriental Mindoro</li>
                        <li class="mb-2"><i class="fas fa-clock me-2 text-success"></i>Pickup hours: 9:00 AM – 6:00 PM (Mon-Sun)</li>
                        <li><i class="fas fa-phone me-2 text-success"></i>Hotline: +63 912 345 6789</li>
                    </ul>
                    <p class="small text-muted mb-0">Bring a valid ID or show your order confirmation email upon pickup.</p>
                </div>
            </div>

            <!-- Recipient Options -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Recipient Options</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="alternateRecipientToggle"
                               name="alternate_recipient_enabled" value="1"
                               <?php echo !empty($alternateRecipientForm['enabled']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="alternateRecipientToggle">
                            Someone else will receive or pick up this order
                        </label>
                    </div>
                    <p class="small text-muted mt-2 mb-3">
                        Keep the order under your name, but let us know who should receive it. We will show their name on the
                        hand-off note and notify riders or the pickup desk accordingly.
                    </p>

                    <div class="<?php echo !empty($alternateRecipientForm['enabled']) ? '' : 'd-none'; ?>" data-recipient-fields>
                        <div class="mb-3">
                            <label class="form-label">Recipient Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="alternate_recipient_name"
                                   value="<?php echo htmlspecialchars($alternateRecipientForm['name']); ?>"
                                   placeholder="e.g. Andrea Santos">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Recipient Contact Number</label>
                            <input type="text" class="form-control" name="alternate_recipient_contact"
                                   value="<?php echo htmlspecialchars($alternateRecipientForm['contact']); ?>"
                                   placeholder="Optional mobile we can call upon delivery">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Recipient Address / Drop-off Instructions<?php echo $fulfillmentMethod === 'delivery' ? ' <span class="text-danger">*</span>' : ''; ?></label>
                            <textarea class="form-control" name="alternate_recipient_address" rows="3"
                                      placeholder="<?php echo htmlspecialchars($recipientAddressPlaceholder ?: 'Gate no., street, barangay, building, or pickup password'); ?>"><?php echo htmlspecialchars($alternateRecipientForm['address']); ?></textarea>
                            <small class="text-muted">We can deliver to any Oriental Mindoro address. Add landmark details if this is different from your saved address.</small>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Notes for the Recipient</label>
                            <textarea class="form-control" name="alternate_recipient_notes" rows="2"
                                      placeholder="Optional reminders (e.g. &quot;Call before handing over&quot;, &quot;Require ID&quot;)"><?php echo htmlspecialchars($alternateRecipientForm['notes']); ?></textarea>
                        </div>
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
                <div class="card-body"
                     data-checkout-summary
                     data-fulfillment="<?php echo htmlspecialchars($fulfillmentMethod); ?>"
                     data-selected-address="<?php echo $selectedShippingAddressId ?: ''; ?>"
                     data-delivery-fee="<?php echo htmlspecialchars(number_format($shippingQuote['fee'] ?? 0, 2, '.', ''), ENT_QUOTES); ?>"
                     data-pickup-fee="<?php echo htmlspecialchars(number_format($pickup_shipping_fee, 2, '.', ''), ENT_QUOTES); ?>"
                     data-discounted-subtotal="<?php echo htmlspecialchars(number_format($discountedSubtotal, 2, '.', ''), ENT_QUOTES); ?>"
                     data-requires-address="<?php echo $requiresAddress ? '1' : '0'; ?>"
                     data-delivery-ready="<?php echo ($fulfillmentMethod === 'delivery' && $shippingQuoteReady) ? '1' : '0'; ?>"
                     data-delivery-status="<?php echo htmlspecialchars($deliveryStatus); ?>"
                     data-quote-endpoint="get-shipping-quote.php"
                     data-shipping-message="<?php echo htmlspecialchars($shippingStatusMessage, ENT_QUOTES); ?>"
                     data-distance-text="<?php echo htmlspecialchars($shippingQuote['distance_text'] ?? '', ENT_QUOTES); ?>"
                     data-duration-text="<?php echo htmlspecialchars($shippingQuote['duration_text'] ?? '', ENT_QUOTES); ?>">
                    <?php if ($couponSuccess): ?>
                        <div class="alert alert-success d-none" data-modal-alert data-alert-title="Coupon Applied">
                            <?php echo htmlspecialchars($couponSuccess); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($couponError): ?>
                        <div class="alert alert-danger d-none" data-modal-alert data-alert-title="Coupon Error">
                            <?php echo htmlspecialchars($couponError); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($rewardSuccess): ?>
                        <div class="alert alert-success d-none" data-modal-alert data-alert-title="Reward Applied">
                            <?php echo htmlspecialchars($rewardSuccess); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($rewardError): ?>
                        <div class="alert alert-danger d-none" data-modal-alert data-alert-title="Reward Error">
                            <?php echo htmlspecialchars($rewardError); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($alternateRecipientForm['enabled'])): ?>
                        <div class="alert alert-light border">
                            <div class="fw-semibold text-success mb-1"><i class="fas fa-user-check me-2"></i>Alternate Recipient</div>
                            <div><?php echo htmlspecialchars($alternateRecipientForm['name'] ?: 'Pending name'); ?></div>
                            <?php if (!empty($alternateRecipientForm['contact'])): ?>
                                <div class="small text-muted">Contact: <?php echo htmlspecialchars($alternateRecipientForm['contact']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($alternateRecipientForm['address'])): ?>
                                <div class="small text-muted mt-1">
                                    <?php echo nl2br(htmlspecialchars($alternateRecipientForm['address'])); ?>
                                </div>
                            <?php endif; ?>
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

                    <div class="mb-3">
                        <label class="form-label">Rewards</label>
                        <div class="small text-muted mb-2">
                            Points: <strong><?php echo (int) $rewardPointsBalance; ?></strong> • Qualified orders: <strong><?php echo (int) $userQualifyingOrders; ?></strong>
                        </div>
                        <?php if ($appliedReward): ?>
                            <div class="alert alert-warning d-flex justify-content-between align-items-center mb-0">
                                <div>
                                    <strong><?php echo number_format((float) $appliedReward['discount_percent'], 2); ?>% Reward</strong>
                                    <div class="small">Discount: ₱<?php echo number_format((float) $appliedReward['discount_amount'], 2); ?> • Cost: <?php echo (int) $appliedReward['points_cost']; ?> pts</div>
                                </div>
                                <button type="submit" name="remove_reward" class="btn btn-link text-danger p-0">Remove</button>
                            </div>
                        <?php elseif (!empty($availableRewardTiers)): ?>
                            <div class="input-group">
                                <select name="reward_tier_id" class="form-select">
                                    <option value="">Select reward tier</option>
                                    <?php foreach ($availableRewardTiers as $tier): ?>
                                        <?php
                                        $tierDisabled = !$tier['is_unlocked'] || !$tier['has_points'];
                                        $tierLabelParts = [
                                            number_format((float) $tier['discount_percent'], 2) . '% off',
                                            $tier['points_cost'] . ' pts',
                                            'from ' . $tier['required_orders'] . ' orders'
                                        ];
                                        if (!$tier['is_unlocked']) {
                                            $tierLabelParts[] = 'locked';
                                        } elseif (!$tier['has_points']) {
                                            $tierLabelParts[] = 'insufficient points';
                                        }
                                        ?>
                                        <option value="<?php echo (int) $tier['id']; ?>" <?php echo $tierDisabled ? 'disabled' : ''; ?>>
                                            <?php echo htmlspecialchars(implode(' - ', $tierLabelParts)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-outline-warning" type="submit" name="apply_reward">Redeem</button>
                            </div>
                            <div class="small text-muted mt-2">Rewards cannot be combined with coupons.</div>
                        <?php else: ?>
                            <div class="small text-muted">No available reward tiers for this order yet. Check your Rewards progress in Profile.</div>
                        <?php endif; ?>
                    </div>

                    <?php if ($hasCustomizeCartItems): ?>
                    <div class="border rounded p-3 mb-3 bg-light">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <h6 class="mb-1 text-success">Your Custom Bundle</h6>
                                <small class="text-muted">Individual stems are priced together as one arrangement.</small>
                            </div>
                            <div class="text-end">
                                <span class="text-uppercase small text-success d-block">Estimated Total</span>
                                <strong>₱<?php echo number_format($customizeBundleTotal, 2); ?></strong>
                            </div>
                        </div>
                        <?php if ($activeCustomDesignImage): ?>
                            <div class="text-center my-3 p-2 bg-white border rounded">
                                <img src="<?php echo htmlspecialchars($activeCustomDesignImage); ?>"
                                    alt="Your custom bouquet design"
                                    class="img-fluid rounded"
                                    style="max-height: 190px; object-fit: contain;">
                                <div class="small text-success fw-semibold mt-1"><i class="fas fa-palette me-1"></i>Custom Canvas Design Preview</div>
                            </div>
                        <?php endif; ?>
                        <?php if ($activeCustomDesign && !empty($activeCustomDesign['design_notes'])): ?>
                            <div class="small text-muted mb-2">
                                <strong>Florist Note:</strong> <?php echo htmlspecialchars($activeCustomDesign['design_notes']); ?>
                            </div>
                        <?php endif; ?>
                        <ul class="list-unstyled small mb-0 mt-3">
                            <?php foreach ($customizeCartItems as $component): ?>
                                <li class="d-flex justify-content-between border-bottom py-1">
                                    <span><?php echo htmlspecialchars($component['name']); ?></span>
                                    <span class="text-muted">×<?php echo (int) $component['quantity']; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="small text-muted mt-2">Final pricing posts on your tracking page.</div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($regularCartItems)): ?>
                        <?php foreach ($regularCartItems as $item): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                            </div>
                            <span>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?php echo htmlspecialchars($orderSummarySubtotalLabel); ?>:</span>
                        <span>₱<?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    <?php if ($couponDiscountAmount > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Coupon Discount:</span>
                            <span>-₱<?php echo number_format($couponDiscountAmount, 2); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($rewardDiscountAmount > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Reward Discount:</span>
                            <span>-₱<?php echo number_format($rewardDiscountAmount, 2); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?php echo htmlspecialchars($orderSummaryDiscountedLabel); ?>:</span>
                        <span>₱<?php echo number_format($discountedSubtotal, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span data-checkout-label="shipping"><?php echo htmlspecialchars($shippingLabel); ?>:</span>
                        <span data-checkout-value="shipping"><?php echo htmlspecialchars($shippingDisplayValue); ?></span>
                    </div>
                    <div class="small text-muted mb-2" data-shipping-distance>
                        <?php if ($fulfillmentMethod === 'pickup'): ?>
                            Pickup selected. Shipping not required.
                        <?php elseif ($shippingQuoteReady && $shippingDistanceSummary !== ''): ?>
                            <?php echo htmlspecialchars($shippingDistanceSummary); ?>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <div class="small text-uppercase text-success mb-2">Estimated delivery</div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($checkoutDeliveryEstimateRange); ?></div>
                    </div>

                    <?php if ($checkoutHasBlockedItems): ?>
                        <div class="alert alert-warning small mb-3">
                            Some cart items are currently out of stock. Orders cannot be placed while Maintenance is OFF.
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-warning small py-2 <?php echo $shippingAlertVisible ? '' : 'd-none'; ?>" data-shipping-alert>
                        <?php echo htmlspecialchars($shippingStatusMessage ?: 'Shipping quote pending. Select an address to continue.'); ?>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3 fw-bold fs-5">
                        <span><?php echo htmlspecialchars($orderSummaryGrandLabel); ?>:</span>
                        <span data-checkout-value="total">₱<?php echo number_format($grand_total, 2); ?></span>
                    </div>
                    
                    <button type="submit" name="place_order" class="btn btn-success w-100 btn-lg"
                            formnovalidate id="placeOrderButton" <?php echo $placeOrderDisabled ? 'disabled' : ''; ?>>
                        Place Order
                    </button>
                    <div id="addressRequirementNotice" class="text-muted small mt-2 <?php echo $addressNoticeShouldShow ? '' : 'd-none'; ?>">
                        Add a shipping address and let us calculate the delivery fee, or switch to pickup to continue.
                    </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const summary = document.querySelector('[data-checkout-summary]');
    if (!summary) {
        return;
    }

    const fulfillmentRadios = document.querySelectorAll('input[name="fulfillment_method"]');
    const addressRadios = document.querySelectorAll('input[name="shipping_address"]');
    const shippingLabelEl = document.querySelector('[data-checkout-label="shipping"]');
    const shippingValueEl = document.querySelector('[data-checkout-value="shipping"]');
    const totalValueEl = document.querySelector('[data-checkout-value="total"]');
    const shippingCard = document.getElementById('shippingAddressCard');
    const pickupCard = document.getElementById('pickupInfoCard');
    const placeOrderButton = document.getElementById('placeOrderButton');
    const addressNotice = document.getElementById('addressRequirementNotice');
    const shippingDistanceEl = document.querySelector('[data-shipping-distance]');
    const shippingAlertEl = document.querySelector('[data-shipping-alert]');
    const recipientToggle = document.getElementById('alternateRecipientToggle');
    const recipientFields = document.querySelector('[data-recipient-fields]');

    const requiresAddress = summary.dataset.requiresAddress === '1';
    const discountedSubtotal = parseFloat(summary.dataset.discountedSubtotal) || 0;
    const pickupFee = parseFloat(summary.dataset.pickupFee) || 0;
    let deliveryFee = parseFloat(summary.dataset.deliveryFee) || 0;
    let deliveryReady = summary.dataset.deliveryReady === '1';
    let deliveryState = summary.dataset.deliveryStatus || (deliveryReady ? 'ready' : 'pending');
    let selectedAddressId = summary.dataset.selectedAddress ? parseInt(summary.dataset.selectedAddress, 10) : null;
    const quoteEndpoint = summary.dataset.quoteEndpoint || '';
    let abortController = null;

    const currencyFormatter = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });

    const getFulfillmentMethod = () => {
        const selected = document.querySelector('input[name="fulfillment_method"]:checked');
        return selected ? selected.value : 'delivery';
    };

    const describeDeliveryState = () => {
        if (deliveryState === 'pending') {
            return ' (pending)';
        }
        if (deliveryState === 'failed') {
            return ' (unavailable)';
        }
        return '';
    };

    const setShippingLabel = (method) => {
        if (!shippingLabelEl) {
            return;
        }
        if (method === 'pickup') {
            shippingLabelEl.textContent = 'Pickup:';
            return;
        }
        shippingLabelEl.textContent = 'Shipping' + describeDeliveryState() + ':';
    };

    const setShippingValue = (method) => {
        if (!shippingValueEl) {
            return;
        }
        if (method === 'pickup') {
            shippingValueEl.textContent = currencyFormatter.format(pickupFee);
            return;
        }
        if (deliveryReady) {
            shippingValueEl.textContent = currencyFormatter.format(deliveryFee);
            return;
        }
        shippingValueEl.textContent = deliveryState === 'failed' ? 'Unavailable' : 'Calculating...';
    };

    const setTotalValue = (method) => {
        if (!totalValueEl) {
            return;
        }
        let fee = pickupFee;
        if (method === 'delivery') {
            fee = deliveryReady ? deliveryFee : 0;
        }
        totalValueEl.textContent = currencyFormatter.format(discountedSubtotal + fee);
    };

    const setDistanceMeta = (method, distanceText = '', durationText = '') => {
        if (!shippingDistanceEl) {
            return;
        }

        if (method === 'pickup') {
            shippingDistanceEl.textContent = 'Pickup selected. Shipping not required.';
            return;
        }

        if (deliveryReady && distanceText) {
            shippingDistanceEl.textContent = durationText ? `${distanceText} • ${durationText}` : distanceText;
        } else {
            shippingDistanceEl.textContent = '';
        }
    };

    const setAlert = (visible, message = '') => {
        if (!shippingAlertEl) {
            return;
        }
        shippingAlertEl.classList.toggle('d-none', !visible);
        shippingAlertEl.textContent = message;
    };

    const updateNotice = (method) => {
        if (!addressNotice) {
            return;
        }
        const showNotice = method === 'delivery' && (requiresAddress || !selectedAddressId || !deliveryReady);
        addressNotice.classList.toggle('d-none', !showNotice);
    };

    const updateButtonState = (method) => {
        if (!placeOrderButton) {
            return;
        }
        if (method === 'pickup') {
            placeOrderButton.disabled = false;
            return;
        }
        const disable = !selectedAddressId || !deliveryReady;
        placeOrderButton.disabled = disable;
    };

    const applyQuoteToUi = (quote) => {
        deliveryReady = !!quote.success;
        deliveryState = deliveryReady ? 'ready' : 'failed';
        summary.dataset.deliveryStatus = deliveryState;

        if (deliveryReady) {
            deliveryFee = parseFloat(quote.fee) || 0;
        }

        summary.dataset.deliveryFee = deliveryFee.toFixed(2);
        summary.dataset.deliveryReady = deliveryReady ? '1' : '0';
        summary.dataset.distanceText = quote.distance_text || '';
        summary.dataset.durationText = quote.duration_text || '';
        summary.dataset.shippingMessage = (quote.messages && quote.messages[0]) || '';

        const method = getFulfillmentMethod();
        setShippingLabel(method);
        setShippingValue(method);
        setTotalValue(method);
        setDistanceMeta(method, summary.dataset.distanceText, summary.dataset.durationText);

        if (method === 'delivery') {
            if (deliveryReady) {
                setAlert(false);
            } else {
                const fallbackMessage = deliveryState === 'failed'
                    ? 'Shipping quote unavailable. Update your address or try again.'
                    : 'Shipping quote pending. Select an address to continue.';
                const message = summary.dataset.shippingMessage || fallbackMessage;
                setAlert(true, message);
            }
        } else {
            setAlert(false);
        }

        updateButtonState(method);
        updateNotice(method);
    };

    const fetchQuote = (addressId) => {
        if (!addressId || !quoteEndpoint) {
            applyQuoteToUi({ success: false, messages: ['Select a delivery address to calculate shipping.'] });
            return;
        }

        if (abortController) {
            abortController.abort();
        }
        abortController = new AbortController();

        deliveryReady = false;
        deliveryState = 'pending';
        summary.dataset.deliveryStatus = 'pending';
        setShippingLabel('delivery');
        setShippingValue('delivery');
        setTotalValue('delivery');
        setDistanceMeta('delivery');
        setAlert(true, 'Calculating shipping fee...');
        updateButtonState('delivery');
        updateNotice('delivery');

        fetch(quoteEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ address_id: addressId, fulfillment_method: 'delivery' }),
            signal: abortController.signal
        })
            .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
            .then(({ ok, data }) => {
                abortController = null;
                if (!ok) {
                    applyQuoteToUi(data || { success: false, messages: ['Unable to calculate shipping.'] });
                    return;
                }
                applyQuoteToUi(data);
            })
            .catch((error) => {
                if (error.name === 'AbortError') {
                    return;
                }
                abortController = null;
                applyQuoteToUi({ success: false, messages: ['Network error while calculating shipping.'] });
            });
    };

    const handleFulfillmentChange = () => {
        const method = getFulfillmentMethod();

        if (shippingCard) {
            shippingCard.classList.toggle('d-none', method !== 'delivery');
        }
        if (pickupCard) {
            pickupCard.classList.toggle('d-none', method !== 'pickup');
        }

        setShippingLabel(method);
        setShippingValue(method);
        setTotalValue(method);
        setDistanceMeta(method, summary.dataset.distanceText, summary.dataset.durationText);

        if (method === 'delivery') {
            updateNotice(method);
            updateButtonState(method);
            if (!deliveryReady && selectedAddressId) {
                fetchQuote(selectedAddressId);
            } else {
                const fallbackMessage = deliveryState === 'failed'
                    ? 'Shipping quote unavailable. Update your address or try again.'
                    : 'Shipping quote pending. Select an address to continue.';
                const message = summary.dataset.shippingMessage || fallbackMessage;
                setAlert(!deliveryReady, message);
            }
        } else {
            setAlert(false);
            updateNotice(method);
            updateButtonState(method);
        }
    };

    fulfillmentRadios.forEach((radio) => {
        radio.addEventListener('change', handleFulfillmentChange);
    });

    addressRadios.forEach((radio) => {
        radio.addEventListener('change', (event) => {
            selectedAddressId = parseInt(event.target.value, 10);
            summary.dataset.selectedAddress = event.target.value;
            if (getFulfillmentMethod() === 'delivery') {
                fetchQuote(selectedAddressId);
            }
        });
    });

    if (recipientToggle && recipientFields) {
        recipientToggle.addEventListener('change', () => {
            recipientFields.classList.toggle('d-none', !recipientToggle.checked);
        });
    }

    handleFulfillmentChange();
});
</script>

<?php include 'includes/footer.php'; ?>
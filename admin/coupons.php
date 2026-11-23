<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$errors = [];
$success = '';
$editingCoupon = null;
$rewardErrors = [];
$rewardSuccess = '';
$editingRewardTier = null;
$shippingErrors = [];
$shippingSuccess = '';

ensureShippingSettingsTable();
ensureRewardsSystem();

$shippingSettings = getShippingSettings();
$shippingLastUpdatedLabel = !empty($shippingSettings['updated_at'])
    ? date('M d, Y g:i A', strtotime($shippingSettings['updated_at']))
    : 'Not updated yet';
$shippingUpdatedByLabel = '';
if (!empty($shippingSettings['updated_by'])) {
    $updatedByUser = getUserById((int) $shippingSettings['updated_by']);
    if ($updatedByUser) {
        $nameParts = [];
        if (!empty($updatedByUser['first_name'])) {
            $nameParts[] = $updatedByUser['first_name'];
        }
        if (!empty($updatedByUser['last_name'])) {
            $nameParts[] = $updatedByUser['last_name'];
        }
        if (empty($nameParts) && !empty($updatedByUser['name'])) {
            $nameParts[] = $updatedByUser['name'];
        }
        if (empty($nameParts) && !empty($updatedByUser['email'])) {
            $nameParts[] = $updatedByUser['email'];
        }
        if (!empty($nameParts)) {
            $shippingUpdatedByLabel = implode(' ', $nameParts);
        } else {
            $shippingUpdatedByLabel = 'User #' . (int) $updatedByUser['id'];
        }
    } else {
        $shippingUpdatedByLabel = 'User #' . (int) $shippingSettings['updated_by'];
    }
}

$shippingErrors = $_SESSION['shipping_errors'] ?? [];
$shippingSuccess = $_SESSION['shipping_success'] ?? '';
$shippingSettingsOverride = $_SESSION['shipping_settings_input'] ?? null;
unset($_SESSION['shipping_errors'], $_SESSION['shipping_success'], $_SESSION['shipping_settings_input']);

$shippingFormValues = $shippingSettings;
if ($shippingSettingsOverride && is_array($shippingSettingsOverride)) {
    $shippingFormValues = array_merge($shippingFormValues, $shippingSettingsOverride);
}

if (isset($_GET['edit'])) {
    $editingCoupon = getCouponById((int) $_GET['edit']);
    if (!$editingCoupon) {
        $_SESSION['error'] = 'Coupon not found.';
        redirect('coupons.php');
    }
}

if (isset($_GET['delete'])) {
    $coupon = getCouponById((int) $_GET['delete']);
    if ($coupon) {
        if (deleteCoupon($coupon['id'])) {
            $_SESSION['success'] = 'Coupon deleted successfully.';
        } else {
            $_SESSION['error'] = 'Failed to delete coupon.';
        }
    }
    redirect('coupons.php');
}

if (isset($_GET['edit_reward'])) {
    $editingRewardTier = getRewardTierById((int) $_GET['edit_reward']);
    if (!$editingRewardTier) {
        $_SESSION['error'] = 'Reward tier not found.';
        redirect('coupons.php#reward-tiers');
    }
}

if (isset($_GET['delete_reward'])) {
    $tierId = (int) $_GET['delete_reward'];
    if ($tierId > 0) {
        if (deleteRewardTier($tierId)) {
            $_SESSION['reward_success'] = 'Reward tier deleted successfully.';
        } else {
            $_SESSION['reward_errors'] = ['Failed to delete reward tier.'];
        }
    }
    redirect('coupons.php#reward-tiers');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_shipping_settings'])) {
    $shippingInput = [
        'zone_roxas_fee' => max(0, (float) ($_POST['zone_roxas_fee'] ?? 0)),
        'zone_oriental_fee' => max(0, (float) ($_POST['zone_oriental_fee'] ?? 0)),
        'zone_out_province_fee' => max(0, (float) ($_POST['zone_out_province_fee'] ?? 0)),
        'google_maps_api_key' => trim((string) ($_POST['google_maps_api_key'] ?? ''))
    ];

    $shippingErrors = [];
    if ($shippingInput['zone_roxas_fee'] < 0 || $shippingInput['zone_oriental_fee'] < 0 || $shippingInput['zone_out_province_fee'] < 0) {
        $shippingErrors[] = 'Shipping fees cannot be negative.';
    }

    if (empty($shippingErrors) && $shippingInput['zone_roxas_fee'] === 0 && $shippingInput['zone_oriental_fee'] === 0 && $shippingInput['zone_out_province_fee'] === 0) {
        $shippingErrors[] = 'Set at least one delivery zone fee above zero to enable deliveries.';
    }

    if (empty($shippingErrors)) {
        if (updateShippingSettings($shippingInput, $_SESSION['user_id'] ?? null)) {
            $_SESSION['shipping_success'] = 'Shipping fee settings updated.';
        } else {
            $shippingErrors[] = 'Failed to update shipping settings. Please try again.';
        }
    }

    if (!empty($shippingErrors)) {
        $_SESSION['shipping_errors'] = $shippingErrors;
        $_SESSION['shipping_settings_input'] = $shippingInput;
    } else {
        unset($_SESSION['shipping_settings_input']);
    }

    redirect('coupons.php#shipping-settings');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_coupon'])) {
    $couponId = isset($_POST['coupon_id']) && $_POST['coupon_id'] !== '' ? (int) $_POST['coupon_id'] : null;

    $code = strtoupper(trim($_POST['code'] ?? ''));
    $discountType = $_POST['discount_type'] ?? 'percentage';
    $discountValue = (float) ($_POST['discount_value'] ?? 0);
    $minOrder = (float) ($_POST['min_order_amount'] ?? 0);
    $maxDiscount = (float) ($_POST['max_discount_amount'] ?? 0);
    $usageLimit = (int) ($_POST['usage_limit'] ?? 0);
    $validFrom = $_POST['valid_from'] ?: null;
    $validUntil = $_POST['valid_until'] ?: null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($code === '') {
        $errors[] = 'Coupon code is required.';
    }

    if (!in_array($discountType, ['percentage', 'fixed'], true)) {
        $errors[] = 'Invalid discount type.';
    }

    if ($discountValue <= 0) {
        $errors[] = 'Discount value must be greater than zero.';
    }

    if ($validFrom && $validUntil && strtotime($validFrom) > strtotime($validUntil)) {
        $errors[] = 'Valid until date must be after valid from date.';
    }

    if (couponCodeExists($code, $couponId)) {
        $errors[] = 'Coupon code already exists.';
    }

    if (empty($errors)) {
        $saved = saveCoupon([
            'code' => $code,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'min_order_amount' => $minOrder,
            'max_discount_amount' => $maxDiscount,
            'usage_limit' => $usageLimit,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'is_active' => $isActive
        ], $couponId);

        if ($saved) {
            $_SESSION['success'] = $couponId ? 'Coupon updated successfully.' : 'Coupon created successfully.';
            redirect('coupons.php');
        } else {
            $errors[] = 'Failed to save coupon. Please try again.';
        }
    }

    $editingCoupon = [
        'id' => $couponId,
        'code' => $code,
        'discount_type' => $discountType,
        'discount_value' => $discountValue,
        'min_order_amount' => $minOrder,
        'max_discount_amount' => $maxDiscount,
        'usage_limit' => $usageLimit,
        'valid_from' => $validFrom,
        'valid_until' => $validUntil,
        'is_active' => $isActive
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_reward_tier'])) {
    $rewardTierId = isset($_POST['reward_tier_id']) && $_POST['reward_tier_id'] !== '' ? (int) $_POST['reward_tier_id'] : null;
    $requiredOrders = max(1, (int) ($_POST['required_orders'] ?? 0));
    $discountPercent = max(0, min(100, (float) ($_POST['discount_percent'] ?? 0)));
    $pointsCost = max(0, (int) ($_POST['points_cost'] ?? 0));
    $isActiveReward = isset($_POST['reward_is_active']) ? 1 : 0;

    if ($requiredOrders <= 0) {
        $rewardErrors[] = 'Required orders must be at least 1.';
    }

    if ($discountPercent <= 0) {
        $rewardErrors[] = 'Discount percent must be greater than zero.';
    }

    if (rewardTierMinOrdersExists($requiredOrders, $rewardTierId)) {
        $rewardErrors[] = 'A reward tier for this required order count already exists.';
    }

    if (empty($rewardErrors)) {
        $saved = saveRewardTier([
            'required_orders' => $requiredOrders,
            'discount_percent' => $discountPercent,
            'points_cost' => $pointsCost,
            'is_active' => $isActiveReward
        ], $rewardTierId);

        if ($saved) {
            $_SESSION['reward_success'] = $rewardTierId ? 'Reward tier updated successfully.' : 'Reward tier created successfully.';
            redirect('coupons.php#reward-tiers');
        }

        $rewardErrors[] = 'Failed to save reward tier. Please try again.';
    }

    $editingRewardTier = [
        'id' => $rewardTierId,
        'required_orders' => $requiredOrders,
        'discount_percent' => $discountPercent,
        'points_cost' => $pointsCost,
        'is_active' => $isActiveReward
    ];
}

$rewardErrors = array_merge($rewardErrors, $_SESSION['reward_errors'] ?? []);
$rewardSuccess = $_SESSION['reward_success'] ?? '';
unset($_SESSION['reward_errors'], $_SESSION['reward_success']);

$coupons = getCoupons();
$rewardTiers = getRewardTiers(false);
$pointsPerOrder = getRewardsPointsPerOrder();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mt-4 mb-3">
    <h2 class="mb-0">Coupons and Rewards</h2>
    <a href="coupons.php" class="btn btn-outline-secondary btn-sm">Clear form</a>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Existing Coupons</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Min Order</th>
                            <th>Usage</th>
                            <th>Valid Range</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($coupons)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No coupons yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($coupons as $coupon): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($coupon['code']); ?></td>
                                    <td><?php echo ucfirst($coupon['discount_type']); ?></td>
                                    <td>
                                        <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                            <?php echo number_format($coupon['discount_value'], 2); ?>%
                                        <?php else: ?>
                                            ₱<?php echo number_format($coupon['discount_value'], 2); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>₱<?php echo number_format($coupon['min_order_amount'], 2); ?></td>
                                    <td>
                                        <?php echo (int) $coupon['used_count']; ?>
                                        <?php if (!empty($coupon['usage_limit'])): ?>
                                            / <?php echo (int) $coupon['usage_limit']; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $from = $coupon['valid_from'] ? date('M d, Y', strtotime($coupon['valid_from'])) : 'Any';
                                        $until = $coupon['valid_until'] ? date('M d, Y', strtotime($coupon['valid_until'])) : 'Any';
                                        echo $from . ' - ' . $until;
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $coupon['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $coupon['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="coupons.php?edit=<?php echo $coupon['id']; ?>"
                                            class="btn btn-sm btn-outline-primary">Edit</a>
                                        <a href="coupons.php?delete=<?php echo $coupon['id']; ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            data-confirm="Delete this coupon?"
                                            data-confirm-variant="danger"
                                            data-confirm-accept="Delete Coupon">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4" id="shipping-settings">
            <div class="card-header bg-info text-white d-flex flex-column">
                <h5 class="mb-1">Shipping Fee Settings</h5>
                <small class="text-white-50">Automatically applies during checkout distance calculations.</small>
            </div>
            <div class="card-body">
                <?php if (!empty($shippingErrors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($shippingErrors as $shippingError): ?>
                            <div><?php echo htmlspecialchars($shippingError); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($shippingSuccess): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($shippingSuccess); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="row g-3">
                    <input type="hidden" name="update_shipping_settings" value="1">
                    <div class="col-12">
                        <p class="text-muted small mb-2">
                            Rates are matched automatically based on the words in the customer's saved address. Keep the spelling of
                            <strong>Roxas</strong> and <strong>Oriental Mindoro</strong> consistent in your guidance.
                        </p>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Roxas + Oriental Mindoro (city)</label>
                        <input type="number" min="0" step="0.01" name="zone_roxas_fee" class="form-control"
                            value="<?php echo htmlspecialchars(number_format((float) ($shippingFormValues['zone_roxas_fee'] ?? 0), 2, '.', '')); ?>">
                        <small class="text-muted">Applied when the address contains both “Roxas” and “Oriental Mindoro”.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Oriental Mindoro (outside Roxas)</label>
                        <input type="number" min="0" step="0.01" name="zone_oriental_fee" class="form-control"
                            value="<?php echo htmlspecialchars(number_format((float) ($shippingFormValues['zone_oriental_fee'] ?? 0), 2, '.', '')); ?>">
                        <small class="text-muted">Used when “Oriental Mindoro” is present but “Roxas” is not.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Outside Oriental Mindoro</label>
                        <input type="number" min="0" step="0.01" name="zone_out_province_fee" class="form-control"
                            value="<?php echo htmlspecialchars(number_format((float) ($shippingFormValues['zone_out_province_fee'] ?? 0), 2, '.', '')); ?>">
                        <small class="text-muted">Fallback rate when the address doesn't mention “Oriental Mindoro”.</small>
                    </div>

                    <div class="col-12">
                        <ul class="small text-muted ps-3 mb-0">
                            <li>Customers with Roxas addresses should include “Roxas, Oriental Mindoro” for the local fee.</li>
                            <li>Addresses that mention Oriental Mindoro but not Roxas are charged with the provincial rate.</li>
                            <li>Any address without “Oriental Mindoro” is treated as out-of-province.</li>
                        </ul>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Google Maps API Key (optional)</label>
                        <input type="text" name="google_maps_api_key" class="form-control"
                            placeholder="Stored for address geocoding"
                            value="<?php echo htmlspecialchars($shippingFormValues['google_maps_api_key'] ?? ''); ?>">
                        <small class="text-muted">Used only for background geocoding when customers save new addresses.</small>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-info text-white w-100">Save Shipping Fees</button>
                    </div>
                </form>

                <hr>

                <div class="small text-muted">
                    <div><strong>Reminder:</strong> Coach customers to include their city plus “Oriental Mindoro” so the correct fee triggers.</div>
                    <div><strong>Last updated:</strong> <?php echo htmlspecialchars($shippingLastUpdatedLabel); ?>
                        <?php if ($shippingUpdatedByLabel): ?>
                            by <?php echo htmlspecialchars($shippingUpdatedByLabel); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4" id="reward-tiers">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Reward Milestones</h5>
                    <small>Customers unlock tiers by total non-cancelled orders and redeem points for percent-off discounts.</small>
                </div>
                <span class="badge bg-dark">+<?php echo (int) $pointsPerOrder; ?> pts / order</span>
            </div>
            <div class="card-body">
                <?php if (!empty($rewardErrors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($rewardErrors as $rewardError): ?>
                            <div><?php echo htmlspecialchars($rewardError); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($rewardSuccess): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($rewardSuccess); ?></div>
                <?php endif; ?>

                <?php if (empty($rewardTiers)): ?>
                    <div class="text-muted">No reward tiers configured yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Required Orders</th>
                                    <th>Discount</th>
                                    <th>Points Cost</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rewardTiers as $tier): ?>
                                    <tr>
                                        <td><?php echo (int) $tier['required_orders']; ?> orders</td>
                                        <td><?php echo number_format((float) $tier['discount_percent'], 2); ?>%</td>
                                        <td><?php echo (int) $tier['points_cost']; ?> pts</td>
                                        <td>
                                            <span class="badge <?php echo !empty($tier['is_active']) ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo !empty($tier['is_active']) ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="coupons.php?edit_reward=<?php echo (int) $tier['id']; ?>#reward-tiers"
                                                class="btn btn-sm btn-outline-primary">Edit</a>
                                            <a href="coupons.php?delete_reward=<?php echo (int) $tier['id']; ?>#reward-tiers"
                                                class="btn btn-sm btn-outline-danger"
                                                data-confirm="Delete this reward tier?"
                                                data-confirm-variant="danger"
                                                data-confirm-accept="Delete Tier">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><?php echo $editingCoupon ? 'Edit Coupon' : 'Create Coupon'; ?></h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success'];
                        unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php echo $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="coupon_id" value="<?php echo $editingCoupon['id'] ?? ''; ?>">
                    <div class="mb-3">
                        <label class="form-label">Coupon Code</label>
                        <input type="text" name="code" class="form-control" required maxlength="50"
                            value="<?php echo htmlspecialchars($editingCoupon['code'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Discount Type</label>
                        <select name="discount_type" class="form-select">
                            <option value="percentage" <?php echo (($editingCoupon['discount_type'] ?? '') === 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                            <option value="fixed" <?php echo (($editingCoupon['discount_type'] ?? '') === 'fixed') ? 'selected' : ''; ?>>Fixed Amount</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Discount Value</label>
                        <input type="number" step="0.01" min="0" name="discount_value" class="form-control" required
                            value="<?php echo htmlspecialchars($editingCoupon['discount_value'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Minimum Order Amount</label>
                        <input type="number" step="0.01" min="0" name="min_order_amount" class="form-control"
                            value="<?php echo htmlspecialchars($editingCoupon['min_order_amount'] ?? '0'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Maximum Discount Amount</label>
                        <input type="number" step="0.01" min="0" name="max_discount_amount" class="form-control"
                            value="<?php echo htmlspecialchars($editingCoupon['max_discount_amount'] ?? '0'); ?>">
                        <small class="text-muted">Leave zero to disable the cap.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Usage Limit</label>
                        <input type="number" min="0" name="usage_limit" class="form-control"
                            value="<?php echo htmlspecialchars($editingCoupon['usage_limit'] ?? '0'); ?>">
                        <small class="text-muted">0 means unlimited usage.</small>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Valid From</label>
                            <input type="date" name="valid_from" class="form-control"
                                value="<?php echo isset($editingCoupon['valid_from']) && $editingCoupon['valid_from'] ? date('Y-m-d', strtotime($editingCoupon['valid_from'])) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valid Until</label>
                            <input type="date" name="valid_until" class="form-control"
                                value="<?php echo isset($editingCoupon['valid_until']) && $editingCoupon['valid_until'] ? date('Y-m-d', strtotime($editingCoupon['valid_until'])) : ''; ?>">
                        </div>
                    </div>
                    <div class="form-check form-switch my-3">
                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="is_active"
                            <?php echo !isset($editingCoupon['is_active']) || $editingCoupon['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">Coupon is active</label>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="submit" name="save_coupon" class="btn btn-success">Save Coupon</button>
                        <?php if ($editingCoupon): ?>
                            <a href="coupons.php" class="btn btn-outline-secondary">Cancel Edit</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><?php echo $editingRewardTier ? 'Edit Reward Tier' : 'Create Reward Tier'; ?></h5>
            </div>
            <div class="card-body">
                <p class="small text-muted">
                    Example: 5 orders = 5% off for 30 points, 10 orders = 10% off for 60 points.
                </p>
                <form method="POST">
                    <input type="hidden" name="reward_tier_id" value="<?php echo htmlspecialchars($editingRewardTier['id'] ?? ''); ?>">
                    <div class="mb-3">
                        <label class="form-label">Required Orders</label>
                        <input type="number" min="1" name="required_orders" class="form-control" required
                            value="<?php echo htmlspecialchars((string) ($editingRewardTier['required_orders'] ?? '')); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Discount Percent (%)</label>
                        <input type="number" min="0.01" max="100" step="0.01" name="discount_percent" class="form-control" required
                            value="<?php echo htmlspecialchars((string) ($editingRewardTier['discount_percent'] ?? '')); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Points Cost</label>
                        <input type="number" min="0" step="1" name="points_cost" class="form-control" required
                            value="<?php echo htmlspecialchars((string) ($editingRewardTier['points_cost'] ?? '0')); ?>">
                        <small class="text-muted">Points deducted once the user redeems this tier during checkout.</small>
                    </div>
                    <div class="form-check form-switch my-3">
                        <input class="form-check-input" type="checkbox" role="switch" name="reward_is_active" id="reward_is_active"
                            <?php echo !isset($editingRewardTier['is_active']) || !empty($editingRewardTier['is_active']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="reward_is_active">Reward tier is active</label>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="submit" name="save_reward_tier" class="btn btn-warning text-dark">Save Reward Tier</button>
                        <?php if ($editingRewardTier): ?>
                            <a href="coupons.php#reward-tiers" class="btn btn-outline-secondary">Cancel Edit</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
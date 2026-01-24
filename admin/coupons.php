<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$errors = [];
$success = '';
$editingCoupon = null;

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

$coupons = getCoupons();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mt-4 mb-3">
    <h2 class="mb-0">Coupons</h2>
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
                                            onclick="return confirm('Delete this coupon?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
    </div>
</div>

<?php include 'includes/footer.php'; ?>
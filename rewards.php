<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

ensureRewardsSystem();

$userId = (int) ($_SESSION['user_id'] ?? 0);
$rewardPoints = getUserRewardPoints($userId);
$qualifyingOrders = getUserQualifyingOrderCount($userId);
$pointsPerOrder = getRewardsPointsPerOrder();
$rewardTiers = getRewardTiers(true);
$currentTier = getCurrentRewardTierForOrders($qualifyingOrders);
$nextTier = getNextRewardTierForOrders($qualifyingOrders);
$pointHistory = getUserRewardPointHistory($userId, 15);

$ordersProgressPercent = 100;
$ordersRemaining = 0;
if ($nextTier) {
    $requiredOrders = max(1, (int) $nextTier['required_orders']);
    $ordersProgressPercent = min(100, ($qualifyingOrders / $requiredOrders) * 100);
    $ordersRemaining = max(0, $requiredOrders - $qualifyingOrders);
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h2 class="mb-0">My Rewards</h2>
        <a href="checkout.php" class="btn btn-outline-success">Redeem at Checkout</a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Reward Points</div>
                    <div class="display-6 fw-bold text-success"><?php echo (int) $rewardPoints; ?></div>
                    <div class="small text-muted mt-2">You earn <?php echo (int) $pointsPerOrder; ?> points for every non-cancelled order.</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Qualified Orders</div>
                    <div class="display-6 fw-bold"><?php echo (int) $qualifyingOrders; ?></div>
                    <div class="small text-muted mt-2">Used to unlock order-based reward milestones.</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Current Tier</div>
                    <?php if ($currentTier): ?>
                        <div class="display-6 fw-bold text-warning"><?php echo number_format((float) $currentTier['discount_percent'], 2); ?>%</div>
                        <div class="small text-muted mt-2">Unlocked at <?php echo (int) $currentTier['required_orders']; ?> orders.</div>
                    <?php else: ?>
                        <div class="h4 fw-bold mb-0">No tier yet</div>
                        <div class="small text-muted mt-2">Place more orders to unlock your first tier.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="mb-2">Progress to Next Tier</h5>
            <?php if ($nextTier): ?>
                <div class="d-flex justify-content-between small text-muted mb-2">
                    <span><?php echo (int) $qualifyingOrders; ?> / <?php echo (int) $nextTier['required_orders']; ?> orders</span>
                    <span><?php echo number_format((float) $nextTier['discount_percent'], 2); ?>% discount tier</span>
                </div>
                <div class="progress" style="height: 14px;">
                    <div class="progress-bar bg-success" role="progressbar"
                        style="width: <?php echo number_format((float) $ordersProgressPercent, 2, '.', ''); ?>%;"
                        aria-valuenow="<?php echo (int) round($ordersProgressPercent); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="small text-muted mt-2">
                    <?php echo (int) $ordersRemaining; ?> more order<?php echo $ordersRemaining === 1 ? '' : 's'; ?> to unlock this tier.
                </div>
            <?php else: ?>
                <div class="alert alert-success mb-0">You have unlocked the highest active reward tier. Great job!</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Reward Tiers</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($rewardTiers)): ?>
                        <div class="p-3 text-muted">No reward tiers are available yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Tier Unlock</th>
                                        <th>Discount</th>
                                        <th>Points Cost</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rewardTiers as $tier): ?>
                                        <?php
                                        $unlockOrders = (int) $tier['required_orders'];
                                        $isUnlocked = $qualifyingOrders >= $unlockOrders;
                                        $hasPoints = $rewardPoints >= (int) $tier['points_cost'];
                                        ?>
                                        <tr>
                                            <td><?php echo $unlockOrders; ?> orders</td>
                                            <td><?php echo number_format((float) $tier['discount_percent'], 2); ?>%</td>
                                            <td><?php echo (int) $tier['points_cost']; ?> pts</td>
                                            <td>
                                                <?php if ($isUnlocked && $hasPoints): ?>
                                                    <span class="badge bg-success">Ready to Redeem</span>
                                                <?php elseif ($isUnlocked): ?>
                                                    <span class="badge bg-secondary">Need More Points</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark">Locked</span>
                                                <?php endif; ?>
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
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Recent Points Activity</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($pointHistory)): ?>
                        <div class="p-3 text-muted">No points activity yet.</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($pointHistory as $entry): ?>
                                <?php
                                $change = (int) ($entry['points_change'] ?? 0);
                                $isPositive = $change >= 0;
                                $label = 'Points update';
                                if (($entry['reason'] ?? '') === 'order_earned') {
                                    $label = 'Order reward earned';
                                } elseif (($entry['reason'] ?? '') === 'order_redeem') {
                                    $label = 'Reward redeemed';
                                }
                                ?>
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($label); ?></div>
                                            <div class="small text-muted">
                                                <?php echo date('M d, Y h:i A', strtotime($entry['created_at'])); ?>
                                                <?php if (!empty($entry['order_id'])): ?>
                                                    • Order #<?php echo (int) $entry['order_id']; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-semibold <?php echo $isPositive ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $isPositive ? '+' : ''; ?><?php echo $change; ?> pts
                                            </div>
                                            <div class="small text-muted">Bal: <?php echo (int) ($entry['balance_after'] ?? 0); ?></div>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

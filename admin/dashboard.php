<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Get statistics
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$total_revenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'delivered'")->fetchColumn();

// Recent orders
$recent_orders = $pdo->query("SELECT o.*, u.username FROM orders o 
                             JOIN users u ON o.user_id = u.id 
                             ORDER BY o.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Low stock products
$low_stock_products = $pdo->query("SELECT * FROM products WHERE stock_quantity < 10 ORDER BY stock_quantity ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Monthly revenue
$monthly_revenue = $pdo->query("SELECT 
    MONTH(created_at) as month,
    SUM(total_amount) as revenue
    FROM orders 
    WHERE status = 'delivered' AND YEAR(created_at) = YEAR(CURDATE())
    GROUP BY MONTH(created_at)
    ORDER BY month")->fetchAll(PDO::FETCH_ASSOC);

$recent_feedback = getRecentFeedbackForAdmin(3);

// Sales analytics datasets
$dailyRevenueSeries = getDailyRevenueSeries(7);
$monthlyRevenueSeries = getMonthlyRevenueSeries(date('Y'));
$yearlyRevenueSeries = getYearlyRevenueSeries(5);
$topProductSeries = getTopSellingProductsSeries(5);

$seriesChangePercent = function (array $values): float {
    $count = count($values);
    if ($count < 2) {
        return 0.0;
    }

    $first = (float) $values[0];
    $last = (float) $values[$count - 1];
    if ($first == 0.0) {
        return $last > 0 ? 100.0 : 0.0;
    }

    return (($last - $first) / abs($first)) * 100;
};

$metricCards = [
    [
        'label' => 'Total Products',
        'value' => number_format((float) $total_products),
        'icon' => 'fas fa-boxes',
        'cardClass' => 'metric-card-products',
        'trendClass' => 'text-danger',
        'trendValue' => $seriesChangePercent($topProductSeries['values'] ?? []),
        'sparkline' => $topProductSeries,
        'sparklineColor' => '#3b82f6',
        'subtitle' => 'Catalog size'
    ],
    [
        'label' => 'Total Orders',
        'value' => number_format((float) $total_orders),
        'icon' => 'fas fa-shopping-cart',
        'cardClass' => 'metric-card-orders',
        'trendClass' => 'text-success',
        'trendValue' => $seriesChangePercent($dailyRevenueSeries['values'] ?? []),
        'sparkline' => $dailyRevenueSeries,
        'sparklineColor' => '#22c55e',
        'subtitle' => 'Recent activity'
    ],
    [
        'label' => 'Total Customers',
        'value' => number_format((float) $total_customers),
        'icon' => 'fas fa-users',
        'cardClass' => 'metric-card-customers',
        'trendClass' => 'text-danger',
        'trendValue' => $seriesChangePercent($monthlyRevenueSeries['values'] ?? []),
        'sparkline' => $monthlyRevenueSeries,
        'sparklineColor' => '#ef4444',
        'subtitle' => 'This year'
    ],
    [
        'label' => 'Total Revenue',
        'value' => '$' . number_format((float) $total_revenue, 2),
        'icon' => 'fas fa-dollar-sign',
        'cardClass' => 'metric-card-revenue',
        'trendClass' => 'text-success',
        'trendValue' => $seriesChangePercent($yearlyRevenueSeries['values'] ?? []),
        'sparkline' => $yearlyRevenueSeries,
        'sparklineColor' => '#22c55e',
        'subtitle' => 'Delivered only'
    ]
];

$revenueOverview = [
    'labels' => $monthlyRevenueSeries['labels'] ?? [],
    'values' => $monthlyRevenueSeries['values'] ?? []
];

$salesMix = [
    'labels' => $topProductSeries['labels'] ?? [],
    'values' => $topProductSeries['values'] ?? []
];

$salesMixTotal = array_sum($salesMix['values']);
$salesMixSummary = [];
$salesMixColors = ['#2563eb', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6'];
foreach ($salesMix['labels'] as $index => $label) {
    $value = (float) ($salesMix['values'][$index] ?? 0);
    $salesMixSummary[] = [
        'label' => $label,
        'value' => $value,
        'percentage' => $salesMixTotal > 0 ? ($value / $salesMixTotal) * 100 : 0,
        'color' => $salesMixColors[$index % count($salesMixColors)]
    ];
}

include 'includes/header.php';
?>

<style>
    .dashboard-page {
        max-width: 1680px;
        background:
            radial-gradient(circle at top left, rgba(59, 130, 246, 0.05), transparent 24%),
            radial-gradient(circle at top right, rgba(34, 197, 94, 0.05), transparent 22%),
            #f6f8fc;
    }

    .dashboard-page-header {
        margin-bottom: 1.5rem;
    }

    .dashboard-shell {
        border: 0;
        border-radius: 24px;
        box-shadow: 0 16px 38px rgba(15, 23, 42, 0.08);
        overflow: hidden;
        background: #fff;
    }

    .metric-card {
        border: 0;
        border-radius: 24px;
        padding: 1.05rem 1.1rem 0.95rem;
        box-shadow: 0 16px 34px rgba(15, 23, 42, 0.08);
        background: #ffffff;
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
        height: 100%;
        color: #0f172a;
        min-height: 170px;
        position: relative;
        overflow: hidden;
    }

    .metric-card::before {
        content: '';
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: rgba(15, 23, 42, 0.08);
    }

    .metric-card-products::before {
        background: linear-gradient(90deg, #60a5fa, #93c5fd);
    }

    .metric-card-orders::before {
        background: linear-gradient(90deg, #34d399, #86efac);
    }

    .metric-card-customers::before {
        background: linear-gradient(90deg, #fb7185, #fda4af);
    }

    .metric-card-revenue::before {
        background: linear-gradient(90deg, #f59e0b, #facc15);
    }

    .metric-card-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
    }

    .metric-kicker {
        font-size: 0.72rem;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 700;
    }

    .metric-value {
        font-size: 2.15rem;
        line-height: 1;
        font-weight: 800;
        color: #1e293b;
        margin-top: 0.2rem;
    }

    .metric-subtitle {
        color: #94a3b8;
        font-size: 0.8rem;
        margin-top: 0.25rem;
    }

    .metric-icon {
        width: 40px;
        height: 40px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        color: #fff;
        flex: 0 0 auto;
        box-shadow: 0 10px 18px rgba(15, 23, 42, 0.12);
    }

    .metric-card-products .metric-icon {
        background: linear-gradient(135deg, #2563eb, #60a5fa);
    }

    .metric-card-orders .metric-icon {
        background: linear-gradient(135deg, #059669, #34d399);
    }

    .metric-card-customers .metric-icon {
        background: linear-gradient(135deg, #ef4444, #fb7185);
    }

    .metric-card-revenue .metric-icon {
        background: linear-gradient(135deg, #d97706, #f59e0b);
    }

    .metric-sparkline {
        position: relative;
        height: 58px;
        margin-top: auto;
    }

    .metric-sparkline canvas {
        width: 100% !important;
        height: 100% !important;
    }

    .metric-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
        padding-top: 0.15rem;
    }

    .metric-trend {
        font-size: 0.9rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .chart-card,
    .panel-card {
        border: 0;
        border-radius: 24px;
        box-shadow: 0 16px 38px rgba(15, 23, 42, 0.08);
        overflow: hidden;
        background: #fff;
    }

    .chart-card .card-header,
    .panel-card .card-header {
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        background: #fff;
        font-weight: 600;
        padding: 1rem 1.15rem;
    }

    .chart-card .card-body,
    .panel-card .card-body {
        padding: 1.1rem 1.15rem 1.15rem;
    }

    .revenue-overview-card .card-header {
        padding-bottom: 0.7rem;
    }

    .revenue-overview-card .card-body {
        padding-top: 0.9rem;
    }

    .sales-mix-card .card-header {
        padding: 0.9rem 1.15rem 0.7rem;
    }

    .sales-mix-card .card-body {
        padding: 0.85rem 1.15rem 1.15rem;
    }

    .revenue-chart-wrap {
        height: 300px;
    }

    .mix-chart-wrap {
        height: 260px;
    }

    .chart-canvas,
    .sparkline-canvas {
        width: 100% !important;
        height: 100% !important;
    }

    .stat-card-link {
        display: block;
        text-decoration: none;
        border-radius: 20px;
    }

    .stat-card-link .metric-card {
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        cursor: pointer;
    }

    .stat-card-link:hover .metric-card,
    .stat-card-link:focus .metric-card {
        transform: translateY(-6px) scale(1.01);
        box-shadow: 0 25px 45px rgba(15, 23, 42, 0.18);
    }

    .stat-card-link:focus-visible {
        outline: none;
    }

    .stat-card-link:focus-visible .metric-card {
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.9), 0 25px 45px rgba(15, 23, 42, 0.2);
    }

    .dashboard-panel {
        border: 0;
        border-radius: 24px;
        box-shadow: 0 16px 38px rgba(15, 23, 42, 0.08);
        overflow: hidden;
        background: #fff;
    }

    .dashboard-panel .card-header {
        background: #fff;
        border-bottom: 1px solid rgba(15, 23, 42, 0.07);
        padding: 0.95rem 1.15rem;
    }

    .dashboard-panel .card-body {
        padding: 1.1rem 1.15rem 1.15rem;
    }

    .dashboard-panel.table-panel .card-body {
        padding: 0;
    }

    .dashboard-panel .table-responsive {
        margin-bottom: 0;
    }

    .dashboard-table thead th {
        background: #f8fafc;
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        font-size: 0.78rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #475569;
    }

    .dashboard-table tbody td {
        vertical-align: middle;
    }

    .dashboard-section-title {
        font-size: 1rem;
        margin-bottom: 0;
    }

    .section-shell {
        --bs-gutter-x: 0.8rem;
        --bs-gutter-y: 0.8rem;
    }

    #revenueChartPeriod .btn {
        border-color: #cbd5e1;
        color: #64748b;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    #revenueChartPeriod .btn:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        color: #475569;
    }

    #revenueChartPeriod .btn.active {
        background: #3b82f6;
        border-color: #3b82f6;
        color: #fff;
    }

    .summary-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        border-radius: 999px;
        background: #f8fafc;
        padding: 0.45rem 0.75rem;
        font-size: 0.78rem;
        color: #475569;
    }

    .sales-mix-summary {
        margin-top: 0.5rem;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.32rem;
    }

    .sales-mix-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.3rem;
        border-radius: 999px;
        background: #f8fafc;
        padding: 0.35rem 0.5rem;
        font-size: 0.7rem;
        color: #475569;
    }

    .sales-mix-item .mix-label {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        min-width: 0;
        flex: 1 1 auto;
        overflow: hidden;
    }

    .sales-mix-item .mix-label .text-truncate {
        font-size: 0.69rem;
        min-width: 0;
    }

    .sales-mix-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex: 0 0 auto;
    }

    .feedback-panel .list-group-item {
        padding: 0.75rem 0;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    }

    .feedback-panel .list-group-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .feedback-panel .list-group-item:first-child {
        padding-top: 0;
    }

    .feedback-panel .list-group-item strong {
        font-size: 0.9rem;
        display: block;
        margin-bottom: 0.2rem;
    }

    .feedback-panel .list-group-item .badge {
        font-size: 0.7rem;
        padding: 0.3rem 0.5rem;
    }

    .feedback-panel .list-group-item .small {
        font-size: 0.8rem;
    }

    .feedback-panel .list-group-item p {
        line-height: 1.3;
        margin-top: 0.3rem;
        margin-bottom: 0.2rem;
        font-size: 0.85rem;
    }

    @media (max-width: 767px) {
        .revenue-chart-wrap,
        .mix-chart-wrap {
            height: 220px;
        }

        .sales-mix-summary {
            margin-top: 0;
        }

        .metric-card {
            min-height: 154px;
        }

        .metric-value {
            font-size: 1.95rem;
        }
    }
</style>

<div class="container-fluid dashboard-page py-4 px-4 px-xxl-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end dashboard-page-header gap-2">
        <div>
            <h2 class="mb-1">Dashboard</h2>
        </div>
        <span class="text-muted"><?php echo date('F j, Y'); ?></span>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <?php foreach ($metricCards as $metricCard): ?>
            <div class="col-xl-3 col-md-6">
                <a href="<?php echo $metricCard['label'] === 'Total Revenue' ? 'reports.php' : ($metricCard['label'] === 'Total Customers' ? 'customers.php' : ($metricCard['label'] === 'Total Orders' ? 'orders.php' : 'products.php')); ?>" class="stat-card-link text-reset">
                    <div class="metric-card <?php echo $metricCard['cardClass']; ?>">
                        <div class="metric-card-top">
                            <div>
                                <div class="metric-kicker"><?php echo htmlspecialchars($metricCard['label']); ?></div>
                                <div class="metric-value"><?php echo htmlspecialchars($metricCard['value']); ?></div>
                                <div class="metric-subtitle"><?php echo htmlspecialchars($metricCard['subtitle']); ?></div>
                            </div>
                            <div class="metric-icon"><i class="<?php echo htmlspecialchars($metricCard['icon']); ?>"></i></div>
                        </div>
                        <div class="metric-sparkline">
                            <canvas id="<?php echo strtolower(str_replace(' ', '', $metricCard['label'])); ?>Sparkline" class="sparkline-canvas"></canvas>
                        </div>
                        <div class="metric-footer">
                            <span class="text-muted small">vs recent trend</span>
                            <span class="metric-trend <?php echo $metricCard['trendClass']; ?>">
                                <i class="fas fa-arrow-<?php echo $metricCard['trendValue'] >= 0 ? 'up' : 'down'; ?> me-1"></i>
                                <?php echo number_format(abs((float) $metricCard['trendValue']), 2); ?>%
                            </span>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row mb-4 section-shell">
        <div class="col-lg-6">
            <div class="card chart-card h-100 revenue-overview-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="dashboard-section-title">Revenue Overview</span>
                    <span class="summary-chip"><i class="fas fa-chart-line me-1 text-success"></i>Delivered orders only</span>
                </div>
                <div class="card-body">
                    <div class="revenue-chart-wrap">
                        <canvas id="monthlyRevenueChart" class="chart-canvas"></canvas>
                    </div>
                    <div class="row g-3 mt-3">
                        <div class="col-md-6">
                            <div class="summary-chip w-100 justify-content-between">
                                <span><i class="fas fa-coins me-1 text-info"></i>Total Revenue</span>
                                <strong><?php echo '$' . number_format((float) $total_revenue, 2); ?></strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="btn-group w-100" role="group" id="revenueChartPeriod">
                                <button type="button" class="btn btn-sm btn-outline-primary active" data-period="daily" style="flex: 1; font-size: 0.75rem;">Daily</button>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-period="monthly" style="flex: 1; font-size: 0.75rem;">Monthly</button>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-period="yearly" style="flex: 1; font-size: 0.75rem;">Yearly</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card chart-card h-100 sales-mix-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="dashboard-section-title">Sales Mix</span>
                    <span class="summary-chip"><i class="fas fa-circle me-1 text-success"></i>Top products</span>
                </div>
                <div class="card-body">
                    <div class="mix-chart-wrap">
                        <canvas id="topProductsChart" class="chart-canvas"></canvas>
                    </div>
                    <?php if (!empty($salesMixSummary)): ?>
                        <div class="sales-mix-summary">
                            <?php foreach ($salesMixSummary as $item): ?>
                                <div class="sales-mix-item">
                                    <span class="mix-label" title="<?php echo htmlspecialchars($item['label']); ?>">
                                        <span class="sales-mix-dot" style="background: <?php echo htmlspecialchars($item['color']); ?>;"></span>
                                        <span class="text-truncate"><?php echo htmlspecialchars($item['label']); ?></span>
                                    </span>
                                    <strong><?php echo number_format((float) $item['percentage'], 0); ?>%</strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Orders -->
        <div class="col-xl-8 col-lg-7">
            <div class="card dashboard-panel table-panel h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold">Recent Orders</h6>
                    <small class="text-muted">Latest 5</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table dashboard-table align-middle mb-0" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo $order['username']; ?></td>
                                        <td>₱<?php echo number_format((float) $order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                            echo $order['status'] === 'delivered' ? 'success' :
                                                ($order['status'] === 'pending' ? 'warning' :
                                                    ($order['status'] === 'cancelled' ? 'danger' : 'info'));
                                            ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <a href="order-detail.php?id=<?php echo $order['id']; ?>"
                                                class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock -->
        <div class="col-xl-4 col-lg-5">
            <!-- Low Stock Alert -->
            <div class="card dashboard-panel mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold">Low Stock Alert</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($low_stock_products)): ?>
                        <p class="text-muted">All products have sufficient stock.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($low_stock_products as $product): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <h6 class="mb-1"><?php echo $product['name']; ?></h6>
                                        <small class="text-muted">Stock: <?php echo $product['stock_quantity']; ?></small>
                                    </div>
                                    <a href="products.php?edit=<?php echo $product['id']; ?>"
                                        class="btn btn-sm btn-warning">Restock</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-xl-8 col-lg-7">
            <div class="card dashboard-panel mb-4 feedback-panel">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold">Latest Customer Feedback</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_feedback)): ?>
                        <p class="text-muted mb-0">No feedback yet. Encourage customers to leave reviews after delivery.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_feedback as $feedback): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?php echo htmlspecialchars($feedback['full_name'] ?: 'Customer'); ?></strong>
                                            <span class="badge bg-success ms-2"><?php echo $feedback['rating']; ?>/5</span>
                                            <div class="small text-muted">for
                                                <?php echo htmlspecialchars($feedback['product_name']); ?>
                                            </div>
                                        </div>
                                        <span
                                            class="badge bg-light text-dark text-capitalize"><?php echo $feedback['status']; ?></span>
                                    </div>
                                    <p class="mb-1 mt-2">"<?php echo htmlspecialchars($feedback['comment']); ?>"</p>
                                    <small
                                        class="text-muted"><?php echo date('M j, Y', strtotime($feedback['created_at'])); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-end mt-3">
                            <a href="feedback.php" class="btn btn-outline-primary btn-sm">Manage feedback</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <!-- Quick Actions -->
            <div class="card dashboard-panel mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <a href="products.php" class="btn btn-outline-success btn-block">
                                <i class="fas fa-plus fa-2x mb-2"></i><br>
                                Add Product
                            </a>
                        </div>
                        <div class="col-6 mb-3">
                            <a href="orders.php" class="btn btn-outline-primary btn-block">
                                <i class="fas fa-shopping-cart fa-2x mb-2"></i><br>
                                View Orders
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="customers.php" class="btn btn-outline-info btn-block">
                                <i class="fas fa-users fa-2x mb-2"></i><br>
                                Customers
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="../index.php" class="btn btn-outline-warning btn-block">
                                <i class="fas fa-store fa-2x mb-2"></i><br>
                                View Shop
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
$productsSparklineJson = json_encode($topProductSeries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
$ordersSparklineJson = json_encode($dailyRevenueSeries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
$customersSparklineJson = json_encode($monthlyRevenueSeries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
$revenueSparklineJson = json_encode($yearlyRevenueSeries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

$dailyRevenueJson = json_encode($dailyRevenueSeries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
$monthlyRevenueJson = json_encode($monthlyRevenueSeries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
$yearlyRevenueJson = json_encode($yearlyRevenueSeries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

$revenueOverviewJson = json_encode($revenueOverview, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
$salesMixJson = json_encode($salesMix, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

$pageScripts = <<<SCRIPTS
<script>
(function () {
    if (typeof Chart === "undefined") {
        return;
    }

    const productsSparklineData = $productsSparklineJson;
    const ordersSparklineData = $ordersSparklineJson;
    const customersSparklineData = $customersSparklineJson;
    const revenueSparklineData = $revenueSparklineJson;
    
    const dailyRevenueData = $dailyRevenueJson;
    const monthlyRevenueData = $monthlyRevenueJson;
    const yearlyRevenueData = $yearlyRevenueJson;
    
    const revenueOverviewData = $revenueOverviewJson;
    const salesMixData = $salesMixJson;

    const peso = function (value) {
        return "$" + Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const palette = ["#2563eb", "#22c55e", "#ef4444", "#f59e0b", "#8b5cf6", "#06b6d4"];

    const sparklineOptions = function (color) {
        return {
            type: "line",
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    borderColor: color,
                    backgroundColor: color + "22",
                    fill: true,
                    tension: 0.38,
                    pointRadius: 0,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                },
                elements: { line: { borderCapStyle: "round" } },
                scales: {
                    x: { display: false },
                    y: { display: false }
                }
            }
        };
    };

    const renderSparkline = function (canvasId, series, color) {
        const ctx = document.getElementById(canvasId);
        if (!ctx || !series || !series.values) {
            return;
        }

        new Chart(ctx, {
            type: "line",
            data: {
                labels: series.labels || [],
                datasets: [{
                    data: series.values || [],
                    borderColor: color,
                    backgroundColor: color + "1a",
                    fill: true,
                    tension: 0.42,
                    pointRadius: 0,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 700 },
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                },
                scales: {
                    x: { display: false },
                    y: { display: false }
                }
            }
        });
    };

    renderSparkline("totalproductsSparkline", productsSparklineData, "#2563eb");
    renderSparkline("totalordersSparkline", ordersSparklineData, "#22c55e");
    renderSparkline("totalcustomersSparkline", customersSparklineData, "#ef4444");
    renderSparkline("totalrevenueSparkline", revenueSparklineData, "#f59e0b");

    const revenueCtx = document.getElementById("monthlyRevenueChart");
    let revenueChart = null;
    
    if (revenueCtx) {
        const gradient = revenueCtx.getContext("2d").createLinearGradient(0, 0, 0, 320);
        gradient.addColorStop(0, "rgba(34, 197, 94, 0.32)");
        gradient.addColorStop(1, "rgba(34, 197, 94, 0.02)");

        revenueChart = new Chart(revenueCtx, {
            type: "line",
            data: {
                labels: monthlyRevenueData.labels,
                datasets: [{
                    label: "Revenue",
                    data: monthlyRevenueData.values,
                    borderColor: "#22c55e",
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.42,
                    pointRadius: 0,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: "index",
                        intersect: false,
                        callbacks: {
                            label: function (ctx) {
                                return " Revenue: " + peso(ctx.raw || 0);
                            }
                        }
                    }
                },
                interaction: {
                    mode: "index",
                    intersect: false
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: "#94a3b8" }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: "rgba(15,23,42,0.06)" },
                        ticks: {
                            color: "#94a3b8",
                            callback: function (value) {
                                return "" + Math.round(value / 1000) + "k";
                            }
                        }
                    }
                }
            }
        });

        // Handle revenue period button clicks
        const periodButtons = document.querySelectorAll('#revenueChartPeriod button');
        periodButtons.forEach(button => {
            button.addEventListener('click', function() {
                const period = this.dataset.period;
                let selectedData;
                
                if (period === 'daily') {
                    selectedData = dailyRevenueData;
                } else if (period === 'yearly') {
                    selectedData = yearlyRevenueData;
                } else {
                    selectedData = monthlyRevenueData;
                }
                
                // Update chart data
                revenueChart.data.labels = selectedData.labels;
                revenueChart.data.datasets[0].data = selectedData.values;
                revenueChart.update();
                
                // Update active button
                periodButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }

    const topProductsCtx = document.getElementById("topProductsChart");
    if (topProductsCtx) {
        new Chart(topProductsCtx, {
            type: "doughnut",
            data: {
                labels: salesMixData.labels,
                datasets: [{
                    label: "Units Sold",
                    data: salesMixData.values,
                    backgroundColor: ["#2563eb", "#22c55e", "#f59e0b", "#ef4444", "#8b5cf6"],
                    borderColor: "#ffffff",
                    borderWidth: 2.5,
                    borderRadius: 6,
                    hoverOffset: 8
                }]
            },
            options: {
                cutout: "68%",
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ctx.label + ': ' + Number(ctx.raw || 0).toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
})();
</script>
SCRIPTS;

include 'includes/footer.php';
?>
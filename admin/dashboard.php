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

include 'includes/header.php';
?>

<style>
    .stat-card {
        border: 0;
        border-radius: 18px;
        padding: 1.65rem;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        background: #ffffff;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
        color: #0f172a;
    }

    .stat-card-products {
        background: linear-gradient(135deg, #93c5fd 0%, #c4b5fd 100%);
    }

    .stat-card-orders {
        background: linear-gradient(135deg, #6ee7b7 0%, #a7f3d0 100%);
    }

    .stat-card-customers {
        background: linear-gradient(135deg, #67e8f9 0%, #a5f3fc 100%);
    }

    .stat-card-revenue {
        background: linear-gradient(135deg, #fcd34d 0%, #fde68a 100%);
    }

    .stat-label {
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.06em;
        margin-bottom: 0.35rem;
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 600;
    }

    .stat-icon {
        font-size: 2.2rem;
        color: rgba(15, 23, 42, 0.5);
        align-self: flex-end;
        transform: translateY(6px);
    }

    .chart-card {
        border-radius: 18px;
        border: 0;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
    }

    .chart-card .card-header {
        border: 0;
        background: transparent;
        font-weight: 600;
    }

    .chart-canvas {
        height: 160px;
    }

    .stat-card-link {
        display: block;
        text-decoration: none;
        border-radius: 20px;
    }

    .stat-card-link .stat-card {
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        cursor: pointer;
    }

    .stat-card-link:hover .stat-card,
    .stat-card-link:focus .stat-card {
        transform: translateY(-6px) scale(1.01);
        box-shadow: 0 25px 45px rgba(15, 23, 42, 0.18);
    }

    .stat-card-link:focus-visible {
        outline: none;
    }

    .stat-card-link:focus-visible .stat-card {
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.9), 0 25px 45px rgba(15, 23, 42, 0.2);
    }

    @media (max-width: 767px) {
        .chart-canvas {
            height: 200px;
        }
    }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Dashboard</h2>
        <span class="text-muted"><?php echo date('F j, Y'); ?></span>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <a href="products.php" class="stat-card-link text-reset">
                <div class="stat-card stat-card-products">
                <div>
                    <div class="stat-label text-primary">Total Products</div>
                    <div class="stat-value"><?php echo $total_products; ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-boxes"></i></div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="orders.php" class="stat-card-link text-reset">
                <div class="stat-card stat-card-orders">
                <div>
                    <div class="stat-label text-success">Total Orders</div>
                    <div class="stat-value"><?php echo $total_orders; ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="customers.php" class="stat-card-link text-reset">
                <div class="stat-card stat-card-customers">
                <div>
                    <div class="stat-label text-info">Total Customers</div>
                    <div class="stat-value"><?php echo $total_customers; ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="reports.php" class="stat-card-link text-reset">
                <div class="stat-card stat-card-revenue">
                <div>
                    <div class="stat-label text-warning">Total Revenue</div>
                    <div class="stat-value">$<?php echo number_format($total_revenue, 2); ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
                </div>
            </a>
        </div>
    </div>

    <!-- Compact Charts -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card chart-card h-100">
                <div class="card-header">Daily Revenue</div>
                <div class="card-body">
                    <canvas id="dailyRevenueChart" class="chart-canvas"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card chart-card h-100">
                <div class="card-header">Monthly Revenue</div>
                <div class="card-body">
                    <canvas id="monthlyRevenueChart" class="chart-canvas"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card chart-card h-100">
                <div class="card-header">Yearly Revenue</div>
                <div class="card-body">
                    <canvas id="yearlyRevenueChart" class="chart-canvas"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card chart-card h-100">
                <div class="card-header">Top Products</div>
                <div class="card-body">
                    <canvas id="topProductsChart" class="chart-canvas"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Orders -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white py-3">
                    <h6 class="m-0 font-weight-bold">Recent Orders</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
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
                                        <td>$<?php echo $order['total_amount']; ?></td>
                                        <td>₱<?php echo $order['total_amount']; ?></td>
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

        <!-- Low Stock & Quick Stats -->
        <div class="col-xl-4 col-lg-5">
            <!-- Low Stock Alert -->
            <div class="card shadow mb-4">
                <div class="card-header bg-warning py-3">
                    <h6 class="m-0 font-weight-bold text-dark">Low Stock Alert</h6>
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

            <!-- Quick Actions -->
            <div class="card shadow">
                <div class="card-header bg-success text-white py-3">
                    <h6 class="m-0 font-weight-bold">Quick Actions</h6>
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

    <div class="row">
        <div class="col-xl-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h6 class="m-0 font-weight-bold">Latest Customer Feedback</h6>
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
                                            <strong><?php echo htmlspecialchars($feedback['full_name'] ?? $feedback['username']); ?></strong>
                                            <span class="badge bg-success ms-2"><?php echo $feedback['rating']; ?>/5</span>
                                            <div class="small text-muted">for
                                                <?php echo htmlspecialchars($feedback['product_name']); ?>
                                            </div>
                                        </div>
                                        <span
                                            class="badge bg-light text-dark text-capitalize"><?php echo $feedback['status']; ?></span>
                                    </div>
                                    <p class="mb-1 mt-2">“<?php echo htmlspecialchars($feedback['comment']); ?>”</p>
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
    </div>

</div>

<?php
$pageScripts = '<script>
(function () {
    if (typeof Chart === "undefined") {
        return;
    }

    const dailyData = ' . json_encode($dailyRevenueSeries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) . ';
    const monthlyData = ' . json_encode($monthlyRevenueSeries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) . ';
    const yearlyData = ' . json_encode($yearlyRevenueSeries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) . ';
    const topProductsData = ' . json_encode($topProductSeries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) . ';

    const peso = function (value) {
        return "₱" + Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 0 });
    };

    const dailyCtx = document.getElementById("dailyRevenueChart");
    if (dailyCtx) {
        new Chart(dailyCtx, {
            type: "line",
            data: {
                labels: dailyData.labels,
                datasets: [{
                    label: "Revenue",
                    data: dailyData.values,
                    tension: 0.35,
                    fill: true,
                    borderWidth: 2,
                    borderColor: "#198754",
                    backgroundColor: "rgba(25,135,84,0.15)"
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        ticks: { callback: peso }
                    }
                }
            }
        });
    }

    const monthlyCtx = document.getElementById("monthlyRevenueChart");
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: "bar",
            data: {
                labels: monthlyData.labels,
                datasets: [{
                    label: "Revenue",
                    data: monthlyData.values,
                    backgroundColor: "rgba(13,110,253,0.8)"
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { ticks: { callback: peso } }
                }
            }
        });
    }

    const yearlyCtx = document.getElementById("yearlyRevenueChart");
    if (yearlyCtx) {
        new Chart(yearlyCtx, {
            type: "bar",
            data: {
                labels: yearlyData.labels,
                datasets: [{
                    label: "Revenue",
                    data: yearlyData.values,
                    backgroundColor: "rgba(33,37,41,0.85)"
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { ticks: { callback: peso } }
                }
            }
        });
    }

    const topProductsCtx = document.getElementById("topProductsChart");
    if (topProductsCtx) {
        new Chart(topProductsCtx, {
            type: "bar",
            data: {
                labels: topProductsData.labels,
                datasets: [{
                    label: "Units Sold",
                    data: topProductsData.values,
                    backgroundColor: "rgba(255,193,7,0.85)"
                }]
            },
            options: {
                indexAxis: "y",
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { precision: 0 } }
                }
            }
        });
    }
})();
</script>';

include 'includes/footer.php';
?>
<?php
require_once '../includes/config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$reportTypes = [
    'sales' => 'Sales Overview',
    'products' => 'Product Performance',
    'customers' => 'Customer Insights',
    'cancellations' => 'Cancelled / Refunded Orders'
];

$periodOptions = [
    'daily' => 'Daily',
    'weekly' => 'Weekly',
    'monthly' => 'Monthly',
    'yearly' => 'Yearly',
    'custom' => 'Custom Range'
];

$type = $_GET['type'] ?? 'sales';
$period = $_GET['period'] ?? 'monthly';
$customStart = $_GET['start'] ?? null;
$customEnd = $_GET['end'] ?? null;

if (!isset($reportTypes[$type])) {
    $type = 'sales';
}

if (!isset($periodOptions[$period])) {
    $period = 'monthly';
}

$range = resolveReportRange($period, $customStart, $customEnd);

switch ($type) {
    case 'products':
        $reportData = getProductPerformanceReport($range['start'], $range['end']);
        break;
    case 'customers':
        $reportData = getCustomerInsightsReport($range['start'], $range['end']);
        break;
    case 'cancellations':
        $reportData = getCancelledRefundedOrdersReport($range['start'], $range['end']);
        break;
    default:
        $reportData = getSalesReportData($range['start'], $range['end']);
        break;
}

$exportParams = http_build_query([
    'type' => $type,
    'period' => $period,
    'start' => $range['start_date'],
    'end' => $range['end_date']
]);

function peso($amount)
{
    return '₱' . number_format((float) $amount, 2);
}

include 'includes/header.php';
?>

<style>
    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: #fff;
        }
    }
</style>

<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
        <div>
            <h2 class="mb-1">Reports &amp; Exports</h2>
            <p class="text-muted mb-0">Generate <?php echo strtolower($reportTypes[$type]); ?> for
                <?php echo htmlspecialchars($range['label']); ?>.</p>
        </div>
        <div class="no-print d-flex flex-wrap gap-2 mt-3 mt-md-0">
            <a class="btn btn-outline-primary" href="export-report.php?format=excel&amp;<?php echo $exportParams; ?>">
                <i class="fas fa-file-excel me-2"></i>Export Excel
            </a>
            <a class="btn btn-outline-danger" href="export-report.php?format=pdf&amp;<?php echo $exportParams; ?>">
                <i class="fas fa-file-pdf me-2"></i>Export PDF
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Print
            </button>
        </div>
    </div>

    <div class="card shadow-sm no-print mb-4">
        <div class="card-body">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-4">
                    <label class="form-label">Report Type</label>
                    <select class="form-select" name="type">
                        <?php foreach ($reportTypes as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $type === $key ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Period</label>
                    <select class="form-select" name="period" id="periodSelect">
                        <?php foreach ($periodOptions as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $period === $key ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start" id="startDate"
                        value="<?php echo htmlspecialchars($range['start_date']); ?>" <?php echo $period === 'custom' ? '' : 'disabled'; ?>>
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end" id="endDate"
                        value="<?php echo htmlspecialchars($range['end_date']); ?>" <?php echo $period === 'custom' ? '' : 'disabled'; ?>>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-sync"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($type === 'sales'): ?>
        <?php $summary = $reportData['summary']; ?>
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card border-left-success h-100">
                    <div class="card-body">
                        <p class="text-uppercase text-muted small mb-1">Revenue</p>
                        <h4 class="mb-0"><?php echo peso($summary['total_revenue']); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-left-primary h-100">
                    <div class="card-body">
                        <p class="text-uppercase text-muted small mb-1">Orders</p>
                        <h4 class="mb-0"><?php echo number_format($summary['total_orders']); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-left-info h-100">
                    <div class="card-body">
                        <p class="text-uppercase text-muted small mb-1">Average Order</p>
                        <h4 class="mb-0"><?php echo peso($summary['avg_order_value']); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-left-warning h-100">
                    <div class="card-body">
                        <p class="text-uppercase text-muted small mb-1">Unique Customers</p>
                        <h4 class="mb-0"><?php echo number_format($summary['unique_customers']); ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Status Breakdown</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($reportData['status_breakdown'])): ?>
                            <p class="text-muted mb-0">No orders recorded.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($reportData['status_breakdown'] as $status => $count): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span class="text-capitalize"><?php echo str_replace('_', ' ', $status); ?></span>
                                        <span class="badge bg-secondary"><?php echo (int) $count; ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-xl-8 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Orders</h6>
                        <small class="text-muted">Delivered &amp; completed</small>
                    </div>
                    <div class="card-body">
                        <?php if (empty($reportData['orders'])): ?>
                            <p class="text-muted mb-0">No fulfilled orders for this range.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Placed</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reportData['orders'] as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order['id']; ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td><?php echo peso($order['total_amount']); ?></td>
                                                <td class="text-capitalize"><?php echo $order['status']; ?></td>
                                                <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($type === 'products'): ?>
        <div class="card shadow">
            <div class="card-header bg-light">
                <h6 class="mb-0">Product Performance</h6>
            </div>
            <div class="card-body">
                <?php if (empty($reportData)): ?>
                    <p class="text-muted mb-0">No product sales recorded for this range.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Units Sold</th>
                                    <th class="text-center">Orders</th>
                                    <th class="text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td class="text-center"><?php echo (int) $row['units_sold']; ?></td>
                                        <td class="text-center"><?php echo (int) $row['order_count']; ?></td>
                                        <td class="text-end"><?php echo peso($row['revenue']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($type === 'customers'): ?>
        <div class="card shadow">
            <div class="card-header bg-light">
                <h6 class="mb-0">Customer Insights</h6>
            </div>
            <div class="card-body">
                <?php if (empty($reportData)): ?>
                    <p class="text-muted mb-0">No customer orders recorded for this range.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th class="text-center">Orders</th>
                                    <th class="text-end">Total Spent</th>
                                    <th class="text-end">Average Order</th>
                                    <th class="text-end">Last Order</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                        <td class="text-center"><?php echo (int) $row['order_count']; ?></td>
                                        <td class="text-end"><?php echo peso($row['total_spent']); ?></td>
                                        <td class="text-end"><?php echo peso($row['avg_order_value']); ?></td>
                                        <td class="text-end"><?php echo date('M j, Y', strtotime($row['last_order_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow">
            <div class="card-header bg-light">
                <h6 class="mb-0">Cancelled / Refunded Orders</h6>
            </div>
            <div class="card-body">
                <?php if (empty($reportData)): ?>
                    <p class="text-muted mb-0">No cancelled or refunded orders for this range.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td class="text-capitalize"><?php echo $order['status']; ?></td>
                                        <td class="text-end"><?php echo peso($order['total_amount']); ?></td>
                                        <td class="text-end"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$pageScripts = '<script>
const periodSelect = document.getElementById("periodSelect");
const startInput = document.getElementById("startDate");
const endInput = document.getElementById("endDate");
function toggleDateInputs() {
    const isCustom = periodSelect.value === "custom";
    startInput.disabled = !isCustom;
    endInput.disabled = !isCustom;
}
periodSelect.addEventListener("change", toggleDateInputs);
toggleDateInputs();
</script>';

include 'includes/footer.php';

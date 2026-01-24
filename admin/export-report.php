<?php
require_once '../includes/config.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$validTypes = ['sales', 'products', 'customers', 'cancellations'];
$format = strtolower($_GET['format'] ?? 'excel');
$type = $_GET['type'] ?? 'sales';
$period = $_GET['period'] ?? 'monthly';
$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;

if (!in_array($type, $validTypes, true)) {
    $type = 'sales';
}

if ($start && $end) {
    $range = resolveReportRange('custom', $start, $end);
} else {
    $range = resolveReportRange($period);
}

switch ($type) {
    case 'products':
        $data = getProductPerformanceReport($range['start'], $range['end']);
        $reportTitle = 'Product Performance Report';
        break;
    case 'customers':
        $data = getCustomerInsightsReport($range['start'], $range['end']);
        $reportTitle = 'Customer Insights Report';
        break;
    case 'cancellations':
        $data = getCancelledRefundedOrdersReport($range['start'], $range['end']);
        $reportTitle = 'Cancelled / Refunded Orders Report';
        break;
    default:
        $data = getSalesReportData($range['start'], $range['end']);
        $reportTitle = 'Sales Report';
        break;
}

$generatedAt = date('M j, Y g:i A');

if ($format !== 'pdf') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Report');

    $sheet->setCellValue('A1', $reportTitle);
    $sheet->setCellValue('A2', 'Range: ' . $range['label']);
    $sheet->setCellValue('A3', 'Generated: ' . $generatedAt);

    $row = 5;

    if ($type === 'sales') {
        $sheet->fromArray(['Metric', 'Value'], null, 'A' . $row);
        $row++;
        $sheet->fromArray(['Total Revenue', $data['summary']['total_revenue']], null, 'A' . $row++);
        $sheet->fromArray(['Total Orders', $data['summary']['total_orders']], null, 'A' . $row++);
        $sheet->fromArray(['Average Order Value', $data['summary']['avg_order_value']], null, 'A' . $row++);
        $sheet->fromArray(['Unique Customers', $data['summary']['unique_customers']], null, 'A' . $row++);
        $row += 1;
        $sheet->fromArray(['Order ID', 'Customer', 'Amount', 'Status', 'Placed'], null, 'A' . $row);
        $row++;
        foreach ($data['orders'] as $order) {
            $sheet->fromArray([
                '#' . $order['id'],
                $order['customer_name'],
                $order['total_amount'],
                ucfirst($order['status']),
                date('M j, Y', strtotime($order['created_at']))
            ], null, 'A' . $row);
            $row++;
        }
    } elseif ($type === 'products') {
        $sheet->fromArray(['Product', 'Units Sold', 'Orders', 'Revenue'], null, 'A' . $row);
        $row++;
        foreach ($data as $item) {
            $sheet->fromArray([
                $item['name'],
                (int) $item['units_sold'],
                (int) $item['order_count'],
                (float) $item['revenue']
            ], null, 'A' . $row);
            $row++;
        }
    } elseif ($type === 'customers') {
        $sheet->fromArray(['Customer', 'Orders', 'Total Spent', 'Avg Order', 'Last Order'], null, 'A' . $row);
        $row++;
        foreach ($data as $customer) {
            $sheet->fromArray([
                $customer['customer_name'],
                (int) $customer['order_count'],
                (float) $customer['total_spent'],
                (float) $customer['avg_order_value'],
                date('M j, Y', strtotime($customer['last_order_date']))
            ], null, 'A' . $row);
            $row++;
        }
    } else {
        $sheet->fromArray(['Order ID', 'Customer', 'Status', 'Amount', 'Date'], null, 'A' . $row);
        $row++;
        foreach ($data as $order) {
            $sheet->fromArray([
                '#' . $order['id'],
                $order['customer_name'],
                ucfirst($order['status']),
                (float) $order['total_amount'],
                date('M j, Y', strtotime($order['created_at']))
            ], null, 'A' . $row);
            $row++;
        }
    }

    foreach (range('A', $sheet->getHighestColumn()) as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="report-' . date('YmdHis') . '.xlsx"');
    $writer->save('php://output');
    exit;
}

$peso = function ($amount) {
    return '₱' . number_format((float) $amount, 2);
};

ob_start();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #212529;
        }

        h2 {
            margin-bottom: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: left;
        }

        th {
            background: #f0f2f5;
        }
    </style>
</head>

<body>
    <h2><?php echo htmlspecialchars($reportTitle); ?></h2>
    <p>
        <strong>Range:</strong> <?php echo htmlspecialchars($range['label']); ?><br>
        <strong>Generated:</strong> <?php echo htmlspecialchars($generatedAt); ?>
    </p>

    <?php if ($type === 'sales'): ?>
        <table>
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Revenue</td>
                    <td><?php echo $peso($data['summary']['total_revenue']); ?></td>
                </tr>
                <tr>
                    <td>Total Orders</td>
                    <td><?php echo number_format($data['summary']['total_orders']); ?></td>
                </tr>
                <tr>
                    <td>Average Order Value</td>
                    <td><?php echo $peso($data['summary']['avg_order_value']); ?></td>
                </tr>
                <tr>
                    <td>Unique Customers</td>
                    <td><?php echo number_format($data['summary']['unique_customers']); ?></td>
                </tr>
            </tbody>
        </table>

        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data['orders'])): ?>
                    <tr>
                        <td colspan="5">No fulfilled orders for this period.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data['orders'] as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo ucfirst($order['status']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                            <td><?php echo $peso($order['total_amount']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php elseif ($type === 'products'): ?>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Units Sold</th>
                    <th>Orders</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                    <tr>
                        <td colspan="4">No product sales recorded.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo (int) $row['units_sold']; ?></td>
                            <td><?php echo (int) $row['order_count']; ?></td>
                            <td><?php echo $peso($row['revenue']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php elseif ($type === 'customers'): ?>
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Orders</th>
                    <th>Total Spent</th>
                    <th>Average Order</th>
                    <th>Last Order</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                    <tr>
                        <td colspan="5">No customer orders recorded.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                            <td><?php echo (int) $row['order_count']; ?></td>
                            <td><?php echo $peso($row['total_spent']); ?></td>
                            <td><?php echo $peso($row['avg_order_value']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($row['last_order_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                    <tr>
                        <td colspan="5">No cancelled or refunded orders.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo ucfirst($order['status']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                            <td><?php echo $peso($order['total_amount']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>

</html>
<?php
$html = ob_get_clean();
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('report-' . date('YmdHis') . '.pdf', ['Attachment' => true]);
exit;

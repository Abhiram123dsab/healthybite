<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/payment.php';
require_once __DIR__ . '/../includes/PaymentLogger.php';

use HealthyBite\PaymentLogger;

session_start();

// Check if user is admin
$auth = new Auth($conn);
if (!$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$logger = new PaymentLogger($conn);
$recentLogs = $logger->getRecentPaymentLogs(100);

// Get payment trends data
$trends = $logger->getPaymentTrends();

// Get failure analysis
$failureAnalysis = $logger->getDetailedFailureAnalysis();

// Get detailed failure analysis with error rates and patterns
$detailedFailures = $logger->getDetailedFailureAnalysis();

// Calculate overall statistics for the last 30 days
$stats = $logger->getPaymentTrends(30)[0] ?? [
    'total_transactions' => 0,
    'successful_transactions' => 0,
    'failed_transactions' => 0,
    'avg_amount' => 0
];

// Handle report export
if (isset($_POST['export_report'])) {
    $startDate = $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_POST['end_date'] ?? date('Y-m-d');
    $reportData = $logger->exportPaymentReport($startDate . ' 00:00:00', $endDate . ' 23:59:59');
    
    // Generate CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="payment_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Order ID', 'User', 'Event Type', 'Status', 'Message', 'Amount', 'Currency']);
    
    foreach ($reportData as $row) {
        fputcsv($output, [
            $row['created_at'],
            $row['order_id'],
            $row['username'],
            $row['event_type'],
            $row['status'],
            $row['message'],
            $row['amount'],
            $row['currency']
        ]);
    }
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Analytics - HealthyBite Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-dashboard">
    <div class="container">
        <h1>Payment Analytics</h1>
        
        <div class="analytics-grid">
            <div class="stat-card">
                <h3>Total Transactions (30 days)</h3>
                <p class="stat-number"><?php echo $stats['total_transactions']; ?></p>
            </div>
            <div class="stat-card success">
                <h3>Successful Transactions</h3>
                <p class="stat-number"><?php echo $stats['successful_transactions']; ?></p>
            </div>
            <div class="stat-card error">
                <h3>Failed Transactions</h3>
                <p class="stat-number"><?php echo $stats['failed_transactions']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Average Transaction Amount</h3>
                <p class="stat-number">$<?php echo number_format($stats['avg_amount'], 2); ?></p>
            </div>
        </div>

        <div class="export-section">
            <h2>Export Payment Report</h2>
            <form method="post" class="export-form">
                <div class="form-group">
                    <label for="start_date">Start Date:</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_date">End Date:</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <button type="submit" name="export_report" class="btn btn-primary">Export CSV</button>
            </form>
        </div>

        <div class="chart-section">
            <h2>Payment Trends</h2>
            <div class="chart-container">
                <canvas id="transactionChart"></canvas>
            </div>
        </div>

        <div class="failure-analysis">
            <h2>Detailed Transaction Failure Analysis</h2>
            <div class="table-responsive">
                <table class="failure-table">
                    <thead>
                        <tr>
                            <th>Error Type</th>
                            <th>Message</th>
                            <th>Occurrences</th>
                            <th>Affected Users</th>
                            <th>Error Rate</th>
                            <th>First Seen</th>
                            <th>Last Seen</th>
                            <th>Avg Amount</th>
                            <th>Currencies</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detailedFailures as $failure): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($failure['event_type']); ?></td>
                            <td><?php echo htmlspecialchars($failure['message']); ?></td>
                            <td><?php echo $failure['occurrence_count']; ?></td>
                            <td><?php echo $failure['affected_users']; ?></td>
                            <td><?php echo number_format($failure['error_rate_percentage'], 2); ?>%</td>
                            <td><?php echo date('Y-m-d H:i', strtotime($failure['first_occurrence'])); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($failure['last_occurrence'])); ?></td>
                            <td>$<?php echo number_format($failure['avg_amount_affected'], 2); ?></td>
                            <td><?php echo htmlspecialchars($failure['currencies_affected']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <h2>Recent Transaction Logs</h2>
        <div class="table-responsive">
            <table class="payment-logs-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Order ID</th>
                        <th>User</th>
                        <th>Event Type</th>
                        <th>Status</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLogs as $log): ?>
                    <tr class="status-<?php echo $log['status']; ?>">
                        <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($log['order_id']); ?></td>
                        <td><?php echo htmlspecialchars($log['username']); ?></td>
                        <td><?php echo htmlspecialchars($log['event_type']); ?></td>
                        <td><?php echo htmlspecialchars($log['status']); ?></td>
                        <td><?php echo htmlspecialchars($log['message']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    // Initialize transaction chart
    const ctx = document.getElementById('transactionChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($trends, 'date')); ?>,
            datasets: [{
                label: 'Total Transactions',
                data: <?php echo json_encode(array_column($trends, 'total_transactions')); ?>,
                borderColor: '#4CAF50',
                tension: 0.1
            }, {
                label: 'Successful Transactions',
                data: <?php echo json_encode(array_column($trends, 'successful_transactions')); ?>,
                borderColor: '#2196F3',
                tension: 0.1
            }, {
                label: 'Failed Transactions',
                data: <?php echo json_encode(array_column($trends, 'failed_transactions')); ?>,
                borderColor: '#FF5722',
                tension: 0.1
            }, {
                label: 'Average Amount ($)',
                data: <?php echo json_encode(array_column($trends, 'avg_amount')); ?>,
                borderColor: '#9C27B0',
                tension: 0.1,
                yAxisID: 'amount'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                },
                amount: {
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
    </script>
    <style>
    .export-section {
        margin: 2rem 0;
        padding: 1rem;
        background: #f5f5f5;
        border-radius: 8px;
    }
    
    .export-form {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .failure-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    
    .failure-table th,
    .failure-table td {
        padding: 0.75rem;
        border: 1px solid #ddd;
        text-align: left;
    }
    
    .failure-table th {
        background-color: #f5f5f5;
    }
    
    .chart-section {
        margin: 2rem 0;
    }
    
    .failure-analysis {
        margin: 2rem 0;
    }
    </style>
</body>
</html>
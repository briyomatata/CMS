<?php
include 'dbconfig.php';

// 1. Get Totals from both tables combined
$combinedQuery = "
    (SELECT purpose_details, amount, 'mpesa' as source, created_at FROM contributions WHERE status = 'completed')
    UNION ALL
    (SELECT purpose_details, amount, 'manual' as source, created_at FROM manual_contributions)
";
$combinedResult = $db->query($combinedQuery);

$unbundledTotals = [];
$grandTotal = 0;
$totalTransactions = 0;

if ($combinedResult) {
    while ($row = $combinedResult->fetch_assoc()) {
        $grandTotal += (float)$row['amount'];
        $totalTransactions++;
        $raw_string = $row['purpose_details'];
        
        // Check if it's a "Multiple" style entry
        if (strpos($raw_string, '(KES') !== false) {
            $items = explode(", ", $raw_string); 
            foreach ($items as $item) {
                if (preg_match('/^(.*) \(KES (.*)\)$/', $item, $matches)) {
                    $catName = trim($matches[1]);
                    $catAmt = (float)$matches[2];
                    $unbundledTotals[$catName] = ($unbundledTotals[$catName] ?? 0) + $catAmt;
                }
            }
        } else {
            // Treat as an Independent Contribution
            $catName = trim($raw_string);
            $unbundledTotals[$catName] = ($unbundledTotals[$catName] ?? 0) + (float)$row['amount'];
        }
    }
}
// Sort: Highest contribution first
arsort($unbundledTotals);

// 2. Get Recent Transactions (Combined) for the table
$recentQuery = "
    (SELECT full_name, amount, purpose_details, mpesa_receipt as receipt, created_at FROM contributions WHERE status = 'completed')
    UNION ALL
    (SELECT full_name, amount, purpose_details, receipt_no as receipt, created_at FROM manual_contributions)
    ORDER BY created_at DESC LIMIT 10
";
$recentResult = $db->query($recentQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Analysis Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .stat-card { border: none; border-radius: 12px; color: white; }
        .bg-gradient-green { background: linear-gradient(45deg, #1e7e34, #28a745); }
        .bg-gradient-blue { background: linear-gradient(45deg, #0056b3, #007bff); }
        .analysis-card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
<div class="container py-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark">Financial Analysis</h2>
        <div class="btn-group">
            <a href="manual_entry.php" class="btn btn-outline-success">Add Manual Entry</a>
            <a href="generate_report.php" class="btn btn-dark">Download PDF Report</a>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card stat-card bg-gradient-green p-4 text-center">
                <small class="text-uppercase opacity-75">Grand Total (M-Pesa + Manual)</small>
                <h1 class="fw-bold">KES <?php echo number_format($grandTotal, 2); ?></h1>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card stat-card bg-gradient-blue p-4 text-center">
                <small class="text-uppercase opacity-75">Total Transactions</small>
                <h1 class="fw-bold"><?php echo number_format($totalTransactions); ?></h1>
            </div>
        </div>
    </div>

    <div class="analysis-card mb-5">
        <h5 class="fw-bold mb-4">Contribution Distribution (Unified)</h5>
        <div class="row">
            <?php foreach ($unbundledTotals as $name => $amount): 
                $percentage = ($grandTotal > 0) ? ($amount / $grandTotal) * 100 : 0;
            ?>
            <div class="col-md-6 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-dark fw-semibold"><?php echo htmlspecialchars($name); ?></span>
                    <span class="text-muted small">KES <?php echo number_format($amount, 2); ?> (<?php echo number_format($percentage, 1); ?>%)</span>
                </div>
                <div class="progress" style="height: 12px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentage; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="analysis-card">
        <h5 class="fw-bold mb-4">Recent Transactions (All Sources)</h5>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Member</th>
                        <th>Details</th>
                        <th>Amount</th>
                        <th>Receipt Code</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $recentResult->fetch_assoc()): ?>
                    <tr>
                        <td class="fw-bold"><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><small><?php echo htmlspecialchars($row['purpose_details']); ?></small></td>
                        <td class="text-success fw-bold">KES <?php echo number_format($row['amount'], 2); ?></td>
                        <td><code class="text-dark"><?php echo $row['receipt']; ?></code></td>
                        <td><small class="text-muted"><?php echo date('d M, Y', strtotime($row['created_at'])); ?></small></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
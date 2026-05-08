<?php
include 'dbconfig.php';

$filter = $_GET['filter'] ?? 'all'; 
$search = $_GET['search'] ?? '';

// 1. DATABASE QUERY
$query = "SELECT category, purpose_details, amount FROM contributions WHERE status = 'completed'";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (category LIKE ? OR purpose_details LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

// Date Filters
if ($filter == 'sabbath') {
    $query .= " AND DATE(created_at) = CURDATE()"; 
} elseif ($filter == 'month') {
    $query .= " AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
} elseif ($filter == 'year') {
    $query .= " AND YEAR(created_at) = YEAR(CURRENT_DATE())";
}

$stmt = $db->prepare($query);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();

// 2. DATA PROCESSING (The "Unbundling" Logic)
$unbundledFunds = [];
$totalCollected = 0;

// 2. DATA PROCESSING (Improved "Fuzzy" Logic)
$unbundledFunds = [];
$totalCollected = 0;

// 2. DATA PROCESSING (The Hybrid Logic)
$unbundledFunds = [];
$totalCollected = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rawDetails = trim($row['purpose_details']);
        $totalRowAmount = (float)$row['amount'];

        // CHECK: Is this a "Multiple" entry with (KES) markers?
        if (strpos($rawDetails, '(KES') !== false) {
            $items = explode(",", $rawDetails);
            foreach ($items as $item) {
                // Extract "Name" and "Amount" from "Name (KES Amount)"
                if (preg_match('/^(.*?)\s*\(KES\s*([\d\.]+)\)/i', trim($item), $matches)) {
                    $fundName = trim($matches[1]);
                    $fundAmount = (float)$matches[2];
                    
                    if (!isset($unbundledFunds[$fundName])) $unbundledFunds[$fundName] = 0;
                    $unbundledFunds[$fundName] += $fundAmount;
                    $totalCollected += $fundAmount;
                }
            }
        } else {
            // This is a "Simple" entry (like your Camp Meeting row)
            // Use the category name as the Fund Name and the full row amount
            $fundName = !empty($rawDetails) ? $rawDetails : $row['category'];
            
            if (!isset($unbundledFunds[$fundName])) $unbundledFunds[$fundName] = 0;
            $unbundledFunds[$fundName] += $totalRowAmount;
            $totalCollected += $totalRowAmount;
        }
    }
}
arsort($unbundledFunds);
arsort($unbundledFunds);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Dashboard - Laiser Hill SDA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .fund-card { transition: transform 0.2s; border: none; border-left: 5px solid #0d6efd; }
        .fund-card:hover { transform: translateY(-5px); }
        .stat-label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: #6c757d; }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">Financial Dashboard</h2>
            <p class="text-muted small">Real-time fund tracking and analysis</p>
        </div>
        <form method="GET" class="d-flex shadow-sm">
            <input type="text" name="search" class="form-control border-0" placeholder="Search funds..." value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-white bg-white border-start-0" type="submit">🔍</button>
        </form>
    </div>

    <div class="mb-4">
        <div class="btn-group shadow-sm">
            <?php 
            $navs = ['all' => 'All Records', 'sabbath' => 'Today', 'month' => 'This Month', 'year' => 'Annual'];
            foreach($navs as $k => $v): ?>
                <a href="?filter=<?php echo $k; ?>&search=<?php echo $search; ?>" 
                   class="btn btn-white <?php echo $filter == $k ? 'bg-primary text-white' : 'bg-white'; ?>">
                   <?php echo $v; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-dark text-white mb-5">
        <div class="card-body p-4 text-center">
            <span class="stat-label text-white-50">Total Consolidated Collection</span>
            <h1 class="display-4 fw-bold mt-2">KES <?php echo number_format($totalCollected, 2); ?></h1>
        </div>
    </div>

    <h5 class="fw-bold mb-3">Individual Fund Breakdown</h5>
    <div class="row g-4">
        <?php if(empty($unbundledFunds)): ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted">No specific fund data found for this selection.</p>
            </div>
        <?php endif; ?>

        <?php foreach($unbundledFunds as $name => $amount): ?>
            <div class="col-md-4 col-lg-3">
                <div class="card fund-card shadow-sm h-100">
                    <div class="card-body">
                        <div class="stat-label"><?php echo htmlspecialchars($name); ?></div>
                        <h4 class="fw-bold mt-2 mb-0">KES <?php echo number_format($amount, 2); ?></h4>
                        <?php 
                            $percent = ($totalCollected > 0) ? ($amount / $totalCollected) * 100 : 0;
                        ?>
                        <div class="progress mt-3" style="height: 4px;">
                            <div class="progress-bar bg-primary" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                        <small class="text-muted mt-2 d-block"><?php echo number_format($percent, 1); ?>% of total</small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>
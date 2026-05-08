<?php
include 'dbconfig.php';

$filter = $_GET['filter'] ?? 'all'; 
$search = $_GET['search'] ?? '';

// 1. DYNAMIC SQL CONSTRUCTION
$query = "SELECT category, purpose_details, amount FROM contributions WHERE status = 'completed'";
$params = [];
$types = "";

// Add Search Filter
if (!empty($search)) {
    $query .= " AND purpose_details LIKE ?"; // Make sure 'full_name' matches your DB column name
    $params[] = "%$search%";
    $types .= "s";
}

// Add Date Filters
if ($filter == 'sabbath') {
    $query .= " AND DATE(created_at) = CURDATE()"; 
} elseif ($filter == 'month') {
    $query .= " AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
} elseif ($filter == 'quarter') {
    $query .= " AND QUARTER(created_at) = QUARTER(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
} elseif ($filter == 'year') {
    $query .= " AND YEAR(created_at) = YEAR(CURRENT_DATE())";
}

// 2. PREPARED STATEMENT EXECUTION
$stmt = $db->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$fundTotals = [];
$categoryTotals = [];
$totalCollected = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $totalCollected += (float)$row['amount'];

        $catName = $row['category'];
        if (!isset($categoryTotals[$catName])) $categoryTotals[$catName] = 0;
        $categoryTotals[$catName] += (float)$row['amount'];

        $items = explode(", ", $row['purpose_details']);
        foreach ($items as $item) {
            if (preg_match('/^(.*) \(KES (.*)\)$/', $item, $matches)) {
                $fundName = trim($matches[1]);
                $fundAmount = (float)$matches[2];
                if (!isset($fundTotals[$fundName])) $fundTotals[$fundName] = 0;
                $fundTotals[$fundName] += $fundAmount;
            }
        }
    }
}
arsort($fundTotals);
arsort($categoryTotals);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Financial Analysis - Laiser Hill SDA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row align-items-center mb-4">
        <div class="col-lg-4">
            <h2 class="mb-0">📊 Fund Analysis</h2>
        </div>
        
        <div class="col-lg-4">
            <form method="GET" class="input-group">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <input type="text" name="search" class="form-control" 
                       placeholder="Search Catergory Name..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-primary" type="submit">Search</button>
                <?php if($search): ?>
                    <a href="?filter=<?php echo urlencode($filter); ?>" class="btn btn-outline-danger">✕</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="col-lg-4 text-end">
            <div class="btn-group">
                <?php 
                // FIXED: Changed hyphen to underscore in variable name
                $time_filters = [
                    'sabbath' => 'Sabbath', 
                    'month'   => 'Monthly', 
                    'quarter' => 'Quarterly', 
                    'year'    => 'Yearly', 
                    'all'     => 'All Time'
                ];
                foreach($time_filters as $key => $label): 
                ?>
                    <a href="?filter=<?php echo $key; ?>&search=<?php echo urlencode($search); ?>" 
                       class="btn btn-outline-primary <?php echo $filter == $key ? 'active' : ''; ?>">
                       <?php echo $label; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if($search): ?>
        <div class="alert alert-info py-2">
            Showing results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
            <?php if($totalCollected == 0) echo " — <span class='text-danger'>No records found.</span>"; ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4 bg-primary text-white">
                <div class="card-body">
                    <h6>Total Collected (<?php echo ucfirst($filter); ?>)</h6>
                    <h3>KES <?php echo number_format($totalCollected, 2); ?></h3>
                </div>
            </div>
            
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">Fund Distribution (%)</div>
                <div class="card-body">
                    <?php if(empty($fundTotals)) echo "<small class='text-muted'>No data available</small>"; ?>
                    <?php foreach ($fundTotals as $name => $amount): 
                        $percent = ($totalCollected > 0) ? ($amount / $totalCollected) * 100 : 0;
                    ?>
                        <small><?php echo htmlspecialchars($name); ?></small>
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fund Description</th>
                                <th class="text-end">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fundTotals as $name => $amount): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($name); ?></strong></td>
                                <td class="text-end">KES <?php echo number_format($amount, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($fundTotals)): ?>
                                <tr><td colspan="2" class="text-center py-4">No records found for this period.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white fw-bold">Analysis by Main Category</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead><tr><th>Category</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($categoryTotals as $cat => $val): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cat); ?></td>
                                <td class="text-end"><strong>KES <?php echo number_format($val, 2); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-success text-white fw-bold">Analysis by Individual Fund Item</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead><tr><th>Fund Item</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($fundTotals as $fund => $val): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fund); ?></td>
                                <td class="text-end"><strong>KES <?php echo number_format($val, 2); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
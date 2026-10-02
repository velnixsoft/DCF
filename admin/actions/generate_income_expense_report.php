<?php
// ============================================================
// admin/actions/generate_income_expense_report.php
// Generates JSON comparative data for Income (Donations) vs Expenses
// Supports Monthly, Yearly, Project-wise, and Category breakdowns with Chart data
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!checkRole($pdo, 'coordinator')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

// Retrieve Filter Parameters
$viewMode = cleanInput($_GET['view_mode'] ?? 'monthly');
$selectedYear = cleanInput($_GET['year'] ?? date('Y'));
$projectId = filter_input(INPUT_GET, 'project_id', FILTER_VALIDATE_INT) ?: null;
$expenseStatus = cleanInput($_GET['expense_status'] ?? 'approved_paid');
$customStart = cleanInput($_GET['custom_start'] ?? '');
$customEnd = cleanInput($_GET['custom_end'] ?? '');

// ── 1. EXPENSE STATUS CLAUSE ──────────────────────────────────
$expStatusClause = "";
if ($expenseStatus === 'approved_paid') {
    $expStatusClause = " AND e.approved_status IN ('Approved', 'Paid')";
} elseif ($expenseStatus === 'approved_only') {
    $expStatusClause = " AND e.approved_status = 'Approved'";
} elseif ($expenseStatus === 'paid_only') {
    $expStatusClause = " AND e.approved_status = 'Paid'";
} elseif ($expenseStatus === 'pending') {
    $expStatusClause = " AND e.approved_status = 'Pending'";
}

// ── 2. GLOBAL TOTALS & SUMMARY ────────────────────────────────
// Total Income (Donations)
$incomeSql = "SELECT COUNT(*) as tx_count, COALESCE(SUM(amount), 0) as total_amount FROM donations WHERE payment_status = 'Success'";
$incomeParams = [];

if ($projectId) {
    $incomeSql .= " AND project_id = ?";
    $incomeParams[] = $projectId;
}

if ($viewMode === 'monthly' && $selectedYear && $selectedYear !== 'all') {
    $incomeSql .= " AND YEAR(created_at) = ?";
    $incomeParams[] = $selectedYear;
} elseif ($viewMode === 'custom' && $customStart && $customEnd) {
    $incomeSql .= " AND DATE(created_at) BETWEEN ? AND ?";
    $incomeParams[] = $customStart;
    $incomeParams[] = $customEnd;
}

$incomeStmt = $pdo->prepare($incomeSql);
$incomeStmt->execute($incomeParams);
$incomeSummary = $incomeStmt->fetch(PDO::FETCH_ASSOC) ?: ['tx_count' => 0, 'total_amount' => 0];

// Total Expense
$expenseSql = "SELECT COUNT(*) as tx_count, COALESCE(SUM(e.amount), 0) as total_amount FROM expenses e WHERE 1=1" . $expStatusClause;
$expenseParams = [];

if ($projectId) {
    $expenseSql .= " AND e.project_id = ?";
    $expenseParams[] = $projectId;
}

if ($viewMode === 'monthly' && $selectedYear && $selectedYear !== 'all') {
    $expenseSql .= " AND YEAR(e.date) = ?";
    $expenseParams[] = $selectedYear;
} elseif ($viewMode === 'custom' && $customStart && $customEnd) {
    $expenseSql .= " AND DATE(e.date) BETWEEN ? AND ?";
    $expenseParams[] = $customStart;
    $expenseParams[] = $customEnd;
}

$expenseStmt = $pdo->prepare($expenseSql);
$expenseStmt->execute($expenseParams);
$expenseSummary = $expenseStmt->fetch(PDO::FETCH_ASSOC) ?: ['tx_count' => 0, 'total_amount' => 0];

$totIncome = (float)$incomeSummary['total_amount'];
$totExpense = (float)$expenseSummary['total_amount'];
$netBalance = $totIncome - $totExpense;
$operatingRatio = ($totIncome > 0) ? round(($totExpense / $totIncome) * 100, 1) : 0;
$savingsRate = ($totIncome > 0) ? round(($netBalance / $totIncome) * 100, 1) : 0;

// ── 3. TIME SERIES COMPARISON (Monthly / Yearly / Custom) ─────
$comparisonData = [];

if ($viewMode === 'monthly') {
    // 12 Months of the selected year
    $targetYear = ($selectedYear && $selectedYear !== 'all') ? (int)$selectedYear : (int)date('Y');
    
    // Fetch Monthly Income for target year
    $mIncSql = "SELECT MONTH(created_at) as m_num, COALESCE(SUM(amount), 0) as m_income, COUNT(*) as m_count 
                FROM donations 
                WHERE payment_status = 'Success' AND YEAR(created_at) = ?";
    $mIncParams = [$targetYear];
    if ($projectId) {
        $mIncSql .= " AND project_id = ?";
        $mIncParams[] = $projectId;
    }
    $mIncSql .= " GROUP BY MONTH(created_at)";
    $mIncStmt = $pdo->prepare($mIncSql);
    $mIncStmt->execute($mIncParams);
    $monthlyIncomeMap = [];
    while ($row = $mIncStmt->fetch(PDO::FETCH_ASSOC)) {
        $monthlyIncomeMap[(int)$row['m_num']] = (float)$row['m_income'];
    }

    // Fetch Monthly Expense for target year
    $mExpSql = "SELECT MONTH(date) as m_num, COALESCE(SUM(amount), 0) as m_expense, COUNT(*) as m_count 
                FROM expenses e 
                WHERE 1=1 {$expStatusClause} AND YEAR(date) = ?";
    $mExpParams = [$targetYear];
    if ($projectId) {
        $mExpSql .= " AND e.project_id = ?";
        $mExpParams[] = $projectId;
    }
    $mExpSql .= " GROUP BY MONTH(date)";
    $mExpStmt = $pdo->prepare($mExpSql);
    $mExpStmt->execute($mExpParams);
    $monthlyExpenseMap = [];
    while ($row = $mExpStmt->fetch(PDO::FETCH_ASSOC)) {
        $monthlyExpenseMap[(int)$row['m_num']] = (float)$row['m_expense'];
    }

    $monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    
    for ($m = 1; $m <= 12; $m++) {
        $inc = $monthlyIncomeMap[$m] ?? 0.0;
        $exp = $monthlyExpenseMap[$m] ?? 0.0;
        $net = $inc - $exp;
        $marginPct = ($inc > 0) ? round(($net / $inc) * 100, 1) : (($exp > 0) ? -100.0 : 0.0);

        $comparisonData[] = [
            'period_key' => sprintf('%04d-%02d', $targetYear, $m),
            'period_label' => $monthNames[$m - 1] . ' ' . $targetYear,
            'short_label' => substr($monthNames[$m - 1], 0, 3),
            'income' => $inc,
            'expense' => $exp,
            'net_balance' => $net,
            'status' => ($net >= 0) ? 'Surplus' : 'Deficit',
            'margin_percentage' => $marginPct
        ];
    }

} elseif ($viewMode === 'yearly') {
    // Multi-Year Comparison
    // Get unique years across donations and expenses
    $yearsSql = "SELECT DISTINCT yr FROM (
        SELECT YEAR(created_at) as yr FROM donations WHERE payment_status = 'Success'
        UNION 
        SELECT YEAR(date) as yr FROM expenses WHERE 1=1 {$expStatusClause}
    ) all_years ORDER BY yr ASC";
    
    $availableYears = $pdo->query($yearsSql)->fetchAll(PDO::FETCH_COLUMN);
    if (empty($availableYears)) {
        $availableYears = [(int)date('Y')];
    }

    // Yearly Income Map
    $yIncSql = "SELECT YEAR(created_at) as yr, COALESCE(SUM(amount), 0) as y_income FROM donations WHERE payment_status = 'Success'";
    $yIncParams = [];
    if ($projectId) {
        $yIncSql .= " AND project_id = ?";
        $yIncParams[] = $projectId;
    }
    $yIncSql .= " GROUP BY YEAR(created_at)";
    $yIncStmt = $pdo->prepare($yIncSql);
    $yIncStmt->execute($yIncParams);
    $yearlyIncomeMap = $yIncStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Yearly Expense Map
    $yExpSql = "SELECT YEAR(date) as yr, COALESCE(SUM(amount), 0) as y_expense FROM expenses e WHERE 1=1 {$expStatusClause}";
    $yExpParams = [];
    if ($projectId) {
        $yExpSql .= " AND e.project_id = ?";
        $yExpParams[] = $projectId;
    }
    $yExpSql .= " GROUP BY YEAR(date)";
    $yExpStmt = $pdo->prepare($yExpSql);
    $yExpStmt->execute($yExpParams);
    $yearlyExpenseMap = $yExpStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($availableYears as $yr) {
        $yr = (int)$yr;
        $inc = (float)($yearlyIncomeMap[$yr] ?? 0.0);
        $exp = (float)($yearlyExpenseMap[$yr] ?? 0.0);
        $net = $inc - $exp;
        $marginPct = ($inc > 0) ? round(($net / $inc) * 100, 1) : (($exp > 0) ? -100.0 : 0.0);

        $comparisonData[] = [
            'period_key' => (string)$yr,
            'period_label' => 'Year ' . $yr,
            'short_label' => (string)$yr,
            'income' => $inc,
            'expense' => $exp,
            'net_balance' => $net,
            'status' => ($net >= 0) ? 'Surplus' : 'Deficit',
            'margin_percentage' => $marginPct
        ];
    }

} else {
    // Custom Date Range
    $comparisonData[] = [
        'period_key' => 'custom',
        'period_label' => ($customStart && $customEnd) ? (date('d M Y', strtotime($customStart)) . ' - ' . date('d M Y', strtotime($customEnd))) : 'Custom Period',
        'short_label' => 'Custom',
        'income' => $totIncome,
        'expense' => $totExpense,
        'net_balance' => $netBalance,
        'status' => ($netBalance >= 0) ? 'Surplus' : 'Deficit',
        'margin_percentage' => $savingsRate
    ];
}

// ── 4. EXPENSE CATEGORY BREAKDOWN ─────────────────────────────
$catSql = "SELECT 
    c.id as category_id,
    c.category_name,
    c.icon as category_icon,
    COUNT(e.id) as expense_count,
    COALESCE(SUM(e.amount), 0) as total_amount
    FROM expense_categories c
    LEFT JOIN expenses e ON c.id = e.category_id {$expStatusClause}";
$catParams = [];

if ($projectId) {
    $catSql .= " AND e.project_id = ?";
    $catParams[] = $projectId;
}
if ($viewMode === 'monthly' && $selectedYear && $selectedYear !== 'all') {
    $catSql .= " AND YEAR(e.date) = ?";
    $catParams[] = $selectedYear;
} elseif ($viewMode === 'custom' && $customStart && $customEnd) {
    $catSql .= " AND DATE(e.date) BETWEEN ? AND ?";
    $catParams[] = $customStart;
    $catParams[] = $customEnd;
}

$catSql .= " GROUP BY c.id, c.category_name, c.icon ORDER BY total_amount DESC";

$catStmt = $pdo->prepare($catSql);
$catStmt->execute($catParams);
$categoryRows = $catStmt->fetchAll(PDO::FETCH_ASSOC);

$categoryBreakdown = [];
foreach ($categoryRows as $cat) {
    $catAmt = (float)$cat['total_amount'];
    $catPct = ($totExpense > 0) ? round(($catAmt / $totExpense) * 100, 1) : 0.0;
    $categoryBreakdown[] = [
        'category_id' => (int)$cat['category_id'],
        'category_name' => $cat['category_name'],
        'category_icon' => $cat['category_icon'] ?: 'fa-receipt',
        'expense_count' => (int)$cat['expense_count'],
        'total_amount' => $catAmt,
        'percentage' => $catPct
    ];
}

// ── 5. PROJECT COMPARISON BREAKDOWN ───────────────────────────
$projSql = "SELECT 
    p.id as project_id,
    p.title as project_title,
    COALESCE((SELECT SUM(d.amount) FROM donations d WHERE d.project_id = p.id AND d.payment_status = 'Success'), 0) as total_income,
    COALESCE((SELECT SUM(e.amount) FROM expenses e WHERE e.project_id = p.id {$expStatusClause}), 0) as total_expense
    FROM projects p
    ORDER BY total_income DESC, total_expense DESC";

$projRows = $pdo->query($projSql)->fetchAll(PDO::FETCH_ASSOC);
$projectBreakdown = [];
foreach ($projRows as $pr) {
    $pInc = (float)$pr['total_income'];
    $pExp = (float)$pr['total_expense'];
    $pNet = $pInc - $pExp;
    $projectBreakdown[] = [
        'project_id' => (int)$pr['project_id'],
        'project_title' => $pr['project_title'],
        'income' => $pInc,
        'expense' => $pExp,
        'net_balance' => $pNet,
        'status' => ($pNet >= 0) ? 'Surplus' : 'Deficit'
    ];
}

// ── 6. RETURN STRUCTURED RESPONSE ─────────────────────────────
echo json_encode([
    'success' => true,
    'summary' => [
        'totalIncome' => $totIncome,
        'totalExpense' => $totExpense,
        'netBalance' => $netBalance,
        'operatingRatio' => $operatingRatio,
        'savingsRate' => $savingsRate,
        'incomeTxCount' => (int)$incomeSummary['tx_count'],
        'expenseTxCount' => (int)$expenseSummary['tx_count'],
        'financialStatus' => ($netBalance >= 0) ? 'Surplus' : 'Deficit'
    ],
    'comparison_data' => $comparisonData,
    'category_breakdown' => $categoryBreakdown,
    'project_breakdown' => $projectBreakdown,
    'chart_config' => [
        'labels' => array_column($comparisonData, 'short_label'),
        'income_data' => array_column($comparisonData, 'income'),
        'expense_data' => array_column($comparisonData, 'expense'),
        'net_data' => array_column($comparisonData, 'net_balance')
    ],
    'filters_applied' => [
        'view_mode' => $viewMode,
        'year' => $selectedYear,
        'project_id' => $projectId,
        'expense_status' => $expenseStatus,
        'custom_start' => $customStart,
        'custom_end' => $customEnd
    ]
], JSON_UNESCAPED_UNICODE);

<?php
// ============================================================
// admin/actions/export_income_expense_excel.php
// Exports Income vs Expense Comparative Statements as Excel (.xls)
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    exit;
}
if (!checkRole($pdo, 'coordinator')) {
    http_response_code(403);
    exit;
}

$viewMode = cleanInput($_GET['view_mode'] ?? 'monthly');
$selectedYear = cleanInput($_GET['year'] ?? date('Y'));
$projectId = filter_input(INPUT_GET, 'project_id', FILTER_VALIDATE_INT) ?: null;
$expenseStatus = cleanInput($_GET['expense_status'] ?? 'approved_paid');
$customStart = cleanInput($_GET['custom_start'] ?? '');
$customEnd = cleanInput($_GET['custom_end'] ?? '');

$siteName = (string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='site_name'")->fetchColumn() ?: 'NGO System');

$expStatusClause = "";
if ($expenseStatus === 'approved_paid') {
    $expStatusClause = " AND e.approved_status IN ('Approved', 'Paid')";
} elseif ($expenseStatus === 'approved_only') {
    $expStatusClause = " AND e.approved_status = 'Approved'";
} elseif ($expenseStatus === 'paid_only') {
    $expStatusClause = " AND e.approved_status = 'Paid'";
}

// ── 1. COMPARISON DATA COMPUTATION ────────────────────────────
$comparisonRows = [];
$totIncome = 0.0;
$totExpense = 0.0;

if ($viewMode === 'monthly') {
    $targetYear = ($selectedYear && $selectedYear !== 'all') ? (int)$selectedYear : (int)date('Y');
    
    // Monthly Income
    $mIncSql = "SELECT MONTH(created_at) as m_num, COALESCE(SUM(amount), 0) as m_income FROM donations WHERE payment_status = 'Success' AND YEAR(created_at) = ?";
    $mIncParams = [$targetYear];
    if ($projectId) { $mIncSql .= " AND project_id = ?"; $mIncParams[] = $projectId; }
    $mIncSql .= " GROUP BY MONTH(created_at)";
    $mIncStmt = $pdo->prepare($mIncSql);
    $mIncStmt->execute($mIncParams);
    $incMap = $mIncStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Monthly Expense
    $mExpSql = "SELECT MONTH(date) as m_num, COALESCE(SUM(amount), 0) as m_expense FROM expenses e WHERE 1=1 {$expStatusClause} AND YEAR(date) = ?";
    $mExpParams = [$targetYear];
    if ($projectId) { $mExpSql .= " AND e.project_id = ?"; $mExpParams[] = $projectId; }
    $mExpSql .= " GROUP BY MONTH(date)";
    $mExpStmt = $pdo->prepare($mExpSql);
    $mExpStmt->execute($mExpParams);
    $expMap = $mExpStmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    for ($m = 1; $m <= 12; $m++) {
        $inc = (float)($incMap[$m] ?? 0);
        $exp = (float)($expMap[$m] ?? 0);
        $net = $inc - $exp;
        $totIncome += $inc;
        $totExpense += $exp;
        $comparisonRows[] = [
            'period' => $monthNames[$m - 1] . ' ' . $targetYear,
            'income' => $inc,
            'expense' => $exp,
            'net' => $net,
            'margin' => ($inc > 0) ? round(($net / $inc) * 100, 1) : (($exp > 0) ? -100.0 : 0.0),
            'status' => ($net >= 0) ? 'Surplus' : 'Deficit'
        ];
    }
} else {
    // Yearly
    $yearsSql = "SELECT DISTINCT yr FROM (
        SELECT YEAR(created_at) as yr FROM donations WHERE payment_status = 'Success'
        UNION 
        SELECT YEAR(date) as yr FROM expenses WHERE 1=1 {$expStatusClause}
    ) all_years ORDER BY yr ASC";
    $years = $pdo->query($yearsSql)->fetchAll(PDO::FETCH_COLUMN) ?: [(int)date('Y')];

    $yIncSql = "SELECT YEAR(created_at) as yr, COALESCE(SUM(amount), 0) as y_income FROM donations WHERE payment_status = 'Success' GROUP BY YEAR(created_at)";
    $yIncMap = $pdo->query($yIncSql)->fetchAll(PDO::FETCH_KEY_PAIR);

    $yExpSql = "SELECT YEAR(date) as yr, COALESCE(SUM(amount), 0) as y_expense FROM expenses e WHERE 1=1 {$expStatusClause} GROUP BY YEAR(date)";
    $yExpMap = $pdo->query($yExpSql)->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($years as $yr) {
        $inc = (float)($yIncMap[$yr] ?? 0);
        $exp = (float)($yExpMap[$yr] ?? 0);
        $net = $inc - $exp;
        $totIncome += $inc;
        $totExpense += $exp;
        $comparisonRows[] = [
            'period' => 'Year ' . $yr,
            'income' => $inc,
            'expense' => $exp,
            'net' => $net,
            'margin' => ($inc > 0) ? round(($net / $inc) * 100, 1) : (($exp > 0) ? -100.0 : 0.0),
            'status' => ($net >= 0) ? 'Surplus' : 'Deficit'
        ];
    }
}

$netTotal = $totIncome - $totExpense;

// ── 2. EXPENSE CATEGORY AGGREGATES ────────────────────────────
$catSql = "SELECT c.category_name, COALESCE(SUM(e.amount), 0) as total_expense 
           FROM expense_categories c 
           LEFT JOIN expenses e ON c.id = e.category_id {$expStatusClause}
           GROUP BY c.id, c.category_name ORDER BY total_expense DESC";
$categoryRows = $pdo->query($catSql)->fetchAll(PDO::FETCH_ASSOC);

// Output Excel headers
$filename = 'income_vs_expense_report_' . $viewMode . '_' . date('Y-m-d') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo "<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">";
echo "<head><meta charset=\"UTF-8\"><style>
    body { font-family: Calibri, Arial, sans-serif; }
    .title { font-size: 16pt; font-weight: bold; color: #0F8B8D; }
    .sub { font-size: 10pt; color: #64748b; margin-bottom: 12px; }
    .kpi-table { margin-bottom: 15px; border-collapse: collapse; }
    .kpi-card { padding: 8px 12px; border: 1px solid #cbd5e1; background-color: #f8fafc; font-size: 10pt; }
    table { border-collapse: collapse; width: 100%; margin-top: 10px; margin-bottom: 20px; }
    th { background-color: #0F8B8D; color: #ffffff; font-weight: bold; padding: 8px 10px; border: 1px solid #0c7274; text-align: left; font-size: 10pt; }
    td { padding: 6px 10px; border: 1px solid #e2e8f0; font-size: 9.5pt; vertical-align: middle; }
    .num { text-align: right; mso-number-format: '0.00'; }
    .center { text-align: center; }
    .surplus { color: #16a34a; font-weight: bold; }
    .deficit { color: #dc2626; font-weight: bold; }
    .total-row { background-color: #f1f5f9; font-weight: bold; border-top: 2px solid #0F8B8D; }
</style></head><body>";

echo "<div class='title'>" . htmlspecialchars(strtoupper($siteName)) . "</div>";
echo "<div class='sub'>Financial Analysis: Income (Donations) vs Operational Expenses Statement</div>";

echo "<table class='kpi-table'><tr>";
echo "<td class='kpi-card'><strong>Total Income:</strong> ₹" . number_format($totIncome, 2) . "</td>";
echo "<td class='kpi-card'><strong>Total Expenses:</strong> ₹" . number_format($totExpense, 2) . "</td>";
echo "<td class='kpi-card'><strong>Net Cashflow:</strong> <span class='" . ($netTotal >= 0 ? 'surplus' : 'deficit') . "'>₹" . number_format($netTotal, 2) . " (" . ($netTotal >= 0 ? 'Surplus' : 'Deficit') . ")</span></td>";
echo "<td class='kpi-card'><strong>Operating Ratio:</strong> " . (($totIncome > 0) ? round(($totExpense / $totIncome) * 100, 1) : 0) . "%</td>";
echo "</tr></table>";

// Main Comparative Table
echo "<table>";
echo "<thead><tr>
    <th class='center'>#</th>
    <th>Period (" . ucfirst($viewMode) . ")</th>
    <th class='num'>Total Income / Donations (₹)</th>
    <th class='num'>Operational Expenses (₹)</th>
    <th class='num'>Net Balance / Cashflow (₹)</th>
    <th class='center'>Net Margin %</th>
    <th class='center'>Status</th>
</tr></thead><tbody>";

foreach ($comparisonRows as $idx => $r) {
    echo "<tr>";
    echo "<td class='center'>" . ($idx + 1) . "</td>";
    echo "<td><strong>" . htmlspecialchars($r['period']) . "</strong></td>";
    echo "<td class='num'>" . number_format($r['income'], 2, '.', '') . "</td>";
    echo "<td class='num'>" . number_format($r['expense'], 2, '.', '') . "</td>";
    echo "<td class='num " . ($r['net'] >= 0 ? 'surplus' : 'deficit') . "'>" . number_format($r['net'], 2, '.', '') . "</td>";
    echo "<td class='center'>" . $r['margin'] . "%</td>";
    echo "<td class='center " . ($r['status'] === 'Surplus' ? 'surplus' : 'deficit') . "'>" . $r['status'] . "</td>";
    echo "</tr>";
}

echo "<tr class='total-row'>";
echo "<td colspan='2' class='center'>ANNUAL TOTAL CONSOLIDATED</td>";
echo "<td class='num'>₹" . number_format($totIncome, 2, '.', '') . "</td>";
echo "<td class='num'>₹" . number_format($totExpense, 2, '.', '') . "</td>";
echo "<td class='num " . ($netTotal >= 0 ? 'surplus' : 'deficit') . "'>₹" . number_format($netTotal, 2, '.', '') . "</td>";
echo "<td class='center'>" . (($totIncome > 0) ? round(($netTotal / $totIncome) * 100, 1) : 0) . "%</td>";
echo "<td class='center " . ($netTotal >= 0 ? 'surplus' : 'deficit') . "'>" . ($netTotal >= 0 ? 'SURPLUS' : 'DEFICIT') . "</td>";
echo "</tr>";
echo "</tbody></table>";

// Category Breakdown Table
echo "<h4 style='font-family: Arial; margin-top: 20px; color: #0F8B8D;'>Expense Category Distribution</h4>";
echo "<table>";
echo "<thead><tr>
    <th class='center'>#</th>
    <th>Category Name</th>
    <th class='num'>Total Expense (₹)</th>
    <th class='center'>% of Total Expenditure</th>
</tr></thead><tbody>";

foreach ($categoryRows as $idx => $cat) {
    $catAmt = (float)$cat['total_expense'];
    $catPct = ($totExpense > 0) ? round(($catAmt / $totExpense) * 100, 1) : 0.0;
    echo "<tr>";
    echo "<td class='center'>" . ($idx + 1) . "</td>";
    echo "<td>" . htmlspecialchars($cat['category_name']) . "</td>";
    echo "<td class='num'>" . number_format($catAmt, 2, '.', '') . "</td>";
    echo "<td class='center'>" . $catPct . "%</td>";
    echo "</tr>";
}
echo "</tbody></table>";

echo "</body></html>";

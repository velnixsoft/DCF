<?php require 'includes/header.php'; ?>
<?php require '../config/db.php'; ?>

<?php
if (!checkRole($pdo, 'manager')) {
    setFlash('error', 'Unauthorized access.');
    header('Location: dashboard.php');
    exit;
}

// 1. Overall stats
$statsStmt = $pdo->query("
    SELECT SUM(amount) as total_amt, 
           COUNT(*) as count_donations, 
           COUNT(DISTINCT sa_student_id) as count_students 
    FROM donations 
    WHERE sa_student_id IS NOT NULL AND payment_status = 'Success'
");
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
$totalAmount = (float)($stats['total_amt'] ?? 0.0);
$totalDonationsCount = (int)($stats['count_donations'] ?? 0);
$totalAmbassadorsCount = (int)($stats['count_students'] ?? 0);
$averageCollection = $totalAmbassadorsCount > 0 ? ($totalAmount / $totalAmbassadorsCount) : 0.0;

// 2. City-wise Breakdown
$cityBreakdown = $pdo->query("
    SELECT IFNULL(collection_city, 'Unknown') as city, 
           SUM(amount) as total_amount, 
           COUNT(*) as count_donations
    FROM donations
    WHERE sa_student_id IS NOT NULL AND payment_status = 'Success'
    GROUP BY collection_city
    ORDER BY total_amount DESC
")->fetchAll(PDO::FETCH_ASSOC);

// 3. Intern Leaderboard
$internLeaderboard = $pdo->query("
    SELECT s.full_name, s.student_no, s.city_name, 
           SUM(d.amount) as total_collected, 
           COUNT(d.id) as donations_count
    FROM donations d
    JOIN sa_students s ON d.sa_student_id = s.id
    WHERE d.payment_status = 'Success'
    GROUP BY d.sa_student_id
    ORDER BY total_collected DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// 4. Recent Ambassador Donations
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$totalRecent = (int)$pdo->query("SELECT COUNT(*) FROM donations d JOIN sa_students s ON d.sa_student_id = s.id")->fetchColumn();

$recentStmt = $pdo->prepare("
    SELECT d.*, s.full_name as student_name, s.student_no
    FROM donations d
    JOIN sa_students s ON d.sa_student_id = s.id
    ORDER BY d.created_at DESC
    LIMIT :limit OFFSET :offset
");
$recentStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$recentStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$recentStmt->execute();
$recentDonations = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="qrTracker">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            
            <div class="flex flex-col gap-4 mb-6">
                <div>
                    <h3 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Ambassador QR Donation Sync</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Track donation amounts collected city-wise and intern-wise via tracking QR codes.</p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Ambassador Collections</p>
                    <h3 class="text-2xl font-bold text-emerald-600 dark:text-emerald-450 mt-2">₹<?php echo number_format($totalAmount, 2); ?></h3>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Transactions</p>
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-white mt-2"><?php echo $totalDonationsCount; ?></h3>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Active Fundraising Interns</p>
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-white mt-2"><?php echo $totalAmbassadorsCount; ?></h3>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Average Raised Per Intern</p>
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-white mt-2">₹<?php echo number_format($averageCollection, 2); ?></h3>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- City-wise Breakdown Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border dark:border-gray-700 lg:col-span-2 flex flex-col">
                    <h4 class="font-bold text-gray-800 dark:text-white mb-4">City-wise Donations Performance</h4>
                    <div class="relative h-64 flex-1">
                        <canvas id="cityChart"></canvas>
                    </div>
                </div>

                <!-- Top fundraising breakdown -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border dark:border-gray-700 flex flex-col">
                    <h4 class="font-bold text-gray-800 dark:text-white mb-4">Top Fundraising Interns</h4>
                    <div class="space-y-4 overflow-y-auto flex-1 max-h-[16rem]">
                        <?php foreach ($internLeaderboard as $i => $row): ?>
                            <div class="flex items-center justify-between border-b pb-2 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded bg-emerald-50 text-emerald-700 font-bold flex items-center justify-center">
                                        <?php echo $i + 1; ?>
                                    </span>
                                    <div>
                                        <p class="font-bold text-gray-800 dark:text-white"><?php echo htmlspecialchars($row['full_name']); ?></p>
                                        <p class="text-[10px] text-gray-400 font-mono"><?php echo htmlspecialchars($row['student_no']); ?> • <?php echo htmlspecialchars($row['city_name']); ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-emerald-700">₹<?php echo number_format($row['total_collected'], 2); ?></p>
                                    <p class="text-[10px] text-gray-450 mt-0.5"><?php echo $row['donations_count']; ?> payments</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($internLeaderboard)) echo '<p class="text-center text-gray-400 py-6">No collection reports recorded yet.</p>'; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Collections List -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow overflow-hidden">
                <div class="p-4 border-b">
                    <h4 class="font-bold text-gray-800 dark:text-white">Recent Ambassador Payments Log</h4>
                </div>
                <div class="divide-y dark:divide-gray-700 lg:hidden">
                    <?php foreach ($recentDonations as $don): ?>
                        <div class="p-4 space-y-2">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($don['donor_name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($don['student_name']); ?> • <?php echo htmlspecialchars($don['student_no']); ?></p>
                                </div>
                                <p class="font-black text-emerald-700">₹<?php echo number_format($don['amount'], 2); ?></p>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500">
                                <span><?php echo htmlspecialchars($don['collection_city'] ?: '-'); ?></span>
                                <span><?php echo htmlspecialchars($don['payment_gateway']); ?></span>
                                <span><?php echo date('d-m-Y H:i', strtotime($don['created_at'])); ?></span>
                            </div>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider <?php echo $don['payment_status'] === 'Success' ? 'bg-green-50 text-green-700' : ($don['payment_status'] === 'Pending' ? 'bg-orange-50 text-orange-700' : 'bg-red-50 text-red-700'); ?>">
                                <?php echo $don['payment_status']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($recentDonations)) echo '<div class="p-8 text-center text-gray-400 font-medium">No tracking donations recorded yet.</div>'; ?>
                </div>
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase">
                            <tr>
                                <th class="p-4">Donor Name</th>
                                <th class="p-4">Collecting Intern</th>
                                <th class="p-4">City</th>
                                <th class="p-4">Amount</th>
                                <th class="p-4">Payment Method</th>
                                <th class="p-4">Date</th>
                                <th class="p-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <?php foreach ($recentDonations as $don): ?>
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition">
                                    <td class="p-4 font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($don['donor_name']); ?></td>
                                    <td class="p-4 text-xs">
                                        <p class="font-semibold text-gray-800 dark:text-white"><?php echo htmlspecialchars($don['student_name']); ?></p>
                                        <p class="text-[10px] text-gray-500 font-mono"><?php echo htmlspecialchars($don['student_no']); ?></p>
                                    </td>
                                    <td class="p-4 text-xs font-semibold text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($don['collection_city'] ?: '—'); ?></td>
                                    <td class="p-4 font-black text-emerald-700">₹<?php echo number_format($don['amount'], 2); ?></td>
                                    <td class="p-4 text-xs"><?php echo htmlspecialchars($don['payment_gateway']); ?></td>
                                    <td class="p-4 text-xs font-mono"><?php echo date('d-m-Y H:i', strtotime($don['created_at'])); ?></td>
                                    <td class="p-4">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider 
                                            <?php echo $don['payment_status'] === 'Success' ? 'bg-green-50 text-green-700' : ($don['payment_status'] === 'Pending' ? 'bg-orange-50 text-orange-700' : 'bg-red-50 text-red-700'); ?>">
                                            <?php echo $don['payment_status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentDonations)) echo '<tr><td colspan="7" class="p-8 text-center text-gray-400 font-medium">No tracking donations recorded yet.</td></tr>'; ?>
                        </tbody>
                    </table>
                </div>
                <?php echo render_admin_pagination($totalRecent, $page, $perPage); ?>
            </div>

        </main>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('qrTracker', () => ({
        // Simple UI controls
    }));
});

// Render City Breakdown Chart
const cityLabels = <?php echo json_encode(array_column($cityBreakdown, 'city')); ?>;
const cityData = <?php echo json_encode(array_column($cityBreakdown, 'total_amount')); ?>;

const ctx = document.getElementById('cityChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: cityLabels,
        datasets: [{
            label: 'Funds Collected (₹)',
            data: cityData,
            backgroundColor: 'rgba(16, 185, 129, 0.65)',
            borderColor: 'rgb(16, 185, 129)',
            borderWidth: 1.5,
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f3f4f6'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});
</script>

<?php require 'includes/footer.php'; ?>

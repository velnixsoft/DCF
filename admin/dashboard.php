<?php
require 'includes/header.php';
require '../config/db.php';

$stmt = $pdo->query("SELECT SUM(amount) FROM donations WHERE payment_status = 'Success'");
$totalDonation = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT COUNT(*) FROM volunteers WHERE status = 'Active'");
$activeVolunteers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM volunteers WHERE status = 'Pending'");
$pendingVolunteers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM projects");
$totalProjects = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'New'");
$newMessages = $stmt->fetchColumn();

$months = [];
$amounts = [];
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    $monthName = date('M', strtotime("-$i months"));

    $stmt = $pdo->prepare("SELECT SUM(amount) FROM donations WHERE payment_status='Success' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$date]);
    $sum = $stmt->fetchColumn() ?: 0;

    $months[] = $monthName;
    $amounts[] = $sum;
}

$recentDonations = $pdo->query("SELECT * FROM donations WHERE payment_status='Success' ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$recentVolunteers = $pdo->query("SELECT * FROM volunteers WHERE status='Pending' ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$todayBirthdays = $pdo->query("SELECT full_name, member_no FROM members WHERE status='Active' AND dob IS NOT NULL AND DATE_FORMAT(dob, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d') ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$pendingStudents = 0;
$pendingReferrals = 0;
$pendingVendorLeads = 0;
$pendingSubmissions = 0;
$pendingNotificationEmails = 0;

try {
    $pendingStudents = (int)$pdo->query("SELECT COUNT(*) FROM sa_students WHERE status = 'Pending'")->fetchColumn();
} catch (Throwable $e) {}
try {
    $pendingReferrals = (int)$pdo->query("SELECT COUNT(*) FROM sa_referrals WHERE verification_status = 'pending' AND deleted_at IS NULL")->fetchColumn();
} catch (Throwable $e) {}
try {
    $pendingVendorLeads = (int)$pdo->query("SELECT COUNT(*) FROM sa_vendor_leads WHERE verification_status = 'pending'")->fetchColumn();
} catch (Throwable $e) {}
try {
    $pendingSubmissions = (int)$pdo->query("SELECT COUNT(*) FROM sa_task_submissions WHERE status = 'Pending'")->fetchColumn();
} catch (Throwable $e) {}
try {
    $pendingNotificationEmails = (int)$pdo->query("SELECT COUNT(*) FROM sa_notifications WHERE email_sent = 0 AND email_retry_count < 5")->fetchColumn();
} catch (Throwable $e) {}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="{ sidebarOpen: false }">

    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">

            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-800 dark:text-white">Dashboard Overview</h2>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Welcome back, Admin! Here's what's happening today.</p>
            </div>

           

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Total Raised</p>
                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">₹<?php echo number_format($totalDonation); ?></h3>
                    </div>
                    <div class="absolute right-4 top-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-full text-green-600 dark:text-green-400 group-hover:scale-110 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Volunteers</p>
                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white mt-1"><?php echo $activeVolunteers; ?> <span class="text-sm font-normal text-gray-400">Active</span></h3>
                        <?php if ($pendingVolunteers > 0): ?>
                            <a href="volunteers.php?status=Pending" class="text-xs text-orange-500 hover:underline mt-1 block">⚠️ <?php echo $pendingVolunteers; ?> Pending Requests</a>
                        <?php endif; ?>
                    </div>
                    <div class="absolute right-4 top-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-full text-blue-600 dark:text-blue-400 group-hover:scale-110 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Projects</p>
                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white mt-1"><?php echo $totalProjects; ?></h3>
                    </div>
                    <div class="absolute right-4 top-4 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-full text-purple-600 dark:text-purple-400 group-hover:scale-110 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden group">
                    <div class="relative z-10">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Messages</p>
                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white mt-1"><?php echo $newMessages; ?> <span class="text-sm font-normal text-gray-400">New</span></h3>
                    </div>
                    <div class="absolute right-4 top-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-full text-red-600 dark:text-red-400 group-hover:scale-110 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">

                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border dark:border-gray-700 lg:col-span-2">
                    <h4 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Donation Trend (Last 6 Months)</h4>
                    <div class="relative h-64 w-full">
                        <canvas id="donationChart"></canvas>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border dark:border-gray-700">
                    <h4 class="text-lg font-bold text-gray-800 dark:text-white mb-4">Quick Actions</h4>
                    <div class="space-y-3">
                        <a href="donations.php?action=add" class="flex items-center p-3 bg-blue-50 dark:bg-gray-700 rounded-lg hover:bg-blue-100 dark:hover:bg-gray-600 transition group">
                            <div class="p-2 bg-blue-600 text-white rounded-lg mr-3 group-hover:scale-110 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold text-gray-800 dark:text-white text-sm">Add Donation</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Record offline payment</p>
                            </div>
                        </a>

                        <a href="projects.php?action=create" class="flex items-center p-3 bg-purple-50 dark:bg-gray-700 rounded-lg hover:bg-purple-100 dark:hover:bg-gray-600 transition group">
                            <div class="p-2 bg-purple-600 text-white rounded-lg mr-3 group-hover:scale-110 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold text-gray-800 dark:text-white text-sm">New Project</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Start a campaign</p>
                            </div>
                        </a>

                        <a href="sponsor_manager.php?action=add" class="flex items-center p-3 bg-green-50 dark:bg-gray-700 rounded-lg hover:bg-green-100 dark:hover:bg-gray-600 transition group">
                            <div class="p-2 bg-green-600 text-white rounded-lg mr-3 group-hover:scale-110 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold text-gray-800 dark:text-white text-sm">Add Partner</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Update sponsors</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border dark:border-gray-700 overflow-hidden">
                    <div class="p-4 border-b dark:border-gray-700 flex justify-between items-center">
                        <h4 class="font-bold text-gray-800 dark:text-white">Recent Donations</h4>
                        <a href="donations.php" class="text-xs text-blue-600 hover:underline">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="p-3 font-medium">Donor</th>
                                    <th class="p-3 font-medium">Amount</th>
                                    <th class="p-3 font-medium text-right">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <?php foreach ($recentDonations as $d): ?>
                                    <tr>
                                        <td class="p-3"><?php echo htmlspecialchars($d['donor_name']); ?></td>
                                        <td class="p-3 font-bold text-green-600">₹<?php echo number_format($d['amount']); ?></td>
                                        <td class="p-3 text-right text-xs text-gray-500"><?php echo date('M d', strtotime($d['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentDonations)) echo '<tr><td colspan="3" class="p-4 text-center">No recent donations</td></tr>'; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border dark:border-gray-700 overflow-hidden">
                    <div class="p-4 border-b dark:border-gray-700 flex justify-between items-center">
                        <h4 class="font-bold text-gray-800 dark:text-white">Pending Volunteers</h4>
                        <a href="volunteers.php?status=Pending" class="text-xs text-blue-600 hover:underline">Manage</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="p-3 font-medium">Name</th>
                                    <th class="p-3 font-medium">Phone</th>
                                    <th class="p-3 font-medium text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <?php foreach ($recentVolunteers as $v): ?>
                                    <tr>
                                        <td class="p-3 flex items-center gap-2">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($v['name']); ?>&size=24" class="rounded-full">
                                            <?php echo htmlspecialchars($v['name']); ?>
                                        </td>
                                        <td class="p-3"><?php echo $v['phone']; ?></td>
                                        <td class="p-3 text-right">
                                            <a href="volunteers.php?status=Pending&id=<?php echo $v['id']; ?>" class="bg-blue-100 text-blue-600 px-2 py-1 rounded text-xs">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentVolunteers)) echo '<tr><td colspan="3" class="p-4 text-center text-gray-500">No pending requests</td></tr>'; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border dark:border-gray-700 overflow-hidden">
                    <div class="p-4 border-b dark:border-gray-700 flex justify-between items-center">
                        <h4 class="font-bold text-gray-800 dark:text-white">Today's Member Birthdays</h4>
                        <a href="member_messages.php" class="text-xs text-blue-600 hover:underline">Send Wishes</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="p-3 font-medium">Name</th>
                                    <th class="p-3 font-medium text-right">Member No</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <?php foreach ($todayBirthdays as $b): ?>
                                    <tr>
                                        <td class="p-3"><?php echo htmlspecialchars($b['full_name']); ?></td>
                                        <td class="p-3 text-right text-xs text-gray-500"><?php echo htmlspecialchars($b['member_no']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($todayBirthdays)) echo '<tr><td colspan="2" class="p-4 text-center text-gray-500">No birthdays today</td></tr>'; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </main>
    </div>
</div>

<script>
    const ctx = document.getElementById('donationChart').getContext('2d');
    const isDark = document.documentElement.classList.contains('dark');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Donations (₹)',
                data: <?php echo json_encode($amounts); ?>,
                borderColor: '#2563EB',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
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
                        color: isDark ? '#374151' : '#f3f4f6'
                    },
                    ticks: {
                        color: isDark ? '#9ca3af' : '#6b7280'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: isDark ? '#9ca3af' : '#6b7280'
                    }
                }
            }
        }
    });
</script>

<?php require 'includes/footer.php'; ?>

<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/student/portal_helpers.php';
require_once __DIR__ . '/includes/student/leaderboard_cache.php';

$student = student_portal_require_student($pdo, 'leaderboard');
$scope = $_GET['scope'] ?? 'national';
if (!in_array($scope, ['national', 'state', 'city', 'campus'], true)) {
    $scope = 'national';
}

$leaderboard = match ($scope) {
    'state' => StudentLeaderboardCache::getScoped($pdo, 'state', (string)($student['state_name'] ?? ''), 50),
    'city' => StudentLeaderboardCache::getScoped($pdo, 'city', (string)($student['city_name'] ?? ''), 50),
    'campus' => StudentLeaderboardCache::getScoped($pdo, 'campus', (string)($student['college_name'] ?? ''), 50),
    default => StudentLeaderboardCache::getNational($pdo, 50),
};

$myRank = 0;
foreach ($leaderboard as $idx => $row) {
    if (($row['full_name'] ?? '') === ($student['full_name'] ?? '')) {
        $myRank = $idx + 1;
        break;
    }
}

student_portal_render_shell_start($pdo, $student, 'Leaderboard', 'leaderboard');
?>

<div class="space-y-6">
    <!-- Header Card -->
    <section class="rounded-3xl border border-slate-100 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-md">
        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                    <i class="fa-solid fa-ranking-star text-xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Rankings & Leaderboard</h2>
                    <p class="text-sm text-slate-500">See where you stand across national, state, city, and campus coordinates.</p>
                </div>
            </div>

            <!-- Current Rank Badge -->
            <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4 flex items-center gap-3">
                <span class="text-2xl">🏆</span>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Your current Rank</p>
                    <p class="text-lg font-black text-slate-800">
                        <?php echo $myRank > 0 ? '#' . $myRank : 'Not Ranked'; ?>
                        <span class="text-xs font-normal text-slate-500 uppercase tracking-wider ml-1">in <?php echo htmlspecialchars($scope); ?></span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Scope Filters -->
        <div class="mt-8 flex flex-wrap gap-2 border-t border-slate-100 pt-6">
            <?php
            $tabs = [
                'national' => 'National Feed',
                'state' => 'State: ' . ($student['state_name'] ?: 'N/A'),
                'city' => 'City: ' . ($student['city_name'] ?: 'N/A'),
                'campus' => 'College: ' . ($student['college_name'] ?: 'N/A'),
            ];
            foreach ($tabs as $key => $label):
                $active = $scope === $key;
            ?>
                <a href="?scope=<?php echo urlencode($key); ?>" 
                   class="rounded-2xl px-5 py-3 text-xs font-bold transition-all border <?php echo $active ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-600/10' : 'bg-slate-50 text-slate-600 border-slate-100 hover:bg-slate-100 hover:text-slate-800'; ?>">
                    <?php echo htmlspecialchars($label); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Table Section -->
    <section class="rounded-3xl border border-slate-100 bg-white overflow-hidden shadow-sm transition-all duration-300 hover:shadow-md">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="px-6 py-4 text-center w-20">Rank</th>
                        <th class="px-6 py-4">Ambassador</th>
                        <th class="px-6 py-4">College / Campus</th>
                        <th class="px-6 py-4">Current Level</th>
                        <th class="px-6 py-4 text-right pr-8">Total Points</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    <?php if (empty($leaderboard)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400 border-none">
                                <span class="text-2xl block mb-2">🔭</span>
                                No ranking data found in this category.
                            </td>
                        </tr>
                    <?php endif; ?>
                    
                    <?php foreach ($leaderboard as $i => $row): 
                        $rank = $i + 1;
                        $isMe = ($row['full_name'] ?? '') === ($student['full_name'] ?? '');
                        
                        // Rank medals
                        $rankDisplay = '#' . $rank;
                        if ($rank === 1) {
                            $rankDisplay = '<span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-50 text-base font-bold text-amber-600 border border-amber-200">🥇</span>';
                        } elseif ($rank === 2) {
                            $rankDisplay = '<span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-base font-bold text-slate-600 border border-slate-200">🥈</span>';
                        } elseif ($rank === 3) {
                            $rankDisplay = '<span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-orange-50 text-base font-bold text-orange-700 border border-orange-200">🥉</span>';
                        }
                    ?>
                        <tr class="transition-all hover:bg-slate-50/50 <?php echo $isMe ? 'bg-emerald-50/70 hover:bg-emerald-50' : ''; ?>">
                            <td class="px-6 py-4.5 text-center font-bold text-slate-800 align-middle">
                                <?php echo $rankDisplay; ?>
                            </td>
                            <td class="px-6 py-4.5 align-middle">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900"><?php echo htmlspecialchars($row['full_name']); ?></span>
                                    <?php if ($isMe): ?>
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[9px] font-bold text-emerald-800 uppercase tracking-wider">You</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4.5 text-slate-500 text-xs align-middle truncate max-w-xs">
                                <?php echo htmlspecialchars($row['college_name'] ?? '—'); ?>
                            </td>
                            <td class="px-6 py-4.5 align-middle">
                                <span class="inline-flex items-center rounded-full bg-slate-100/80 border border-slate-200/30 px-3 py-1 text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    <?php echo htmlspecialchars($row['level_name'] ?? 'Student Ambassador'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4.5 text-right pr-8 font-black text-emerald-600 align-middle text-base">
                                <?php echo (int)($row['total_points'] ?? 0); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php student_portal_render_shell_end(); ?>

<?php require 'includes/header.php'; ?>
<?php require '../config/db.php'; ?>
<?php require_once '../includes/student/points.php'; ?>

<?php
if (!checkRole($pdo, 'coordinator')) {
    header('Location: dashboard.php');
    exit;
}

$penalties = [];
$students = $pdo->query("SELECT id, full_name, student_no FROM sa_students WHERE status = 'Active' ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
$penaltyRules = [];
try {
    $penaltyRules = $pdo->query("SELECT rule_code, rule_name, base_points FROM sa_point_rules WHERE category = 'penalty' AND is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
    $penalties = $pdo->query("
        SELECT p.*, s.full_name, s.student_no FROM sa_penalties p
        JOIN sa_students s ON s.id = p.student_id
        ORDER BY p.created_at DESC LIMIT 100
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}
$csrfToken = generateCsrfToken();
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64">
        <?php require 'includes/navbar.php'; ?>
        <main class="flex-1 overflow-y-auto p-4 md:p-6">
            <h3 class="text-2xl font-bold mb-6">Student Penalties</h3>
            <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
                <form action="actions/penalty_logic.php" method="POST" class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 p-6 space-y-4 h-fit">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <select name="student_id" required class="w-full px-3 py-2.5 rounded-xl border dark:border-gray-600 dark:bg-gray-700 text-sm">
                        <option value="">Select student</option>
                        <?php foreach ($students as $s): ?><option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['full_name']); ?></option><?php endforeach; ?>
                    </select>
                    <select name="penalty_code" required class="w-full px-3 py-2.5 rounded-xl border dark:border-gray-600 dark:bg-gray-700 text-sm">
                        <option value="">Penalty rule</option>
                        <?php foreach ($penaltyRules as $r): ?><option value="<?php echo htmlspecialchars($r['rule_code']); ?>"><?php echo htmlspecialchars($r['rule_name']); ?> (-<?php echo (int)$r['base_points']; ?>)</option><?php endforeach; ?>
                        <?php if (empty($penaltyRules)): ?><option value="FAKE_REFERRAL_PENALTY">Fake Referral (-50)</option><option value="MISCONDUCT_PENALTY">Misconduct (-100)</option><?php endif; ?>
                    </select>
                    <input type="text" name="reason" required placeholder="Reason details" class="w-full px-3 py-2.5 rounded-xl border dark:border-gray-600 dark:bg-gray-700 text-sm">
                    <button type="submit" class="w-full bg-red-600 text-white rounded-xl py-2.5 text-sm font-bold">Apply Penalty</button>
                </form>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 overflow-x-auto">
                    <table class="w-full text-sm"><thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase font-bold"><tr><th class="p-4">Student</th><th class="p-4">Code</th><th class="p-4">Points</th><th class="p-4">Reason</th></tr></thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        <?php foreach ($penalties as $p): ?>
                            <tr><td class="p-4"><?php echo htmlspecialchars($p['full_name']); ?></td><td class="p-4 font-mono text-xs"><?php echo htmlspecialchars($p['penalty_code']); ?></td><td class="p-4 text-red-600 font-bold">-<?php echo (int)$p['penalty_points']; ?></td><td class="p-4 text-xs"><?php echo htmlspecialchars($p['reason_title']); ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (empty($penalties)): ?><tr><td colspan="4" class="p-8 text-center text-gray-400">No penalties recorded.</td></tr><?php endif; ?>
                    </tbody></table>
                </div>
            </div>
        </main>
    </div>
</div>
<?php require 'includes/footer.php'; ?>

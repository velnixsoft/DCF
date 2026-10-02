<?php
/**
 * Backfill sa_referrals from sa_students.referred_by_student_id
 *
 * Usage:
 *   php process/backfill_referrals.php
 *   php process/backfill_referrals.php --dry-run
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('CLI only');
}

require dirname(__DIR__) . '/config/db.php';
require dirname(__DIR__) . '/includes/student/referral_service.php';

$dryRun = in_array('--dry-run', $argv ?? [], true);

echo "Referral backfill " . ($dryRun ? '(dry run)' : '') . PHP_EOL;

$tableCheck = $pdo->query("SHOW TABLES LIKE 'sa_referrals'");
if (!$tableCheck || !$tableCheck->fetchColumn()) {
    echo "sa_referrals table not found. Run migrations first.\n";
    exit(1);
}

$stmt = $pdo->query("
    SELECT s.id, s.full_name, s.email, s.mobile, s.referred_by_student_id, s.created_at,
           ref.referral_code
    FROM sa_students s
    JOIN sa_students ref ON ref.id = s.referred_by_student_id
    WHERE s.referred_by_student_id IS NOT NULL
      AND s.referred_by_student_id > 0
    ORDER BY s.id ASC
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$created = 0;
$skipped = 0;
$service = new StudentReferralService($pdo);

foreach ($rows as $row) {
    $check = $pdo->prepare("
        SELECT id FROM sa_referrals
        WHERE referrer_student_id = ? AND referred_student_id = ? AND deleted_at IS NULL
        LIMIT 1
    ");
    $check->execute([(int)$row['referred_by_student_id'], (int)$row['id']]);
    if ($check->fetchColumn()) {
        $skipped++;
        continue;
    }

    echo "Backfill referral: student #{$row['id']} referred by #{$row['referred_by_student_id']}\n";

    if (!$dryRun) {
        $service->createPendingReferral(
            (int)$row['referred_by_student_id'],
            (int)$row['id'],
            (string)($row['referral_code'] ?? ''),
            [
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'mobile' => $row['mobile'],
            ]
        );
    }

    $created++;
}

echo "Done. Created: {$created}, Skipped existing: {$skipped}\n";

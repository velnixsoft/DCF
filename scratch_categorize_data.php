<?php
$sql = file_get_contents(__DIR__ . '/Database.sql');

preg_match_all('/INSERT INTO `([^`]+)` \(([^)]+)\)\s*VALUES\s*(.*?);/s', $sql, $matches, PREG_SET_ORDER);

$categories = [
    'system_configs' => ['settings', 'notification_templates', 'attendance_thresholds', 'badge_rules', 'promotion_rules', 'achievement_positions', 'member_designations', 'sa_point_rules', 'sa_badges'],
    'content_and_pages' => ['programs', 'program_features', 'projects', 'posts', 'gallery', 'sliders', 'testimonials', 'health_programs', 'templates', 'certificates', 'bank_accounts', 'payment_qrs', 'management_body'],
    'core_users_roles' => ['users', 'user_permissions', 'field_agents', 'volunteers', 'members', 'sa_students', 'sa_programs', 'sa_referrals', 'sa_student_badges'],
    'test_logs_transactions' => ['admin_audit_logs', 'contact_messages', 'donations', 'inquiries', 'qr_scan_logs', 'qr_tokens', 'razorpay_webhook_logs', 'sa_activity_logs', 'sa_attendance_logs', 'sa_login_logs', 'sa_point_transactions']
];

foreach ($matches as $m) {
    $tbl = $m[1];
    $vals = trim($m[3]);
    $lines = explode("\n", $vals);
    $count = count($lines);
    
    $cat = 'other';
    foreach ($categories as $cName => $tbls) {
        if (in_array($tbl, $tbls)) {
            $cat = $cName;
            break;
        }
    }
    
    echo "[$cat] $tbl ($count rows)\n";
    if (in_array($tbl, ['users', 'field_agents', 'members', 'volunteers', 'sa_students', 'admin_audit_logs', 'contact_messages', 'donations', 'inquiries', 'qr_scan_logs', 'qr_tokens', 'razorpay_webhook_logs', 'sa_activity_logs', 'sa_attendance_logs', 'sa_login_logs', 'sa_point_transactions'])) {
        foreach ($lines as $l) {
            echo "   " . trim(substr($l, 0, 150)) . "\n";
        }
    }
}

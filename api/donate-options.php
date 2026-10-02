<?php
require_once __DIR__ . '/_bootstrap.php';

$projects = [];
try {
    $stmt = $pdo->query("SELECT id, title FROM projects WHERE status = 'Active' ORDER BY title ASC");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
}

$banks = [];
try {
    $stmt = $pdo->query("SELECT id, bank_name, account_holder, account_number, ifsc_code FROM bank_accounts WHERE is_active = 1 ORDER BY id DESC");
    $banks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
}

$qrs = [];
try {
    $stmt = $pdo->query("SELECT id, title, qr_image_path FROM payment_qrs WHERE is_active = 1 ORDER BY id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['qr_image_url'] = api_public_url($row['qr_image_path'] ?? '');
        $qrs[] = $row;
    }
} catch (Throwable $e) {
}

$settings = api_settings($pdo);
api_ok([
    'projects' => $projects,
    'bank_accounts' => $banks,
    'payment_qrs' => $qrs,
    'enable_80g' => ((string)($settings['enable_80g'] ?? '0') === '1'),
    'site_name' => $settings['site_name'] ?? 'NGO',
], 'Donation options loaded.');

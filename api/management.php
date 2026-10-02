<?php
require_once __DIR__ . '/_bootstrap.php';

$members = [];
try {
    $stmt = $pdo->query("SELECT id, name, designation, department, phone, email, bio, photo, fb_url, linkedin_url, sort_order FROM management_body WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['photo_url'] = api_public_url($row['photo'] ?? '');
        $members[] = $row;
    }
} catch (Throwable $e) {
}

api_ok([
    'members' => $members,
    'groups' => api_group_by_department($members),
], 'Management body loaded.');

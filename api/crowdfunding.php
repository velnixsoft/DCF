<?php
require_once __DIR__ . '/_bootstrap.php';

$id = api_int('id');
if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM crowdfunding_campaigns WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $campaign = false;
    }

    if (!$campaign) {
        api_error('Campaign not found.', 404);
    }

    $goal = (float)($campaign['goal_amount'] ?? 0);
    $raised = (float)($campaign['raised_amount'] ?? 0);
    $campaign['image_url'] = api_public_url($campaign['image_path'] ?? '');
    $campaign['progress_percent'] = api_percent($raised, $goal);
    $campaign['excerpt'] = api_excerpt($campaign['description'] ?? '', 220);

    api_ok(['campaign' => $campaign], 'Campaign loaded.');
}

$items = [];
try {
    $stmt = $pdo->query("SELECT * FROM crowdfunding_campaigns WHERE status IN ('Active','Completed','Paused') ORDER BY created_at DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $goal = (float)($row['goal_amount'] ?? 0);
        $raised = (float)($row['raised_amount'] ?? 0);
        $row['image_url'] = api_public_url($row['image_path'] ?? '');
        $row['progress_percent'] = api_percent($raised, $goal);
        $row['excerpt'] = api_excerpt($row['description'] ?? '', 180);
        $items[] = $row;
    }
} catch (Throwable $e) {
}

api_ok(['campaigns' => $items], 'Crowdfunding campaigns loaded.');

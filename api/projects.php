<?php
require_once __DIR__ . '/_bootstrap.php';

$id = api_int('id');
$status = strtolower(api_trim('status', 'all'));

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $project = false;
    }

    if (!$project) {
        api_error('Project not found.', 404);
    }

    $goal = (float)($project['target_amount'] ?? 0);
    $raised = (float)($project['raised_amount'] ?? 0);

    $gallery = [];
    try {
        $stmt = $pdo->prepare("SELECT id, image_path, created_at FROM project_gallery WHERE project_id = ? ORDER BY id DESC");
        $stmt->execute([$id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['image_url'] = api_public_url($row['image_path'] ?? '');
            $gallery[] = $row;
        }
    } catch (Throwable $e) {
    }

    $project['thumbnail_url'] = api_public_url($project['thumbnail_image'] ?? '');
    $project['upi_qr_url'] = api_public_url($project['upi_qr_image'] ?? '');
    $project['progress_percent'] = api_percent($raised, $goal);
    $project['excerpt'] = api_excerpt($project['description'] ?? '', 220);

    api_ok([
        'project' => $project,
        'gallery' => $gallery,
    ], 'Project loaded.');
}

$where = '';
if ($status === 'active') {
    $where = "WHERE status = 'Active'";
} elseif ($status === 'completed') {
    $where = "WHERE status = 'Completed'";
} elseif ($status === 'paused') {
    $where = "WHERE status = 'Paused'";
}

$items = [];
try {
    $stmt = $pdo->query("SELECT * FROM projects {$where} ORDER BY created_at DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $goal = (float)($row['target_amount'] ?? 0);
        $raised = (float)($row['raised_amount'] ?? 0);
        $row['thumbnail_url'] = api_public_url($row['thumbnail_image'] ?? '');
        $row['upi_qr_url'] = api_public_url($row['upi_qr_image'] ?? '');
        $row['progress_percent'] = api_percent($raised, $goal);
        $row['excerpt'] = api_excerpt($row['description'] ?? '', 180);
        $items[] = $row;
    }
} catch (Throwable $e) {
}

api_ok(['projects' => $items], 'Projects loaded.');

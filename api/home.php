<?php
require_once __DIR__ . '/_bootstrap.php';

$slides = [];
try {
    $stmt = $pdo->query("SELECT id, title, subtitle, image_path, priority FROM sliders WHERE is_active = 1 ORDER BY priority ASC, id DESC LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['image_url'] = api_public_url($row['image_path'] ?? '');
        $slides[] = $row;
    }
} catch (Throwable $e) {
}

$settings = api_settings($pdo);
$about = [
    'title' => $settings['about_title'] ?? 'About Our Journey',
    'description' => $settings['about_desc'] ?? '',
    'image_url' => api_public_url($settings['about_image'] ?? ''),
    'mission' => $settings['about_mission'] ?? '',
    'vision' => $settings['about_vision'] ?? '',
];

$stats = [
    'fund_raised' => 0.0,
    'active_volunteers' => 0,
    'ongoing_projects' => 0,
];

try {
    $stats['fund_raised'] = (float)($pdo->query("SELECT COALESCE(SUM(amount),0) FROM donations WHERE payment_status = 'Success'")->fetchColumn() ?: 0);
} catch (Throwable $e) {
}

try {
    $stats['active_volunteers'] = (int)($pdo->query("SELECT COUNT(*) FROM volunteers WHERE status = 'Active'")->fetchColumn() ?: 0);
} catch (Throwable $e) {
}

try {
    $stats['ongoing_projects'] = (int)($pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'Active'")->fetchColumn() ?: 0);
} catch (Throwable $e) {
}

$projects = [];
try {
    $stmt = $pdo->query("SELECT id, title, description, thumbnail_image, target_amount, raised_amount, status, created_at FROM projects WHERE status = 'Active' ORDER BY created_at DESC LIMIT 3");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $goal = (float)($row['target_amount'] ?? 0);
        $raised = (float)($row['raised_amount'] ?? 0);
        $row['thumbnail_url'] = api_public_url($row['thumbnail_image'] ?? '');
        $row['progress_percent'] = api_percent($raised, $goal);
        $row['excerpt'] = api_excerpt($row['description'] ?? '', 120);
        $projects[] = $row;
    }
} catch (Throwable $e) {
}

$gallery = [];
try {
    $stmt = $pdo->query("SELECT id, title, type, file_path, created_at FROM gallery ORDER BY id DESC LIMIT 12");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['file_url'] = api_public_url($row['file_path'] ?? '');
        $gallery[] = $row;
    }
} catch (Throwable $e) {
}

$sponsors = [];
try {
    $stmt = $pdo->query("SELECT id, name, logo_path, website_url, priority FROM sponsors ORDER BY priority ASC, id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['logo_url'] = api_public_url($row['logo_path'] ?? '');
        $sponsors[] = $row;
    }
} catch (Throwable $e) {
}

$birthdays = [];
try {
    $stmt = $pdo->query("
        SELECT full_name
        FROM members
        WHERE status = 'Active'
          AND dob IS NOT NULL
          AND DATE_FORMAT(dob, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
        ORDER BY full_name ASC
        LIMIT 10
    ");
    $birthdays = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
}

api_ok([
    'slides' => $slides,
    'about' => $about,
    'stats' => $stats,
    'featured_projects' => $projects,
    'gallery' => $gallery,
    'sponsors' => $sponsors,
    'birthday_members' => $birthdays,
], 'Home data loaded.');

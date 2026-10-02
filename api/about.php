<?php
require_once __DIR__ . '/_bootstrap.php';

$settings = api_settings($pdo);
$content = [
    'title' => $settings['about_title'] ?? 'About Our Journey',
    'description' => $settings['about_desc'] ?? '',
    'image_url' => api_public_url($settings['about_image'] ?? ''),
    'mission' => $settings['about_mission'] ?? '',
    'vision' => $settings['about_vision'] ?? '',
    'paragraphs' => [],
];

foreach (preg_split("/\r\n|\n|\r/", trim((string)$content['description'])) as $line) {
    $line = trim((string)$line);
    if ($line !== '') {
        $content['paragraphs'][] = $line;
    }
}

api_ok($content, 'About content loaded.');

<?php
require_once __DIR__ . '/_bootstrap.php';

$settings = api_settings($pdo);
$content = [
    'title' => $settings['objectives_title'] ?? 'Our Objectives',
    'description' => $settings['objectives_content'] ?? '',
    'image_url' => api_public_url($settings['objectives_image'] ?? ''),
    'paragraphs' => [],
];

foreach (preg_split("/\r\n|\n|\r/", trim((string)$content['description'])) as $line) {
    $line = trim((string)$line);
    if ($line !== '') {
        $content['paragraphs'][] = $line;
    }
}

api_ok($content, 'Objectives content loaded.');

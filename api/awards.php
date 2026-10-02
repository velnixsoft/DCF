<?php
require_once __DIR__ . '/_bootstrap.php';

$settings = api_settings($pdo);
api_ok([
    'title' => $settings['awards_title'] ?? 'Awards & Recognition',
    'content' => $settings['awards_content'] ?? '',
    'image_url' => api_public_url($settings['awards_image'] ?? ''),
], 'Awards content loaded.');

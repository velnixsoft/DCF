<?php
require_once __DIR__ . '/_bootstrap.php';

$settings = api_settings($pdo);
api_ok([
    'address' => $settings['ngo_address'] ?? '',
    'email' => $settings['ngo_email'] ?? '',
    'phone' => $settings['ngo_phone'] ?? '',
    'map_iframe' => $settings['contact_map_iframe'] ?? '',
    'socials' => [
        'facebook' => $settings['social_facebook'] ?? '',
        'instagram' => $settings['social_instagram'] ?? '',
        'youtube' => $settings['social_youtube'] ?? '',
    ],
], 'Contact details loaded.');

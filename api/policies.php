<?php
require_once __DIR__ . '/_bootstrap.php';

$settings = api_settings($pdo);
api_ok([
    'privacy_policy' => $settings['privacy_policy_content'] ?? '',
    'refund_policy' => $settings['refund_policy_content'] ?? '',
    'terms_conditions' => $settings['terms_conditions_content'] ?? ($settings['terms_conditions'] ?? ''),
], 'Policy content loaded.');

<?php
require_once __DIR__ . '/_bootstrap.php';

api_require_method(['POST']);

$token = api_bearer_token();
if ($token !== '') {
    api_revoke_access_token($pdo, $token);
}

unset(
    $_SESSION['member_logged_in'],
    $_SESSION['member_id'],
    $_SESSION['member_no'],
    $_SESSION['member_email'],
    $_SESSION['member_name']
);

api_ok([], 'Logged out successfully.');

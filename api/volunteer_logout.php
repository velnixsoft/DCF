<?php
require_once __DIR__ . '/_bootstrap.php';

api_require_method(['POST']);

$token = api_bearer_token();
if ($token !== '') {
    api_revoke_access_token($pdo, $token);
}

unset(
    $_SESSION['volunteer_logged_in'],
    $_SESSION['volunteer_id'],
    $_SESSION['volunteer_email'],
    $_SESSION['volunteer_verified_email']
);

api_ok([], 'Logged out successfully.');

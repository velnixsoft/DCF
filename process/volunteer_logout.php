<?php
require_once __DIR__ . '/../includes/functions.php';

unset(
    $_SESSION['volunteer_logged_in'],
    $_SESSION['volunteer_id'],
    $_SESSION['volunteer_email'],
    $_SESSION['volunteer_verified_email']
);

setFlash('success', 'Logged out successfully.');
header('Location: ../volunteer-login.php');
exit;


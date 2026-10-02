<?php
require_once __DIR__ . '/../includes/functions.php';

unset(
    $_SESSION['member_logged_in'],
    $_SESSION['member_id'],
    $_SESSION['member_no'],
    $_SESSION['member_email'],
    $_SESSION['member_name']
);

setFlash('success', 'Logged out successfully.');
header('Location: ../member-login.php');
exit;


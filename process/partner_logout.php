<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['partner_logged_in'], $_SESSION['partner_id'], $_SESSION['partner_code'], $_SESSION['partner_name']);

header('Location: ../partner-login.php');
exit;

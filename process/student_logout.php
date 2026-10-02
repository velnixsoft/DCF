<?php
require_once __DIR__ . '/../includes/functions.php';

unset($_SESSION['student_logged_in']);
unset($_SESSION['student_id']);
unset($_SESSION['student_no']);
unset($_SESSION['student_name']);
unset($_SESSION['student_email']);

setFlash('success', 'You have been logged out of the ambassador portal.');
header('Location: ../student-login.php');
exit;

<?php
require 'config/db.php';
require 'includes/functions.php';

$stmt = $pdo->prepare("SELECT * FROM sa_students WHERE id = 1 LIMIT 1");
$stmt->execute();
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['student_logged_in'] = true;
    $_SESSION['student_id'] = (int)$student['id'];
    $_SESSION['student_no'] = $student['student_no'];
    $_SESSION['student_name'] = $student['full_name'];
    $_SESSION['student_email'] = $student['email'];
    
    // Ensure CSRF token is set
    generateCsrfToken();
    
    header('Location: student-dashboard.php');
    exit;
} else {
    echo "Student ID 1 not found.";
}

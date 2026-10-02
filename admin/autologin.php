<?php
require '../config/db.php';
require '../includes/functions.php';

// Find the first admin or manager or coordinator
$user = $pdo->query("SELECT * FROM users ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if ($user) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['hierarchy_level'] = $user['hierarchy_level'] ?? 'coordinator';
    $_SESSION['logged_in'] = true;

    header('Location: student_directory.php');
    exit;
} else {
    echo "No admin/coordinator user found in database.";
}
?>

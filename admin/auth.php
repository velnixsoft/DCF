<?php
require '../config/db.php';
require '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlash('error', 'Invalid Security Token!');
        header('Location: ' . appUrlPath() . '/admin');
        exit;
    }

    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {

        if ($remember) {
            setcookie('remember_email', $email, time() + (86400 * 30), "/");
        } else {
            if (isset($_COOKIE['remember_email'])) {
                setcookie('remember_email', '', time() - 3600, "/");
            }
        }
session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['hierarchy_level'] = $user['hierarchy_level'] ?? 'coordinator';
        $_SESSION['logged_in'] = true;

        setFlash('success', 'Welcome back, ' . htmlspecialchars($user['name']));
        header('Location: ' . getDefaultAdminLandingPage($pdo));
        exit;
    } else {
        setFlash('error', 'Invalid email or password.');
        header('Location: ' . appUrlPath() . '/admin');
        exit;
    }
}

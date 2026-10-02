<?php
session_start();
require '../../config/db.php';
require '../../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'manager', 'page.training_videos')) {
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    setFlash('error', 'Invalid request.');
    header('Location: ../training_videos.php');
    exit;
}

$action = (string)($_POST['action'] ?? '');

try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'admin_training_videos_json' LIMIT 1");
    $stmt->execute();
    $raw = (string)($stmt->fetchColumn() ?: '[]');
    $decoded = json_decode($raw, true);
    $videos = is_array($decoded) ? $decoded : [];

    if ($action === 'add') {
        $title = cleanInput($_POST['title'] ?? '');
        $url = trim((string)($_POST['url'] ?? ''));
        if ($title === '' || $url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Valid title and URL are required.');
        }
        if (!preg_match('/^[a-zA-Z\s]+$/', $title)) {
            throw new Exception('Title must contain only letters and spaces.');
        }
        $videos[] = ['title' => $title, 'url' => $url];
    } elseif ($action === 'delete') {
        $idx = (int)($_POST['index'] ?? -1);
        if ($idx < 0 || $idx >= count($videos)) {
            throw new Exception('Invalid index.');
        }
        array_splice($videos, $idx, 1);
    } else {
        throw new Exception('Invalid action.');
    }

    $json = json_encode(array_values($videos), JSON_UNESCAPED_SLASHES);
    $upd = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'admin_training_videos_json'");
    $upd->execute([$json !== false ? $json : '[]']);

    setFlash('success', 'Training videos updated.');
    header('Location: ../training_videos.php');
    exit;
} catch (Throwable $e) {
    setFlash('error', $e->getMessage() ?: 'Operation failed.');
    header('Location: ../training_videos.php');
    exit;
}

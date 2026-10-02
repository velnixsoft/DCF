<?php
session_start();
require '../../config/db.php';
require '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../inquiries.php');
    exit;
}

if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    setFlash('error', 'Invalid Security Token!');
    header('Location: ../inquiries.php');
    exit;
}

if (!canAccessModule($pdo, 'coordinator', 'page.inquiries')) {
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'Invalid request.');
    header('Location: ../inquiries.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT attachment_path FROM inquiries WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $del = $pdo->prepare("DELETE FROM inquiries WHERE id = ? LIMIT 1");
    $del->execute([$id]);

    $attachment = (string)($row['attachment_path'] ?? '');
    if ($attachment !== '') {
        $baseDir = realpath(__DIR__ . '/../../uploads/inquiries');
        $file = basename($attachment);
        if ($baseDir && $file !== '') {
            $target = realpath($baseDir . DIRECTORY_SEPARATOR . $file);
            if ($target && str_starts_with($target, $baseDir) && is_file($target)) {
                @unlink($target);
            }
        }
    }

    setFlash('success', 'Inquiry deleted successfully.');
} catch (Throwable $e) {
    error_log($e->getMessage());
    setFlash('error', 'Unable to delete inquiry right now.');
}

header('Location: ../inquiries.php');
exit;

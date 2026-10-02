<?php
session_start();
require '../../config/db.php';
require '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../inquiries.php');
    exit;
}

if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
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
$status = cleanInput($_POST['status'] ?? 'New');
$adminNotes = trim((string)($_POST['admin_notes'] ?? ''));

$allowedStatuses = ['New', 'In Progress', 'Resolved', 'Closed'];
if ($id <= 0 || !in_array($status, $allowedStatuses, true)) {
    setFlash('error', 'Invalid request.');
    header('Location: ../inquiries.php');
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE inquiries SET status = ?, admin_notes = ? WHERE id = ? LIMIT 1");
    $stmt->execute([$status, $adminNotes === '' ? null : $adminNotes, $id]);
    setFlash('success', 'Inquiry updated successfully.');
} catch (Throwable $e) {
    error_log($e->getMessage());
    setFlash('error', 'Unable to update inquiry right now.');
}

header('Location: ../inquiries.php');
exit;

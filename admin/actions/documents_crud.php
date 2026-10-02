<?php
session_start();
require '../../config/db.php';
require '../../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'manager', 'page.documents')) {
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    setFlash('error', 'Invalid request.');
    header('Location: ../documents.php');
    exit;
}

$action = (string)($_POST['action'] ?? '');
$id = (int)($_POST['id'] ?? 0);

try {
    if ($action === 'delete') {
        if ($id <= 0) throw new Exception('Invalid id');
        $stmt = $pdo->prepare("SELECT file_path FROM ngo_documents WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $filePath = (string)($stmt->fetchColumn() ?: '');

        $pdo->prepare("DELETE FROM ngo_documents WHERE id = ?")->execute([$id]);
        if ($filePath !== '' && file_exists(__DIR__ . '/../../' . $filePath)) {
            @unlink(__DIR__ . '/../../' . $filePath);
        }
        setFlash('success', 'Document deleted.');
        header('Location: ../documents.php');
        exit;
    }

    if (!in_array($action, ['create', 'update'], true)) throw new Exception('Invalid action');

    $title = trim((string)($_POST['title'] ?? ''));
    $category = (string)($_POST['category'] ?? 'other');
    $isPublic = isset($_POST['is_public']) ? 1 : 0;
    if ($title === '') throw new Exception('Title is required.');
    if (!in_array($category, ['certificate', 'annual_report', 'other'], true)) $category = 'other';

    $newFilePath = null;
    $oldFilePath = null;

    if ($action === 'update') {
        if ($id <= 0) throw new Exception('Invalid id');
        $stmt = $pdo->prepare("SELECT file_path FROM ngo_documents WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $oldFilePath = $stmt->fetchColumn() ?: null;
    }

    if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['doc_file']['size'] > 5 * 1024 * 1024) throw new Exception('File must be under 5MB.');

        $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $_FILES['doc_file']['tmp_name']) : ($_FILES['doc_file']['type'] ?? '');
        if ($finfo) finfo_close($finfo);
        if (!in_array($mime, $allowed, true)) throw new Exception('Invalid file type.');

        $targetDir = __DIR__ . '/../../uploads/documents/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['doc_file']['name'], PATHINFO_EXTENSION));
        $fileName = 'doc_' . time() . '_' . bin2hex(random_bytes(6)) . ($ext ? ('.' . $ext) : '');
        if (!move_uploaded_file($_FILES['doc_file']['tmp_name'], $targetDir . $fileName)) {
            throw new Exception('Failed to upload file.');
        }
        $newFilePath = 'uploads/documents/' . $fileName;
    } elseif ($action === 'create') {
        throw new Exception('File is required.');
    }

    if ($action === 'create') {
        $stmt = $pdo->prepare("INSERT INTO ngo_documents (title, category, file_path, is_public) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $category, $newFilePath, $isPublic]);
        setFlash('success', 'Document uploaded.');
        header('Location: ../documents.php');
        exit;
    }

    $set = "title = ?, category = ?, is_public = ?";
    $params = [$title, $category, $isPublic];
    if ($newFilePath !== null) {
        $set .= ", file_path = ?";
        $params[] = $newFilePath;
    }
    $params[] = $id;
    $pdo->prepare("UPDATE ngo_documents SET $set WHERE id = ?")->execute($params);

    if ($newFilePath !== null && $oldFilePath && $oldFilePath !== $newFilePath && file_exists(__DIR__ . '/../../' . $oldFilePath)) {
        @unlink(__DIR__ . '/../../' . $oldFilePath);
    }

    setFlash('success', 'Document updated.');
    header('Location: ../documents.php?edit=' . $id);
    exit;
} catch (Throwable $e) {
    setFlash('error', $e->getMessage() ?: 'Operation failed.');
    header('Location: ../documents.php');
    exit;
}

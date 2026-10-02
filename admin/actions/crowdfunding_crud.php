<?php
session_start();
require '../../config/db.php';
require '../../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'manager', 'page.crowdfunding')) {
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    setFlash('error', 'Invalid request.');
    header('Location: ../crowdfunding.php');
    exit;
}

$action = (string)($_POST['action'] ?? '');
$id = (int)($_POST['id'] ?? 0);

try {
    if ($action === 'delete') {
        if ($id <= 0) throw new Exception('Invalid id');
        $stmt = $pdo->prepare("SELECT image_path FROM crowdfunding_campaigns WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $img = (string)($stmt->fetchColumn() ?: '');

        $pdo->prepare("DELETE FROM crowdfunding_campaigns WHERE id = ?")->execute([$id]);
        if ($img !== '' && file_exists(__DIR__ . '/../../' . $img)) {
            @unlink(__DIR__ . '/../../' . $img);
        }
        setFlash('success', 'Campaign deleted.');
        header('Location: ../crowdfunding.php');
        exit;
    }

    if (!in_array($action, ['create', 'update'], true)) throw new Exception('Invalid action');

    $title = trim((string)($_POST['title'] ?? ''));
    $description = (string)($_POST['description'] ?? '');
    $goal = (float)($_POST['goal_amount'] ?? 0);
    $raised = (float)($_POST['raised_amount'] ?? 0);
    $status = (string)($_POST['status'] ?? 'Active');

    if ($title === '' || $goal <= 0) throw new Exception('Title and valid goal amount are required.');
    if ($raised < 0) $raised = 0;
    if (!in_array($status, ['Active', 'Completed', 'Paused'], true)) $status = 'Active';

    $imagePath = null;
    $oldImagePath = null;

    if ($action === 'update') {
        if ($id <= 0) throw new Exception('Invalid id');
        $stmt = $pdo->prepare("SELECT image_path FROM crowdfunding_campaigns WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $oldImagePath = $stmt->fetchColumn() ?: null;
    }

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['image']['size'] > 2 * 1024 * 1024) throw new Exception('Image must be under 2MB.');
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $_FILES['image']['tmp_name']) : ($_FILES['image']['type'] ?? '');
        if ($finfo) finfo_close($finfo);
        if (!in_array($mime, $allowedTypes, true)) throw new Exception('Invalid image type.');

        $targetDir = __DIR__ . '/../../uploads/crowdfunding/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $fileName = 'camp_' . time() . '_' . bin2hex(random_bytes(6)) . ($ext ? ('.' . $ext) : '');
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $fileName)) {
            throw new Exception('Failed to upload image.');
        }
        $imagePath = 'uploads/crowdfunding/' . $fileName;
    }

    if ($action === 'create') {
        $stmt = $pdo->prepare("INSERT INTO crowdfunding_campaigns (title, description, goal_amount, raised_amount, image_path, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description !== '' ? $description : null, $goal, $raised, $imagePath, $status]);
        setFlash('success', 'Campaign created.');
        header('Location: ../crowdfunding.php');
        exit;
    }

    $set = "title = ?, description = ?, goal_amount = ?, raised_amount = ?, status = ?";
    $params = [$title, $description !== '' ? $description : null, $goal, $raised, $status];
    if ($imagePath !== null) {
        $set .= ", image_path = ?";
        $params[] = $imagePath;
    }
    $params[] = $id;
    $pdo->prepare("UPDATE crowdfunding_campaigns SET $set WHERE id = ?")->execute($params);

    if ($imagePath !== null && $oldImagePath && $oldImagePath !== $imagePath && file_exists(__DIR__ . '/../../' . $oldImagePath)) {
        @unlink(__DIR__ . '/../../' . $oldImagePath);
    }

    setFlash('success', 'Campaign updated.');
    header('Location: ../crowdfunding.php?edit=' . $id);
    exit;
} catch (Throwable $e) {
    setFlash('error', $e->getMessage() ?: 'Operation failed.');
    header('Location: ../crowdfunding.php');
    exit;
}

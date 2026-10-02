<?php
session_start();
require '../../config/db.php';
require '../../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'manager', 'page.events')) {
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    setFlash('error', 'Invalid request.');
    header('Location: ../events.php');
    exit;
}

$action = (string)($_POST['action'] ?? '');
$eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;

try {
    if ($action === 'upload') {
        if ($eventId <= 0) throw new Exception('Invalid event.');
        if (empty($_FILES['images']) || !is_array($_FILES['images']['name'])) throw new Exception('No images selected.');

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $targetDir = __DIR__ . '/../../uploads/events/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $count = count($_FILES['images']['name']);
        $uploaded = 0;

        for ($i = 0; $i < $count; $i++) {
            if (($_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            if (($_FILES['images']['size'][$i] ?? 0) > 2 * 1024 * 1024) continue;

            $tmp = (string)($_FILES['images']['tmp_name'][$i] ?? '');
            if ($tmp === '') continue;

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? finfo_file($finfo, $tmp) : '';
            if ($finfo) finfo_close($finfo);
            if (!in_array($mime, $allowedTypes, true)) continue;

            $ext = strtolower(pathinfo((string)($_FILES['images']['name'][$i] ?? ''), PATHINFO_EXTENSION));
            $fileName = 'event_' . $eventId . '_' . time() . '_' . bin2hex(random_bytes(6)) . ($ext ? ('.' . $ext) : '');
            if (!move_uploaded_file($tmp, $targetDir . $fileName)) continue;

            $dbPath = 'uploads/events/' . $fileName;
            $stmt = $pdo->prepare("INSERT INTO event_gallery (event_id, image_path) VALUES (?, ?)");
            $stmt->execute([$eventId, $dbPath]);
            $uploaded++;
        }

        setFlash('success', $uploaded > 0 ? "Uploaded $uploaded photo(s)." : 'No photos uploaded (check size/type).');
        header('Location: ../event_gallery.php?event_id=' . $eventId);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 || $eventId <= 0) throw new Exception('Invalid request.');

        $stmt = $pdo->prepare("SELECT image_path FROM event_gallery WHERE id = ? AND event_id = ? LIMIT 1");
        $stmt->execute([$id, $eventId]);
        $path = (string)($stmt->fetchColumn() ?: '');

        $pdo->prepare("DELETE FROM event_gallery WHERE id = ? AND event_id = ?")->execute([$id, $eventId]);
        if ($path !== '' && file_exists(__DIR__ . '/../../' . $path)) {
            @unlink(__DIR__ . '/../../' . $path);
        }

        setFlash('success', 'Photo deleted.');
        header('Location: ../event_gallery.php?event_id=' . $eventId);
        exit;
    }

    throw new Exception('Invalid action.');
} catch (Throwable $e) {
    setFlash('error', $e->getMessage() ?: 'Operation failed.');
    if ($eventId > 0) {
        header('Location: ../event_gallery.php?event_id=' . $eventId);
    } else {
        header('Location: ../events.php');
    }
    exit;
}

<?php
session_start();
require '../../config/db.php';
require '../../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'manager', 'page.news')) {
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    setFlash('error', 'Invalid request.');
    header('Location: ../news.php');
    exit;
}

function news_slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : ('post-' . date('YmdHis'));
}

function news_unique_slug(PDO $pdo, string $baseSlug, int $excludeId = 0): string
{
    $slug = $baseSlug;
    $i = 2;
    while (true) {
        $sql = "SELECT COUNT(*) FROM posts WHERE slug = ?" . ($excludeId > 0 ? " AND id <> ?" : "");
        $stmt = $pdo->prepare($sql);
        $stmt->execute($excludeId > 0 ? [$slug, $excludeId] : [$slug]);
        if ((int)$stmt->fetchColumn() === 0) return $slug;
        $slug = $baseSlug . '-' . $i;
        $i++;
        if ($i > 2000) return $baseSlug . '-' . bin2hex(random_bytes(3));
    }
}

$action = (string)($_POST['action'] ?? '');
$id = (int)($_POST['id'] ?? 0);

try {
    if ($action === 'delete') {
        if ($id <= 0) throw new Exception('Invalid id');
        $stmt = $pdo->prepare("SELECT cover_path FROM posts WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $cover = (string)($stmt->fetchColumn() ?: '');

        $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$id]);

        if ($cover !== '' && file_exists(__DIR__ . '/../../' . $cover)) {
            @unlink(__DIR__ . '/../../' . $cover);
        }

        setFlash('success', 'Post deleted.');
        header('Location: ../news.php');
        exit;
    }

    if (!in_array($action, ['create', 'update'], true)) {
        throw new Exception('Invalid action');
    }

    $title = trim((string)($_POST['title'] ?? ''));
    $content = (string)($_POST['content'] ?? '');
    $status = (string)($_POST['status'] ?? 'Draft');
    $slugInput = trim((string)($_POST['slug'] ?? ''));
    $_SESSION['news_form_old'] = [
        'id' => $id,
        'title' => $title,
        'slug' => $slugInput,
        'content' => $content,
        'status' => $status,
    ];
    if ($title === '') throw new Exception('Title required');
    if (!in_array($status, ['Draft', 'Published', 'Archived'], true)) $status = 'Draft';

    $baseSlug = news_slugify($slugInput !== '' ? $slugInput : $title);
    $slug = news_unique_slug($pdo, $baseSlug, $action === 'update' ? $id : 0);

    $coverPath = null;
    $oldCoverPath = null;

    if ($action === 'update') {
        if ($id <= 0) throw new Exception('Invalid id');
        $stmt = $pdo->prepare("SELECT cover_path FROM posts WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $oldCoverPath = $stmt->fetchColumn() ?: null;
    }

    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['cover']['size'] > 2 * 1024 * 1024) {
            throw new Exception('Cover image must be under 2MB.');
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $_FILES['cover']['tmp_name']) : ($_FILES['cover']['type'] ?? '');
        if ($finfo) finfo_close($finfo);
        if (!in_array($mime, $allowedTypes, true)) {
            throw new Exception('Invalid cover image type.');
        }

        $targetDir = __DIR__ . '/../../uploads/news/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
        $fileName = 'news_' . time() . '_' . bin2hex(random_bytes(6)) . ($ext ? ('.' . $ext) : '');
        if (!move_uploaded_file($_FILES['cover']['tmp_name'], $targetDir . $fileName)) {
            throw new Exception('Failed to upload cover image.');
        }
        $coverPath = 'uploads/news/' . $fileName;
    }

    if ($action === 'create') {
        $publishedAt = ($status === 'Published') ? date('Y-m-d H:i:s') : null;
        $stmt = $pdo->prepare("INSERT INTO posts (title, slug, content, cover_path, status, published_at, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $slug, $content !== '' ? $content : null, $coverPath, $status, $publishedAt, (int)($_SESSION['user_id'] ?? 0)]);
        unset($_SESSION['news_form_old']);
        setFlash('success', 'Post created.');
        header('Location: ../news.php');
        exit;
    }

    $publishedAt = null;
    if ($status === 'Published') {
        $publishedAt = date('Y-m-d H:i:s');
        try {
            $stmt = $pdo->prepare("SELECT published_at FROM posts WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $existing = $stmt->fetchColumn();
            if (!empty($existing)) $publishedAt = $existing;
        } catch (Throwable $e) {
        }
    }

    $set = "title = ?, slug = ?, content = ?, status = ?, published_at = ?, updated_at = NOW()";
    $params = [$title, $slug, $content !== '' ? $content : null, $status, $publishedAt];
    if ($coverPath !== null) {
        $set .= ", cover_path = ?";
        $params[] = $coverPath;
    }
    $params[] = $id;
    $pdo->prepare("UPDATE posts SET $set WHERE id = ?")->execute($params);

    if ($coverPath !== null && $oldCoverPath && $oldCoverPath !== $coverPath && file_exists(__DIR__ . '/../../' . $oldCoverPath)) {
        @unlink(__DIR__ . '/../../' . $oldCoverPath);
    }

    unset($_SESSION['news_form_old']);
    setFlash('success', 'Post updated.');
    header('Location: ../news.php?edit=' . $id);
    exit;
} catch (Throwable $e) {
    setFlash('error', $e->getMessage() ?: 'Operation failed.');
    header('Location: ../news.php' . ($id > 0 ? '?edit=' . $id : ''));
    exit;
}

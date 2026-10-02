<?php
require_once __DIR__ . '/_bootstrap.php';

$slug = api_trim('slug');
$id = api_int('id');

if ($slug !== '' || $id > 0) {
    try {
        if ($slug !== '') {
            $stmt = $pdo->prepare("SELECT * FROM posts WHERE slug = ? AND status = 'Published' LIMIT 1");
            $stmt->execute([$slug]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND status = 'Published' LIMIT 1");
            $stmt->execute([$id]);
        }
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $post = false;
    }

    if (!$post) {
        api_error('News post not found.', 404);
    }

    $post['cover_url'] = api_public_url($post['cover_path'] ?? '');
    $post['excerpt'] = api_excerpt($post['content'] ?? '', 220);

    api_ok(['post' => $post], 'News post loaded.');
}

$limit = max(1, min(50, api_int('limit', 30)));
$items = [];
try {
    $stmt = $pdo->prepare("
        SELECT id, title, slug, content, cover_path, published_at, created_at
        FROM posts
        WHERE status = 'Published'
        ORDER BY COALESCE(published_at, created_at) DESC
        LIMIT {$limit}
    ");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['cover_url'] = api_public_url($row['cover_path'] ?? '');
        $row['excerpt'] = api_excerpt($row['content'] ?? '', 180);
        $items[] = $row;
    }
} catch (Throwable $e) {
}

api_ok(['posts' => $items], 'News loaded.');

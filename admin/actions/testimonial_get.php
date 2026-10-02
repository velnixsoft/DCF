<?php
require '../../config/db.php';
require '../../includes/functions.php';

if (!canAccessModule($pdo, 'coordinator', 'page.testimonials')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name, role, content, stars, initial, priority_order, avatar_path FROM testimonials WHERE id = ?");
$stmt->execute([$id]);
$testimonial = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
if ($testimonial) {
    echo json_encode(['success' => true, 'data' => $testimonial]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Not found']);
}
?>


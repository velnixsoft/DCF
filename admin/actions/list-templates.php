<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/member_module.php';
require_once __DIR__ . '/../../includes/template_builder.php';

tb_require_admin_access($pdo);

if (!dbTableExists($pdo, 'templates')) {
    tb_json_response(['success' => true, 'templates' => []]);
}

$type = trim((string)($_GET['type'] ?? ''));
$params = [];
$where = '';
if ($type !== '' && in_array($type, tb_allowed_template_types(), true)) {
    $where = 'WHERE template_type = ?';
    $params[] = $type;
}

$stmt = $pdo->prepare("SELECT id, template_name, template_type, canvas_width, canvas_height, background_image, status, created_at, updated_at FROM templates {$where} ORDER BY updated_at DESC, id DESC");
$stmt->execute($params);
tb_json_response(['success' => true, 'templates' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

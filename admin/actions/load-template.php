<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/member_module.php';
require_once __DIR__ . '/../../includes/template_builder.php';

tb_require_admin_access($pdo);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0 || !dbTableExists($pdo, 'templates')) {
    tb_json_response(['success' => false, 'message' => 'Template not found.'], 404);
}

$stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$template) {
    tb_json_response(['success' => false, 'message' => 'Template not found.'], 404);
}

tb_json_response(['success' => true, 'template' => $template]);

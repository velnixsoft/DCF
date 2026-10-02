<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/member_module.php';
require_once __DIR__ . '/../../includes/template_builder.php';

tb_require_admin_access($pdo);
tb_validate_csrf();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0 || !dbTableExists($pdo, 'templates')) {
    tb_json_response(['success' => false, 'message' => 'Template not found.'], 404);
}

$stmt = $pdo->prepare("DELETE FROM templates WHERE id = ?");
$stmt->execute([$id]);
tb_json_response(['success' => true, 'message' => 'Template deleted.']);

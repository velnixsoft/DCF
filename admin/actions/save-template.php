<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/member_module.php';
require_once __DIR__ . '/../../includes/template_builder.php';

tb_require_admin_access($pdo);
tb_validate_csrf();

try {
    if (!dbTableExists($pdo, 'templates')) {
        tb_json_response(['success' => false, 'message' => 'Templates table is missing. Run the migration first.'], 500);
    }

    $id = (int)($_POST['id'] ?? 0);
    $templateName = trim((string)($_POST['template_name'] ?? ''));
    $templateType = trim((string)($_POST['template_type'] ?? ''));
    $canvasWidth = (int)($_POST['canvas_width'] ?? 0);
    $canvasHeight = (int)($_POST['canvas_height'] ?? 0);
    $jsonData = (string)($_POST['json_data'] ?? '');
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;

    if ($templateName === '' || strlen($templateName) > 150) {
        tb_json_response(['success' => false, 'message' => 'Enter a valid template name.'], 422);
    }
    if (!in_array($templateType, tb_allowed_template_types(), true)) {
        tb_json_response(['success' => false, 'message' => 'Invalid template type.'], 422);
    }
    if ($canvasWidth < 200 || $canvasWidth > 3000 || $canvasHeight < 200 || $canvasHeight > 3000) {
        tb_json_response(['success' => false, 'message' => 'Invalid canvas size.'], 422);
    }

    $decoded = json_decode($jsonData, true);
    if (!is_array($decoded) || !isset($decoded['objects']) || !is_array($decoded['objects'])) {
        tb_json_response(['success' => false, 'message' => 'Invalid template JSON.'], 422);
    }

    $backgroundImage = trim((string)($_POST['existing_background_image'] ?? ''));
    if (!empty($_FILES['background_image'])) {
        $uploaded = tb_sanitize_upload($_FILES['background_image']);
        if ($uploaded !== null) {
            $backgroundImage = $uploaded;
        }
    }

    if ($id > 0) {
        $ownerStmt = $pdo->prepare("SELECT id FROM templates WHERE id = ? LIMIT 1");
        $ownerStmt->execute([$id]);
        if (!$ownerStmt->fetch()) {
            tb_json_response(['success' => false, 'message' => 'Template not found.'], 404);
        }

        $stmt = $pdo->prepare("
            UPDATE templates
            SET user_id = ?, template_name = ?, template_type = ?, canvas_width = ?, canvas_height = ?,
                background_image = ?, json_data = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([(int)$_SESSION['user_id'], $templateName, $templateType, $canvasWidth, $canvasHeight, $backgroundImage, $jsonData, $status ? 1 : 0, $id]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO templates (user_id, template_name, template_type, canvas_width, canvas_height, background_image, json_data, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([(int)$_SESSION['user_id'], $templateName, $templateType, $canvasWidth, $canvasHeight, $backgroundImage, $jsonData, $status ? 1 : 0]);
        $id = (int)$pdo->lastInsertId();
    }

    tb_json_response([
        'success' => true,
        'message' => 'Template saved.',
        'id' => $id,
        'template_name' => $templateName,
        'template_type' => $templateType,
        'background_image' => $backgroundImage
    ]);
} catch (Throwable $e) {
    tb_json_response(['success' => false, 'message' => $e->getMessage()], 500);
}

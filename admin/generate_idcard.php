<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/member_module.php';
require_once __DIR__ . '/../includes/template_builder.php';
require_once __DIR__ . '/../libs/fpdf/fpdf.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!canAccessModule($pdo, 'coordinator', 'page.volunteers')) {
    die('Unauthorized');
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die('Invalid request.');
}

$stmt = $pdo->prepare("SELECT * FROM volunteers WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$volunteer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$volunteer || empty($volunteer['id_card_no'])) {
    die('ID Card not active.');
}

$settings = mm_load_settings($pdo);
$brandId = (int)($_GET['brand'] ?? 0);
$templateId = (int)($_GET['template_id'] ?? 0);
$settings = mm_load_document_brand_settings($settings, $brandId);
$siteName = $settings['site_name'] ?? 'NGO';
$docNo = mm_volunteer_doc_no('id_card', $volunteer['id_card_no'] ?? ('VOL-' . $volunteer['id']));
$qrPayload = mm_volunteer_qr_payload($volunteer, $siteName, $docNo);
$customTemplate = $templateId > 0
    ? tb_load_template_by_id($pdo, $templateId, 'id_card')
    : tb_load_active_template($pdo, 'id_card');

if ($customTemplate) {
    $pdfContent = tb_render_template_pdf(
        $pdo,
        $customTemplate,
        tb_volunteer_pdf_context($volunteer, $settings, $docNo, $qrPayload)
    );
} else {
    $pdfContent = mm_pdf_volunteer_id_card($volunteer, $settings, $siteName, $docNo, $qrPayload);
}

if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="Volunteer_ID_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)$volunteer['id_card_no']) . '.pdf"');
echo $pdfContent;
exit;

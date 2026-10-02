<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../libs/fpdf/fpdf.php';
require_once __DIR__ . '/../includes/member_module.php';
require_once __DIR__ . '/../includes/template_builder.php';

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

$stmt = $pdo->prepare("SELECT * FROM volunteers WHERE id = ?");
$stmt->execute([$id]);
$volunteer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$volunteer || empty($volunteer['id_card_no'])) {
    die('Volunteer not found or certificate not available.');
}

$settings = mm_load_settings($pdo);
$tpl = mm_get_pdf_color_template($settings);
$siteName = $settings['site_name'] ?? 'NGO';
$docNo = 'VCERT-' . date('Y') . '-' . preg_replace('/[^A-Z0-9]/', '', strtoupper((string)($volunteer['id_card_no'] ?? ('VOL-' . $volunteer['id']))));
$verifyBase = ($settings['ngo_website'] ?? '') ? rtrim((string)$settings['ngo_website'], '/') : '';
$docVerifyUrl = $verifyBase
    ? ($verifyBase . '/volunteer-certificate-verify.php?doc=' . urlencode($docNo))
    : ('VCERT:' . $docNo . ';VOL:' . ($volunteer['id_card_no'] ?? $volunteer['id']));

$templateId = (int)($_GET['template_id'] ?? 0);
$customTemplate = $templateId > 0
    ? tb_load_template_by_id($pdo, $templateId, 'volunteer_certificate')
    : tb_load_active_template($pdo, 'volunteer_certificate');

if ($customTemplate) {
    $pdfContent = tb_render_template_pdf($pdo, $customTemplate, tb_volunteer_pdf_context($volunteer, $settings, $docNo, $docVerifyUrl));
} else {
    $pdfContent = mm_pdf_volunteer_certificate($volunteer, $settings, $tpl, $siteName, $docNo, $docVerifyUrl);
}

if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="Volunteer_Certificate_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', (string)($volunteer['id_card_no'] ?? $volunteer['id'])) . '.pdf"');
echo $pdfContent;
exit;

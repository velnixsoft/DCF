<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';
require_once __DIR__ . '/../../includes/member_module.php';
require_once __DIR__ . '/../../includes/template_builder.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'coordinator', 'page.volunteers')) {
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('error', 'Invalid request.');
    header('Location: ../volunteers.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM volunteers WHERE id = ?");
$stmt->execute([$id]);
$volunteer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$volunteer || empty($volunteer['email']) || empty($volunteer['id_card_no'])) {
    setFlash('error', 'Volunteer email or certificate data is missing.');
    header('Location: ../volunteers.php');
    exit;
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

try {
    mm_send_email(
        $settings,
        $volunteer['email'],
        $volunteer['name'],
        'Your Volunteer Certificate - ' . $siteName,
        '<p>Dear ' . htmlspecialchars($volunteer['name']) . ',</p>'
            . '<p>Please find your volunteer certificate attached.</p>'
            . '<p>Regards,<br>' . htmlspecialchars($siteName) . '</p>',
        [[
            'name' => 'Volunteer_Certificate_' . preg_replace('/[^A-Za-z0-9\-]/', '', (string)($volunteer['id_card_no'] ?? $volunteer['id'])) . '.pdf',
            'content' => $pdfContent,
        ]]
    );
    setFlash('success', 'Volunteer certificate emailed successfully.');
} catch (Throwable $e) {
    setFlash('error', 'Certificate email failed: ' . $e->getMessage());
}

header('Location: ../volunteers.php');
exit;

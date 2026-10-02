<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/member_module.php';
require_once __DIR__ . '/../../includes/template_builder.php';
require_once __DIR__ . '/../../libs/fpdf/fpdf.php';

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
    header('Location: ../volunteers.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM volunteers WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$volunteer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$volunteer || empty($volunteer['id_card_no']) || empty($volunteer['email'])) {
    setFlash('error', 'Cannot send email. ID card or volunteer email is missing.');
    header('Location: ../volunteers.php');
    exit;
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

$validUntil = !empty($volunteer['valid_until']) ? date('d F, Y', strtotime((string)$volunteer['valid_until'])) : '-';
$subject = 'Your Volunteer ID Card - ' . $siteName;
$body = '<p>Dear ' . htmlspecialchars((string)$volunteer['name']) . ',</p>'
    . '<p>Your admin-approved volunteer ID card is attached to this email.</p>'
    . '<p><strong>Volunteer ID:</strong> ' . htmlspecialchars((string)$volunteer['id_card_no']) . '<br>'
    . '<strong>Valid Until:</strong> ' . htmlspecialchars($validUntil) . '</p>'
    . '<p>Regards,<br>' . htmlspecialchars($siteName) . '</p>';

$sent = mm_send_email(
    $settings,
    $volunteer['email'],
    $volunteer['name'],
    $subject,
    $body,
    [[
        'name' => 'Volunteer_ID_Card_' . preg_replace('/[^A-Za-z0-9\-]/', '', (string)$volunteer['id_card_no']) . '.pdf',
        'content' => $pdfContent,
    ]]
);

if ($sent) {
    setFlash('success', 'ID card emailed to ' . $volunteer['email']);
} else {
    setFlash('error', 'Mail Error: ' . ($GLOBALS['mm_last_mail_error'] ?? 'Unknown error'));
}

header('Location: ../volunteers.php');
exit;

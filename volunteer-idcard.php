<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/member_module.php';
require_once __DIR__ . '/includes/template_builder.php';
require_once __DIR__ . '/libs/fpdf/fpdf.php';

if (empty($_SESSION['volunteer_logged_in']) || empty($_SESSION['volunteer_id'])) {
    die('Unauthorized.');
}

$id = (int)$_SESSION['volunteer_id'];
$stmt = $pdo->prepare("SELECT * FROM volunteers WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$volunteer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$volunteer || empty($volunteer['id_card_no'])) {
    die('ID Card not active.');
}

$settings = mm_load_settings($pdo);
$siteName = $settings['site_name'] ?? 'NGO';
$docNo = mm_volunteer_doc_no('id_card', $volunteer['id_card_no'] ?? ('VOL-' . $volunteer['id']));
$qrPayload = mm_volunteer_qr_payload($volunteer, $siteName, $docNo);
$customTemplate = tb_load_active_template($pdo, 'id_card');

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

$fileName = 'Volunteer_ID_' . preg_replace('/[^A-Za-z0-9\-]/', '', (string)$volunteer['id_card_no']) . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $fileName . '"');
echo $pdfContent;
exit;

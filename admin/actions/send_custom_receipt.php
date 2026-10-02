<?php
// ============================================================
// admin/actions/send_custom_receipt.php
// Dispatches Custom Receipt PDF as attachment to Payer Email
// ============================================================

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/member_module.php';
require_once __DIR__ . '/../../includes/custom_receipt_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (isset($_POST['ajax']) || isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized session.']);
        exit;
    }
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'coordinator', 'page.donations') && !canAccessModule($pdo, 'coordinator', 'page.expenses')) {
    if (isset($_POST['ajax']) || isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Access denied.']);
        exit;
    }
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

$id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));
$recipientEmail = trim((string)($_POST['email'] ?? ($_GET['email'] ?? '')));

if ($id <= 0) {
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid receipt ID.']);
        exit;
    }
    setFlash('error', 'Invalid Receipt ID.');
    header('Location: ../custom_receipts.php');
    exit;
}

// Fetch receipt record
$stmt = $pdo->prepare("SELECT * FROM `custom_receipts` WHERE `id` = ? LIMIT 1");
$stmt->execute([$id]);
$receipt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$receipt) {
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Receipt record not found.']);
        exit;
    }
    setFlash('error', 'Receipt record not found.');
    header('Location: ../custom_receipts.php');
    exit;
}

if (empty($recipientEmail)) {
    $recipientEmail = trim((string)($receipt['payer_email'] ?? ''));
}

if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Please provide a valid recipient email address.']);
        exit;
    }
    setFlash('error', 'Recipient email address is invalid or missing.');
    header('Location: ../custom_receipts.php');
    exit;
}

// Load settings
$settings = function_exists('mm_load_settings') ? mm_load_settings($pdo) : [];
if (empty($settings)) {
    $setStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = $setStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
}

$siteName = $settings['site_name'] ?? 'NGO Support Team';
$receiptNo = $receipt['receipt_no'];
$payerName = $receipt['payer_name'];
$amount = (float)$receipt['amount'];
$purpose = $receipt['purpose'];
$receiptDate = !empty($receipt['date']) ? date('d F, Y', strtotime((string)$receipt['date'])) : date('d F, Y');

// Check if PDF exists on disk or generate binary content
$pdfContent = '';
$pdfPath = $receipt['pdf_path'] ?? '';
$absolutePath = $pdfPath !== '' ? (__DIR__ . '/../../' . ltrim($pdfPath, '/')) : '';

if ($absolutePath !== '' && file_exists($absolutePath) && filesize($absolutePath) > 500) {
    $pdfContent = file_get_contents($absolutePath);
} else {
    $pdfContent = cr_render_receipt_pdf($pdo, $receipt, 'S');
}

$pdfFileName = 'Receipt_' . preg_replace('/[^A-Za-z0-9\-]/', '_', $receiptNo) . '.pdf';

// Build Professional HTML Email Body
$emailBody = "
<div style='font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; max-width: 620px; margin: auto; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; background-color: #ffffff;'>
    <div style='background: linear-gradient(135deg, #0F8B8D, #095254); color: #ffffff; padding: 30px 24px; text-align: center;'>
        <h2 style='margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.5px;'>Official Payment & Donation Receipt</h2>
        <p style='margin: 6px 0 0 0; font-size: 13px; color: #e6fffa; opacity: 0.9;'>{$siteName}</p>
    </div>
    <div style='padding: 28px 24px; color: #334155; line-height: 1.6;'>
        <p style='margin-top: 0; font-size: 15px;'>Dear <strong>" . htmlspecialchars($payerName) . "</strong>,</p>
        <p style='font-size: 14px; margin-bottom: 20px;'>Thank you for your valuable contribution and support. We are pleased to issue your official receipt with transaction reference details below:</p>
        
        <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; margin-bottom: 24px;'>
            <table style='width: 100%; font-size: 13px; border-collapse: collapse;'>
                <tr>
                    <td style='padding: 6px 0; color: #64748b; width: 40%;'><strong>Receipt Number:</strong></td>
                    <td style='padding: 6px 0; font-family: monospace; font-weight: bold; color: #0F8B8D;'>#" . htmlspecialchars($receiptNo) . "</td>
                </tr>
                <tr>
                    <td style='padding: 6px 0; color: #64748b;'><strong>Amount Received:</strong></td>
                    <td style='padding: 6px 0; font-weight: 800; color: #0f172a; font-size: 15px;'>INR " . number_format($amount, 2) . "</td>
                </tr>
                <tr>
                    <td style='padding: 6px 0; color: #64748b;'><strong>Purpose / Head:</strong></td>
                    <td style='padding: 6px 0; font-weight: 600; color: #334155;'>" . htmlspecialchars($purpose) . "</td>
                </tr>
                <tr>
                    <td style='padding: 6px 0; color: #64748b;'><strong>Payment Mode:</strong></td>
                    <td style='padding: 6px 0; color: #334155;'>" . htmlspecialchars($receipt['payment_mode']) . "</td>
                </tr>
                <tr>
                    <td style='padding: 6px 0; color: #64748b;'><strong>Receipt Date:</strong></td>
                    <td style='padding: 6px 0; color: #334155;'>{$receiptDate}</td>
                </tr>
            </table>
        </div>

        <p style='font-size: 13px; color: #475569;'>Your digitally signed official receipt (with scannable verification QR Code) is attached to this email as a PDF document for your records and tax filing.</p>
        
        <div style='text-align: center; margin: 28px 0;'>
            <a href='" . rtrim(appBaseUrl(), '/') . "/download-custom-receipt.php?id={$id}' style='background-color: #0F8B8D; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 10px; font-weight: bold; font-size: 13px; display: inline-block;'>View / Download PDF Online</a>
        </div>

        <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;'>
        <p style='font-size: 12px; color: #64748b; margin-bottom: 0;'>With warm regards,<br><strong style='color: #0F8B8D;'>" . htmlspecialchars($siteName) . "</strong></p>
    </div>
    <div style='background-color: #f1f5f9; padding: 14px; text-align: center; font-size: 11px; color: #94a3b8;'>
        This is an automated system receipt. Please do not reply directly to this email.
    </div>
</div>";

$emailSubject = "Official Receipt #{$receiptNo} - {$siteName}";

$sent = mm_send_email(
    $settings,
    $recipientEmail,
    $payerName,
    $emailSubject,
    $emailBody,
    [
        [
            'name' => $pdfFileName,
            'content' => $pdfContent,
        ]
    ]
);

if ($sent) {
    // Optionally update payer_email in DB if was empty
    if (empty($receipt['payer_email']) && !empty($recipientEmail)) {
        $upd = $pdo->prepare("UPDATE `custom_receipts` SET `payer_email` = ? WHERE `id` = ?");
        $upd->execute([$recipientEmail, $id]);
    }

    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'message' => "Receipt #{$receiptNo} successfully emailed to {$recipientEmail} with PDF attachment."
        ]);
        exit;
    }

    setFlash('success', "Receipt #{$receiptNo} successfully emailed to {$recipientEmail}.");
} else {
    $mailError = $GLOBALS['mm_last_mail_error'] ?? 'SMTP dispatch failure. Please check Mail settings.';
    
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => "Email dispatch failed: {$mailError}"
        ]);
        exit;
    }

    setFlash('error', "Email could not be sent. Error: {$mailError}");
}

header('Location: ../custom_receipts.php');
exit;

<?php
// ============================================================
// admin/actions/custom_receipt_logic.php
// Handles Custom Receipt Generation, Validation & PDF Storage
// ============================================================

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/member_module.php';
require_once __DIR__ . '/../../includes/custom_receipt_helper.php';
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. RBAC Check
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

$action = cleanInput($_POST['action'] ?? ($_GET['action'] ?? ''));
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
if ($currentUserId > 0) {
    $uChk = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $uChk->execute([$currentUserId]);
    if (!$uChk->fetch()) {
        $currentUserId = null;
    }
} else {
    $currentUserId = null;
}

try {
    // ── 1. GENERATE / CREATE CUSTOM RECEIPT ───────────────────────
    if ($action === 'create_receipt') {
        // Validate CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!validateCsrfToken($token)) {
            throw new Exception('Invalid security token. Please refresh and try again.');
        }

        $payerName = cleanInput($_POST['payer_name'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $purpose = cleanInput($_POST['purpose'] ?? 'Donation / Contribution');
        $paymentMode = cleanInput($_POST['payment_mode'] ?? 'Cash');
        $date = cleanInput($_POST['date'] ?? date('Y-m-d'));
        $transactionRef = cleanInput($_POST['transaction_ref'] ?? '');
        $payerPhone = cleanInput($_POST['payer_phone'] ?? '');
        $payerEmail = cleanInput($_POST['payer_email'] ?? '');
        $payerPan = cleanInput($_POST['payer_pan'] ?? '');
        $payerAddress = cleanInput($_POST['payer_address'] ?? '');
        $remarks = cleanInput($_POST['remarks'] ?? '');
        $isDownload = isset($_POST['download_now']) && $_POST['download_now'] == '1';

        // Validation
        if (empty($payerName)) {
            throw new Exception('Payer Name is required.');
        }
        if ($amount <= 0) {
            throw new Exception('Please enter a valid positive amount.');
        }
        if (empty($date)) {
            $date = date('Y-m-d');
        }

        // Auto-generate Receipt No
        $customReceiptNo = cleanInput($_POST['receipt_no'] ?? '');
        if (empty($customReceiptNo)) {
            $receiptNo = cr_generate_receipt_no($pdo, 'REC');
        } else {
            // Check uniqueness
            $chk = $pdo->prepare("SELECT id FROM custom_receipts WHERE receipt_no = ? LIMIT 1");
            $chk->execute([$customReceiptNo]);
            if ($chk->fetch()) {
                throw new Exception("Receipt number '{$customReceiptNo}' is already in use. Please use a unique number.");
            }
            $receiptNo = $customReceiptNo;
        }

        $amountInWords = cr_number_to_words($amount);

        // Define PDF path
        $pdfDir = __DIR__ . '/../../uploads/custom_receipts';
        if (!is_dir($pdfDir)) {
            @mkdir($pdfDir, 0777, true);
        }
        $safeFileName = 'Receipt_' . preg_replace('/[^A-Za-z0-9\-]/', '_', $receiptNo) . '.pdf';
        $relativePdfPath = 'uploads/custom_receipts/' . $safeFileName;
        $absolutePdfPath = $pdfDir . '/' . $safeFileName;

        // Insert into Database
        $stmt = $pdo->prepare("
            INSERT INTO `custom_receipts` (
                `receipt_no`, `payer_name`, `payer_phone`, `payer_email`, `payer_pan`, `payer_address`,
                `amount`, `amount_in_words`, `date`, `purpose`, `payment_mode`, `transaction_ref`,
                `generated_by`, `pdf_path`, `status`, `remarks`, `created_at`
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, 'generated', ?, NOW()
            )
        ");

        $stmt->execute([
            $receiptNo,
            $payerName,
            $payerPhone ?: null,
            $payerEmail ?: null,
            $payerPan ?: null,
            $payerAddress ?: null,
            $amount,
            $amountInWords,
            $date,
            $purpose,
            $paymentMode,
            $transactionRef ?: null,
            $currentUserId,
            $relativePdfPath,
            $remarks ?: null
        ]);

        $receiptId = $pdo->lastInsertId();

        // Render and Save PDF with QR code to disk
        $receiptData = [
            'id' => $receiptId,
            'receipt_no' => $receiptNo,
            'payer_name' => $payerName,
            'payer_phone' => $payerPhone,
            'payer_email' => $payerEmail,
            'payer_pan' => $payerPan,
            'payer_address' => $payerAddress,
            'amount' => $amount,
            'amount_in_words' => $amountInWords,
            'date' => $date,
            'purpose' => $purpose,
            'payment_mode' => $paymentMode,
            'transaction_ref' => $transactionRef,
            'remarks' => $remarks
        ];

        cr_render_receipt_pdf($pdo, $receiptData, 'F', $absolutePdfPath);

        // If AJAX request, return JSON
        if (isset($_POST['ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 'success',
                'message' => "Receipt #{$receiptNo} generated successfully with QR code.",
                'receipt_id' => $receiptId,
                'receipt_no' => $receiptNo,
                'pdf_url' => "../download_custom_receipt.php?id={$receiptId}",
                'direct_download_url' => "../download_custom_receipt.php?id={$receiptId}&download=1",
                'whatsapp_text' => "*Namaste {$payerName},*\n\nYour official receipt *#{$receiptNo}* for INR " . number_format($amount, 2) . " towards *{$purpose}* has been issued.\n\nDownload Receipt PDF: " . rtrim(appBaseUrl(), '/') . "/download-custom-receipt.php?id={$receiptId}\n\nThank you for your support!"
            ]);
            exit;
        }

        // If direct download requested from standard POST
        if ($isDownload) {
            header("Location: ../download_custom_receipt.php?id={$receiptId}&download=1");
            exit;
        }

        setFlash('success', "Receipt #{$receiptNo} generated successfully!");
        header("Location: ../download_custom_receipt.php?id={$receiptId}");
        exit;
    }

    // ── 2. DELETE CUSTOM RECEIPT ──────────────────────────────────
    if ($action === 'delete_receipt') {
        $token = $_POST['csrf_token'] ?? '';
        if (!validateCsrfToken($token)) {
            throw new Exception('Invalid security token.');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('Invalid receipt ID.');
        }

        // Fetch to remove PDF
        $stmt = $pdo->prepare("SELECT pdf_path, receipt_no FROM custom_receipts WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            if (!empty($row['pdf_path'])) {
                $filePath = __DIR__ . '/../../' . $row['pdf_path'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            $del = $pdo->prepare("DELETE FROM custom_receipts WHERE id = ?");
            $del->execute([$id]);
            setFlash('success', "Receipt #{$row['receipt_no']} deleted successfully.");
        }

        header('Location: ../custom_receipts.php');
        exit;
    }

    throw new Exception('Unknown or invalid action requested.');

} catch (Throwable $e) {
    if (isset($_POST['ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit;
    }

    setFlash('error', $e->getMessage());
    header('Location: ../generate_custom_receipt.php');
    exit;
}

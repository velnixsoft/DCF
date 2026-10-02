<?php
// ============================================================
// admin/actions/sanstha_certificate_logic.php
// Controller for Sanstha Authorization Certificate (CRUD, PDF, Email)
// Author: VELNIX SOFT / Antigravity AI
// Date: 2026-09-12
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/member_module.php';
require_once '../../includes/template_builder.php';
require_once '../../includes/sanstha_certificate_helper.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'coordinator', 'page.member_documents') && !canAccessModule($pdo, 'coordinator', 'page.visitor_certificates')) {
    setFlash('error', 'Access denied. Coordinator permissions required.');
    header('Location: ../dashboard.php');
    exit;
}

$action = cleanInput($_REQUEST['action'] ?? '');
$currentUserId = (int)($_SESSION['user_id'] ?? 0);

// ── 1. STREAM / DOWNLOAD PDF DIRECTLY ─────────────────────────
if ($action === 'download_certificate' || $action === 'view_certificate') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id <= 0) {
        die('Invalid Certificate ID.');
    }
    $mode = ($action === 'download_certificate') ? 'D' : 'I';
    generate_sanstha_certificate_pdf($pdo, $id, $mode);
    exit;
}

// ── 2. GET CERTIFICATE DATA (AJAX) ────────────────────────────
if ($action === 'get_certificate_data') {
    header('Content-Type: application/json; charset=utf-8');
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM sanstha_certificates WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $cert = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cert) {
        echo json_encode(['success' => true, 'certificate' => $cert]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Certificate not found.']);
    }
    exit;
}

// ── 3. CSRF Verification for State Changing Actions ───────────
$csrfToken = $_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? '');
if (!verifyCsrfToken($csrfToken)) {
    if (!empty($_POST['is_ajax']) || !empty($_GET['is_ajax'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Security token expired. Please refresh and try again.']);
        exit;
    }
    setFlash('error', 'Security token invalid.');
    header('Location: ../sanstha_certificates.php');
    exit;
}

// ── 4. SAVE / ISSUE SANSTHA CERTIFICATE ──────────────────────
if ($action === 'save_certificate') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
    $sansthaName = cleanInput($_POST['sanstha_name'] ?? '');
    $authPerson = cleanInput($_POST['authorized_person'] ?? '');
    $designation = cleanInput($_POST['designation'] ?? 'Center Head / Director');
    $authType = cleanInput($_POST['auth_type'] ?? 'Branch Office');
    $phone = cleanInput($_POST['contact_phone'] ?? '');
    $email = filter_var($_POST['contact_email'] ?? '', FILTER_SANITIZE_EMAIL);
    $address = cleanInput($_POST['center_address'] ?? '');
    $city = cleanInput($_POST['city'] ?? '');
    $district = cleanInput($_POST['district'] ?? '');
    $state = cleanInput($_POST['state'] ?? '');
    $pincode = cleanInput($_POST['pincode'] ?? '');
    $validFrom = cleanInput($_POST['valid_from'] ?? date('Y-m-d'));
    $validUntil = !empty($_POST['valid_until']) ? cleanInput($_POST['valid_until']) : null;
    $scope = cleanInput($_POST['scope_of_work'] ?? '');
    $templateId = filter_input(INPUT_POST, 'template_id', FILTER_VALIDATE_INT) ?: null;
    $templateNo = (int)($_POST['template_no'] ?? 1);
    $status = cleanInput($_POST['status'] ?? 'active');

    if ($templateNo < 1 || $templateNo > 6) $templateNo = 1;

    if ($sansthaName === '' || $authPerson === '') {
        $msg = 'Please fill required fields: Sanstha Name and Authorized Person Name.';
        if (!empty($_POST['is_ajax'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        }
        setFlash('error', $msg);
        header('Location: ../sanstha_certificates.php');
        exit;
    }

    try {
        if ($id > 0) {
            // Update existing certificate
            $stmt = $pdo->prepare("UPDATE sanstha_certificates SET
                sanstha_name = ?,
                authorized_person = ?,
                designation = ?,
                auth_type = ?,
                contact_phone = ?,
                contact_email = ?,
                center_address = ?,
                city = ?,
                district = ?,
                state = ?,
                pincode = ?,
                valid_from = ?,
                valid_until = ?,
                scope_of_work = ?,
                template_id = ?,
                template_no = ?,
                status = ?
                WHERE id = ?");
            $stmt->execute([
                $sansthaName, $authPerson, $designation, $authType, $phone, $email,
                $address, $city, $district, $state, $pincode, $validFrom, $validUntil,
                $scope, $templateId, $templateNo, $status, $id
            ]);
            $certId = $id;
        } else {
            // Create new certificate
            $certNo = generate_sanstha_cert_no($pdo);
            $stmt = $pdo->prepare("INSERT INTO sanstha_certificates (
                certificate_no, sanstha_name, authorized_person, designation, auth_type,
                contact_phone, contact_email, center_address, city, district, state,
                pincode, valid_from, valid_until, scope_of_work, template_id, template_no,
                status, issued_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $certNo, $sansthaName, $authPerson, $designation, $authType,
                $phone, $email, $address, $city, $district, $state,
                $pincode, $validFrom, $validUntil, $scope, $templateId, $templateNo,
                $status, $currentUserId
            ]);
            $certId = (int)$pdo->lastInsertId();
        }

        // Generate and save PDF
        $relPdfPath = generate_sanstha_certificate_pdf($pdo, $certId, 'F');

        $isAjax = !empty($_POST['is_ajax']);
        $saveAndDownload = !empty($_POST['save_and_download']);

        if ($saveAndDownload) {
            generate_sanstha_certificate_pdf($pdo, $certId, 'D');
            exit;
        }

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => 'Sanstha Authorization Certificate generated and saved successfully!',
                'id' => $certId,
                'pdf_path' => $relPdfPath
            ]);
            exit;
        }

        setFlash('success', 'Sanstha Authorization Certificate saved successfully!');
        header('Location: ../sanstha_certificates.php');
        exit;
    } catch (Throwable $e) {
        if (!empty($_POST['is_ajax'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
        setFlash('error', 'Error generating certificate: ' . $e->getMessage());
        header('Location: ../sanstha_certificates.php');
        exit;
    }
}

// ── 5. UPDATE STATUS (AJAX) ──────────────────────────────────
if ($action === 'update_status') {
    header('Content-Type: application/json; charset=utf-8');
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $newStatus = cleanInput($_POST['status'] ?? 'active');
    $allowed = ['active', 'expired', 'suspended', 'revoked'];

    if (!$id || !in_array($newStatus, $allowed, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status parameters.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE sanstha_certificates SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);
    echo json_encode(['success' => true, 'message' => 'Status updated to ' . ucfirst($newStatus)]);
    exit;
}

// ── 6. DELETE CERTIFICATE ─────────────────────────────────────
if ($action === 'delete_certificate') {
    header('Content-Type: application/json; charset=utf-8');
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid Certificate ID.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT pdf_path FROM sanstha_certificates WHERE id = ?");
    $stmt->execute([$id]);
    $pdf = $stmt->fetchColumn();

    if ($pdf && file_exists('../../' . $pdf)) {
        @unlink('../../' . $pdf);
    }

    $dStmt = $pdo->prepare("DELETE FROM sanstha_certificates WHERE id = ?");
    $dStmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Sanstha Certificate deleted successfully.']);
    exit;
}

// ── 7. EMAIL CERTIFICATE TO SANSTHA ──────────────────────────
if ($action === 'email_certificate') {
    header('Content-Type: application/json; charset=utf-8');
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $recipientEmail = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

    if (!$id || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please provide a valid recipient email address.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM sanstha_certificates WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $cert = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cert) {
        echo json_encode(['success' => false, 'message' => 'Certificate not found.']);
        exit;
    }

    // Ensure PDF is generated on disk
    $pdfRelPath = $cert['pdf_path'];
    if (!$pdfRelPath || !file_exists('../../' . $pdfRelPath)) {
        $pdfRelPath = generate_sanstha_certificate_pdf($pdo, $cert['id'], 'F');
    }
    $fullPdfPath = realpath('../../' . $pdfRelPath);

    $settings = mm_load_settings($pdo);
    $orgName = $settings['site_name'] ?? 'NGO Organization';

    $subject = "Official Certificate of Sanstha Authorization - {$cert['sanstha_name']} ({$cert['certificate_no']})";
    $body = "Dear {$cert['authorized_person']},\n\n"
          . "Greetings from {$orgName}.\n\n"
          . "Please find attached your official Certificate of Sanstha Authorization ({$cert['certificate_no']}) designating '{$cert['sanstha_name']}' as an authorized {$cert['auth_type']}.\n\n"
          . "Authorization Details:\n"
          . "• Certificate Reference: {$cert['certificate_no']}\n"
          . "• Authorization Category: {$cert['auth_type']}\n"
          . "• In-Charge: {$cert['authorized_person']} ({$cert['designation']})\n"
          . "• Valid From: " . date('d M Y', strtotime($cert['valid_from'])) . "\n"
          . "• Valid Until: " . (!empty($cert['valid_until']) ? date('d M Y', strtotime($cert['valid_until'])) : 'Perpetual') . "\n\n"
          . "You can verify this certificate anytime online by scanning the embedded QR code.\n\n"
          . "Warm Regards,\n"
          . "{$orgName} Governing Council\n"
          . ($settings['ngo_website'] ?? '');

    $sent = false;
    if (function_exists('sendNotificationEmail')) {
        $attachments = $fullPdfPath && file_exists($fullPdfPath) ? [$fullPdfPath] : [];
        $sent = sendNotificationEmail($pdo, $recipientEmail, $subject, $body, $attachments);
    } else {
        $headers = "From: " . ($settings['ngo_email'] ?? 'noreply@ngo.org') . "\r\n";
        $sent = @mail($recipientEmail, $subject, $body, $headers);
    }

    if ($sent) {
        echo json_encode(['success' => true, 'message' => "Certificate dispatched successfully to {$recipientEmail}!"]);
    } else {
        echo json_encode(['success' => false, 'message' => "Unable to dispatch email. Please verify mail server settings."]);
    }
    exit;
}

header('Location: ../sanstha_certificates.php');
exit;

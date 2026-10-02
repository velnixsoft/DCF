<?php
// ============================================================
// admin/actions/doctor_agreement_logic.php
// Controller for Doctor Agreements CRUD & Certificate Auto-Generation
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/member_module.php';
require_once '../../includes/doctor_certificate_helper.php';
require_once '../../includes/upload_validator.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'coordinator', 'page.healthcare')) {
    setFlash('error', 'Access denied. Healthcare coordinator permissions required.');
    header('Location: ../dashboard.php');
    exit;
}

$action = cleanInput($_REQUEST['action'] ?? '');
$currentUserId = (int)($_SESSION['user_id'] ?? 0);

// ── 1. AJAX: GET SINGLE AGREEMENT JSON ───────────────────────
if ($action === 'get_agreement_json') {
    header('Content-Type: application/json; charset=utf-8');
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid Agreement ID.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT 
            da.*,
            hp.name AS partner_name,
            hp.type AS partner_type,
            hp.speciality AS partner_speciality,
            hp.contact AS partner_contact,
            hp.email AS partner_email
        FROM `doctor_agreements` da
        JOIN `healthcare_providers` hp ON da.partner_id = hp.id
        WHERE da.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Agreement record not found.']);
    }
    exit;
}

// ── 2. STREAM / DOWNLOAD CERTIFICATE DIRECTLY ────────────────
if ($action === 'download_certificate' || $action === 'view_certificate') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id <= 0) {
        die('Invalid Agreement ID.');
    }
    $mode = ($action === 'download_certificate') ? 'D' : 'I';
    generateDoctorCertificatePdf($pdo, $id, $mode);
    exit;
}

// ── 3. CSRF Check for Modifying Actions ──────────────────────
$csrfToken = $_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? '');
if (!verifyCsrfToken($csrfToken)) {
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Security token invalid. Please reload.']);
        exit;
    }
    setFlash('error', 'Security validation failed (CSRF token mismatch).');
    header('Location: ../doctor_agreements.php');
    exit;
}

try {
    // ── 4. CREATE OR UPDATE DOCTOR AGREEMENT ─────────────────────
    if ($action === 'save') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $partnerId = filter_input(INPUT_POST, 'partner_id', FILTER_VALIDATE_INT);
        $agreementNo = cleanInput($_POST['agreement_no'] ?? '');
        $agreementTitle = cleanInput($_POST['agreement_title'] ?? 'Doctor Empanelment Agreement');
        $doctorName = cleanInput($_POST['doctor_name'] ?? '');
        $speciality = cleanInput($_POST['speciality'] ?? '');
        $discountTerms = cleanInput($_POST['discount_terms'] ?? '');
        $signedDate = cleanInput($_POST['signed_date'] ?? '');
        $validUntil = cleanInput($_POST['valid_until'] ?? '');
        $status = cleanInput($_POST['status'] ?? 'active');
        $remarks = cleanInput($_POST['remarks'] ?? '');

        if (!$partnerId) {
            throw new Exception('Please select a valid Healthcare Partner / Doctor from directory.');
        }

        // Auto-generate agreement number if empty
        if (empty($agreementNo)) {
            $year = date('Y');
            $count = (int)$pdo->query("SELECT COUNT(*) FROM doctor_agreements WHERE YEAR(created_at) = {$year}")->fetchColumn() + 1;
            $agreementNo = "AGR-DR-{$year}-" . str_pad((string)$count, 3, '0', STR_PAD_LEFT);
        }

        // Handle uploaded agreement document (PDF, DOC, DOCX)
        $agreementDocPath = null;
        $fileSize = null;

        if (isset($_FILES['agreement_doc']) && $_FILES['agreement_doc']['error'] === UPLOAD_ERR_OK) {
            $allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'png'];
            $fileInfo = pathinfo($_FILES['agreement_doc']['name']);
            $ext = strtolower($fileInfo['extension'] ?? '');

            if (in_array($ext, $allowedExts, true) && $_FILES['agreement_doc']['size'] <= 10 * 1024 * 1024) {
                $uploadDir = __DIR__ . '/../../uploads/agreements/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $safeName = 'Agreement_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $agreementNo) . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['agreement_doc']['tmp_name'], $uploadDir . $safeName)) {
                    $agreementDocPath = 'uploads/agreements/' . $safeName;
                    $bytes = filesize($uploadDir . $safeName);
                    $fileSize = ($bytes >= 1048576) ? round($bytes / 1048576, 2) . ' MB' : round($bytes / 1024) . ' KB';
                }
            } else {
                throw new Exception('Invalid agreement document file type or file exceeds 10MB limit.');
            }
        }

        if ($id && $id > 0) {
            // Update
            $sql = "
                UPDATE `doctor_agreements` SET
                    `partner_id` = ?,
                    `agreement_no` = ?,
                    `agreement_title` = ?,
                    `doctor_name` = ?,
                    `speciality` = ?,
                    `discount_terms` = ?,
                    `signed_date` = ?,
                    `valid_until` = ?,
                    `status` = ?,
                    `remarks` = ?
            ";
            $params = [
                $partnerId, $agreementNo, $agreementTitle, $doctorName ?: null,
                $speciality ?: null, $discountTerms ?: null, $signedDate ?: null,
                $validUntil ?: null, $status, $remarks ?: null
            ];

            if ($agreementDocPath) {
                $sql .= ", `agreement_doc_path` = ?, `file_size` = ?";
                $params[] = $agreementDocPath;
                $params[] = $fileSize;
            }

            $sql .= " WHERE id = ?";
            $params[] = $id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Auto re-generate certificate if active and already has certificate
            generateDoctorCertificatePdf($pdo, $id, 'S');

            setFlash('success', "Doctor Agreement [{$agreementNo}] updated and certificate refreshed successfully.");
        } else {
            // Create
            $stmt = $pdo->prepare("
                INSERT INTO `doctor_agreements` (
                    `partner_id`, `agreement_no`, `agreement_title`, `doctor_name`,
                    `speciality`, `discount_terms`, `agreement_doc_path`, `file_size`,
                    `signed_date`, `valid_until`, `status`, `remarks`, `created_by`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $partnerId, $agreementNo, $agreementTitle, $doctorName ?: null,
                $speciality ?: null, $discountTerms ?: null, $agreementDocPath, $fileSize,
                $signedDate ?: null, $validUntil ?: null, $status, $remarks ?: null,
                $currentUserId ?: null
            ]);
            $newId = (int)$pdo->lastInsertId();

            // Auto-generate official certificate PDF with QR code
            generateDoctorCertificatePdf($pdo, $newId, 'S');

            setFlash('success', "Doctor Agreement [{$agreementNo}] created and Authorized Doctor Certificate auto-generated!");
        }

        header('Location: ../doctor_agreements.php');
        exit;
    }

    // ── 5. MANUAL CERTIFICATE RE-GENERATION TRIGGER ─────────────
    if ($action === 'generate_certificate') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($id <= 0) {
            setFlash('error', 'Invalid Agreement ID.');
            header('Location: ../doctor_agreements.php');
            exit;
        }

        $res = generateDoctorCertificatePdf($pdo, $id, 'S');
        if ($res['success']) {
            setFlash('success', "Official Certificate [{$res['cert_no']}] generated with QR verification code successfully.");
        } else {
            setFlash('error', $res['message']);
        }

        header('Location: ../doctor_agreements.php');
        exit;
    }

    // ── 6. EMAIL CERTIFICATE TO DOCTOR / PARTNER ────────────────
    if ($action === 'email_certificate') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($id <= 0) {
            setFlash('error', 'Invalid Agreement ID.');
            header('Location: ../doctor_agreements.php');
            exit;
        }

        $res = generateDoctorCertificatePdf($pdo, $id, 'S');
        if (!$res['success']) {
            setFlash('error', $res['message']);
            header('Location: ../doctor_agreements.php');
            exit;
        }

        if (empty($res['email']) || !filter_var($res['email'], FILTER_VALIDATE_EMAIL)) {
            setFlash('error', "No valid email address configured for partner '{$res['doctor_name']}'. Please update partner contact details.");
            header('Location: ../doctor_agreements.php');
            exit;
        }

        $settings = mm_load_settings($pdo);
        $siteName = trim((string)($settings['site_name'] ?? 'Jaysmrutti Foundation')) ?: 'Jaysmrutti Foundation';
        $subject = "Official Empanelment Certificate & Authorization - {$siteName}";
        
        $emailBody = '
            <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1e293b; padding: 20px;">
                <h2 style="color: #0F8B8D;">Official Healthcare Partner Empanelment Certificate</h2>
                <p>Dear <strong>' . htmlspecialchars($res['doctor_name']) . '</strong>,</p>
                <p>We are honored to have you empanelled as an Official Healthcare Partner under the <strong>' . htmlspecialchars($siteName) . '</strong> Healthcare Outreach Mission.</p>
                <p>Please find attached your official <strong>Authorized Doctor & Healthcare Partner Certificate</strong> (Certificate No: <strong>' . htmlspecialchars($res['cert_no']) . '</strong>) featuring dynamic QR code authentication.</p>
                <p>You may display this certificate at your clinic / consultation room for patient verification.</p>
                <br>
                <p>Warm regards,<br><strong>' . htmlspecialchars($siteName) . ' Healthcare Directorate</strong></p>
            </div>
        ';

        try {
            if (function_exists('sendNotificationEmail')) {
                sendNotificationEmail($pdo, $res['email'], $subject, $emailBody, [
                    [
                        'name' => $res['file_name'],
                        'content' => $res['pdf_content']
                    ]
                ]);
            }
            setFlash('success', "Official certificate emailed to {$res['email']} successfully.");
        } catch (Throwable $e) {
            setFlash('error', "Failed to email certificate: " . $e->getMessage());
        }

        header('Location: ../doctor_agreements.php');
        exit;
    }

    // ── 7. DELETE AGREEMENT ─────────────────────────────────────
    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id <= 0) {
            setFlash('error', 'Invalid Agreement ID.');
            header('Location: ../doctor_agreements.php');
            exit;
        }

        $stmt = $pdo->prepare("SELECT agreement_no, agreement_doc_path, certificate_pdf_path FROM doctor_agreements WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Delete agreement doc
            if (!empty($row['agreement_doc_path'])) {
                $f = __DIR__ . '/../../' . ltrim($row['agreement_doc_path'], '/\\');
                if (file_exists($f)) @unlink($f);
            }
            // Delete certificate pdf
            if (!empty($row['certificate_pdf_path'])) {
                $f = __DIR__ . '/../../' . ltrim($row['certificate_pdf_path'], '/\\');
                if (file_exists($f)) @unlink($f);
            }

            $del = $pdo->prepare("DELETE FROM doctor_agreements WHERE id = ?");
            $del->execute([$id]);

            setFlash('success', "Doctor Agreement [{$row['agreement_no']}] and associated certificates deleted successfully.");
        } else {
            setFlash('error', 'Agreement record not found.');
        }

        header('Location: ../doctor_agreements.php');
        exit;
    }

    setFlash('error', 'Invalid action specified.');
    header('Location: ../doctor_agreements.php');
    exit;

} catch (Exception $e) {
    setFlash('error', 'Error: ' . $e->getMessage());
    header('Location: ../doctor_agreements.php');
    exit;
}

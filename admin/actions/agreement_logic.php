<?php
// ============================================================
// admin/actions/agreement_logic.php
// Controller for Agreements & MoUs Management CRUD & PDF Generation
// Author: VELNIX SOFT / Antigravity AI
// Date: 2026-09-12
// ============================================================

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/admin_audit.php';
require_once __DIR__ . '/../../includes/agreement_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── AUTH & PERMISSION CHECK ───────────────────────────────────
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (isset($_GET['action']) || isset($_POST['is_ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        exit;
    }
    header('Location: ../index.php');
    exit;
}

if (!checkRole($pdo, 'coordinator')) {
    if (isset($_GET['action']) || isset($_POST['is_ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied. Coordinator/Manager/Admin role required.']);
        exit;
    }
    setFlash('error', 'Access denied. Coordinator/Manager/Admin role required.');
    header('Location: ../dashboard.php');
    exit;
}

$adminUserId = (int)($_SESSION['user_id'] ?? 0);

// ── GET ACTIONS (DOWNLOAD / VIEW / AJAX) ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = cleanInput($_GET['action'] ?? '');

    // 1. Download Agreement PDF
    if ($action === 'download_agreement') {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            setFlash('error', 'Invalid agreement ID specified.');
            header('Location: ../agreements.php');
            exit;
        }

        try {
            generate_agreement_pdf($pdo, $id, 'download');
            exit;
        } catch (Exception $e) {
            setFlash('error', 'PDF Generation Error: ' . $e->getMessage());
            header('Location: ../agreements.php');
            exit;
        }
    }

    // 2. View Agreement PDF Inline
    if ($action === 'view_agreement') {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            setFlash('error', 'Invalid agreement ID specified.');
            header('Location: ../agreements.php');
            exit;
        }

        try {
            generate_agreement_pdf($pdo, $id, 'inline');
            exit;
        } catch (Exception $e) {
            setFlash('error', 'PDF View Error: ' . $e->getMessage());
            header('Location: ../agreements.php');
            exit;
        }
    }

    // 3. Get Template Text via AJAX
    if ($action === 'get_template_text') {
        header('Content-Type: application/json; charset=utf-8');
        $type = cleanInput($_GET['type'] ?? 'mou');
        $partnerName = cleanInput($_GET['partner_name'] ?? '');
        $partnerAddress = cleanInput($_GET['partner_address'] ?? '');
        $signedDate = cleanInput($_GET['signed_date'] ?? '');
        $validUntil = cleanInput($_GET['valid_until'] ?? '');

        $lhSettings = get_letterhead_settings($pdo);
        $preset = get_agreement_template_preset($type, [
            'lhSettings' => $lhSettings,
            'partner_name' => $partnerName ?: '[Partner Name]',
            'partner_address' => $partnerAddress ?: '[Partner Address]',
            'signed_date' => $signedDate,
            'valid_until' => $validUntil
        ]);

        echo json_encode([
            'success' => true,
            'type' => $type,
            'title' => $preset['title'],
            'content' => $preset['content'],
            'first_party_name' => $preset['first_party_name'],
            'first_party_designation' => $preset['first_party_designation'],
            'second_party_name' => $preset['second_party_name'],
            'second_party_designation' => $preset['second_party_designation']
        ]);
        exit;
    }

    // Default GET fallback
    header('Location: ../agreements.php');
    exit;
}

// ── POST ACTIONS (SAVE / EMAIL / DELETE / STATUS) ──────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = isset($_POST['is_ajax']) && $_POST['is_ajax'] === '1';
    $csrfToken = $_POST['csrf_token'] ?? '';

    // CSRF Check
    if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrfToken)) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Security token (CSRF) validation failed.']);
            exit;
        }
        setFlash('error', 'Security token validation failed. Please try again.');
        header('Location: ../agreements.php');
        exit;
    }

    try {
        $action = cleanInput($_POST['action'] ?? '');

        // ------------------------------------------------------
        // 1. SAVE AGREEMENT / MOU (CREATE OR UPDATE)
        // ------------------------------------------------------
        if ($action === 'save_agreement') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $type = cleanInput($_POST['type'] ?? 'mou');
            $partnerName = cleanInput($_POST['partner_name'] ?? '');
            $partnerType = cleanInput($_POST['partner_type'] ?? 'organization');
            $partnerContact = cleanInput($_POST['partner_contact'] ?? '');
            $partnerEmail = cleanInput($_POST['partner_email'] ?? '');
            $partnerAddress = cleanInput($_POST['partner_address'] ?? '');
            $title = cleanInput($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $signedStatus = cleanInput($_POST['signed_status'] ?? 'draft');
            $signedDate = !empty($_POST['signed_date']) ? cleanInput($_POST['signed_date']) : date('Y-m-d');
            $validUntil = !empty($_POST['valid_until']) ? cleanInput($_POST['valid_until']) : null;
            $firstPartyName = cleanInput($_POST['first_party_name'] ?? '');
            $firstPartyDesig = cleanInput($_POST['first_party_designation'] ?? '');
            $secondPartyName = cleanInput($_POST['second_party_name'] ?? '');
            $secondPartyDesig = cleanInput($_POST['second_party_designation'] ?? '');
            $downloadNow = isset($_POST['download_now']) && $_POST['download_now'] === '1';

            if (empty($partnerName)) {
                throw new Exception('Partner Organization / Second Party Name is required.');
            }
            if (empty($title)) {
                throw new Exception('Agreement / MoU Title is required.');
            }
            if (empty($content)) {
                throw new Exception('Agreement Content & Clauses are required.');
            }

            $allowedTypes = ['mou', 'authorization', 'service_agreement', 'partnership'];
            if (!in_array($type, $allowedTypes, true)) {
                $type = 'mou';
            }

            $allowedStatuses = ['draft', 'pending_signature', 'signed', 'expired', 'terminated'];
            if (!in_array($signedStatus, $allowedStatuses, true)) {
                $signedStatus = 'draft';
            }

            if ($id) {
                // UPDATE
                $stmtCheck = $pdo->prepare("SELECT * FROM agreements WHERE id = ? LIMIT 1");
                $stmtCheck->execute([$id]);
                $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                if (!$existing) {
                    throw new Exception('Agreement record not found.');
                }

                $stmtUpdate = $pdo->prepare("
                    UPDATE agreements SET
                        partner_name = ?,
                        partner_type = ?,
                        partner_contact = ?,
                        partner_email = ?,
                        partner_address = ?,
                        type = ?,
                        title = ?,
                        content = ?,
                        signed_status = ?,
                        signed_date = ?,
                        valid_until = ?,
                        first_party_name = ?,
                        first_party_designation = ?,
                        second_party_name = ?,
                        second_party_designation = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");

                $stmtUpdate->execute([
                    $partnerName, $partnerType, $partnerContact, $partnerEmail, $partnerAddress,
                    $type, $title, $content, $signedStatus, $signedDate, $validUntil,
                    $firstPartyName, $firstPartyDesig, $secondPartyName, $secondPartyDesig,
                    $id
                ]);

                $agreementId = $id;
                $agreementNo = $existing['agreement_no'];

                if (function_exists('admin_audit_log')) {
                    admin_audit_log($pdo, 'update_agreement', 'agreements', $id, "Updated Agreement {$agreementNo} for {$partnerName}");
                }
            } else {
                // CREATE NEW
                $agreementNo = generate_agreement_number($pdo, $type);

                $stmtInsert = $pdo->prepare("
                    INSERT INTO agreements (
                        agreement_no, partner_name, partner_type, partner_contact, partner_email, partner_address,
                        type, title, content, signed_status, signed_date, valid_until,
                        first_party_name, first_party_designation, second_party_name, second_party_designation,
                        created_by
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?
                    )
                ");

                $stmtInsert->execute([
                    $agreementNo, $partnerName, $partnerType, $partnerContact, $partnerEmail, $partnerAddress,
                    $type, $title, $content, $signedStatus, $signedDate, $validUntil,
                    $firstPartyName, $firstPartyDesig, $secondPartyName, $secondPartyDesig,
                    $adminUserId
                ]);

                $agreementId = (int)$pdo->lastInsertId();

                if (function_exists('admin_audit_log')) {
                    admin_audit_log($pdo, 'create_agreement', 'agreements', $agreementId, "Created Agreement {$agreementNo} ({$type}) for {$partnerName}");
                }
            }

            // Generate & Persist PDF on Letterhead
            $pdfRelPath = generate_agreement_pdf($pdo, $agreementId, 'save');

            if ($downloadNow) {
                generate_agreement_pdf($pdo, $agreementId, 'download');
                exit;
            }

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => "Agreement {$agreementNo} saved & PDF generated successfully.",
                    'agreement_id' => $agreementId,
                    'agreement_no' => $agreementNo,
                    'pdf_path' => $pdfRelPath
                ]);
                exit;
            }

            setFlash('success', "Agreement '{$agreementNo}' saved & PDF generated successfully.");
            header('Location: ../agreements.php');
            exit;
        }

        // ------------------------------------------------------
        // 2. EMAIL AGREEMENT TO PARTNER
        // ------------------------------------------------------
        elseif ($action === 'email_agreement') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new Exception('Invalid agreement ID.');
            }

            $stmt = $pdo->prepare("SELECT * FROM agreements WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $agr = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$agr) {
                throw new Exception('Agreement not found.');
            }

            $recipientEmail = cleanInput($_POST['email'] ?? ($agr['partner_email'] ?? ''));
            if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('A valid recipient email address is required to dispatch the agreement.');
            }

            // Ensure PDF is generated on disk
            $pdfRelPath = generate_agreement_pdf($pdo, $id, 'save');
            $pdfFullPath = __DIR__ . '/../../' . ltrim($pdfRelPath, '/');

            $lhSettings = get_letterhead_settings($pdo);
            $emailSubject = "Official {$agr['title']} [{$agr['agreement_no']}] - {$lhSettings['org_name']}";
            $emailBody = "Dear Authorized Representative,\n\n" .
                "Greetings from {$lhSettings['org_name']}.\n\n" .
                "Please find attached the official {$agr['title']} (Ref: {$agr['agreement_no']}) executed for mutual community cooperation and social welfare programs.\n\n" .
                "Agreement Details:\n" .
                "• Partner: {$agr['partner_name']}\n" .
                "• Reference No: {$agr['agreement_no']}\n" .
                "• Signed Date: " . date('d M Y', strtotime($agr['signed_date'])) . "\n" .
                (!empty($agr['valid_until']) ? "• Valid Until: " . date('d M Y', strtotime($agr['valid_until'])) . "\n" : "") .
                "• Status: " . strtoupper($agr['signed_status']) . "\n\n" .
                "Please retain this copy for your institutional records.\n\n" .
                "Warm regards,\n" .
                "{$lhSettings['signatory_name']}\n" .
                "{$lhSettings['signatory_designation']}\n" .
                "{$lhSettings['org_name']}\n" .
                "{$lhSettings['phone']} | {$lhSettings['email']}";

            $emailSent = false;
            if (function_exists('sendNotificationEmail')) {
                $emailSent = sendNotificationEmail($pdo, $recipientEmail, $emailSubject, $emailBody, [$pdfFullPath]);
            }

            if (function_exists('admin_audit_log')) {
                admin_audit_log($pdo, 'email_agreement', 'agreements', $id, "Emailed agreement {$agr['agreement_no']} to {$recipientEmail}");
            }

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => "Agreement {$agr['agreement_no']} dispatched to {$recipientEmail} successfully."
                ]);
                exit;
            }

            setFlash('success', "Agreement {$agr['agreement_no']} dispatched to {$recipientEmail} successfully.");
            header('Location: ../agreements.php');
            exit;
        }

        // ------------------------------------------------------
        // 3. UPDATE STATUS (QUICK TOGGLE VIA AJAX)
        // ------------------------------------------------------
        elseif ($action === 'update_status') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $newStatus = cleanInput($_POST['status'] ?? 'signed');

            $allowed = ['draft', 'pending_signature', 'signed', 'expired', 'terminated'];
            if (!in_array($newStatus, $allowed, true)) {
                throw new Exception('Invalid status value.');
            }

            $stmt = $pdo->prepare("UPDATE agreements SET signed_status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $id]);

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'new_status' => $newStatus]);
                exit;
            }

            setFlash('success', 'Status updated successfully.');
            header('Location: ../agreements.php');
            exit;
        }

        // ------------------------------------------------------
        // 4. DELETE AGREEMENT
        // ------------------------------------------------------
        elseif ($action === 'delete_agreement') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new Exception('Invalid agreement ID.');
            }

            $stmt = $pdo->prepare("SELECT * FROM agreements WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $agr = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($agr) {
                if (!empty($agr['pdf_path'])) {
                    $pdfFile = __DIR__ . '/../../' . ltrim($agr['pdf_path'], '/');
                    if (file_exists($pdfFile) && is_file($pdfFile)) {
                        @unlink($pdfFile);
                    }
                }

                $delStmt = $pdo->prepare("DELETE FROM agreements WHERE id = ?");
                $delStmt->execute([$id]);

                if (function_exists('admin_audit_log')) {
                    admin_audit_log($pdo, 'delete_agreement', 'agreements', $id, "Deleted Agreement {$agr['agreement_no']} for {$agr['partner_name']}");
                }
            }

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'message' => 'Agreement record and PDF deleted successfully.']);
                exit;
            }

            setFlash('success', 'Agreement deleted successfully.');
            header('Location: ../agreements.php');
            exit;
        }

        throw new Exception('Unknown action requested.');

    } catch (Exception $e) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }

        setFlash('error', $e->getMessage());
        header('Location: ../agreements.php');
        exit;
    }
}

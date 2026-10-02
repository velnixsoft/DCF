<?php
// ============================================================
// process/acknowledge_agreement.php
// Handles Partner Digital Acknowledgment ("I Agree" + Timestamp + IP)
// Author: VELNIX SOFT / Antigravity AI
// Date: 2026-09-12
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/agreement_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAjax = isset($_POST['is_ajax']) && $_POST['is_ajax'] === '1';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit;
    }
    header('Location: ../member-dashboard.php');
    exit;
}

// Verify CSRF
$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrfToken)) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Security token (CSRF) validation failed.']);
        exit;
    }
    setFlash('error', 'Security token expired. Please try again.');
    header('Location: ../member-dashboard.php');
    exit;
}

try {
    $agreementId = filter_input(INPUT_POST, 'agreement_id', FILTER_VALIDATE_INT);
    $token = cleanInput($_POST['token'] ?? '');
    $iAgree = isset($_POST['i_agree']) && $_POST['i_agree'] === '1';
    $signatoryName = cleanInput($_POST['signatory_name'] ?? '');
    $signatoryDesignation = cleanInput($_POST['signatory_designation'] ?? '');

    if (!$agreementId) {
        throw new Exception('Invalid Agreement ID.');
    }

    if (!$iAgree) {
        throw new Exception('You must check and confirm the "I Agree" checkbox to digitally acknowledge this Memorandum of Understanding.');
    }

    if (empty($signatoryName)) {
        throw new Exception('Signatory full name is required for digital verification.');
    }

    // Find agreement
    $stmt = $pdo->prepare("SELECT * FROM agreements WHERE id = ? LIMIT 1");
    $stmt->execute([$agreementId]);
    $agr = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agr) {
        throw new Exception('Agreement document not found.');
    }

    // Check authorization: Either logged-in member/partner or matching secure token
    $isAuthorized = false;
    if (!empty($_SESSION['member_logged_in']) && !empty($_SESSION['member_id'])) {
        $mId = (int)$_SESSION['member_id'];
        $mEmail = (string)($_SESSION['member_email'] ?? '');
        $mPhone = (string)($_SESSION['member_phone'] ?? '');

        if ($agr['partner_member_id'] == $mId || $agr['partner_email'] === $mEmail || ($agr['partner_contact'] && $agr['partner_contact'] === $mPhone)) {
            $isAuthorized = true;
        }
    }

    if (!empty($token) && !empty($agr['acknowledgment_token']) && hash_equals($agr['acknowledgment_token'], $token)) {
        $isAuthorized = true;
    }

    // If logged in as admin/coordinator, allow testing/ack
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        $isAuthorized = true;
    }

    if (!$isAuthorized) {
        // Fallback: Check if partner_name matches member name
        if (!empty($_SESSION['member_name']) && stripos($agr['partner_name'], $_SESSION['member_name']) !== false) {
            $isAuthorized = true;
        }
    }

    if (!$isAuthorized) {
        throw new Exception('Unauthorized access. You do not have permission to digitally sign this agreement.');
    }

    // Client IP & User Agent
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $clientIp = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Browser', 0, 250);
    $now = date('Y-m-d H:i:s');

    // Update database record
    $upd = $pdo->prepare("
        UPDATE agreements SET
            is_acknowledged = 1,
            acknowledged_at = ?,
            acknowledged_name = ?,
            acknowledged_ip = ?,
            acknowledged_user_agent = ?,
            second_party_name = COALESCE(NULLIF(second_party_name, ''), ?),
            second_party_designation = COALESCE(NULLIF(second_party_designation, ''), ?),
            signed_status = 'signed',
            updated_at = NOW()
        WHERE id = ?
    ");
    $upd->execute([
        $now,
        $signatoryName,
        $clientIp,
        $userAgent,
        $signatoryName,
        $signatoryDesignation ?: 'Authorized Signatory',
        $agreementId
    ]);

    // Regenerate letterhead PDF with updated signed status
    $updatedAgr = array_merge($agr, [
        'is_acknowledged' => 1,
        'acknowledged_at' => $now,
        'acknowledged_name' => $signatoryName,
        'signed_status' => 'signed'
    ]);
    generate_agreement_pdf($pdo, $updatedAgr, 'save');

    $formattedTime = date('d M Y, h:i A', strtotime($now));

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => "Memorandum of Understanding [{$agr['agreement_no']}] digitally acknowledged and executed successfully on {$formattedTime}.",
            'agreement_no' => $agr['agreement_no'],
            'acknowledged_at' => $formattedTime,
            'signatory_name' => $signatoryName,
            'client_ip' => $clientIp
        ]);
        exit;
    }

    setFlash('success', "MoU [{$agr['agreement_no']}] digitally acknowledged successfully on {$formattedTime}.");
    header("Location: ../view-agreement.php?id={$agreementId}");
    exit;

} catch (Exception $e) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }

    setFlash('error', $e->getMessage());
    $redirectUrl = !empty($agreementId) ? "../view-agreement.php?id={$agreementId}" : "../member-dashboard.php";
    header("Location: {$redirectUrl}");
    exit;
}

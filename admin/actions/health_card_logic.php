<?php
// ============================================================
// admin/actions/health_card_logic.php
// Controller for Health Card Applications Approval, Rejection & Management
// Compatible with Volunteer/Member ID Card & Certificate logic pattern
// ============================================================

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/health_card_helper.php';
require_once __DIR__ . '/../../includes/upload_validator.php';
require_once __DIR__ . '/../../includes/admin_audit.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── AUTH & ROLE CHECK ─────────────────────────────────────────
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (isset($_GET['action']) || isset($_POST['is_ajax'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        exit;
    }
    header('Location: ../index.php');
    exit;
}

if (!checkRole($pdo, 'coordinator')) {
    if (isset($_GET['action']) || isset($_POST['is_ajax'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Access denied. Coordinator/Manager/Admin role required.']);
        exit;
    }
    setFlash('error', 'Access denied. Coordinator/Manager/Admin role required.');
    header('Location: ../dashboard.php');
    exit;
}

$adminUserId = (int)($_SESSION['user_id'] ?? 0);

// ── GET ACTIONS (AJAX / JSON) ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = cleanInput($_GET['action'] ?? '');

    if ($action === 'get_card_json') {
        header('Content-Type: application/json; charset=utf-8');
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid Card ID.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM health_cards WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$card) {
            echo json_encode(['success' => false, 'message' => 'Health Card not found.']);
            exit;
        }

        echo json_encode(['success' => true, 'data' => $card]);
        exit;
    }

    header('Location: ../health_card_applications.php');
    exit;
}

// ── POST ACTIONS ──────────────────────────────────────────────
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
        setFlash('error', 'Security token (CSRF) validation failed. Please try again.');
        header('Location: ../health_card_applications.php');
        exit;
    }

    $action = cleanInput($_POST['action'] ?? '');

    try {
        // ------------------------------------------------------
        // 1. APPROVE HEALTH CARD APPLICATION & GENERATE PDF+QR
        // ------------------------------------------------------
        if ($action === 'approve_card') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new Exception('Invalid application ID specified.');
            }

            $stmt = $pdo->prepare("SELECT * FROM health_cards WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $card = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$card) {
                throw new Exception('Health card application not found.');
            }

            // Auto-generate card number if not present or placeholder
            $cardNumber = $card['card_number'];
            if (empty($cardNumber) || strpos($cardNumber, 'PENDING') !== false || $cardNumber === '0') {
                $cardNumber = hc_generate_card_number($pdo);
            }

            $issueDate = date('Y-m-d');
            $validityYears = filter_input(INPUT_POST, 'validity_years', FILTER_VALIDATE_INT) ?: 1;
            $expiryDate = date('Y-m-d', strtotime("+{$validityYears} year"));
            $status = 'active';

            $verifyUrl = appBaseUrl() . "/verify-health-card.php?card=" . urlencode($cardNumber);
            $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($verifyUrl);

            // Update record first
            $upd = $pdo->prepare("
                UPDATE health_cards SET
                    card_number = ?,
                    issue_date = ?,
                    expiry_date = ?,
                    status = ?,
                    qr_code_path = ?,
                    issued_by = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $upd->execute([$cardNumber, $issueDate, $expiryDate, $status, $qrCodeUrl, $adminUserId, $id]);

            // Render and store the official PDF with embedded QR & photo
            $pdfRelPath = hc_save_card_pdf($pdo, $id);

            // Log Audit
            if (function_exists('admin_audit_log')) {
                admin_audit_log($pdo, 'approve_health_card', 'health_cards', $id, "Approved health card {$cardNumber} for {$card['applicant_name']}");
            }

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => "Health Card for '{$card['applicant_name']}' approved successfully with Card No: {$cardNumber}",
                    'card_number' => $cardNumber,
                    'pdf_path' => $pdfRelPath,
                    'status' => 'active'
                ]);
                exit;
            }

            setFlash('success', "Health Card for '{$card['applicant_name']}' approved successfully with Card No: {$cardNumber}");
            header('Location: ../health_card_applications.php');
            exit;
        }

        // ------------------------------------------------------
        // 2. REJECT HEALTH CARD APPLICATION
        // ------------------------------------------------------
        elseif ($action === 'reject_card') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new Exception('Invalid application ID.');
            }

            $stmt = $pdo->prepare("SELECT * FROM health_cards WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $card = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$card) {
                throw new Exception('Application not found.');
            }

            $reason = cleanInput($_POST['reason'] ?? 'Documents incomplete / Not eligible');
            $updatedRemarks = ($card['remarks'] ? $card['remarks'] . " | " : "") . "Rejection Reason: " . $reason;

            $upd = $pdo->prepare("UPDATE health_cards SET status = 'rejected', remarks = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$updatedRemarks, $id]);

            if (function_exists('admin_audit_log')) {
                admin_audit_log($pdo, 'reject_health_card', 'health_cards', $id, "Rejected health card {$card['card_number']}: {$reason}");
            }

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => "Application for '{$card['applicant_name']}' rejected.",
                    'status' => 'rejected'
                ]);
                exit;
            }

            setFlash('success', "Application for '{$card['applicant_name']}' has been marked as Rejected.");
            header('Location: ../health_card_applications.php');
            exit;
        }

        // ------------------------------------------------------
        // 3. RENEW HEALTH CARD
        // ------------------------------------------------------
        elseif ($action === 'renew_card') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new Exception('Invalid card ID.');
            }

            $stmt = $pdo->prepare("SELECT * FROM health_cards WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $card = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$card) {
                throw new Exception('Health card not found.');
            }

            $issueDate = date('Y-m-d');
            $expiryDate = date('Y-m-d', strtotime('+1 year'));
            $status = 'renewed';

            $upd = $pdo->prepare("
                UPDATE health_cards SET
                    issue_date = ?,
                    expiry_date = ?,
                    status = 'active',
                    issued_by = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $upd->execute([$issueDate, $expiryDate, $adminUserId, $id]);

            $pdfRelPath = hc_save_card_pdf($pdo, $id);

            if (function_exists('admin_audit_log')) {
                admin_audit_log($pdo, 'renew_health_card', 'health_cards', $id, "Renewed health card {$card['card_number']}");
            }

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => "Health Card '{$card['card_number']}' renewed successfully for 1 year.",
                    'expiry_date' => $expiryDate,
                    'pdf_path' => $pdfRelPath
                ]);
                exit;
            }

            setFlash('success', "Health Card '{$card['card_number']}' renewed successfully.");
            header('Location: ../health_card_applications.php');
            exit;
        }

        // ------------------------------------------------------
        // 4. RE-GENERATE CARD PDF
        // ------------------------------------------------------
        elseif ($action === 'regenerate_pdf') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new Exception('Invalid card ID.');
            }

            $pdfRelPath = hc_save_card_pdf($pdo, $id);

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => 'Health Card PDF regenerated successfully.',
                    'pdf_path' => $pdfRelPath
                ]);
                exit;
            }

            setFlash('success', 'Health Card PDF regenerated successfully.');
            header('Location: ../health_card_applications.php');
            exit;
        }

        // ------------------------------------------------------
        // 5. DELETE HEALTH CARD
        // ------------------------------------------------------
        elseif ($action === 'delete_card') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new Exception('Invalid card ID.');
            }

            $stmt = $pdo->prepare("SELECT * FROM health_cards WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $card = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$card) {
                throw new Exception('Health card not found.');
            }

            // Remove photo if exists
            if (!empty($card['photo'])) {
                $photoPath = __DIR__ . '/../../' . ltrim($card['photo'], '/');
                if (file_exists($photoPath) && is_file($photoPath)) {
                    @unlink($photoPath);
                }
            }

            // Remove PDF if exists
            if (!empty($card['pdf_path'])) {
                $pdfPath = __DIR__ . '/../../' . ltrim($card['pdf_path'], '/');
                if (file_exists($pdfPath) && is_file($pdfPath)) {
                    @unlink($pdfPath);
                }
            }

            $del = $pdo->prepare("DELETE FROM health_cards WHERE id = ?");
            $del->execute([$id]);

            if (function_exists('admin_audit_log')) {
                admin_audit_log($pdo, 'delete_health_card', 'health_cards', $id, "Deleted health card {$card['card_number']} - {$card['applicant_name']}");
            }

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => "Health Card '{$card['card_number']}' deleted successfully."
                ]);
                exit;
            }

            setFlash('success', "Health Card '{$card['card_number']}' deleted successfully.");
            header('Location: ../health_card_applications.php');
            exit;
        }

        else {
            throw new Exception("Unknown action: {$action}");
        }

    } catch (Throwable $e) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }

        setFlash('error', $e->getMessage());
        header('Location: ../health_card_applications.php');
        exit;
    }
}

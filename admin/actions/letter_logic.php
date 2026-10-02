<?php
// ============================================================
// admin/actions/letter_logic.php
// Handles CRUD and PDF generation for Letters & Letterhead Module
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/letterhead_helper.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'coordinator', 'page.letters')) {
    setFlash('error', 'Access denied. You do not have permission to manage letters.');
    header('Location: ../dashboard.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    setFlash('error', 'Invalid or expired security token. Please try again.');
    header('Location: ../letters.php');
    exit;
}

$action = cleanInput($_POST['action'] ?? '');
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
    // ── 1. CREATE LETTER ─────────────────────────────────────────
    if ($action === 'create_letter') {
        $letter_type = cleanInput($_POST['letter_type'] ?? 'General Official Letter');
        $subject = cleanInput($_POST['subject'] ?? '');
        $recipient_name = cleanInput($_POST['recipient_name'] ?? '');
        $recipient_designation = cleanInput($_POST['recipient_designation'] ?? '');
        $recipient_organization = cleanInput($_POST['recipient_organization'] ?? '');
        $recipient_address = cleanInput($_POST['recipient_address'] ?? '');
        $recipient_email = cleanInput($_POST['recipient_email'] ?? '');
        $recipient_phone = cleanInput($_POST['recipient_phone'] ?? '');
        $generated_date = cleanInput($_POST['generated_date'] ?? date('Y-m-d'));
        $content = $_POST['content'] ?? '';
        $status = in_array($_POST['status'] ?? '', ['Draft', 'Generated', 'Sent', 'Archived'], true) ? $_POST['status'] : 'Generated';
        $signatory_name = cleanInput($_POST['signatory_name'] ?? '');
        $signatory_designation = cleanInput($_POST['signatory_designation'] ?? '');
        $reference_no = cleanInput($_POST['reference_no'] ?? '');

        if (empty($subject) || empty($recipient_name) || empty($content)) {
            setFlash('error', 'Subject, Recipient Name, and Letter Content are required.');
            header('Location: ../letter_editor.php');
            exit;
        }

        // Auto-generate reference number if blank
        if (empty($reference_no)) {
            $year = date('Y', strtotime($generated_date));
            $seq = $pdo->query("SELECT COUNT(*) FROM letters WHERE YEAR(generated_date) = {$year}")->fetchColumn() + 1;
            $reference_no = "JMF/LTR/{$year}/" . str_pad($seq, 3, '0', STR_PAD_LEFT);
        }

        // Check uniqueness of reference_no
        $chk = $pdo->prepare("SELECT id FROM letters WHERE reference_no = ?");
        $chk->execute([$reference_no]);
        if ($chk->fetch()) {
            $reference_no .= '-' . time();
        }

        $stmt = $pdo->prepare("
            INSERT INTO letters (
                reference_no, letter_type, subject, content, recipient_name, 
                recipient_designation, recipient_organization, recipient_address, 
                recipient_email, recipient_phone, generated_date, generated_by, 
                status, signatory_name, signatory_designation
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $reference_no, $letter_type, $subject, $content, $recipient_name,
            $recipient_designation, $recipient_organization, $recipient_address,
            $recipient_email, $recipient_phone, $generated_date, $currentUserId,
            $status, $signatory_name, $signatory_designation
        ]);

        $letterId = (int)$pdo->lastInsertId();

        // Optional: Pre-generate PDF file on disk
        $pdfDir = __DIR__ . '/../../uploads/letters/';
        if (!is_dir($pdfDir)) mkdir($pdfDir, 0777, true);
        $pdfFileName = 'Letter_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $reference_no) . '_' . time() . '.pdf';
        $savePath = $pdfDir . $pdfFileName;
        $relPath = 'uploads/letters/' . $pdfFileName;

        try {
            generate_letterhead_pdf($pdo, $letterId, 'F', $savePath);
            $pdo->prepare("UPDATE letters SET pdf_path = ? WHERE id = ?")->execute([$relPath, $letterId]);
        } catch (Throwable $e) {
            // Non-fatal, PDF will still generate on-the-fly
        }

        setFlash('success', 'Official Letter generated and saved successfully.');
        
        if (isset($_POST['save_and_download'])) {
            header("Location: ../download_letter.php?id={$letterId}&download=1");
        } elseif (isset($_POST['save_and_email'])) {
            $emailResult = email_letterhead_to_recipient($pdo, $letterId, $_POST['recipient_email'], $_POST['email_message'] ?? null);
            if ($emailResult['success']) {
                setFlash('success', 'Letter saved and emailed successfully to ' . htmlspecialchars($_POST['recipient_email']));
            } else {
                setFlash('warning', 'Letter saved, but email dispatch failed: ' . $emailResult['message']);
            }
            header("Location: ../letters.php");
        } else {
            header("Location: ../letters.php");
        }
        exit;
    }

    // ── 2. UPDATE LETTER ─────────────────────────────────────────
    if ($action === 'update_letter') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            setFlash('error', 'Invalid Letter ID.');
            header('Location: ../letters.php');
            exit;
        }

        $letter_type = cleanInput($_POST['letter_type'] ?? 'General Official Letter');
        $subject = cleanInput($_POST['subject'] ?? '');
        $recipient_name = cleanInput($_POST['recipient_name'] ?? '');
        $recipient_designation = cleanInput($_POST['recipient_designation'] ?? '');
        $recipient_organization = cleanInput($_POST['recipient_organization'] ?? '');
        $recipient_address = cleanInput($_POST['recipient_address'] ?? '');
        $recipient_email = cleanInput($_POST['recipient_email'] ?? '');
        $recipient_phone = cleanInput($_POST['recipient_phone'] ?? '');
        $generated_date = cleanInput($_POST['generated_date'] ?? date('Y-m-d'));
        $content = $_POST['content'] ?? '';
        $status = in_array($_POST['status'] ?? '', ['Draft', 'Generated', 'Sent', 'Archived'], true) ? $_POST['status'] : 'Generated';
        $signatory_name = cleanInput($_POST['signatory_name'] ?? '');
        $signatory_designation = cleanInput($_POST['signatory_designation'] ?? '');
        $reference_no = cleanInput($_POST['reference_no'] ?? '');

        if (empty($subject) || empty($recipient_name) || empty($content)) {
            setFlash('error', 'Subject, Recipient Name, and Letter Content are required.');
            header("Location: ../letter_editor.php?id={$id}");
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE letters SET 
                reference_no = ?, letter_type = ?, subject = ?, content = ?, 
                recipient_name = ?, recipient_designation = ?, recipient_organization = ?, 
                recipient_address = ?, recipient_email = ?, recipient_phone = ?, 
                generated_date = ?, status = ?, signatory_name = ?, signatory_designation = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $reference_no, $letter_type, $subject, $content,
            $recipient_name, $recipient_designation, $recipient_organization,
            $recipient_address, $recipient_email, $recipient_phone,
            $generated_date, $status, $signatory_name, $signatory_designation,
            $id
        ]);

        // Re-generate updated PDF file on disk
        $pdfDir = __DIR__ . '/../../uploads/letters/';
        if (!is_dir($pdfDir)) mkdir($pdfDir, 0777, true);
        $pdfFileName = 'Letter_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $reference_no) . '_' . time() . '.pdf';
        $savePath = $pdfDir . $pdfFileName;
        $relPath = 'uploads/letters/' . $pdfFileName;

        try {
            generate_letterhead_pdf($pdo, $id, 'F', $savePath);
            $pdo->prepare("UPDATE letters SET pdf_path = ? WHERE id = ?")->execute([$relPath, $id]);
        } catch (Throwable $e) {}

        setFlash('success', 'Letter updated successfully.');

        if (isset($_POST['save_and_download'])) {
            header("Location: ../download_letter.php?id={$id}&download=1");
        } elseif (isset($_POST['save_and_email'])) {
            $emailResult = email_letterhead_to_recipient($pdo, $id, $_POST['recipient_email'], $_POST['email_message'] ?? null);
            if ($emailResult['success']) {
                setFlash('success', 'Letter updated and emailed successfully to ' . htmlspecialchars($_POST['recipient_email']));
            } else {
                setFlash('warning', 'Letter updated, but email dispatch failed: ' . $emailResult['message']);
            }
            header("Location: ../letters.php");
        } else {
            header("Location: ../letters.php");
        }
        exit;
    }

    // ── 3. DELETE LETTER ─────────────────────────────────────────
    if ($action === 'delete_letter') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            setFlash('error', 'Invalid Letter ID.');
            header('Location: ../letters.php');
            exit;
        }

        // Remove PDF if exists
        $pathStmt = $pdo->prepare("SELECT pdf_path FROM letters WHERE id = ?");
        $pathStmt->execute([$id]);
        $oldPath = $pathStmt->fetchColumn();
        if ($oldPath && file_exists(__DIR__ . '/../../' . $oldPath)) {
            @unlink(__DIR__ . '/../../' . $oldPath);
        }

        $pdo->prepare("DELETE FROM letters WHERE id = ?")->execute([$id]);
        setFlash('success', 'Letter record deleted successfully.');
        header('Location: ../letters.php');
        exit;
    }

    // ── 4. UPDATE STATUS ─────────────────────────────────────────
    if ($action === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = in_array($_POST['status'] ?? '', ['Draft', 'Generated', 'Sent', 'Archived'], true) ? $_POST['status'] : 'Generated';

        $pdo->prepare("UPDATE letters SET status = ? WHERE id = ?")->execute([$status, $id]);
        setFlash('success', 'Letter status updated to ' . $status . '.');
        header('Location: ../letters.php');
        exit;
    }

    // ── 5. EMAIL LETTER TO RECIPIENT ─────────────────────────────
    if ($action === 'email_letter') {
        $id = (int)($_POST['id'] ?? 0);
        $targetEmail = cleanInput($_POST['recipient_email'] ?? '');
        $customMessage = cleanInput($_POST['custom_message'] ?? '');

        if (!$id) {
            setFlash('error', 'Invalid Letter ID.');
            header('Location: ../letters.php');
            exit;
        }

        $res = email_letterhead_to_recipient($pdo, $id, $targetEmail, $customMessage);
        if ($res['success']) {
            setFlash('success', $res['message']);
        } else {
            setFlash('error', 'Email Dispatch Failed: ' . $res['message']);
        }
        header('Location: ../letters.php');
        exit;
    }

    // Unknown action
    setFlash('error', 'Unrecognized action request.');
    header('Location: ../letters.php');
    exit;

} catch (Throwable $e) {
    setFlash('error', 'Operation failed: ' . $e->getMessage());
    header('Location: ../letters.php');
    exit;
}

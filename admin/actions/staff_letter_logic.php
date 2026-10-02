<?php
// ============================================================
// admin/actions/staff_letter_logic.php
// Controller for Staff & Volunteer Letters Management (CRUD, PDF, Email)
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/member_module.php';
require_once '../../includes/letterhead_helper.php';
require_once '../../includes/staff_letter_helper.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'coordinator', 'page.letters')) {
    setFlash('error', 'Access denied. Letterhead coordinator permissions required.');
    header('Location: ../dashboard.php');
    exit;
}

$action = cleanInput($_REQUEST['action'] ?? '');
$currentUserId = (int)($_SESSION['user_id'] ?? 0);

// ── 1. AJAX: GET PRE-BUILT TEMPLATE TEXT ─────────────────────
if ($action === 'get_template_text') {
    header('Content-Type: application/json; charset=utf-8');
    $type = cleanInput($_GET['type'] ?? 'offer');
    $settings = mm_load_settings($pdo);
    $orgName = $settings['site_name'] ?? 'Jaysmrutti Foundation';
    $templates = get_staff_letter_templates($orgName);

    if (isset($templates[$type])) {
        echo json_encode(['success' => true, 'template' => $templates[$type]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Template type not found.']);
    }
    exit;
}

// ── 2. STREAM / DOWNLOAD PDF DIRECTLY ─────────────────────────
if ($action === 'download_letter' || $action === 'view_letter') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id <= 0) {
        die('Invalid Letter ID.');
    }
    $mode = ($action === 'download_letter') ? 'D' : 'I';
    generate_staff_letter_pdf($pdo, $id, $mode);
    exit;
}

// ── 3. CSRF Verification ──────────────────────────────────────
$csrfToken = $_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? '');
if (!verifyCsrfToken($csrfToken)) {
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Security token invalid. Please reload.']);
        exit;
    }
    setFlash('error', 'Security validation failed (CSRF token mismatch).');
    header('Location: ../staff_letters.php');
    exit;
}

try {
    // ── 4. CREATE OR UPDATE STAFF LETTER ─────────────────────────
    if ($action === 'save_letter') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $type = cleanInput($_POST['type'] ?? 'joining');
        $letterNo = cleanInput($_POST['letter_no'] ?? '');
        $name = cleanInput($_POST['name'] ?? '');
        $contact = cleanInput($_POST['contact'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $designation = cleanInput($_POST['designation'] ?? '');
        $department = cleanInput($_POST['department'] ?? '');
        $issuedDate = cleanInput($_POST['issued_date'] ?? date('Y-m-d'));
        $joiningDate = cleanInput($_POST['joining_date'] ?? '');
        $salaryOrStipend = cleanInput($_POST['salary_or_stipend'] ?? '');
        $subject = cleanInput($_POST['subject'] ?? '');
        $letterContent = $_POST['letter_content'] ?? '';
        $signatoryName = cleanInput($_POST['signatory_name'] ?? '');
        $signatoryDesignation = cleanInput($_POST['signatory_designation'] ?? '');
        $status = cleanInput($_POST['status'] ?? 'issued');

        if (empty($name) || empty($designation) || empty($letterContent)) {
            throw new Exception('Recipient Name, Designation, and Letter Content are required.');
        }

        // Auto-generate reference number if empty
        if (empty($letterNo)) {
            $year = date('Y', strtotime($issuedDate));
            $prefixMap = [
                'offer' => "JMF/OFF/{$year}/",
                'appointment' => "JMF/APP/{$year}/",
                'joining' => "JMF/JOIN/{$year}/",
                'volunteer_joining' => "JMF/VOL/{$year}/",
                'experience' => "JMF/EXP/{$year}/",
                'appreciation' => "JMF/APR/{$year}/"
            ];
            $prefix = $prefixMap[$type] ?? "JMF/STF/{$year}/";
            $count = (int)$pdo->query("SELECT COUNT(*) FROM staff_letters WHERE YEAR(issued_date) = {$year}")->fetchColumn() + 1;
            $letterNo = $prefix . str_pad((string)$count, 3, '0', STR_PAD_LEFT);
        }

        if ($id && $id > 0) {
            // Update
            $stmt = $pdo->prepare("
                UPDATE `staff_letters` SET
                    `type` = ?,
                    `letter_no` = ?,
                    `name` = ?,
                    `contact` = ?,
                    `email` = ?,
                    `designation` = ?,
                    `department` = ?,
                    `issued_date` = ?,
                    `joining_date` = ?,
                    `salary_or_stipend` = ?,
                    `subject` = ?,
                    `letter_content` = ?,
                    `signatory_name` = ?,
                    `signatory_designation` = ?,
                    `status` = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $type, $letterNo, $name, $contact ?: null, $email ?: null,
                $designation, $department ?: null, $issuedDate, $joiningDate ?: null,
                $salaryOrStipend ?: null, $subject ?: null, $letterContent,
                $signatoryName ?: null, $signatoryDesignation ?: null, $status,
                $id
            ]);

            // Re-render PDF
            generate_staff_letter_pdf($pdo, $id, 'S');

            setFlash('success', "Staff Letter [{$letterNo}] updated and letterhead PDF regenerated successfully.");
        } else {
            // Create
            $stmt = $pdo->prepare("
                INSERT INTO `staff_letters` (
                    `type`, `letter_no`, `name`, `contact`, `email`,
                    `designation`, `department`, `issued_date`, `joining_date`,
                    `salary_or_stipend`, `subject`, `letter_content`,
                    `signatory_name`, `signatory_designation`, `status`, `issued_by`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $type, $letterNo, $name, $contact ?: null, $email ?: null,
                $designation, $department ?: null, $issuedDate, $joiningDate ?: null,
                $salaryOrStipend ?: null, $subject ?: null, $letterContent,
                $signatoryName ?: null, $signatoryDesignation ?: null, $status,
                $currentUserId ?: null
            ]);
            $newId = (int)$pdo->lastInsertId();

            // Auto-render PDF
            generate_staff_letter_pdf($pdo, $newId, 'S');

            setFlash('success', "Staff Letter [{$letterNo}] generated successfully!");
        }

        // Check if user requested immediate download
        if (isset($_POST['save_and_download'])) {
            header('Location: staff_letter_logic.php?action=download_letter&id=' . ($id ?: $newId));
            exit;
        }

        header('Location: ../staff_letters.php');
        exit;
    }

    // ── 5. EMAIL LETTER TO RECIPIENT ─────────────────────────────
    if ($action === 'email_letter') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($id <= 0) {
            setFlash('error', 'Invalid Letter ID.');
            header('Location: ../staff_letters.php');
            exit;
        }

        $res = generate_staff_letter_pdf($pdo, $id, 'S');
        if (!$res['success']) {
            setFlash('error', $res['message']);
            header('Location: ../staff_letters.php');
            exit;
        }

        if (empty($res['recipient_email']) || !filter_var($res['recipient_email'], FILTER_VALIDATE_EMAIL)) {
            setFlash('error', "No valid email address recorded for recipient '{$res['recipient_name']}'.");
            header('Location: ../staff_letters.php');
            exit;
        }

        $settings = mm_load_settings($pdo);
        $siteName = trim((string)($settings['site_name'] ?? 'Jaysmrutti Foundation')) ?: 'Jaysmrutti Foundation';
        $emailSubject = "Official Document: " . ($res['letter_no']) . " - {$siteName}";

        $emailBody = '
            <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1e293b; padding: 20px;">
                <h2 style="color: #0F8B8D;">Official Letter from ' . htmlspecialchars($siteName) . '</h2>
                <p>Dear <strong>' . htmlspecialchars($res['recipient_name']) . '</strong>,</p>
                <p>Please find attached your official institutional letter (Reference No: <strong>' . htmlspecialchars($res['letter_no']) . '</strong>) issued on official letterhead.</p>
                <p>Please review and keep this document safe for your official records.</p>
                <br>
                <p>Warm regards,<br><strong>' . htmlspecialchars($siteName) . ' Administration</strong></p>
            </div>
        ';

        try {
            if (function_exists('sendNotificationEmail')) {
                sendNotificationEmail($pdo, $res['recipient_email'], $emailSubject, $emailBody, [
                    [
                        'name' => $res['file_name'],
                        'content' => $res['pdf_content']
                    ]
                ]);
            }
            setFlash('success', "Official Letterhead PDF successfully emailed to {$res['recipient_email']}.");
        } catch (Throwable $e) {
            setFlash('error', "Failed to dispatch email: " . $e->getMessage());
        }

        header('Location: ../staff_letters.php');
        exit;
    }

    // ── 6. DELETE LETTER ─────────────────────────────────────────
    if ($action === 'delete_letter') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id <= 0) {
            setFlash('error', 'Invalid Letter ID.');
            header('Location: ../staff_letters.php');
            exit;
        }

        $stmt = $pdo->prepare("SELECT letter_no, pdf_path FROM staff_letters WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            if (!empty($row['pdf_path'])) {
                $f = __DIR__ . '/../../' . ltrim($row['pdf_path'], '/\\');
                if (file_exists($f)) @unlink($f);
            }

            $del = $pdo->prepare("DELETE FROM staff_letters WHERE id = ?");
            $del->execute([$id]);

            setFlash('success', "Staff Letter [{$row['letter_no']}] deleted successfully.");
        } else {
            setFlash('error', 'Letter record not found.');
        }

        header('Location: ../staff_letters.php');
        exit;
    }

    setFlash('error', 'Invalid action specified.');
    header('Location: ../staff_letters.php');
    exit;

} catch (Exception $e) {
    setFlash('error', 'Error: ' . $e->getMessage());
    header('Location: ../staff_letters.php');
    exit;
}

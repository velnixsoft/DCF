<?php
// ============================================================
// process/submit_feedback.php
// Public, Member, Volunteer & Employee Feedback Submission Handler
// Generates unique Reference Code & sends Email Confirmation
// ============================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/member_module.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// CSRF check
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'Security token invalid or expired. Please refresh the page and try again.']);
    exit;
}

$isAnonymous = isset($_POST['is_anonymous']) && ($_POST['is_anonymous'] == '1' || $_POST['is_anonymous'] === 'true') ? 1 : 0;
$submitterType = in_array($_POST['submitter_type'] ?? '', ['employee', 'member', 'volunteer', 'field_agent', 'donor', 'other'], true) ? $_POST['submitter_type'] : 'member';

$name = cleanInput($_POST['name'] ?? '');
if ($isAnonymous) {
    if (empty($name)) {
        $name = 'Anonymous Contributor (' . ucfirst($submitterType) . ')';
    }
}

$contact = cleanInput($_POST['contact'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$department = cleanInput($_POST['department'] ?? '');
$userIdentifier = cleanInput($_POST['user_identifier'] ?? '');
$category = cleanInput($_POST['category'] ?? 'general_suggestion');
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT) ?: 5;
if ($rating < 1) $rating = 1;
if ($rating > 5) $rating = 5;

$subject = cleanInput($_POST['subject'] ?? '');
$message = cleanInput($_POST['message'] ?? '');
$priority = in_array(strtolower($_POST['priority'] ?? ''), ['low', 'medium', 'high'], true) ? strtolower($_POST['priority']) : 'medium';

// Validation
if (empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Please provide both Subject and Feedback Message.']);
    exit;
}

if (!$isAnonymous && empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Please provide your Name or choose Anonymous submission.']);
    exit;
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
    exit;
}

// Generate unique Feedback Number (e.g. FB-2026-0005)
$year = date('Y');
$prefix = "FB-{$year}-";
$countTotal = (int)$pdo->query("SELECT COUNT(*) FROM `feedbacks` WHERE YEAR(created_at) = {$year}")->fetchColumn() + 1;
$feedbackNo = $prefix . str_pad($countTotal, 4, '0', STR_PAD_LEFT);

// Guarantee uniqueness
$chk = $pdo->prepare("SELECT id FROM `feedbacks` WHERE `feedback_no` = ?");
$chk->execute([$feedbackNo]);
if ($chk->fetch()) {
    $feedbackNo .= '-' . substr(uniqid(), -4);
}

// Handle optional file attachment
$attachmentPath = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $allowedExts = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
    $fileInfo = pathinfo($_FILES['attachment']['name']);
    $ext = strtolower($fileInfo['extension'] ?? '');

    if (in_array($ext, $allowedExts, true) && $_FILES['attachment']['size'] <= 5 * 1024 * 1024) {
        $uploadDir = __DIR__ . '/../uploads/feedbacks/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $filename = 'Feedback_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $feedbackNo) . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $filename)) {
            $attachmentPath = 'uploads/feedbacks/' . $filename;
        }
    }
}

$userIp = $_SERVER['REMOTE_ADDR'] ?? null;

try {
    $stmt = $pdo->prepare("
        INSERT INTO `feedbacks` (
            `feedback_no`, `submitter_type`, `user_identifier`, `name`, `contact`, `email`,
            `department`, `category`, `rating`, `subject`, `message`, `is_anonymous`,
            `status`, `priority`, `attachment_path`, `user_ip`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)
    ");

    $stmt->execute([
        $feedbackNo,
        $submitterType,
        $userIdentifier ?: null,
        $name,
        $isAnonymous ? null : ($contact ?: null),
        $isAnonymous ? null : ($email ?: null),
        $department ?: null,
        $category,
        $rating,
        $subject,
        $message,
        $isAnonymous,
        $priority,
        $attachmentPath,
        $userIp
    ]);

    $feedbackId = (int)$pdo->lastInsertId();

    $settings = mm_load_settings($pdo);
    $siteName = trim((string)($settings['site_name'] ?? 'Jaysmrutti Foundation')) ?: 'Jaysmrutti Foundation';
    $ngoEmail = trim((string)($settings['ngo_email'] ?? ''));
    $ngoPhone = trim((string)($settings['ngo_phone'] ?? '+91 7651910331'));
    $dateFormatted = date('d M Y, h:i A');

    // ── 1. SEND CONFIRMATION EMAIL TO SUBMITTER (IF EMAIL GIVEN) ───
    if (!$isAnonymous && !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $userSubject = "[{$feedbackNo}] Feedback Received: Thank you for sharing your thoughts - {$siteName}";
        
        $starsHtml = str_repeat('&#9733;', $rating) . str_repeat('&#9734;', 5 - $rating);

        $userHtmlBody = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: "Segoe UI", Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; color: #1e293b; }
                .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
                .header { background: linear-gradient(135deg, #0F8B8D, #1070B0); padding: 30px; text-align: center; color: #ffffff; }
                .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
                .header p { margin: 6px 0 0; opacity: 0.9; font-size: 13px; }
                .body { padding: 28px; font-size: 14px; line-height: 1.6; }
                .ticket-box { background: #f0fdf4; border: 2px dashed #10b981; border-radius: 12px; padding: 16px; text-align: center; margin: 20px 0; }
                .ticket-num { font-size: 22px; font-weight: 800; color: #059669; letter-spacing: 1px; }
                .detail-row { display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding: 10px 0; }
                .detail-label { color: #64748b; font-weight: 600; }
                .detail-val { font-weight: 700; color: #0f172a; }
                .footer { background: #f1f5f9; padding: 20px; text-align: center; font-size: 12px; color: #64748b; }
                .btn { display: inline-block; background: #0F8B8D; color: #ffffff !important; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 700; margin-top: 15px; }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="header">
                    <h1>' . htmlspecialchars($siteName) . '</h1>
                    <p>Employee, Member & Stakeholder Feedback System</p>
                </div>
                <div class="body">
                    <p>Dear <strong>' . htmlspecialchars($name) . '</strong>,</p>
                    <p>Thank you for submitting your valuable feedback. Your input helps us foster a supportive environment and continually improve our institutional programs.</p>

                    <div class="ticket-box">
                        <div style="font-size: 11px; text-transform: uppercase; color: #059669; font-weight: 700; margin-bottom: 4px;">Your Feedback Reference Number</div>
                        <div class="ticket-num">' . htmlspecialchars($feedbackNo) . '</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Submitted on: ' . $dateFormatted . '</div>
                    </div>

                    <div style="margin: 20px 0;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 8px 0; color: #64748b; font-weight: 600;">Submitter Wing:</td>
                                <td style="padding: 8px 0; text-align: right; font-weight: 700;">' . ucfirst($submitterType) . '</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 8px 0; color: #64748b; font-weight: 600;">Category:</td>
                                <td style="padding: 8px 0; text-align: right; font-weight: 700;">' . ucwords(str_replace('_', ' ', $category)) . '</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 8px 0; color: #64748b; font-weight: 600;">Rating Given:</td>
                                <td style="padding: 8px 0; text-align: right; font-weight: 700; color: #f59e0b;">' . $starsHtml . ' (' . $rating . '/5)</td>
                            </tr>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 8px 0; color: #64748b; font-weight: 600;">Subject:</td>
                                <td style="padding: 8px 0; text-align: right; font-weight: 700;">' . htmlspecialchars($subject) . '</td>
                            </tr>
                        </table>
                    </div>

                    <div style="background: #f8fafc; border-left: 4px solid #0F8B8D; padding: 12px 16px; border-radius: 6px; margin: 15px 0;">
                        <strong style="color: #0F8B8D;">Your Feedback / Suggestion:</strong>
                        <p style="margin: 6px 0 0; color: #334155; font-style: italic;">' . nl2br(htmlspecialchars($message)) . '</p>
                    </div>

                    <p style="color: #64748b; font-size: 13px;">
                        Our leadership and review committee will examine your submission with full diligence and priority.
                    </p>

                    <div style="text-align: center;">
                        <a href="' . (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : '') . '/track-feedback.php?ticket=' . urlencode($feedbackNo) . '" class="btn">
                            Track Feedback Status
                        </a>
                    </div>
                </div>
                <div class="footer">
                    <p style="margin: 0;"><strong>' . htmlspecialchars($siteName) . '</strong></p>
                    <p style="margin: 4px 0 0;">Helpline: ' . htmlspecialchars($ngoPhone) . ' | Email: ' . htmlspecialchars($ngoEmail) . '</p>
                </div>
            </div>
        </body>
        </html>';

        try {
            if (function_exists('sendNotificationEmail')) {
                sendNotificationEmail($pdo, $email, $userSubject, $userHtmlBody);
            }
        } catch (Throwable $e) {
            // Log silently
            error_log("Failed to send feedback confirmation email: " . $e->getMessage());
        }
    }

    // ── 2. SEND ALERT TO NGO ADMIN ────────────────────────────────
    if (!empty($ngoEmail) && filter_var($ngoEmail, FILTER_VALIDATE_EMAIL)) {
        $adminSubject = "[New Feedback - {$feedbackNo}] {$subject} ({$submitterType})";
        $adminHtmlBody = '
            <div style="font-family: Arial, sans-serif; padding: 20px; line-height: 1.6;">
                <h2 style="color: #0F8B8D; margin-top: 0;">New Feedback Received [Ref: ' . htmlspecialchars($feedbackNo) . ']</h2>
                <p><strong>Submitter:</strong> ' . htmlspecialchars($name) . ' (' . ucfirst($submitterType) . ')' . ($isAnonymous ? ' <em>[ANONYMOUS]</em>' : '') . '</p>
                <p><strong>Contact:</strong> ' . htmlspecialchars($contact ?: 'N/A') . ' | <strong>Email:</strong> ' . htmlspecialchars($email ?: 'N/A') . '</p>
                <p><strong>Department:</strong> ' . htmlspecialchars($department ?: 'General') . '</p>
                <p><strong>Category:</strong> ' . ucwords(str_replace('_', ' ', $category)) . ' | <strong>Rating:</strong> ' . $rating . '/5</p>
                <p><strong>Subject:</strong> ' . htmlspecialchars($subject) . '</p>
                <div style="background: #f1f5f9; padding: 15px; border-radius: 8px;">
                    ' . nl2br(htmlspecialchars($message)) . '
                </div>
                <p style="margin-top: 20px;"><a href="' . (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : '') . '/admin/feedbacks.php" style="background: #0F8B8D; color: #fff; padding: 10px 18px; text-decoration: none; border-radius: 6px; font-weight: bold;">Review in Admin Panel</a></p>
            </div>
        ';
        try {
            if (function_exists('sendNotificationEmail')) {
                sendNotificationEmail($pdo, $ngoEmail, $adminSubject, $adminHtmlBody);
            }
        } catch (Throwable $e) {
            error_log("Admin feedback alert error: " . $e->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Your feedback has been submitted successfully!',
        'feedback_no' => $feedbackNo,
        'name' => $name,
        'email' => $email,
        'rating' => $rating,
        'submitter_type' => $submitterType,
        'is_anonymous' => $isAnonymous
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
}

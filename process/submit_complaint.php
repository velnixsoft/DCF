<?php
// ============================================================
// process/submit_complaint.php
// Public & Member Complaint / Suggestion Submission Handler
// Generates unique Ticket Reference & sends Email Confirmation
// ============================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/member_module.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$name = cleanInput($_POST['name'] ?? '');
$contact = cleanInput($_POST['contact'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$type = in_array(strtolower($_POST['type'] ?? ''), ['complaint', 'suggestion'], true) ? strtolower($_POST['type']) : 'complaint';
$subject = cleanInput($_POST['subject'] ?? '');
$description = cleanInput($_POST['description'] ?? '');
$priority = in_array(strtolower($_POST['priority'] ?? ''), ['low', 'medium', 'high', 'urgent'], true) ? strtolower($_POST['priority']) : 'medium';

if (empty($name) || empty($contact) || empty($subject) || empty($description)) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields (Name, Contact, Subject, Description).']);
    exit;
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

// Generate unique Ticket Number (e.g. TKT-2026-0001 or SUG-2026-0001)
$year = date('Y');
$prefix = ($type === 'suggestion') ? "SUG-{$year}-" : "TKT-{$year}-";
$countToday = (int)$pdo->query("SELECT COUNT(*) FROM `complaints` WHERE YEAR(created_at) = {$year}")->fetchColumn() + 1;
$ticketNo = $prefix . str_pad($countToday, 4, '0', STR_PAD_LEFT);

// Guarantee uniqueness
$chk = $pdo->prepare("SELECT id FROM `complaints` WHERE `ticket_no` = ?");
$chk->execute([$ticketNo]);
if ($chk->fetch()) {
    $ticketNo .= '-' . substr(uniqid(), -4);
}

// Handle optional file attachment
$attachmentPath = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $allowedExts = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
    $fileInfo = pathinfo($_FILES['attachment']['name']);
    $ext = strtolower($fileInfo['extension'] ?? '');

    if (in_array($ext, $allowedExts, true) && $_FILES['attachment']['size'] <= 5 * 1024 * 1024) {
        $uploadDir = __DIR__ . '/../uploads/complaints/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $filename = 'Attachment_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $ticketNo) . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $filename)) {
            $attachmentPath = 'uploads/complaints/' . $filename;
        }
    }
}

$userIp = $_SERVER['REMOTE_ADDR'] ?? null;

try {
    $stmt = $pdo->prepare("
        INSERT INTO `complaints` (
            `ticket_no`, `name`, `contact`, `email`, `type`, `subject`, 
            `description`, `status`, `priority`, `attachment_path`, `user_ip`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)
    ");

    $stmt->execute([
        $ticketNo, $name, $contact, $email ?: null, $type, $subject,
        $description, $priority, $attachmentPath, $userIp
    ]);

    $complaintId = (int)$pdo->lastInsertId();

    $settings = mm_load_settings($pdo);
    $siteName = trim((string)($settings['site_name'] ?? 'Jaysmrutti Foundation')) ?: 'Jaysmrutti Foundation';
    $ngoEmail = trim((string)($settings['ngo_email'] ?? ''));
    $ngoPhone = trim((string)($settings['ngo_phone'] ?? '+91 7651910331'));
    $typeLabel = ucfirst($type);
    $dateFormatted = date('d M Y, h:i A');

    // ── 1. SEND CONFIRMATION EMAIL TO SUBMITTER ────────────────────
    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $userSubject = "[{$ticketNo}] Confirmation: Your {$typeLabel} has been registered - {$siteName}";
        
        $userHtmlBody = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; color: #1e293b; }
                .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
                .header-bar { height: 6px; background: linear-gradient(90deg, #0F8B8D, #0c7274); }
                .header { padding: 26px 30px; background: #ffffff; border-bottom: 1px solid #f1f5f9; }
                .org-title { font-size: 20px; font-weight: 800; color: #0f172a; margin: 0; }
                .org-tagline { font-size: 12px; color: #0F8B8D; font-weight: 600; margin-top: 2px; }
                .content { padding: 30px; font-size: 14px; line-height: 1.6; color: #334155; }
                .ticket-box { background: #f0fdfd; border: 2px dashed #0F8B8D; border-radius: 12px; padding: 18px; margin: 20px 0; text-align: center; }
                .ticket-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #0F8B8D; letter-spacing: 1px; }
                .ticket-number { font-size: 26px; font-weight: 900; color: #0f172a; font-family: monospace; margin: 6px 0; }
                .meta-table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 13px; }
                .meta-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; }
                .meta-table td.label { font-weight: 700; color: #64748b; width: 35%; }
                .quote-box { background: #f8fafc; border-left: 4px solid #0F8B8D; padding: 14px 18px; border-radius: 6px; margin: 15px 0; font-size: 13px; color: #334155; }
                .footer { padding: 22px 30px; background: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8; text-align: center; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header-bar"></div>
                <div class="header">
                    <h1 class="org-title">' . htmlspecialchars($siteName) . '</h1>
                    <div class="org-tagline">Citizen Grievance & Citizen Support Portal</div>
                </div>
                <div class="content">
                    <p style="font-size: 16px; font-weight: 700; color: #0f172a;">Dear ' . htmlspecialchars($name) . ',</p>
                    <p>Thank you for reaching out to <strong>' . htmlspecialchars($siteName) . '</strong>. Your <strong>' . htmlspecialchars($type) . '</strong> has been logged into our grievance and feedback system.</p>
                    
                    <div class="ticket-box">
                        <div class="ticket-title">Your Unique Tracking Ticket</div>
                        <div class="ticket-number">' . htmlspecialchars($ticketNo) . '</div>
                        <div style="font-size: 11px; color: #64748b;">Please preserve this ticket number for all status inquiries.</div>
                    </div>

                    <table class="meta-table">
                        <tr>
                            <td class="label">Ticket Reference</td>
                            <td><strong>' . htmlspecialchars($ticketNo) . '</strong></td>
                        </tr>
                        <tr>
                            <td class="label">Submission Type</td>
                            <td><span style="font-weight: 700; color: #0F8B8D;">' . htmlspecialchars($typeLabel) . '</span></td>
                        </tr>
                        <tr>
                            <td class="label">Subject</td>
                            <td>' . htmlspecialchars($subject) . '</td>
                        </tr>
                        <tr>
                            <td class="label">Registered On</td>
                            <td>' . htmlspecialchars($dateFormatted) . '</td>
                        </tr>
                        <tr>
                            <td class="label">Current Status</td>
                            <td><span style="background: #fef3c7; color: #92400e; padding: 3px 8px; border-radius: 10px; font-weight: 700; font-size: 11px;">Pending Review</span></td>
                        </tr>
                    </table>

                    <p><strong>Your Submitted Matter:</strong></p>
                    <div class="quote-box">
                        ' . nl2br(htmlspecialchars($description)) . '
                    </div>

                    <div style="text-align: center; margin: 25px 0;">
                        <a href="' . htmlspecialchars(appUrl() . '/track-complaint.php?ticket=' . urlencode($ticketNo)) . '" style="display: inline-block; padding: 12px 28px; background-color: #0F8B8D; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 13px; border-radius: 10px; box-shadow: 0 4px 6px -1px rgba(15,139,141,0.2);">
                            Track Your Ticket Status Online &rarr;
                        </a>
                    </div>

                    <p style="margin-top: 20px;">Our administrative committee will examine the submission and provide a resolution. You will receive email notifications as the status of your ticket updates.</p>

                    <p style="margin-top: 25px;">Warm regards,<br><strong>Public Grievance Cell</strong><br><span style="font-size: 12px; color: #64748b;">' . htmlspecialchars($siteName) . '</span></p>
                </div>
                <div class="footer">
                    <div>Helpline: ' . htmlspecialchars($ngoPhone) . ' | Support Email: ' . htmlspecialchars($ngoEmail) . '</div>
                    <div style="margin-top: 4px;">This is an automated confirmation email. Please do not reply directly to this message.</div>
                </div>
            </div>
        </body>
        </html>
        ';

        try {
            mm_send_email($settings, $email, $name, $userSubject, $userHtmlBody);
        } catch (Throwable $e) {
            error_log('Failed to dispatch user confirmation email: ' . $e->getMessage());
        }
    }

    // ── 2. SEND NOTIFICATION EMAIL TO NGO ADMIN ────────────────────
    if ($ngoEmail !== '') {
        $adminSubject = "[{$ticketNo}] New {$typeLabel} Received: {$subject}";
        $adminHtmlBody = "
            <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;'>
                <h3 style='color: #0F8B8D; margin-top: 0;'>New {$typeLabel} Logged</h3>
                <p>A new <strong>{$type}</strong> has been registered on the public portal with Ticket Reference: <strong>{$ticketNo}</strong>.</p>
                <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 15px 0;'>
                <p><strong>Ticket Number:</strong> {$ticketNo}</p>
                <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
                <p><strong>Contact:</strong> " . htmlspecialchars($contact) . "</p>
                <p><strong>Email:</strong> " . htmlspecialchars($email ?: 'N/A') . "</p>
                <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
                <p><strong>Priority:</strong> " . ucfirst(htmlspecialchars($priority)) . "</p>
                <p><strong>Description:</strong></p>
                <blockquote style='background: #f8fafc; border-left: 4px solid #0F8B8D; padding: 12px 16px; margin: 0;'>" . nl2br(htmlspecialchars($description)) . "</blockquote>
                <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 15px 0;'>
                <p style='font-size: 12px; color: #64748b;'>Review, update status, and post official administrative replies from the Admin Dashboard.</p>
            </div>
        ";

        try {
            mm_send_email($settings, $ngoEmail, $siteName, $adminSubject, $adminHtmlBody);
        } catch (Throwable $e) {
            error_log('Failed to dispatch admin notification email: ' . $e->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Your {$type} has been submitted successfully. Your reference ticket number is {$ticketNo}.",
        'ticket_no' => $ticketNo,
        'id' => $complaintId
    ]);

} catch (PDOException $e) {
    error_log('Complaint submission error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Unable to submit at this time. Please try again later.']);
}
exit;

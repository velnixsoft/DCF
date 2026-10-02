<?php
// ============================================================
// admin/actions/complaint_logic.php
// Handles Complaint & Suggestion Status Updates, Direct Admin Replies,
// Email Notifications to Citizens, and Deletions.
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/member_module.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'coordinator', 'page.complaints')) {
    setFlash('error', 'Access denied. You do not have permission to manage complaints.');
    header('Location: ../dashboard.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    setFlash('error', 'Invalid or expired security token. Please try again.');
    header('Location: ../complaints.php');
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
    // ── 1. UPDATE COMPLAINT / POST REPLY ─────────────────────────
    if ($action === 'update_complaint') {
        $id = (int)($_POST['id'] ?? 0);
        $status = cleanInput($_POST['status'] ?? 'pending');
        $adminReply = trim((string)($_POST['admin_reply'] ?? ''));
        $sendEmail = isset($_POST['send_email']) && $_POST['send_email'] == '1';

        $allowedStatuses = ['pending', 'in_progress', 'on_hold', 'resolved'];
        if ($id <= 0 || !in_array($status, $allowedStatuses, true)) {
            setFlash('error', 'Invalid ticket ID or status selected.');
            header('Location: ../complaints.php');
            exit;
        }

        // Fetch current ticket record
        $stmt = $pdo->prepare("SELECT * FROM `complaints` WHERE id = ?");
        $stmt->execute([$id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            setFlash('error', 'Complaint ticket record not found.');
            header('Location: ../complaints.php');
            exit;
        }

        $resolvedAt = ($status === 'resolved') ? date('Y-m-d H:i:s') : ($ticket['resolved_at'] ?: null);
        $repliedAt = !empty($adminReply) ? date('Y-m-d H:i:s') : ($ticket['replied_at'] ?: null);
        $replyBy = !empty($adminReply) ? $currentUserId : ($ticket['reply_by'] ?: null);

        $upd = $pdo->prepare("
            UPDATE `complaints` SET 
                `status` = ?,
                `admin_reply` = ?,
                `reply_by` = ?,
                `replied_at` = ?,
                `resolved_at` = ?
            WHERE id = ?
        ");

        $upd->execute([
            $status,
            $adminReply === '' ? null : $adminReply,
            $replyBy,
            $repliedAt,
            $resolvedAt,
            $id
        ]);

        $emailDispatched = false;
        $emailError = '';

        // ── 2. SEND EMAIL NOTIFICATION ON REPLY / STATUS CHANGE ─────
        if ($sendEmail && !empty($ticket['email']) && filter_var($ticket['email'], FILTER_VALIDATE_EMAIL)) {
            $settings = mm_load_settings($pdo);
            $siteName = trim((string)($settings['site_name'] ?? 'Jaysmrutti Foundation')) ?: 'Jaysmrutti Foundation';
            $ngoPhone = trim((string)($settings['ngo_phone'] ?? '+91 7651910331'));
            $ngoEmail = trim((string)($settings['ngo_email'] ?? 'info@velnixsoft.com'));

            $statusLabels = [
                'pending' => 'Pending Review',
                'in_progress' => 'Under Active Investigation',
                'on_hold' => 'On Hold / Awaiting Further Details',
                'resolved' => 'Resolved & Closed'
            ];
            $statusColors = [
                'pending' => '#d97706',
                'in_progress' => '#2563eb',
                'on_hold' => '#ea580c',
                'resolved' => '#16a34a'
            ];

            $curStatusLabel = $statusLabels[$status] ?? ucfirst($status);
            $curStatusColor = $statusColors[$status] ?? '#0F8B8D';
            $typeLabel = ucfirst($ticket['type']);
            $trackUrl = appUrl() . '/track-complaint.php?ticket=' . urlencode($ticket['ticket_no']);

            $emailSubject = "[{$ticket['ticket_no']}] Status Update: Your {$typeLabel} is now {$curStatusLabel} - {$siteName}";

            $replySection = '';
            if (!empty($adminReply)) {
                $replySection = '
                <div style="margin: 20px 0; padding: 18px 22px; background-color: #f0fdfd; border: 2px solid #0F8B8D; border-radius: 12px;">
                    <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #0F8B8D; letter-spacing: 0.5px; margin-bottom: 8px;">
                        Official Administrative Resolution / Response:
                    </div>
                    <div style="font-size: 14px; color: #0f172a; line-height: 1.6; white-space: pre-line;">
                        ' . nl2br(htmlspecialchars($adminReply)) . '
                    </div>
                </div>';
            }

            $emailBody = '
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
                    .status-pill { display: inline-block; padding: 6px 14px; border-radius: 20px; color: #ffffff; font-weight: 800; font-size: 12px; background-color: ' . $curStatusColor . '; }
                    .meta-table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 13px; }
                    .meta-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; }
                    .meta-table td.label { font-weight: 700; color: #64748b; width: 35%; }
                    .btn { display: inline-block; padding: 12px 28px; background-color: #0F8B8D; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 13px; border-radius: 10px; }
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
                        <p style="font-size: 16px; font-weight: 700; color: #0f172a;">Dear ' . htmlspecialchars($ticket['name']) . ',</p>
                        <p>There is an official update regarding your registered <strong>' . htmlspecialchars($ticket['type']) . '</strong> with Ticket Reference <strong>' . htmlspecialchars($ticket['ticket_no']) . '</strong>.</p>
                        
                        <div style="margin: 15px 0;">
                            <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-right: 8px;">New Status:</span>
                            <span class="status-pill">' . htmlspecialchars($curStatusLabel) . '</span>
                        </div>

                        ' . $replySection . '

                        <table class="meta-table">
                            <tr>
                                <td class="label">Ticket Reference</td>
                                <td><strong>' . htmlspecialchars($ticket['ticket_no']) . '</strong></td>
                            </tr>
                            <tr>
                                <td class="label">Subject</td>
                                <td>' . htmlspecialchars($ticket['subject']) . '</td>
                            </tr>
                            <tr>
                                <td class="label">Original Date</td>
                                <td>' . date('d M Y', strtotime($ticket['created_at'])) . '</td>
                            </tr>
                            <tr>
                                <td class="label">Updated On</td>
                                <td>' . date('d M Y, h:i A') . '</td>
                            </tr>
                        </table>

                        <div style="text-align: center; margin: 25px 0;">
                            <a href="' . htmlspecialchars($trackUrl) . '" class="btn">
                                View Full Status Online &rarr;
                            </a>
                        </div>

                        <p style="margin-top: 20px;">If you have further questions or additional information to provide, please contact our help desk with your ticket reference number.</p>

                        <p style="margin-top: 25px;">Warm regards,<br><strong>Public Grievance & Redressal Cell</strong><br><span style="font-size: 12px; color: #64748b;">' . htmlspecialchars($siteName) . '</span></p>
                    </div>
                    <div class="footer">
                        <div>Helpline: ' . htmlspecialchars($ngoPhone) . ' | Support Email: ' . htmlspecialchars($ngoEmail) . '</div>
                        <div style="margin-top: 4px;">This is an automated notification email. Please do not reply directly to this message.</div>
                    </div>
                </div>
            </body>
            </html>
            ';

            try {
                $emailDispatched = mm_send_email($settings, $ticket['email'], $ticket['name'], $emailSubject, $emailBody);
            } catch (Throwable $e) {
                $emailError = $e->getMessage();
            }
        }

        $msg = "Ticket #{$ticket['ticket_no']} updated successfully (Status: {$status}).";
        if ($sendEmail) {
            if ($emailDispatched) {
                $msg .= " Notification email dispatched to {$ticket['email']}.";
            } elseif (!empty($ticket['email'])) {
                $msg .= " (Note: Email dispatch skipped or had error: {$emailError})";
            }
        }

        setFlash('success', $msg);
        header('Location: ../complaints.php');
        exit;
    }

    // ── 3. DELETE COMPLAINT ──────────────────────────────────────
    if ($action === 'delete_complaint') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'Invalid ticket ID.');
            header('Location: ../complaints.php');
            exit;
        }

        // Unlink attachment if exists
        $stmt = $pdo->prepare("SELECT attachment_path FROM `complaints` WHERE id = ?");
        $stmt->execute([$id]);
        $attach = $stmt->fetchColumn();
        if ($attach && file_exists(__DIR__ . '/../../' . $attach)) {
            @unlink(__DIR__ . '/../../' . $attach);
        }

        $pdo->prepare("DELETE FROM `complaints` WHERE id = ?")->execute([$id]);
        setFlash('success', 'Ticket record deleted permanently.');
        header('Location: ../complaints.php');
        exit;
    }

    setFlash('error', 'Unrecognized action.');
    header('Location: ../complaints.php');
    exit;

} catch (Throwable $e) {
    error_log('Complaint management action failed: ' . $e->getMessage());
    setFlash('error', 'Operation failed: ' . $e->getMessage());
    header('Location: ../complaints.php');
    exit;
}

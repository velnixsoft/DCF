<?php
// ============================================================
// admin/actions/feedback_logic.php
// Handles Feedback Status Updates, Management Replies,
// Automated Email Notifications to Contributors, and Deletions.
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/member_module.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'coordinator', 'page.complaints')) {
    setFlash('error', 'Access denied. You do not have permission to manage feedbacks.');
    header('Location: ../dashboard.php');
    exit;
}

$action = cleanInput($_REQUEST['action'] ?? '');
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

// ── 1. AJAX: GET SINGLE FEEDBACK JSON ────────────────────────
if ($action === 'get_feedback_json') {
    header('Content-Type: application/json; charset=utf-8');
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid feedback ID.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT 
            f.*, 
            u.name AS replied_by_name, 
            u.role AS replied_by_role
        FROM `feedbacks` f
        LEFT JOIN `users` u ON f.reply_by = u.id
        WHERE f.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Feedback not found.']);
    }
    exit;
}

// ── CSRF Check for POST requests ──────────────────────────────
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please reload.']);
        exit;
    }
    setFlash('error', 'Invalid or expired security token. Please try again.');
    header('Location: ../feedbacks.php');
    exit;
}

try {
    // ── 2. UPDATE FEEDBACK / POST REPLY ──────────────────────────
    if ($action === 'update_feedback') {
        $id = (int)($_POST['id'] ?? 0);
        $status = cleanInput($_POST['status'] ?? 'pending');
        $priority = cleanInput($_POST['priority'] ?? 'medium');
        $adminReply = trim((string)($_POST['admin_reply'] ?? ''));
        $sendEmail = isset($_POST['send_email']) && ($_POST['send_email'] == '1' || $_POST['send_email'] === 'true');

        $allowedStatuses = ['pending', 'under_review', 'action_taken', 'closed'];
        $allowedPriorities = ['low', 'medium', 'high'];

        if ($id <= 0 || !in_array($status, $allowedStatuses, true)) {
            setFlash('error', 'Invalid feedback ID or status selected.');
            header('Location: ../feedbacks.php');
            exit;
        }

        if (!in_array($priority, $allowedPriorities, true)) {
            $priority = 'medium';
        }

        // Fetch current feedback record
        $stmt = $pdo->prepare("SELECT * FROM `feedbacks` WHERE id = ?");
        $stmt->execute([$id]);
        $feedback = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$feedback) {
            setFlash('error', 'Feedback record not found.');
            header('Location: ../feedbacks.php');
            exit;
        }

        $resolvedAt = in_array($status, ['action_taken', 'closed'], true) ? date('Y-m-d H:i:s') : ($feedback['resolved_at'] ?: null);
        $repliedAt = !empty($adminReply) ? date('Y-m-d H:i:s') : ($feedback['replied_at'] ?: null);
        $replyBy = !empty($adminReply) ? $currentUserId : ($feedback['reply_by'] ?: null);

        $upd = $pdo->prepare("
            UPDATE `feedbacks` SET 
                `status` = ?,
                `priority` = ?,
                `admin_reply` = ?,
                `reply_by` = ?,
                `replied_at` = ?,
                `resolved_at` = ?
            WHERE id = ?
        ");

        $upd->execute([
            $status,
            $priority,
            $adminReply === '' ? null : $adminReply,
            $replyBy,
            $repliedAt,
            $resolvedAt,
            $id
        ]);

        $emailDispatched = false;
        $emailError = '';

        // ── 3. SEND NOTIFICATION EMAIL (IF REQUESTED & VALID) ───────
        if ($sendEmail && !$feedback['is_anonymous'] && !empty($feedback['email']) && filter_var($feedback['email'], FILTER_VALIDATE_EMAIL)) {
            $settings = mm_load_settings($pdo);
            $siteName = trim((string)($settings['site_name'] ?? 'Jaysmrutti Foundation')) ?: 'Jaysmrutti Foundation';
            $ngoPhone = trim((string)($settings['ngo_phone'] ?? '+91 7651910331'));
            $ngoEmail = trim((string)($settings['ngo_email'] ?? 'info@velnixsoft.com'));

            $statusLabels = [
                'pending' => 'Pending Review',
                'under_review' => 'Under Active Review',
                'action_taken' => 'Action Taken & Approved',
                'closed' => 'Closed / Implemented'
            ];
            $statusPretty = $statusLabels[$status] ?? ucfirst($status);

            $statusColors = [
                'pending' => '#d97706',
                'under_review' => '#0284c7',
                'action_taken' => '#4f46e5',
                'closed' => '#059669'
            ];
            $color = $statusColors[$status] ?? '#0F8B8D';

            $emailSubject = "[Update - {$feedback['feedback_no']}] Status changed to: {$statusPretty} - {$siteName}";

            $htmlBody = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <style>
                    body { font-family: "Segoe UI", Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; color: #1e293b; }
                    .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
                    .header { background: linear-gradient(135deg, #1070B0, #0F8B8D); padding: 30px; text-align: center; color: #ffffff; }
                    .header h1 { margin: 0; font-size: 22px; font-weight: 700; }
                    .header p { margin: 6px 0 0; opacity: 0.9; font-size: 13px; }
                    .body { padding: 28px; font-size: 14px; line-height: 1.6; }
                    .badge { display: inline-block; background-color: ' . $color . '; color: #ffffff; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
                    .reply-box { background: #f0fdf4; border-left: 4px solid #10b981; border-radius: 6px; padding: 16px; margin: 20px 0; }
                    .footer { background: #f1f5f9; padding: 20px; text-align: center; font-size: 12px; color: #64748b; }
                    .btn { display: inline-block; background: #1070B0; color: #ffffff !important; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 700; margin-top: 15px; }
                </style>
            </head>
            <body>
                <div class="card">
                    <div class="header">
                        <h1>' . htmlspecialchars($siteName) . '</h1>
                        <p>Institutional Feedback & Response Review</p>
                    </div>
                    <div class="body">
                        <p>Dear <strong>' . htmlspecialchars($feedback['name']) . '</strong>,</p>
                        <p>We are writing to update you on your feedback submission registered with reference code <strong>' . htmlspecialchars($feedback['feedback_no']) . '</strong>.</p>
                        
                        <div style="text-align: center; margin: 20px 0;">
                            <span style="font-size: 12px; color: #64748b; display: block; margin-bottom: 6px;">CURRENT STATUS</span>
                            <span class="badge">' . htmlspecialchars($statusPretty) . '</span>
                        </div>

                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin: 15px 0; font-size: 13px;">
                            <p style="margin: 0 0 6px 0;"><strong>Subject:</strong> ' . htmlspecialchars($feedback['subject']) . '</p>
                            <p style="margin: 0; color: #64748b;"><strong>Wing / Category:</strong> ' . ucfirst($feedback['submitter_type']) . ' &bull; ' . ucwords(str_replace('_', ' ', $feedback['category'])) . '</p>
                        </div>';

            if (!empty($adminReply)) {
                $htmlBody .= '
                        <div class="reply-box">
                            <strong style="color: #065f46; display: block; margin-bottom: 6px;">Management Response & Action Taken:</strong>
                            <p style="margin: 0; color: #1e293b;">' . nl2br(htmlspecialchars($adminReply)) . '</p>
                        </div>';
            }

            $htmlBody .= '
                        <p style="color: #64748b; font-size: 13px;">
                            You can check real-time progress and institutional action notes at any time using our feedback tracker.
                        </p>

                        <div style="text-align: center;">
                            <a href="' . (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : '') . '/track-feedback.php?ticket=' . urlencode($feedback['feedback_no']) . '" class="btn">
                                View Live Feedback Record
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
                    $sendResult = sendNotificationEmail($pdo, $feedback['email'], $emailSubject, $htmlBody);
                    $emailDispatched = (bool)$sendResult;
                }
            } catch (Throwable $e) {
                $emailError = $e->getMessage();
            }
        }

        $msg = "Feedback [{$feedback['feedback_no']}] status updated to '{$status}' successfully.";
        if ($emailDispatched) {
            $msg .= " Notification email sent to {$feedback['email']}.";
        } elseif ($sendEmail && !empty($emailError)) {
            $msg .= " (Note: Email delivery failed: {$emailError})";
        }

        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => $msg]);
            exit;
        }

        setFlash('success', $msg);
        header('Location: ../feedbacks.php');
        exit;
    }

    // ── 4. DELETE FEEDBACK ───────────────────────────────────────
    if ($action === 'delete_feedback') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            setFlash('error', 'Invalid feedback ID.');
            header('Location: ../feedbacks.php');
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM `feedbacks` WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            if (!empty($row['attachment_path'])) {
                $fullPath = __DIR__ . '/../../' . ltrim($row['attachment_path'], '/\\');
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }

            $del = $pdo->prepare("DELETE FROM `feedbacks` WHERE id = ?");
            $del->execute([$id]);

            setFlash('success', "Feedback [{$row['feedback_no']}] deleted successfully.");
        } else {
            setFlash('error', 'Feedback record not found.');
        }

        header('Location: ../feedbacks.php');
        exit;
    }

    setFlash('error', 'Unknown action specified.');
    header('Location: ../feedbacks.php');
    exit;

} catch (Exception $e) {
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
    setFlash('error', 'Database error: ' . $e->getMessage());
    header('Location: ../feedbacks.php');
    exit;
}

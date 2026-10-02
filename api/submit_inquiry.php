<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/member_module.php';

api_require_method(['POST']);

$name = cleanInput(api_input('name', ''));
$email = filter_var((string)api_input('email', ''), FILTER_SANITIZE_EMAIL);
$phone = cleanInput(api_input('phone', ''));
$problem = cleanInput(api_input('problem', ''));
$category = cleanInput(api_input('category', ''));
$urgency = cleanInput(api_input('urgency', ''));
$memberId = (int)api_input('member_id', 0);
if ($name === '') {
    api_error('Name is required.', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    api_error('A valid email address is required.', 422);
}
if ($phone === '') {
    api_error('Phone number is required.', 422);
}
$phoneClean = preg_replace('/[^0-9]/', '', $phone);
if (strlen($phoneClean) !== 10 || !preg_match("/^[6-9][0-9]{9}$/", $phoneClean)) {
    api_error('Phone number must start with 6, 7, 8, or 9 and be exactly 10 digits.', 422);
}
$phone = $phoneClean;
if ($problem === '') {
    api_error('Problem description is required.', 422);
}

$attachmentPath = null;
$attachmentUrl = null;

if (!empty($_FILES['attachment']) && (int)($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    $maxSize = 5 * 1024 * 1024;
    $tmpName = (string)$_FILES['attachment']['tmp_name'];
    $size = (int)$_FILES['attachment']['size'];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmpName) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!in_array($mime, $allowedTypes, true) || $size > $maxSize) {
        api_error('Attachment must be a PDF, JPG, or PNG file up to 5MB.', 422);
    }

    $uploadDir = dirname(__DIR__) . '/uploads/inquiries/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = strtolower(pathinfo((string)$_FILES['attachment']['name'], PATHINFO_EXTENSION));
    $safeExt = in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true) ? $ext : 'dat';
    $fileName = 'inquiry_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $safeExt;
    $target = $uploadDir . $fileName;

    if (!move_uploaded_file($tmpName, $target)) {
        api_error('Unable to upload attachment.', 500);
    }

    $attachmentPath = 'uploads/inquiries/' . $fileName;
    $attachmentUrl = api_public_url($attachmentPath);
}

try {
    if ($memberId > 0 && dbColumnExists($pdo, 'inquiries', 'member_id')) {
        $stmt = $pdo->prepare("INSERT INTO inquiries (member_id, submitter_name, submitter_email, submitter_phone, problem_description, category, urgency, attachment_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$memberId, $name, $email, $phone, $problem, $category, $urgency, $attachmentPath]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO inquiries (submitter_name, submitter_email, submitter_phone, problem_description, category, urgency, attachment_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $problem, $category, $urgency, $attachmentPath]);
    }
    $inquiryId = (int)$pdo->lastInsertId();
} catch (Throwable $e) {
    api_error('Unable to submit inquiry right now. Please try again.', 500);
}

$settings = mm_load_settings($pdo);
$adminEmail = trim((string)($settings['ngo_email'] ?? ''));
$siteName = trim((string)($settings['site_name'] ?? 'NGO')) ?: 'NGO';

if ($adminEmail !== '') {
    $body = '
        <div style="font-family:Arial,sans-serif;max-width:680px;margin:auto;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden">
            <div style="background:#15803d;color:#fff;padding:18px 20px">
                <h2 style="margin:0;font-size:20px">New Inquiry Received</h2>
                <p style="margin:6px 0 0;font-size:13px;opacity:.9">' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '</p>
            </div>
            <div style="padding:20px;color:#111827">
                <table style="width:100%;border-collapse:collapse;font-size:14px">
                    <tr><td style="padding:6px 0;color:#6b7280;width:120px">Name</td><td style="padding:6px 0">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</td></tr>
                    <tr><td style="padding:6px 0;color:#6b7280">Email</td><td style="padding:6px 0">' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</td></tr>
                    <tr><td style="padding:6px 0;color:#6b7280">Phone</td><td style="padding:6px 0">' . htmlspecialchars($phone !== '' ? $phone : 'N/A', ENT_QUOTES, 'UTF-8') . '</td></tr>
                    <tr><td style="padding:6px 0;color:#6b7280">Category</td><td style="padding:6px 0">' . htmlspecialchars($category !== '' ? $category : 'General', ENT_QUOTES, 'UTF-8') . '</td></tr>
                    <tr><td style="padding:6px 0;color:#6b7280">Urgency</td><td style="padding:6px 0">' . htmlspecialchars($urgency !== '' ? $urgency : 'normal', ENT_QUOTES, 'UTF-8') . '</td></tr>
                </table>
                <div style="margin-top:18px">
                    <p style="margin:0 0 8px;color:#6b7280">Message</p>
                    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:14px;white-space:pre-wrap;line-height:1.6">' . htmlspecialchars($problem, ENT_QUOTES, 'UTF-8') . '</div>
                </div>';

    if ($attachmentUrl !== null) {
        $body .= '
                <div style="margin-top:18px">
                    <p style="margin:0 0 8px;color:#6b7280">Attachment</p>
                    <a href="' . htmlspecialchars($attachmentUrl, ENT_QUOTES, 'UTF-8') . '">View uploaded file</a>
                </div>';
    }

    $body .= '
            </div>
        </div>';

    $attachments = [];
    if (!empty($attachmentPath)) {
        $diskPath = dirname(__DIR__) . '/' . $attachmentPath;
        if (is_file($diskPath) && is_readable($diskPath)) {
            $attachments[] = [
                'name' => basename($diskPath),
                'content' => file_get_contents($diskPath),
            ];
        }
    }

    mm_send_email($settings, $adminEmail, $siteName, 'New Inquiry Submitted: ' . $name, $body, $attachments);
}

api_ok([
    'inquiry_id' => $inquiryId,
    'attachment_url' => $attachmentUrl,
], 'Thank you! Your inquiry has been submitted. We will contact you soon.');

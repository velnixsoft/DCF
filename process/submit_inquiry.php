<?php
session_start();
require '../config/db.php';
require '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../inquiry.php');
    exit;
}

$returnTo = (string)($_POST['return_to'] ?? '');
$returnUrl = '../inquiry.php';
if ($returnTo === 'member_dashboard') {
    $returnUrl = '../member-dashboard.php';
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
    setFlash('error', 'Invalid Security Token! Please try again.');
    header('Location: ' . $returnUrl);
    exit;
}

$name = cleanInput($_POST['name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$phone = cleanInput($_POST['phone'] ?? '');
$problem = cleanInput($_POST['problem'] ?? '');
$category = cleanInput($_POST['category'] ?? '');
$urgency = cleanInput($_POST['urgency'] ?? '');

if (empty($name)) {
    setFlash('error', 'Full name is required.');
    header('Location: ' . $returnUrl);
    exit;
}
if (!preg_match('/^[a-zA-Z\s]{2,100}$/', $name)) {
    setFlash('error', 'Name must be between 2 and 100 characters and contain only letters and spaces.');
    header('Location: ' . $returnUrl);
    exit;
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'A valid email address is required.');
    header('Location: ' . $returnUrl);
    exit;
}
$phoneClean = preg_replace('/[^0-9]/', '', $phone);
if (empty($phone)) {
    setFlash('error', 'Phone number is required.');
    header('Location: ' . $returnUrl);
    exit;
}
if (strlen($phoneClean) !== 10 || !preg_match("/^[6-9][0-9]{9}$/", $phoneClean)) {
    setFlash('error', 'Phone number must be exactly 10 digits and start with 6, 7, 8, or 9.');
    header('Location: ' . $returnUrl);
    exit;
}
if (empty($problem)) {
    setFlash('error', 'Problem description is required.');
    header('Location: ' . $returnUrl);
    exit;
}
if (strlen($problem) < 10) {
    setFlash('error', 'Problem description must be at least 10 characters.');
    header('Location: ' . $returnUrl);
    exit;
}
if (empty($category) || !in_array($category, ['membership', 'donation', 'volunteer', 'event', 'general'], true)) {
    setFlash('error', 'Please select a valid category.');
    header('Location: ' . $returnUrl);
    exit;
}
if (!in_array($urgency, ['normal', 'urgent', 'critical'], true)) {
    $urgency = 'normal';
}

// Handle attachment
$attachment = null;
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['attachment']['tmp_name']);
    finfo_close($finfo);
    
    if (in_array($mime, $allowed_types) && $_FILES['attachment']['size'] <= $max_size) {
        $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        $filename = 'inquiry_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
        $path = '../uploads/inquiries/' . $filename;
        
        // Create directory if not exists
        if (!is_dir('../uploads/inquiries')) mkdir('../uploads/inquiries', 0755, true);
        
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $path)) {
            $attachment = $path;
        }
    }
}

$memberId = null;
if (!empty($_SESSION['member_logged_in']) && !empty($_SESSION['member_id'])) {
    $memberId = (int)$_SESSION['member_id'];
    if ($memberId <= 0) $memberId = null;
}

try {
    if ($memberId !== null && dbColumnExists($pdo, 'inquiries', 'member_id')) {
        $stmt = $pdo->prepare("INSERT INTO inquiries (member_id, submitter_name, submitter_email, submitter_phone, problem_description, category, urgency, attachment_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$memberId, $name, $email, $phone, $problem, $category, $urgency, $attachment]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO inquiries (submitter_name, submitter_email, submitter_phone, problem_description, category, urgency, attachment_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $problem, $category, $urgency, $attachment]);
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    setFlash('error', 'Unable to submit inquiry right now. Please try again.');
    header('Location: ' . $returnUrl);
    exit;
}

// Send admin notification (basic)
$settings = [];
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE setting_key IN ('ngo_email', 'site_name')");
while ($row = $stmt_settings->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$admin_email = $settings['ngo_email'] ?? 'admin@ngo.com';
$site_name = $settings['site_name'] ?? 'NGO';

$subject = "New Inquiry Submitted: " . $name;
$message = "
New inquiry received:

Name: $name
Email: $email
Phone: $phone
Category: $category
Urgency: $urgency
Description: $problem

" . ($attachment ? "Attachment: $attachment" : '');

// For now, log (extend with PHPMailer later)
error_log($subject . "\n" . $message);

setFlash('success', 'Thank you! Your inquiry has been submitted. We will contact you soon.');
header('Location: ' . $returnUrl . '?success=1');
exit;
?>


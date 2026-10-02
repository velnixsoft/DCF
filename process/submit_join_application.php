<?php
// ============================================================
// process/submit_join_application.php
// Direct Application Submission for Join Foundation, Join Project, Job Application
// Author: VELNIX SOFT / Antigravity AI
// Date: 2026-09-12
// ============================================================

session_start();

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
    || isset($_POST['is_ajax']);

if ($isAjax) {
    header('Content-Type: application/json');
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/join_application_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit;
    }
    header('Location: ../join-us.php');
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    if ($isAjax) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid or expired CSRF token. Please refresh the page.']);
        exit;
    }
    setFlash('error', 'Invalid security token.');
    header('Location: ../join-us.php');
    exit;
}

// ── Sanitize & Validate Inputs ──────────────────────────────
$applicantName   = cleanInput($_POST['applicant_name'] ?? '');
$contact         = cleanInput($_POST['contact'] ?? '');
$email           = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: null;
$state           = cleanInput($_POST['state'] ?? '');
$district        = cleanInput($_POST['district'] ?? '');
$applicationType = cleanInput($_POST['application_type'] ?? 'join_foundation');
$projectId       = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT) ?: null;
$jobId           = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT) ?: null;
$details         = cleanInput($_POST['details'] ?? '');
$feeAmount       = filter_input(INPUT_POST, 'fee_amount', FILTER_VALIDATE_FLOAT) ?: 0.00;
$paymentMode     = cleanInput($_POST['payment_mode'] ?? 'exempted');

$allowedTypes = ['join_foundation', 'join_project', 'job_application'];
if (!in_array($applicationType, $allowedTypes, true)) {
    $applicationType = 'join_foundation';
}

$errors = [];
if ($applicantName === '') {
    $errors[] = 'Full Name is required.';
}
if ($contact === '') {
    $errors[] = 'Mobile / Contact number is required.';
} elseif (!preg_match('/^[0-9]{10}$/', preg_replace('/[^0-9]/', '', $contact))) {
    $errors[] = 'Please provide a valid 10-digit mobile number.';
}

if ($applicationType === 'join_project' && !$projectId) {
    $errors[] = 'Please select a specific project to volunteer for.';
}
if ($applicationType === 'job_application' && !$jobId) {
    $errors[] = 'Please select a job vacancy to apply for.';
}

if ($errors) {
    if ($isAjax) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
        exit;
    }
    setFlash('error', implode(' ', $errors));
    header('Location: ../join-us.php');
    exit;
}

// ── Generate Unique Application Number ──────────────────────
$applicationNo = generate_join_application_no($pdo, $applicationType);
$paymentStatus = ($feeAmount > 0) ? 'pending' : 'exempted';
$paymentMethod = ($feeAmount > 0) ? ($paymentMode ?: 'Manual') : 'Exempted';

try {
    $stmt = $pdo->prepare("INSERT INTO join_applications 
        (application_no, applicant_name, contact, email, state, district, application_type, project_id, job_id, details, fee_amount, payment_status, payment_method, status, applied_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
    
    $stmt->execute([
        $applicationNo,
        $applicantName,
        $contact,
        $email,
        $state ?: null,
        $district ?: null,
        $applicationType,
        $projectId,
        $jobId,
        $details ?: null,
        $feeAmount > 0 ? $feeAmount : null,
        $paymentStatus,
        $paymentMethod
    ]);

    $appId = (int)$pdo->lastInsertId();

    // Send confirmation email
    try {
        if (!empty($email)) {
            $siteName = $settings['site_name'] ?? 'Jaysmrutti Foundation';
            $typeLabel = match ($applicationType) {
                'join_project' => 'Project Volunteer Application',
                'job_application' => 'Job Vacancy Application',
                default => 'Foundation Membership Application'
            };

            $mailSubject = "Application Received - {$applicationNo} | {$siteName}";
            $mailBody = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                <h2 style='color: #0F8B8D; margin-top: 0;'>Application Received Successfully!</h2>
                <p>Dear <strong>" . htmlspecialchars($applicantName) . "</strong>,</p>
                <p>Thank you for applying to <strong>{$siteName}</strong>. Your application has been logged under reference number <strong>{$applicationNo}</strong>.</p>
                <table style='width: 100%; border-collapse: collapse; margin: 15px 0;'>
                    <tr style='background: #f8fafc;'><td style='padding: 8px; border: 1px solid #cbd5e1;'><strong>Application No:</strong></td><td style='padding: 8px; border: 1px solid #cbd5e1; font-weight: bold; color: #0F8B8D;'>{$applicationNo}</td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #cbd5e1;'><strong>Category:</strong></td><td style='padding: 8px; border: 1px solid #cbd5e1;'>{$typeLabel}</td></tr>
                    <tr style='background: #f8fafc;'><td style='padding: 8px; border: 1px solid #cbd5e1;'><strong>Contact:</strong></td><td style='padding: 8px; border: 1px solid #cbd5e1;'>{$contact}</td></tr>
                    <tr><td style='padding: 8px; border: 1px solid #cbd5e1;'><strong>Status:</strong></td><td style='padding: 8px; border: 1px solid #cbd5e1;'><span style='color: #0284c7; font-weight: bold;'>Under Review</span></td></tr>
                </table>
                <p>Our coordination committee will review your submission and connect with you on next steps.</p>
                <p style='color: #64748b; font-size: 12px; margin-top: 25px;'>Warm regards,<br>{$siteName}</p>
            </div>";

            sendNotificationEmail($pdo, $email, $mailSubject, $mailBody);
        }
    } catch (Throwable $mailEx) {
        error_log('Join application confirmation email error: ' . $mailEx->getMessage());
    }

    if ($isAjax) {
        echo json_encode([
            'success' => true,
            'message' => 'Application submitted successfully!',
            'application_no' => $applicationNo,
            'applicant_name' => $applicantName,
            'contact' => $contact,
            'email' => $email ?: '',
            'application_type' => $applicationType,
            'fee_amount' => (float)$feeAmount,
            'payment_status' => $paymentStatus,
            'status' => 'pending'
        ]);
        exit;
    }

    setFlash('success', "Application submitted successfully! Your Reference No is {$applicationNo}");
    header('Location: ../join-us.php?success=1&app_no=' . urlencode($applicationNo));
    exit;

} catch (PDOException $e) {
    error_log('Database insert error in submit_join_application: ' . $e->getMessage());
    if ($isAjax) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save application. Please try again.']);
        exit;
    }
    setFlash('error', 'Database error. Please try again.');
    header('Location: ../join-us.php');
    exit;
}

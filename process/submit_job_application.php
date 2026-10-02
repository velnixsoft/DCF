<?php
// ============================================================
// process/submit_job_application.php
// Handles Job Candidate Application Submissions & Resume Uploads
// ============================================================

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload_validator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    // 1. CSRF Verification
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrfToken)) {
        throw new Exception('Security validation failed (Invalid CSRF Token). Please refresh the page and try again.');
    }

    // 2. Validate Job ID & Job Status
    $jobId = filter_input(INPUT_POST, 'job_id', FILTER_VALIDATE_INT);
    if (!$jobId || $jobId <= 0) {
        throw new Exception('Please select a valid job opening.');
    }

    $jobStmt = $pdo->prepare("SELECT id, job_code, title, category, status, location FROM job_openings WHERE id = ? LIMIT 1");
    $jobStmt->execute([$jobId]);
    $job = $jobStmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        throw new Exception('Selected job opening was not found.');
    }

    if ($job['status'] !== 'active') {
        throw new Exception('Applications for this position (' . htmlspecialchars($job['title']) . ') are currently closed.');
    }

    // 3. Extract & Sanitize Form Fields
    $applicantName = cleanInput($_POST['applicant_name'] ?? '');
    $contact = cleanInput($_POST['contact'] ?? '');
    $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);
    $gender = cleanInput($_POST['gender'] ?? 'Male');
    $dob = cleanInput($_POST['dob'] ?? '');
    $qualification = cleanInput($_POST['qualification'] ?? '');
    $experienceYears = cleanInput($_POST['experience_years'] ?? 'Fresher / Entry Level');
    $currentCity = cleanInput($_POST['current_city'] ?? '');
    $state = cleanInput($_POST['state'] ?? '');
    $district = cleanInput($_POST['district'] ?? '');
    $address = cleanInput($_POST['address'] ?? '');
    $coverLetter = cleanInput($_POST['cover_letter'] ?? '');

    // Basic Validations
    if (empty($applicantName)) {
        throw new Exception('Candidate Full Name is required.');
    }
    if (empty($contact) || strlen(preg_replace('/[^0-9]/', '', $contact)) < 10) {
        throw new Exception('Please provide a valid 10-digit mobile / WhatsApp number.');
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please provide a valid email address.');
    }
    if (empty($qualification)) {
        throw new Exception('Please specify your highest qualification.');
    }
    if (empty($state) || empty($district)) {
        throw new Exception('State and District are required.');
    }
    if (empty($address)) {
        throw new Exception('Complete residential address is required.');
    }

    $allowedGenders = ['Male', 'Female', 'Other'];
    if (!in_array($gender, $allowedGenders, true)) {
        $gender = 'Male';
    }

    // Calculate Age if DOB provided
    $age = null;
    if (!empty($dob)) {
        try {
            $dobDate = new DateTime($dob);
            $now = new DateTime();
            $age = $now->diff($dobDate)->y;
        } catch (Throwable $e) {
            $age = null;
        }
    } elseif (!empty($_POST['age'])) {
        $age = (int)$_POST['age'];
    }

    // Check duplicate application for the same job within active period
    $dupStmt = $pdo->prepare("SELECT id, application_no, applied_date FROM job_applications WHERE job_id = ? AND (email = ? OR contact = ?) LIMIT 1");
    $dupStmt->execute([$jobId, $email, $contact]);
    $existingApp = $dupStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingApp) {
        throw new Exception('You have already applied for this role with application number ' . htmlspecialchars($existingApp['application_no']) . ' on ' . date('d M Y', strtotime($existingApp['applied_date'])) . '.');
    }

    // 4. Handle Resume File Upload
    $resumePath = null;
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadDir = __DIR__ . '/../uploads/resumes';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/octet-stream',
        ];

        $val = validateUploadedFile(
            $_FILES['resume'],
            $allowedMimes,
            10 * 1024 * 1024, // 10MB
            ['pdf', 'doc', 'docx']
        );

        if (!$val['success']) {
            throw new Exception('Resume upload error: ' . ($val['message'] ?? 'Invalid file format or size. Only PDF, DOC, DOCX up to 10MB allowed.'));
        }

        $stored = storeValidatedUpload(
            $_FILES['resume'],
            $uploadDir,
            'uploads/resumes',
            'resume_' . preg_replace('/[^a-z0-9]/', '', strtolower($applicantName))
        );

        if (!$stored['success']) {
            throw new Exception($stored['message'] ?? 'Failed to save uploaded resume file.');
        }

        $resumePath = $stored['relative_path'];
    }

    // 5. Generate Unique Application Number (e.g. APP-2026-92831)
    $appNo = 'APP-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
    // Ensure uniqueness
    $tries = 0;
    while ($tries < 5) {
        $chk = $pdo->prepare("SELECT id FROM job_applications WHERE application_no = ? LIMIT 1");
        $chk->execute([$appNo]);
        if (!$chk->fetchColumn()) {
            break;
        }
        $appNo = 'APP-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $tries++;
    }

    // 6. Insert Application Record into Database
    $insertStmt = $pdo->prepare("
        INSERT INTO job_applications (
            application_no, job_id, applicant_name, contact, email, 
            gender, dob, age, qualification, experience_years, 
            current_city, state, district, address, resume_path, 
            cover_letter, status, applied_date, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, 
            ?, 'pending', NOW(), NOW()
        )
    ");

    $insertStmt->execute([
        $appNo,
        $jobId,
        $applicantName,
        $contact,
        $email,
        $gender,
        !empty($dob) ? $dob : null,
        $age,
        $qualification,
        $experienceYears,
        $currentCity ?: null,
        $state,
        $district,
        $address,
        $resumePath,
        $coverLetter ?: null
    ]);

    $appId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'application_no' => $appNo,
        'application_id' => $appId,
        'applicant_name' => $applicantName,
        'job_title' => $job['title'],
        'job_code' => $job['job_code'],
        'job_location' => $job['location'],
        'applied_date' => date('d M Y, h:i A'),
        'message' => 'Your application has been received successfully! Our recruitment team will review your profile and reach out to you.'
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected server error occurred. Please try again or contact support.'
    ]);
    exit;
}

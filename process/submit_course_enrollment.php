<?php
// ============================================================
// process/submit_course_enrollment.php
// Handles Student Interest & Skill Course Enrollment Submissions
// ============================================================

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

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

    // 2. Validate Course ID & Status
    $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    if (!$courseId || $courseId <= 0) {
        throw new Exception('Please select a valid training course / program.');
    }

    $cStmt = $pdo->prepare("SELECT id, title, category, mode, duration, fee_type, status, location FROM skill_courses WHERE id = ? LIMIT 1");
    $cStmt->execute([$courseId]);
    $course = $cStmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        throw new Exception('Selected skill course was not found.');
    }

    if ($course['status'] !== 'active') {
        throw new Exception('Enrollments for ' . htmlspecialchars($course['title']) . ' are currently not active.');
    }

    // 3. Extract & Sanitize Form Fields
    $applicantName = cleanInput($_POST['applicant_name'] ?? '');
    $contact = cleanInput($_POST['contact'] ?? '');
    $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_SANITIZE_EMAIL);
    $gender = cleanInput($_POST['gender'] ?? 'Male');
    $dob = cleanInput($_POST['dob'] ?? '');
    $qualification = cleanInput($_POST['qualification'] ?? '');
    $city = cleanInput($_POST['city'] ?? '');
    $state = cleanInput($_POST['state'] ?? '');
    $district = cleanInput($_POST['district'] ?? '');
    $address = cleanInput($_POST['address'] ?? '');
    $motivation = cleanInput($_POST['motivation'] ?? '');

    // Basic Validations
    if (empty($applicantName)) {
        throw new Exception('Candidate Full Name is required.');
    }
    if (empty($contact) || strlen(preg_replace('/[^0-9]/', '', $contact)) < 10) {
        throw new Exception('Please provide a valid 10-digit contact / WhatsApp number.');
    }
    if (empty($qualification)) {
        throw new Exception('Please specify your current educational qualification.');
    }
    if (empty($state) || empty($district)) {
        throw new Exception('State and District are required.');
    }
    if (empty($address)) {
        throw new Exception('Residential Address is required.');
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

    // Check duplicate enrollment for the same course with same phone
    $dupStmt = $pdo->prepare("SELECT id, application_no, applied_date FROM skill_course_enrollments WHERE course_id = ? AND contact = ? LIMIT 1");
    $dupStmt->execute([$courseId, $contact]);
    $existing = $dupStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        throw new Exception('You have already enrolled for this course with registration number ' . htmlspecialchars($existing['application_no']) . ' on ' . date('d M Y', strtotime($existing['applied_date'])) . '.');
    }

    // 4. Generate Unique Application / Enrollment Number
    $appNo = 'ENR-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $tries = 0;
    while ($tries < 5) {
        $chk = $pdo->prepare("SELECT id FROM skill_course_enrollments WHERE application_no = ? LIMIT 1");
        $chk->execute([$appNo]);
        if (!$chk->fetchColumn()) {
            break;
        }
        $appNo = 'ENR-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $tries++;
    }

    // 5. Insert Record
    $insertStmt = $pdo->prepare("
        INSERT INTO skill_course_enrollments (
            application_no, course_id, applicant_name, contact, email,
            gender, dob, age, qualification, state, district, city,
            address, motivation, status, applied_date, created_at
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, 'pending', NOW(), NOW()
        )
    ");

    $insertStmt->execute([
        $appNo,
        $courseId,
        $applicantName,
        $contact,
        $email ?: null,
        $gender,
        !empty($dob) ? $dob : null,
        $age,
        $qualification,
        $state,
        $district,
        $city ?: null,
        $address,
        $motivation ?: null
    ]);

    $enrId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'application_no' => $appNo,
        'enrollment_id' => $enrId,
        'applicant_name' => $applicantName,
        'course_title' => $course['title'],
        'mode' => $course['mode'],
        'duration' => $course['duration'],
        'fee_type' => $course['fee_type'],
        'applied_date' => date('d M Y, h:i A'),
        'message' => 'Interest registered successfully! Our training counselor will contact you on ' . $contact . ' with batch timings and onboarding details.'
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
        'message' => 'An unexpected error occurred. Please try again.'
    ]);
    exit;
}

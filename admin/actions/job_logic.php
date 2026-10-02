<?php
// ============================================================
// admin/actions/job_logic.php
// Backend controller for Job Openings & Job Applications
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/admin_audit.php';

$action = cleanInput($_REQUEST['action'] ?? '');

// Helper to return JSON responses
function sendJsonResponse(bool $success, string $message, array $data = []): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// 1. AJAX: Get single Job Opening JSON
if ($action === 'get_job_json') {
    if (!checkRole($pdo, 'coordinator')) {
        sendJsonResponse(false, 'Unauthorized access.');
    }

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        sendJsonResponse(false, 'Invalid job ID.');
    }

    $stmt = $pdo->prepare("SELECT * FROM job_openings WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($job) {
        sendJsonResponse(true, 'Job loaded successfully.', ['job' => $job]);
    } else {
        sendJsonResponse(false, 'Job opening record not found.');
    }
}

// 2. AJAX: Get single Job Application JSON
if ($action === 'get_application_json') {
    if (!checkRole($pdo, 'coordinator')) {
        sendJsonResponse(false, 'Unauthorized access.');
    }

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        sendJsonResponse(false, 'Invalid application ID.');
    }

    $stmt = $pdo->prepare("
        SELECT a.*, j.title AS job_title, j.category AS job_category, j.job_code, j.location AS job_location,
               u.name AS reviewed_by_name
        FROM job_applications a
        JOIN job_openings j ON a.job_id = j.id
        LEFT JOIN users u ON a.reviewed_by = u.id
        WHERE a.id = ? LIMIT 1
    ");
    $stmt->execute([$id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($application) {
        sendJsonResponse(true, 'Application loaded successfully.', ['application' => $application]);
    } else {
        sendJsonResponse(false, 'Application record not found.');
    }
}

// Ensure POST for modifying operations
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request method.');
    header('Location: ../jobs.php');
    exit;
}

// Validate CSRF token
$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrfToken)) {
    if (isset($_POST['is_ajax'])) {
        sendJsonResponse(false, 'Security validation failed (Invalid CSRF Token).');
    }
    setFlash('error', 'Security validation failed (Invalid CSRF Token).');
    header('Location: ../jobs.php');
    exit;
}

// Role Check: Coordinator or higher
if (!checkRole($pdo, 'coordinator')) {
    if (isset($_POST['is_ajax'])) {
        sendJsonResponse(false, 'Unauthorized. Coordinator or higher role required.');
    }
    setFlash('error', 'Unauthorized. Coordinator or higher role required.');
    header('Location: ../dashboard.php');
    exit;
}

$currentUserId = (int)($_SESSION['user_id'] ?? 0);

// Helper to auto-generate unique Job Code: JOB-YYYY-XXXX
function generateUniqueJobCode(PDO $pdo): string {
    $year = date('Y');
    $prefix = "JOB-{$year}-";
    $stmt = $pdo->prepare("SELECT job_code FROM job_openings WHERE job_code LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();

    if ($last && preg_match('/JOB-\d{4}-(\d+)/', $last, $m)) {
        $next = (int)$m[1] + 1;
    } else {
        $stmtCount = $pdo->query("SELECT COUNT(*) FROM job_openings");
        $next = ((int)$stmtCount->fetchColumn()) + 1;
    }
    return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
}

// ------------------------------------------------------------
// ACTION: CREATE JOB OPENING
// ------------------------------------------------------------
if ($action === 'create_job') {
    try {
        $title = cleanInput($_POST['title'] ?? '');
        $category = cleanInput($_POST['category'] ?? 'other');
        $categoryCustom = cleanInput($_POST['category_custom'] ?? '');
        $description = trim((string)($_POST['description'] ?? ''));
        $requirements = trim((string)($_POST['requirements'] ?? ''));
        $responsibilities = trim((string)($_POST['responsibilities'] ?? ''));
        $location = cleanInput($_POST['location'] ?? '');
        $state = cleanInput($_POST['state'] ?? '');
        $district = cleanInput($_POST['district'] ?? '');
        $block = cleanInput($_POST['block'] ?? '');
        $openingsCount = max(1, (int)($_POST['openings_count'] ?? 1));
        $salaryRange = cleanInput($_POST['salary_range'] ?? '');
        $jobType = cleanInput($_POST['job_type'] ?? 'Full-time');
        $experienceRequired = cleanInput($_POST['experience_required'] ?? '');
        $minQualification = cleanInput($_POST['min_qualification'] ?? '');
        $status = cleanInput($_POST['status'] ?? 'active');
        $postedDate = cleanInput($_POST['posted_date'] ?? date('Y-m-d'));
        $lastDate = cleanInput($_POST['last_date'] ?? '');

        if (empty($title)) {
            throw new Exception('Job Title is required.');
        }
        if (empty($location)) {
            throw new Exception('Job Location / Headquarters is required.');
        }
        if (empty($description)) {
            throw new Exception('Job Description is required.');
        }

        $allowedCategories = ['state_coordinator', 'district_coordinator', 'block_coordinator', 'panchayat_coordinator', 'other'];
        if (!in_array($category, $allowedCategories, true)) {
            $category = 'other';
        }

        $allowedJobTypes = ['Full-time', 'Part-time', 'Contract', 'Internship', 'Volunteer'];
        if (!in_array($jobType, $allowedJobTypes, true)) {
            $jobType = 'Full-time';
        }

        $allowedStatuses = ['active', 'inactive', 'closed', 'draft'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'active';
        }

        $jobCode = generateUniqueJobCode($pdo);

        $stmt = $pdo->prepare("
            INSERT INTO job_openings (
                job_code, title, category, category_custom, description, requirements, responsibilities,
                location, state, district, block, openings_count, salary_range, job_type,
                experience_required, min_qualification, status, posted_date, last_date,
                created_by, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, NOW()
            )
        ");

        $stmt->execute([
            $jobCode, $title, $category, $categoryCustom ?: null, $description, $requirements ?: null, $responsibilities ?: null,
            $location, $state ?: null, $district ?: null, $block ?: null, $openingsCount, $salaryRange ?: null, $jobType,
            $experienceRequired ?: null, $minQualification ?: null, $status, $postedDate, $lastDate ?: null,
            $currentUserId
        ]);

        $newId = (int)$pdo->lastInsertId();
        logAdminAudit($pdo, 'CREATE_JOB_OPENING', "Created job opening {$jobCode}: {$title} (ID: {$newId})");

        if (isset($_POST['is_ajax'])) {
            sendJsonResponse(true, "Job opening '{$title}' created successfully.", ['id' => $newId, 'job_code' => $jobCode]);
        }

        setFlash('success', "Job opening '{$title}' created successfully.");
        header('Location: ../jobs.php');
        exit;

    } catch (Throwable $e) {
        if (isset($_POST['is_ajax'])) {
            sendJsonResponse(false, $e->getMessage());
        }
        setFlash('error', $e->getMessage());
        header('Location: ../jobs.php');
        exit;
    }
}

// ------------------------------------------------------------
// ACTION: UPDATE JOB OPENING
// ------------------------------------------------------------
if ($action === 'update_job') {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            throw new Exception('Invalid Job ID.');
        }

        $title = cleanInput($_POST['title'] ?? '');
        $category = cleanInput($_POST['category'] ?? 'other');
        $categoryCustom = cleanInput($_POST['category_custom'] ?? '');
        $description = trim((string)($_POST['description'] ?? ''));
        $requirements = trim((string)($_POST['requirements'] ?? ''));
        $responsibilities = trim((string)($_POST['responsibilities'] ?? ''));
        $location = cleanInput($_POST['location'] ?? '');
        $state = cleanInput($_POST['state'] ?? '');
        $district = cleanInput($_POST['district'] ?? '');
        $block = cleanInput($_POST['block'] ?? '');
        $openingsCount = max(1, (int)($_POST['openings_count'] ?? 1));
        $salaryRange = cleanInput($_POST['salary_range'] ?? '');
        $jobType = cleanInput($_POST['job_type'] ?? 'Full-time');
        $experienceRequired = cleanInput($_POST['experience_required'] ?? '');
        $minQualification = cleanInput($_POST['min_qualification'] ?? '');
        $status = cleanInput($_POST['status'] ?? 'active');
        $postedDate = cleanInput($_POST['posted_date'] ?? date('Y-m-d'));
        $lastDate = cleanInput($_POST['last_date'] ?? '');

        if (empty($title)) {
            throw new Exception('Job Title is required.');
        }
        if (empty($location)) {
            throw new Exception('Job Location is required.');
        }
        if (empty($description)) {
            throw new Exception('Job Description is required.');
        }

        $stmt = $pdo->prepare("
            UPDATE job_openings SET
                title = ?, category = ?, category_custom = ?, description = ?, requirements = ?, responsibilities = ?,
                location = ?, state = ?, district = ?, block = ?, openings_count = ?, salary_range = ?, job_type = ?,
                experience_required = ?, min_qualification = ?, status = ?, posted_date = ?, last_date = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $title, $category, $categoryCustom ?: null, $description, $requirements ?: null, $responsibilities ?: null,
            $location, $state ?: null, $district ?: null, $block ?: null, $openingsCount, $salaryRange ?: null, $jobType,
            $experienceRequired ?: null, $minQualification ?: null, $status, $postedDate, $lastDate ?: null,
            $id
        ]);

        logAdminAudit($pdo, 'UPDATE_JOB_OPENING', "Updated job opening ID {$id}: {$title}");

        if (isset($_POST['is_ajax'])) {
            sendJsonResponse(true, "Job opening '{$title}' updated successfully.");
        }

        setFlash('success', "Job opening '{$title}' updated successfully.");
        header('Location: ../jobs.php');
        exit;

    } catch (Throwable $e) {
        if (isset($_POST['is_ajax'])) {
            sendJsonResponse(false, $e->getMessage());
        }
        setFlash('error', $e->getMessage());
        header('Location: ../jobs.php');
        exit;
    }
}

// ------------------------------------------------------------
// ACTION: TOGGLE JOB STATUS
// ------------------------------------------------------------
if ($action === 'toggle_job_status') {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $newStatus = cleanInput($_POST['status'] ?? '');

        if (!$id) {
            throw new Exception('Invalid Job ID.');
        }

        $allowed = ['active', 'inactive', 'closed', 'draft'];
        if (!in_array($newStatus, $allowed, true)) {
            throw new Exception('Invalid status value.');
        }

        $stmt = $pdo->prepare("UPDATE job_openings SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $id]);

        logAdminAudit($pdo, 'TOGGLE_JOB_STATUS', "Changed job ID {$id} status to {$newStatus}");

        if (isset($_POST['is_ajax'])) {
            sendJsonResponse(true, "Job status changed to " . ucfirst($newStatus) . ".");
        }

        setFlash('success', "Job status updated to " . ucfirst($newStatus) . ".");
        header('Location: ../jobs.php');
        exit;

    } catch (Throwable $e) {
        if (isset($_POST['is_ajax'])) {
            sendJsonResponse(false, $e->getMessage());
        }
        setFlash('error', $e->getMessage());
        header('Location: ../jobs.php');
        exit;
    }
}

// ------------------------------------------------------------
// ACTION: DELETE JOB OPENING
// ------------------------------------------------------------
if ($action === 'delete_job') {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            throw new Exception('Invalid Job ID.');
        }

        $chk = $pdo->prepare("SELECT title, job_code FROM job_openings WHERE id = ?");
        $chk->execute([$id]);
        $row = $chk->fetch();

        if (!$row) {
            throw new Exception('Job opening not found.');
        }

        $del = $pdo->prepare("DELETE FROM job_openings WHERE id = ?");
        $del->execute([$id]);

        logAdminAudit($pdo, 'DELETE_JOB_OPENING', "Deleted job opening ID {$id} ({$row['job_code']}: {$row['title']})");

        if (isset($_POST['is_ajax'])) {
            sendJsonResponse(true, "Job opening '{$row['title']}' deleted successfully.");
        }

        setFlash('success', "Job opening '{$row['title']}' deleted successfully.");
        header('Location: ../jobs.php');
        exit;

    } catch (Throwable $e) {
        if (isset($_POST['is_ajax'])) {
            sendJsonResponse(false, $e->getMessage());
        }
        setFlash('error', $e->getMessage());
        header('Location: ../jobs.php');
        exit;
    }
}

// ------------------------------------------------------------
// ACTION: UPDATE APPLICATION STATUS (Shortlisted, Rejected, Selected, etc.)
// ------------------------------------------------------------
if ($action === 'update_application_status') {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $newStatus = cleanInput($_POST['status'] ?? '');
        $adminNotes = cleanInput($_POST['admin_notes'] ?? '');
        $interviewDate = cleanInput($_POST['interview_date'] ?? '');
        $interviewVenue = cleanInput($_POST['interview_venue'] ?? '');

        if (!$id) {
            throw new Exception('Invalid Application ID.');
        }

        $allowedStatuses = ['pending', 'reviewed', 'shortlisted', 'interview_scheduled', 'selected', 'rejected'];
        if (!in_array($newStatus, $allowedStatuses, true)) {
            throw new Exception('Invalid application status.');
        }

        $stmt = $pdo->prepare("
            UPDATE job_applications SET
                status = ?,
                admin_notes = ?,
                interview_date = ?,
                interview_venue = ?,
                reviewed_by = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $newStatus,
            $adminNotes ?: null,
            $interviewDate ?: null,
            $interviewVenue ?: null,
            $currentUserId,
            $id
        ]);

        logAdminAudit($pdo, 'UPDATE_JOB_APP_STATUS', "Changed application ID {$id} status to {$newStatus}");

        $statusLabels = [
            'pending' => 'Pending Review',
            'reviewed' => 'Marked as Reviewed',
            'shortlisted' => 'Candidate Shortlisted ⭐',
            'interview_scheduled' => 'Interview Scheduled 📅',
            'selected' => 'Candidate Selected 🎉',
            'rejected' => 'Application Rejected ❌'
        ];

        $statusMsg = $statusLabels[$newStatus] ?? ucfirst($newStatus);

        if (isset($_POST['is_ajax'])) {
            sendJsonResponse(true, "Application status successfully updated to: {$statusMsg}", [
                'status' => $newStatus,
                'status_label' => $statusMsg
            ]);
        }

        setFlash('success', "Application status updated to: {$statusMsg}");
        header('Location: ../job_applications.php');
        exit;

    } catch (Throwable $e) {
        if (isset($_POST['is_ajax'])) {
            sendJsonResponse(false, $e->getMessage());
        }
        setFlash('error', $e->getMessage());
        header('Location: ../job_applications.php');
        exit;
    }
}

// ------------------------------------------------------------
// ACTION: DELETE JOB APPLICATION
// ------------------------------------------------------------
if ($action === 'delete_application') {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            throw new Exception('Invalid Application ID.');
        }

        $chk = $pdo->prepare("SELECT applicant_name, application_no, resume_path FROM job_applications WHERE id = ?");
        $chk->execute([$id]);
        $row = $chk->fetch();

        if (!$row) {
            throw new Exception('Application record not found.');
        }

        // Delete resume file if on disk
        if (!empty($row['resume_path'])) {
            $resumeFullPath = __DIR__ . '/../../' . ltrim($row['resume_path'], '/\\');
            if (file_exists($resumeFullPath) && is_file($resumeFullPath)) {
                @unlink($resumeFullPath);
            }
        }

        $del = $pdo->prepare("DELETE FROM job_applications WHERE id = ?");
        $del->execute([$id]);

        logAdminAudit($pdo, 'DELETE_JOB_APPLICATION', "Deleted application ID {$id} ({$row['application_no']}: {$row['applicant_name']})");

        if (isset($_POST['is_ajax'])) {
            sendJsonResponse(true, "Application of '{$row['applicant_name']}' deleted successfully.");
        }

        setFlash('success', "Application of '{$row['applicant_name']}' deleted successfully.");
        header('Location: ../job_applications.php');
        exit;

    } catch (Throwable $e) {
        if (isset($_POST['is_ajax'])) {
            sendJsonResponse(false, $e->getMessage());
        }
        setFlash('error', $e->getMessage());
        header('Location: ../job_applications.php');
        exit;
    }
}

// Fallback for unrecognized action
setFlash('error', 'Unrecognized action.');
header('Location: ../jobs.php');
exit;

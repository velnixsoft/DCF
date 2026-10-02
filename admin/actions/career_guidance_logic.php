<?php
// ============================================================
// admin/actions/career_guidance_logic.php
// Controller for Career Guidance CMS, Skill Courses & Enrollments
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/upload_validator.php';

// Access control check
if (!checkRole($pdo, 'coordinator')) {
    if (isset($_GET['ajax']) || isset($_POST['ajax'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        exit;
    }
    setFlash('error', 'Unauthorized access.');
    header('Location: ../dashboard.php');
    exit;
}

$action = cleanInput($_REQUEST['action'] ?? '');
$adminUserId = $_SESSION['user_id'] ?? null;

// ============================================================
// 1. AJAX: Fetch Single Course JSON
// ============================================================
if ($action === 'get_course_json') {
    header('Content-Type: application/json; charset=utf-8');
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid Course ID']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM skill_courses WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($course) {
        echo json_encode(['success' => true, 'data' => $course]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Course not found']);
    }
    exit;
}

// ============================================================
// 2. AJAX: Fetch Single Enrollment JSON
// ============================================================
if ($action === 'get_enrollment_json') {
    header('Content-Type: application/json; charset=utf-8');
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid Enrollment ID']);
        exit;
    }
    $stmt = $pdo->prepare("
        SELECT e.*, c.title AS course_title, c.category AS course_category
        FROM skill_course_enrollments e
        JOIN skill_courses c ON e.course_id = c.id
        WHERE e.id = ? LIMIT 1
    ");
    $stmt->execute([$id]);
    $enr = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($enr) {
        echo json_encode(['success' => true, 'data' => $enr]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Enrollment record not found']);
    }
    exit;
}

// ============================================================
// CSRF Validation for POST requests
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrfToken)) {
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Security validation failed (CSRF mismatch).']);
            exit;
        }
        setFlash('error', 'Security validation failed (CSRF token invalid).');
        header('Location: ../career_guidance_manager.php');
        exit;
    }
}

// ============================================================
// 3. POST: Update CMS Page Content (About Us style)
// ============================================================
if ($action === 'update_cms') {
    try {
        $fields = [
            'career_guidance_title' => cleanInput($_POST['career_guidance_title'] ?? 'Career Guidance & Skills Training'),
            'career_guidance_subtitle' => cleanInput($_POST['career_guidance_subtitle'] ?? ''),
            'career_guidance_desc' => cleanInput($_POST['career_guidance_desc'] ?? ''),
            'career_guidance_counseling_text' => cleanInput($_POST['career_guidance_counseling_text'] ?? ''),
            'career_guidance_helpline' => cleanInput($_POST['career_guidance_helpline'] ?? ''),
            'career_guidance_email' => cleanInput($_POST['career_guidance_email'] ?? '')
        ];

        foreach ($fields as $key => $val) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $val, $val]);
        }

        // Handle Banner Image upload
        if (isset($_FILES['career_guidance_banner']) && $_FILES['career_guidance_banner']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/content';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $val = validateUploadedFile(
                $_FILES['career_guidance_banner'],
                ['image/jpeg', 'image/png', 'image/webp'],
                3 * 1024 * 1024,
                ['jpg', 'jpeg', 'png', 'webp']
            );

            if ($val['success']) {
                $stored = storeValidatedUpload(
                    $_FILES['career_guidance_banner'],
                    $uploadDir,
                    'uploads/content',
                    'career_guidance_banner'
                );
                if ($stored['success']) {
                    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('career_guidance_banner_image', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->execute([$stored['relative_path'], $stored['relative_path']]);
                }
            }
        }

        setFlash('success', 'Career Guidance CMS content updated successfully.');
    } catch (Throwable $e) {
        setFlash('error', 'Error updating CMS content: ' . $e->getMessage());
    }
    header('Location: ../career_guidance_manager.php?tab=cms');
    exit;
}

// ============================================================
// 4. POST: Create or Update Skill Course
// ============================================================
if ($action === 'save_course') {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $title = cleanInput($_POST['title'] ?? '');
        $category = cleanInput($_POST['category'] ?? 'vocational');
        $categoryCustom = cleanInput($_POST['category_custom'] ?? '');
        $description = cleanInput($_POST['description'] ?? '');
        $curriculum = cleanInput($_POST['curriculum'] ?? '');
        $duration = cleanInput($_POST['duration'] ?? '3 Months');
        $eligibility = cleanInput($_POST['eligibility'] ?? '10th / 12th Pass');
        $mode = cleanInput($_POST['mode'] ?? 'Offline');
        $feeType = cleanInput($_POST['fee_type'] ?? '100% Free');
        $instructor = cleanInput($_POST['instructor'] ?? 'Senior NGO Trainer');
        $location = cleanInput($_POST['location'] ?? 'Field Training Center');
        $batchStartDate = cleanInput($_POST['batch_start_date'] ?? '');
        $maxSeats = filter_input(INPUT_POST, 'max_seats', FILTER_VALIDATE_INT) ?: 30;
        $status = cleanInput($_POST['status'] ?? 'active');

        if (empty($title)) {
            throw new Exception('Course Title is required.');
        }

        $allowedCategories = ['vocational', 'computer_it', 'soft_skills', 'competitive_exams', 'entrepreneurship', 'healthcare_aid', 'other'];
        if (!in_array($category, $allowedCategories, true)) {
            $category = 'vocational';
        }

        $imagePath = null;
        if (isset($_FILES['course_image']) && $_FILES['course_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/content';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $val = validateUploadedFile(
                $_FILES['course_image'],
                ['image/jpeg', 'image/png', 'image/webp'],
                3 * 1024 * 1024,
                ['jpg', 'jpeg', 'png', 'webp']
            );
            if ($val['success']) {
                $stored = storeValidatedUpload(
                    $_FILES['course_image'],
                    $uploadDir,
                    'uploads/content',
                    'course_' . preg_replace('/[^a-z0-9]/', '', strtolower($title))
                );
                if ($stored['success']) {
                    $imagePath = $stored['relative_path'];
                }
            }
        }

        if ($id && $id > 0) {
            // Update Course
            if ($imagePath) {
                $stmt = $pdo->prepare("
                    UPDATE skill_courses 
                    SET title = ?, category = ?, category_custom = ?, description = ?, curriculum = ?,
                        duration = ?, eligibility = ?, mode = ?, fee_type = ?, instructor = ?,
                        location = ?, batch_start_date = ?, max_seats = ?, status = ?, image_path = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $title, $category, $categoryCustom ?: null, $description, $curriculum,
                    $duration, $eligibility, $mode, $feeType, $instructor,
                    $location, !empty($batchStartDate) ? $batchStartDate : null, $maxSeats, $status, $imagePath,
                    $id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE skill_courses 
                    SET title = ?, category = ?, category_custom = ?, description = ?, curriculum = ?,
                        duration = ?, eligibility = ?, mode = ?, fee_type = ?, instructor = ?,
                        location = ?, batch_start_date = ?, max_seats = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $title, $category, $categoryCustom ?: null, $description, $curriculum,
                    $duration, $eligibility, $mode, $feeType, $instructor,
                    $location, !empty($batchStartDate) ? $batchStartDate : null, $maxSeats, $status,
                    $id
                ]);
            }
            setFlash('success', 'Course "' . htmlspecialchars($title) . '" updated successfully.');
        } else {
            // Insert New Course
            $stmt = $pdo->prepare("
                INSERT INTO skill_courses (
                    title, category, category_custom, description, curriculum,
                    duration, eligibility, mode, fee_type, instructor,
                    location, batch_start_date, max_seats, status, image_path, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, NOW()
                )
            ");
            $stmt->execute([
                $title, $category, $categoryCustom ?: null, $description, $curriculum,
                $duration, $eligibility, $mode, $feeType, $instructor,
                $location, !empty($batchStartDate) ? $batchStartDate : null, $maxSeats, $status, $imagePath
            ]);
            setFlash('success', 'New Skill Course "' . htmlspecialchars($title) . '" added successfully.');
        }

    } catch (Throwable $e) {
        setFlash('error', 'Failed to save course: ' . $e->getMessage());
    }
    header('Location: ../career_guidance_manager.php?tab=courses');
    exit;
}

// ============================================================
// 5. POST: Toggle Course Status
// ============================================================
if ($action === 'toggle_course_status') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare("UPDATE skill_courses SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Course status updated.');
    }
    header('Location: ../career_guidance_manager.php?tab=courses');
    exit;
}

// ============================================================
// 6. POST: Delete Course
// ============================================================
if ($action === 'delete_course') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM skill_courses WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Course deleted successfully.');
    }
    header('Location: ../career_guidance_manager.php?tab=courses');
    exit;
}

// ============================================================
// 7. POST: Update Enrollment Status & Notes
// ============================================================
if ($action === 'update_enrollment_status') {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $status = cleanInput($_POST['status'] ?? 'pending');
        $adminNotes = cleanInput($_POST['admin_notes'] ?? '');

        $allowed = ['pending', 'contacted', 'enrolled', 'completed', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            $status = 'pending';
        }

        if (!$id) {
            throw new Exception('Invalid Enrollment record.');
        }

        $stmt = $pdo->prepare("
            UPDATE skill_course_enrollments 
            SET status = ?, admin_notes = ?, reviewed_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$status, $adminNotes ?: null, $adminUserId, $id]);

        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => 'Enrollment status updated to ' . ucfirst($status)]);
            exit;
        }

        setFlash('success', 'Enrollment status updated to ' . ucfirst($status));
    } catch (Throwable $e) {
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
        setFlash('error', 'Error updating enrollment: ' . $e->getMessage());
    }
    header('Location: ../career_guidance_manager.php?tab=enrollments');
    exit;
}

// ============================================================
// 8. POST: Delete Enrollment
// ============================================================
if ($action === 'delete_enrollment') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM skill_course_enrollments WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Enrollment entry deleted successfully.');
    }
    header('Location: ../career_guidance_manager.php?tab=enrollments');
    exit;
}

header('Location: ../career_guidance_manager.php');
exit;

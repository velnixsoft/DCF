<?php
require '../../config/db.php';
require '../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../testimonials.php');
    exit;
}

if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    setFlash('error', 'Security Token Invalid');
    header('Location: ../testimonials.php');
    exit;
}

if (!canAccessModule($pdo, 'coordinator', 'page.testimonials')) {
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $stars = $_POST['stars'] ?? '★★★★★';
        $initial = trim(strtoupper($_POST['initial'] ?? ''));
        $priority_order = (int)($_POST['priority_order'] ?? 0);

    if (strlen($name) < 2 || strlen($role) < 2 || strlen($content) < 10 || strlen($initial) !== 1 || $priority_order < 0 || $priority_order > 999) {
            setFlash('error', 'Invalid data. Check: Name/Role (min 2 chars), Content (min 10 chars), Initial (exactly 1 char), Priority (0-999)');
            header('Location: ../testimonials.php');
            exit;
        }

        $avatarPath = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['avatar']['size'] > 500 * 1024) { // 500KB
                setFlash('error', 'Avatar max 500KB.');
                header('Location: ../testimonials.php');
                exit;
            }

            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowed, true)) {
                setFlash('error', 'Invalid image type. JPG/PNG/WEBP only.');
                header('Location: ../testimonials.php');
                exit;
            }

            $uploadDir = '../../uploads/testimonials/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $fileName = 'testimonial_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $fileName)) {
                $avatarPath = 'uploads/testimonials/' . $fileName;
            }
        }

        if ($id > 0) {
            // Update
            $sql = "UPDATE testimonials SET 
                    name = ?, role = ?, content = ?, stars = ?, initial = ?,
                    priority_order = ?, avatar_path = COALESCE(?, avatar_path)
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $role, $content, $stars, $initial, $priority_order, $avatarPath, $id]);
            setFlash('success', 'Testimonial updated successfully.');
        } else {
            // Insert
            $sql = "INSERT INTO testimonials (name, role, content, stars, initial, priority_order, avatar_path, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $role, $content, $stars, $initial, $priority_order, $avatarPath]);
            setFlash('success', 'Testimonial added successfully.');
        }
    }

    elseif ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'Inactive';
        if (!in_array($status, ['Active', 'Inactive'], true)) {
            throw new Exception('Invalid status');
        }
        $stmt = $pdo->prepare("UPDATE testimonials SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        setFlash('success', "Testimonial " . strtolower($status) . "d.");
    }

    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) throw new Exception('Invalid ID');
        $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Testimonial deleted.');
    }

    else {
        throw new Exception('Unknown action');
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    setFlash('error', $e->getMessage() ?: 'Operation failed.');
}

header('Location: ../testimonials.php');
exit;
?>


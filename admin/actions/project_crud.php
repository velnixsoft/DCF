<?php
require '../../config/db.php';
require '../../includes/functions.php';

if (!isset($_SESSION['logged_in'])) { header('Location: ../index.php'); exit; }
if (!canAccessModule($pdo, 'manager', 'page.projects')) { setFlash('error', 'Access denied.'); header('Location: ../dashboard.php'); exit; }

function handleUpload($fileInput, $targetDir, $id = '', $isQR = false) {
    if (empty($fileInput['name'])) return ['success' => true, 'path' => null];
    if ($fileInput['error'] !== UPLOAD_ERR_OK) return ['success' => false, 'error' => 'File upload error. Code: ' . $fileInput['error']];
    if ($fileInput['size'] > 2097152) return ['success' => false, 'error' => 'File size exceeds 2MB.'];
    
    $fileType = strtolower(pathinfo($fileInput['name'], PATHINFO_EXTENSION));
    if (!in_array($fileType, ['jpg', 'jpeg', 'png', 'webp'])) return ['success' => false, 'error' => 'Only JPG, JPEG, PNG, and WEBP files allowed.'];

    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    
    $fileName = ($id ? $id . '_' : '') . time() . '_' . uniqid() . '.' . $fileType;
    if (move_uploaded_file($fileInput['tmp_name'], $targetDir . $fileName)) {
        return ['success' => true, 'path' => str_replace('../../', '', $targetDir) . $fileName];
    }
    return ['success' => false, 'error' => 'Failed to move uploaded file.'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlash('error', 'Security Token Invalid');
        header('Location: ../projects.php');
        exit;
    }

    $action = $_POST['action'];

    if ($action === 'create') {
        $pdo->beginTransaction();
        try {
            $title = trim($_POST['title'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $target = (float)($_POST['target_amount'] ?? 0);
            $raised = (float)($_POST['raised_amount'] ?? 0);
            if ($title === '') throw new Exception('Title is required.');
            if ($desc === '') throw new Exception('Description is required.');
            if ($target < 1) throw new Exception('Minimum Target amount should be Rs. 1');
            if ($raised < 0) $raised = 0;

            $videoUrl = trim($_POST['video_url'] ?? '');
            if ($videoUrl !== '' && !preg_match('%^(?:https?://)?(?:www\.)?(?:youtube\.com|youtu\.be|youtube-nocookie\.com)/.*$%i', $videoUrl)) {
                throw new Exception('Invalid YouTube Video URL.');
            }

            $thumbResult = handleUpload($_FILES['thumbnail'], '../../uploads/projects/thumbnails/');
            if (!$thumbResult['success']) throw new Exception('Thumbnail: ' . $thumbResult['error']);

            $qrResult = handleUpload($_FILES['qr'], '../../uploads/projects/qrs/', '', true);
            if (!$qrResult['success']) throw new Exception('QR Code: ' . $qrResult['error']);

            $sql = "INSERT INTO projects (title, description, target_amount, raised_amount, thumbnail_image, upi_qr_image, video_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $desc, $target, $raised, $thumbResult['path'], $qrResult['path'], $_POST['video_url'], $_POST['status']]);
            $projectId = $pdo->lastInsertId();

            if (!empty($_FILES['gallery']['name'][0])) {
                foreach ($_FILES['gallery']['tmp_name'] as $key => $tmp) {
                    $file = ['name' => $_FILES['gallery']['name'][$key], 'tmp_name' => $tmp, 'size' => $_FILES['gallery']['size'][$key], 'error' => $_FILES['gallery']['error'][$key]];
                    $galleryResult = handleUpload($file, '../../uploads/projects/gallery/', $projectId);
                    if ($galleryResult['success'] && $galleryResult['path']) {
                        $pdo->prepare("INSERT INTO project_gallery (project_id, image_path) VALUES (?, ?)")->execute([$projectId, $galleryResult['path']]);
                    }
                }
            }
            $pdo->commit();
            setFlash('success', 'Project created successfully!');
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash('error', 'Error: ' . $e->getMessage());
        }
        header('Location: ../projects.php');
    }

    if ($action === 'update_details') {
        $id = $_POST['id'];
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $target = (float)($_POST['target_amount'] ?? 0);
        $raised = (float)($_POST['raised_amount'] ?? 0);
        if ($title === '') { setFlash('error', 'Title is required.'); header('Location: ../project_edit.php?id=' . $id); exit; }
        if ($desc === '') { setFlash('error', 'Description is required.'); header('Location: ../project_edit.php?id=' . $id); exit; }
        if ($target < 1) { setFlash('error', 'Minimum Target amount should be Rs. 1'); header('Location: ../project_edit.php?id=' . $id); exit; }
        if ($raised < 0) $raised = 0;

        $videoUrl = trim($_POST['video_url'] ?? '');
        if ($videoUrl !== '' && !preg_match('%^(?:https?://)?(?:www\.)?(?:youtube\.com|youtu\.be|youtube-nocookie\.com)/.*$%i', $videoUrl)) {
            setFlash('error', 'Invalid YouTube Video URL.');
            header('Location: ../project_edit.php?id=' . $id);
            exit;
        }

        $sql = "UPDATE projects SET title=?, description=?, target_amount=?, raised_amount=?, video_url=?, status=? WHERE id=?";
        $pdo->prepare($sql)->execute([$title, $desc, $target, $raised, $_POST['video_url'], $_POST['status'], $id]);
        setFlash('success', 'Project details updated.');
        header('Location: ../project_edit.php?id=' . $id);
    }
    
    if ($action === 'update_media') {
        $id = $_POST['id'];
        $thumbResult = handleUpload($_FILES['thumbnail'], '../../uploads/projects/thumbnails/', $id);
        if (!$thumbResult['success']) { setFlash('error', 'Thumbnail: '.$thumbResult['error']); header('Location: ../project_edit.php?id='.$id); exit; }
        if ($thumbResult['path']) $pdo->prepare("UPDATE projects SET thumbnail_image=? WHERE id=?")->execute([$thumbResult['path'], $id]);
        
        $qrResult = handleUpload($_FILES['qr'], '../../uploads/projects/qrs/', $id, true);
        if (!$qrResult['success']) { setFlash('error', 'QR Code: '.$qrResult['error']); header('Location: ../project_edit.php?id='.$id); exit; }
        if ($qrResult['path']) $pdo->prepare("UPDATE projects SET upi_qr_image=? WHERE id=?")->execute([$qrResult['path'], $id]);
        
        setFlash('success', 'Project media updated.');
        header('Location: ../project_edit.php?id=' . $id);
    }

    if ($action === 'add_gallery') {
        $id = $_POST['id'];
        $uploadedCount = 0;
        $errors = [];
        if (!empty($_FILES['gallery']['name'][0])) {
            foreach ($_FILES['gallery']['tmp_name'] as $key => $tmp) {
                $file = ['name' => $_FILES['gallery']['name'][$key], 'tmp_name' => $tmp, 'size' => $_FILES['gallery']['size'][$key], 'error' => $_FILES['gallery']['error'][$key]];
                $res = handleUpload($file, '../../uploads/projects/gallery/', $id);
                if ($res['success'] && $res['path']) {
                    $pdo->prepare("INSERT INTO project_gallery (project_id, image_path) VALUES (?, ?)")->execute([$id, $res['path']]);
                    $uploadedCount++;
                } elseif (!$res['success'] && !empty($file['name'])) {
                    $errors[] = $file['name'] . ': ' . $res['error'];
                }
            }
        }
        if ($uploadedCount > 0) {
            $msg = "$uploadedCount gallery images added.";
            if (!empty($errors)) $msg .= " (Failed: " . implode(', ', $errors) . ")";
            setFlash('success', $msg);
        } else {
            if (!empty($errors)) {
                setFlash('error', 'Upload failed: ' . implode(', ', $errors));
            } else {
                setFlash('error', 'Please select at least one gallery image file to upload.');
            }
        }
        header('Location: ../project_edit.php?id=' . $id);
    }
    if ($action === 'delete_gallery_image') {
        $id = $_POST['image_id'];
        $projectId = $_POST['project_id'];
        $path = $pdo->query("SELECT image_path FROM project_gallery WHERE id=$id")->fetchColumn();
        if ($path && file_exists('../../' . $path)) unlink('../../' . $path);
        $pdo->prepare("DELETE FROM project_gallery WHERE id=?")->execute([$id]);
        setFlash('success', 'Image removed from gallery.');
        header('Location: ../project_edit.php?id=' . $projectId);
    }

    if ($action === 'delete_project') {
        $id = $_POST['id'];
        $pdo->beginTransaction();
        try {
            $gallery = $pdo->query("SELECT image_path FROM project_gallery WHERE project_id=$id")->fetchAll(PDO::FETCH_COLUMN);
            foreach($gallery as $path) if(file_exists('../../'.$path)) unlink('../../'.$path);
            
            $mainImages = $pdo->query("SELECT thumbnail_image, upi_qr_image FROM projects WHERE id=$id")->fetch(PDO::FETCH_ASSOC);
            if ($mainImages['thumbnail_image'] && file_exists('../../'.$mainImages['thumbnail_image'])) unlink('../../'.$mainImages['thumbnail_image']);
            if ($mainImages['upi_qr_image'] && file_exists('../../'.$mainImages['upi_qr_image'])) unlink('../../'.$mainImages['upi_qr_image']);

            $pdo->prepare("DELETE FROM projects WHERE id=?")->execute([$id]);
            $pdo->commit();
            setFlash('success', 'Project deleted permanently.');
        } catch(Exception $e) {
            $pdo->rollBack();
            setFlash('error', 'Could not delete project.');
        }
        header('Location: ../projects.php');
    }
    
    exit;
}

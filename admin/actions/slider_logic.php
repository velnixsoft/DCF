<?php
require '../../config/db.php';
require '../../includes/functions.php';

if (!isset($_SESSION['logged_in'])) {
    header('Location: ../index.php');
    exit;
}
if (!canAccessModule($pdo, 'manager', 'page.slider_manager')) {
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlash('error', 'Security Token Invalid');
        header('Location: ../slider_manager.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $active_count = $pdo->query("SELECT COUNT(*) FROM sliders WHERE is_active = 1")->fetchColumn();
        if ($active_count >= 5) {
            setFlash('error', 'Maximum 5 active slides are allowed. Please deactivate one first.');
        } else {
            $title = cleanInput($_POST['title']);
            $subtitle = cleanInput($_POST['subtitle']);
            if (!empty($_FILES['image']['name'])) {
                $targetDir = "../../uploads/slider/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                $fileName = time() . '_' . basename($_FILES['image']['name']);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $fileName)) {
                    $pdo->prepare("INSERT INTO sliders (title, subtitle, image_path) VALUES (?, ?, ?)")->execute([$title, $subtitle, 'uploads/slider/' . $fileName]);
                    setFlash('success', 'New slide added successfully!');
                }
            }
        }
    }

    if ($action === 'delete') {
        $id = $_POST['id'];
        $path = $pdo->query("SELECT image_path FROM sliders WHERE id=$id")->fetchColumn();
        if ($path && file_exists("../../" . $path)) unlink("../../" . $path);
        $pdo->prepare("DELETE FROM sliders WHERE id=?")->execute([$id]);
        setFlash('success', 'Slide has been deleted.');
    }

    if ($action === 'toggle_status') {
        $id = $_POST['id'];
        $current_status = $pdo->query("SELECT is_active FROM sliders WHERE id=$id")->fetchColumn();
        $new_status = ($current_status == 1) ? 0 : 1;

        if ($new_status == 1) {
            $active_count = $pdo->query("SELECT COUNT(*) FROM sliders WHERE is_active = 1")->fetchColumn();
            if ($active_count >= 5) {
                setFlash('error', 'Cannot activate more than 5 slides.');
                header('Location: ../slider_manager.php');
                exit;
            }
        }

        $pdo->prepare("UPDATE sliders SET is_active = ? WHERE id = ?")->execute([$new_status, $id]);
        setFlash('success', 'Slide status updated.');
    }

    header('Location: ../slider_manager.php');
    exit;
}

<?php
session_start();
require '../../config/db.php';
require '../../includes/functions.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'manager', 'page.events')) {
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    setFlash('error', 'Invalid request.');
    header('Location: ../events.php');
    exit;
}

$action = $_POST['action'];
$id = (int)($_POST['id'] ?? 0);

try {
    switch ($action) {
        case 'create':
        case 'update':
            $title = cleanInput($_POST['title']);
            $description = cleanInput($_POST['description'] ?? '');
            $event_date = $_POST['event_date'];
            $location = cleanInput($_POST['location']);
            $registration_open = (int)$_POST['registration_open'];
            $max_registrations = isset($_POST['max_registrations']) && $_POST['max_registrations'] !== '' ? (int)$_POST['max_registrations'] : null;
            $status = cleanInput($_POST['status']);
            
            // Validate event date format, year limit, and past date restriction (on create)
            if (!empty($event_date)) {
                $dateObj = DateTime::createFromFormat('Y-m-d', $event_date);
                if (!$dateObj || $dateObj->format('Y-m-d') !== $event_date) {
                    throw new Exception('Invalid Event Date format.');
                }
                $year = (int)$dateObj->format('Y');
                if ($year > 9999) {
                    throw new Exception('Year cannot be more than 4 digits.');
                }
            }
            
            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, location, registration_open, max_registrations, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description ?: null, $event_date, $location, $registration_open, $max_registrations, $status, $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE events SET title=?, description=?, event_date=?, location=?, registration_open=?, max_registrations=?, status=? WHERE id=?");
                $stmt->execute([$title, $description ?: null, $event_date, $location, $registration_open, $max_registrations, $status, $id]);
            }
            $success_action = $action === 'create' ? 'created' : 'updated';
            header("Location: ../events.php?action={$success_action}");
            break;
            
        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM events WHERE id=?");
            $stmt->execute([$id]);
            header('Location: ../events.php');
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    setFlash('error', 'Operation failed.');
    header('Location: ../events.php');
}
?>


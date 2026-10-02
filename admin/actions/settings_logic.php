<?php
require '../../config/db.php';
require '../../includes/functions.php';

if (!isset($_SESSION['logged_in'])) { header('Location: ../index.php'); exit; }
if (!canAccessModule($pdo, 'admin', 'page.settings')) { setFlash('error', 'Access denied. Admin required.'); header('Location: ../dashboard.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlash('error', 'Token Invalid');
        header('Location: ../settings.php');
        exit;
    }

    $action = $_POST['action'] ?? null;
    $tab = $_POST['tab'] ?? 'general';

    if ($action === 'add_bank') {
        $name = cleanInput($_POST['bank_name']);
        $holder = cleanInput($_POST['account_holder']);
        $acc_no = cleanInput($_POST['account_number']);
        $ifsc = cleanInput($_POST['ifsc_code']);
        $pdo->prepare("INSERT INTO bank_accounts (bank_name, account_holder, account_number, ifsc_code) VALUES (?, ?, ?, ?)")->execute([$name, $holder, $acc_no, $ifsc]);
        setFlash('success', 'Bank Account Added.');
        header('Location: ../settings.php?tab=payments'); exit;
    }
    
    if ($action === 'delete_bank') {
        $id = $_POST['id'];
        $pdo->prepare("DELETE FROM bank_accounts WHERE id=?")->execute([$id]);
        setFlash('success', 'Bank Account Removed.');
        header('Location: ../settings.php?tab=payments'); exit;
    }
    
    if ($action === 'add_qr') {
        $title = cleanInput($_POST['title']);
        if (!empty($_FILES['qr_image']['name'])) {
            $targetDir = "../../uploads/qrs/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $fileName = time() . '_' . basename($_FILES['qr_image']['name']);
            if (move_uploaded_file($_FILES['qr_image']['tmp_name'], $targetDir . $fileName)) {
                $dbPath = 'uploads/qrs/' . $fileName;
                $pdo->prepare("INSERT INTO payment_qrs (title, qr_image_path) VALUES (?, ?)")->execute([$title, $dbPath]);
                setFlash('success', 'QR Code Added.');
            }
        }
        header('Location: ../settings.php?tab=payments'); exit;
    }

    if ($action === 'delete_qr') {
        $id = $_POST['id'];
        $path = $pdo->prepare("SELECT qr_image_path FROM payment_qrs WHERE id=?");
        $path->execute([$id]);
        $filePath = $path->fetchColumn();
        if ($filePath && file_exists('../../' . $filePath)) unlink('../../' . $filePath);
        $pdo->prepare("DELETE FROM payment_qrs WHERE id=?")->execute([$id]);
        setFlash('success', 'QR Code Removed.');
        header('Location: ../settings.php?tab=payments'); exit;
    }

    if(!$action) {
        if ($tab === 'footer') {
            $whatsapp = preg_replace('/\D+/', '', (string)($_POST['whatsapp_number'] ?? ''));
            if ($whatsapp !== '' && !preg_match('/^[0-9]{10,15}$/', $whatsapp)) {
                setFlash('error', 'WhatsApp number must contain only digits and be 10 to 15 digits long.');
                header('Location: ../settings.php?tab=footer');
                exit;
            }
            $_POST['whatsapp_number'] = $whatsapp;
        }

        $uploadDir = dirname(dirname(__DIR__)) . "/uploads/settings/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        function handleUpload($inputName, $dbKey, $uploadDir, $pdo) {
            if (!empty($_FILES[$inputName]['name'])) {
                $fileName = $inputName . '_' . time() . '.' . pathinfo($_FILES[$inputName]['name'], PATHINFO_EXTENSION);
                if(move_uploaded_file($_FILES[$inputName]['tmp_name'], $uploadDir . $fileName)) {
                    $dbPath = 'uploads/settings/' . $fileName;
                    $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$dbKey, $dbPath, $dbPath]);
                }
            }
        }
        
        handleUpload('site_favicon', 'site_favicon', $uploadDir, $pdo);
        handleUpload('ngo_logo', 'ngo_logo', $uploadDir, $pdo);
        handleUpload('ngo_signature', 'ngo_signature', $uploadDir, $pdo);
        handleUpload('certificate_bg',  'certificate_bg', $uploadDir, $pdo);
        handleUpload('letterhead_logo', 'letterhead_logo', $uploadDir, $pdo);
        handleUpload('letterhead_signature_image', 'letterhead_signature_image', $uploadDir, $pdo);

        for ($i = 1; $i <= 3; $i++) {
            $prefix = 'doc_brand_' . $i . '_';
            handleUpload($prefix . 'logo', $prefix . 'logo', $uploadDir, $pdo);
            handleUpload($prefix . 'signature', $prefix . 'signature', $uploadDir, $pdo);
            handleUpload($prefix . 'certificate_bg', $prefix . 'certificate_bg', $uploadDir, $pdo);
        }

        $exclude = [
            'csrf_token', 'tab', 'action', 'ngo_logo', 'ngo_signature', 'site_favicon', 
            'letterhead_logo', 'letterhead_signature_image',
            'new_password', 'confirm_password', 'certificate_bg', 'current_password'
        ];
        foreach ($_POST as $key => $value) {
            if (!in_array($key, $exclude)) {
                $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$key, $value, $value]);
            }
        }

        if ($tab === 'security' && !empty($_POST['new_password'])) {
            if ($_POST['new_password'] !== $_POST['confirm_password']) {
                setFlash('error', 'New passwords do not match!');
            } else {
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $userPass = $stmt->fetchColumn();
                if (password_verify($_POST['current_password'], $userPass)) {
                    $hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
                    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $_SESSION['user_id']]);
                    setFlash('success', 'Password updated successfully!');
                } else {
                    setFlash('error', 'Incorrect current password!');
                }
            }
        } else {
            setFlash('success', 'Settings updated successfully!');
        }
        
        header('Location: ../settings.php?tab=' . $tab);
        exit;
    }
}
?>

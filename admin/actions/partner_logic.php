<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/partner/portal_helpers.php';
require_once __DIR__ . '/../../includes/admin_audit.php';

if (!checkRole($pdo, 'coordinator')) {
    setFlash('error', 'Unauthorized access.');
    header('Location: ../dashboard.php');
    exit;
}

$action = $_POST['action'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? '';

if (empty($_SESSION['csrf_token']) || $csrfToken !== $_SESSION['csrf_token']) {
    setFlash('error', 'CSRF verification failed.');
    header('Location: ../partner_directory.php');
    exit;
}

$adminUserId = (int)($_SESSION['user_id'] ?? 0);
$id = (int)($_POST['id'] ?? 0);

try {
    if ($action === 'approve' && $id > 0) {
        $apiKey = partner_generate_api_key();
        $stmt = $pdo->prepare("
            UPDATE sa_partners
            SET status = 'Active', api_key = ?, api_enabled = 1,
                approved_by_user_id = ?, approved_at = NOW(), updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$apiKey, $adminUserId, $id]);
        admin_audit_log($pdo, 'partner_approve', 'sa_partners', $id, 'Partner approved with API key');
        setFlash('success', 'Partner approved and API access enabled.');
    } elseif ($action === 'reject' && $id > 0) {
        $reason = cleanInput($_POST['reason'] ?? 'Not approved');
        $stmt = $pdo->prepare("UPDATE sa_partners SET status = 'Rejected', rejection_reason = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$reason, $id]);
        admin_audit_log($pdo, 'partner_reject', 'sa_partners', $id, $reason);
        setFlash('success', 'Partner registration rejected.');
    } elseif ($action === 'suspend' && $id > 0) {
        $reason = cleanInput($_POST['reason'] ?? 'Suspended by admin');
        $stmt = $pdo->prepare("UPDATE sa_partners SET status = 'Suspended', rejection_reason = ?, api_enabled = 0, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$reason, $id]);
        admin_audit_log($pdo, 'partner_suspend', 'sa_partners', $id, $reason);
        setFlash('success', 'Partner suspended.');
    } elseif ($action === 'toggle_api' && $id > 0) {
        $stmt = $pdo->prepare('SELECT api_enabled, api_key FROM sa_partners WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $newState = (int)($row['api_enabled'] ?? 0) === 1 ? 0 : 1;
            $apiKey = $row['api_key'] ?: partner_generate_api_key();
            $upd = $pdo->prepare('UPDATE sa_partners SET api_enabled = ?, api_key = ?, updated_at = NOW() WHERE id = ?');
            $upd->execute([$newState, $apiKey, $id]);
            setFlash('success', $newState === 1 ? 'API access enabled.' : 'API access disabled.');
        }
    }
} catch (Throwable $e) {
    setFlash('error', 'Action failed: ' . $e->getMessage());
}

header('Location: ../partner_directory.php');
exit;

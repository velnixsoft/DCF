<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require '../config/db.php';
require '../includes/functions.php';

$userId = (int)$_SESSION['user_id'];
if (normalizeHierarchyRole($_SESSION['hierarchy_level'] ?? '') !== 'field_agent') {
    echo json_encode(['success' => false, 'message' => 'Field Agent only']);
    exit;
}

// Get agent
$agentStmt = $pdo->prepare('SELECT id FROM field_agents WHERE user_id = ?');
$agentStmt->execute([$userId]);
$agentId = $agentStmt->fetchColumn();

if (!$agentId) {
    echo json_encode(['success' => false, 'message' => 'Agent profile not found']);
    exit;
}

$donorName = cleanInput($_POST['donor_name'] ?? '');
$donorPhone = cleanInput($_POST['donor_phone'] ?? '');
$donorAddress = cleanInput($_POST['donor_address'] ?? '');
$donorEmail = cleanInput($_POST['donor_email'] ?? '');
$amount = (float)($_POST['amount'] ?? 0);
$paymentMode = $_POST['payment_mode'] ?? 'cash';

if (!$donorName || !$donorPhone || $amount < 10) {
    echo json_encode(['success' => false, 'message' => 'Donor name, phone, and minimum ₹10 required']);
    exit;
}

// Insert donation
$receiptNo = generateNextDonationReceiptNumber($pdo);
$paymentStatus = $paymentMode === 'online' ? 'Success' : 'Pending';
$stmt = $pdo->prepare('
    INSERT INTO donations (
        donor_name, donor_mobile, donor_email, donor_address, amount, 
        payment_gateway, payment_status, field_agent_id, payment_mode_field, receipt_no
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
');
if (!$stmt->execute([
    $donorName, $donorPhone, $donorEmail, $donorAddress, $amount,
    'Manual (Field Agent)', $paymentStatus, $agentId, $paymentMode, $receiptNo
])) {
    echo json_encode(['success' => false, 'message' => 'Failed to record donation']);
    exit;
}

$donationId = $pdo->lastInsertId();

if ($paymentMode === 'cash') {
    // Create cash deposit record (7 day deadline) only for cash mode.
    $deadline = date('Y-m-d', strtotime('+7 days'));
    $cashStmt = $pdo->prepare('
        INSERT INTO cash_deposits (donation_id, agent_id, collected_amount, deposit_deadline) 
        VALUES (?, ?, ?, ?)
    ');
    $cashStmt->execute([$donationId, $agentId, $amount, $deadline]);
} else {
    $deadline = null;
}

// Update agent monthly collected
$pdo->prepare('UPDATE field_agents SET collected_amount_monthly = collected_amount_monthly + ? WHERE id = ?')
    ->execute([$amount, $agentId]);

echo json_encode([
    'success' => true,
    'message' => $paymentMode === 'cash'
        ? 'Donation recorded successfully! Deposit cash within 7 days.'
        : 'Online donation recorded successfully.',
    'donation_id' => $donationId,
    'deadline' => $deadline,
    'receipt_no' => $receiptNo
]);


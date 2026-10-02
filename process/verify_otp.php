<?php
require '../config/db.php';
require '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$otp = trim($_POST['otp'] ?? '');

if (empty($_SESSION['otp_code']) || $_SESSION['otp_code'] != $otp || $_SESSION['otp_email'] != $email || time() > $_SESSION['otp_expiry']) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP. Please try again.']);
    exit;
}

// 1. Fetch one-time donations
$stmt = $pdo->prepare("
    SELECT d.*, p.title AS project_title 
    FROM donations d 
    LEFT JOIN projects p ON d.project_id = p.id 
    WHERE d.donor_email = ? 
    ORDER BY d.created_at DESC
");
$stmt->execute([$email]);
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch recurring donation profiles
$recStmt = $pdo->prepare("
    SELECT r.*, p.title AS project_title,
           (SELECT COUNT(*) FROM recurring_donation_transactions WHERE recurring_donation_id = r.id AND status = 'success') AS total_cycles_paid,
           (SELECT COALESCE(SUM(amount), 0) FROM recurring_donation_transactions WHERE recurring_donation_id = r.id AND status = 'success') AS total_paid_amount
    FROM recurring_donations r
    LEFT JOIN projects p ON r.project_id = p.id
    WHERE r.donor_email = ?
    ORDER BY r.created_at DESC
");
$recStmt->execute([$email]);
$recurring = $recStmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch recurring cycles history
$txStmt = $pdo->prepare("
    SELECT t.*, r.frequency, r.razorpay_subscription_id, p.title AS project_title, d.receipt_no AS donation_receipt_no
    FROM recurring_donation_transactions t
    JOIN recurring_donations r ON t.recurring_donation_id = r.id
    LEFT JOIN donations d ON t.donation_id = d.id
    LEFT JOIN projects p ON r.project_id = p.id
    WHERE r.donor_email = ?
    ORDER BY t.charge_date DESC, t.id DESC
");
$txStmt->execute([$email]);
$recurringTransactions = $txStmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Fetch item donations
$itemStmt = $pdo->prepare("
    SELECT i.*, c.category_name, c.category_icon, p.title AS project_title
    FROM item_donations i
    LEFT JOIN item_donation_categories c ON i.category_id = c.id
    LEFT JOIN projects p ON i.project_id = p.id
    WHERE i.donor_email = ?
    ORDER BY i.created_at DESC
");
$itemStmt->execute([$email]);
$itemDonationsRaw = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

$itemDonations = [];
foreach ($itemDonationsRaw as $itm) {
    $rcp = $itm['receipt_no'] ?: ('RCP-ITM-' . $itm['id']);
    $token = generateItemDonationReceiptToken($itm['id'], $itm['donation_code'], $rcp, $itm['donor_email'], $itm['created_at']);
    $itm['receipt_download_url'] = 'download-item-receipt.php?id=' . (int)$itm['id'] . '&token=' . urlencode($token);
    $itm['verify_url'] = 'verify-item.php?code=' . urlencode($itm['donation_code']) . '&token=' . urlencode($token);
    $itemDonations[] = $itm;
}

unset($_SESSION['otp_code'], $_SESSION['otp_expiry']);
$_SESSION['donor_verified'] = true;
$_SESSION['donor_email'] = $email;

echo json_encode([
    'success' => true,
    'email' => $email,
    'donations' => $donations,
    'recurring' => $recurring,
    'recurring_transactions' => $recurringTransactions,
    'item_donations' => $itemDonations
]);


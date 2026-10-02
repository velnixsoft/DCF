<?php
// ============================================================
// process/verify_phonepe_payment.php
// Handles PhonePe browser redirect / POST callback
// ============================================================

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payments/PaymentGatewayManager.php';

use App\Payments\PaymentGatewayManager;

$manager = new PaymentGatewayManager($pdo);
$phonepeAdapter = $manager->getGateway('phonepe');

$payload = $_POST;
if (empty($payload) && !empty($_GET)) {
    $payload = $_GET;
}

$verifyResult = $phonepeAdapter->verifyPayment($payload);
$orderId = $verifyResult['order_id'];
$paymentId = $verifyResult['payment_id'] ?: $orderId;
$status = $verifyResult['status'];

if ($verifyResult['success']) {
    // Check if donation
    $stmt = $pdo->prepare("SELECT * FROM donations WHERE razorpay_order_id = ? OR transaction_id = ? OR id = ? LIMIT 1");
    $stmt->execute([$orderId, $orderId, is_numeric($orderId) ? (int)$orderId : 0]);
    $donation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($donation) {
        if ($donation['payment_status'] === 'Pending') {
            $receipt_no = generateNextReceiptNumber($pdo);
            $update = $pdo->prepare("
                UPDATE donations SET
                    payment_status = 'Success',
                    payment_gateway = 'PhonePe',
                    transaction_id = ?,
                    razorpay_payment_id = ?,
                    receipt_no = ?
                WHERE id = ?
            ");
            $update->execute([$paymentId, $paymentId, $receipt_no, $donation['id']]);

            if (!empty($donation['project_id'])) {
                $pdo->prepare("UPDATE projects SET raised_amount = raised_amount + ? WHERE id = ?")
                    ->execute([$donation['amount'], $donation['project_id']]);
            }
        }

        $_SESSION['last_donation_id'] = $donation['id'];
        $_SESSION['last_receipt_no'] = $donation['receipt_no'] ?? ($receipt_no ?? '');
        header('Location: ' . rtrim(appBaseUrl(), '/') . '/thankyou.php?donation_id=' . $donation['id']);
        exit;
    }

    // Check if join application
    $joinStmt = $pdo->prepare("SELECT * FROM join_applications WHERE razorpay_order_id = ? OR application_no = ? OR id = ? LIMIT 1");
    $joinStmt->execute([$orderId, $orderId, is_numeric($orderId) ? (int)$orderId : 0]);
    $app = $joinStmt->fetch(PDO::FETCH_ASSOC);
    if ($app) {
        $pdo->prepare("
            UPDATE join_applications SET
                payment_status = 'paid',
                payment_method = 'PhonePe',
                transaction_id = ?,
                razorpay_payment_id = ?,
                updated_at = NOW()
            WHERE id = ?
        ")->execute([$paymentId, $paymentId, $app['id']]);

        setFlash('success', 'Thank you! Your application fee payment has been successfully verified via PhonePe.');
        header('Location: ' . rtrim(appBaseUrl(), '/') . '/join-us.php?status=success&app_id=' . $app['id']);
        exit;
    }

    // Fallback redirect
    header('Location: ' . rtrim(appBaseUrl(), '/') . '/thankyou.php');
    exit;
} else {
    // Payment failed or cancelled
    setFlash('error', 'PhonePe payment was not successful or was cancelled. Please try again.');
    header('Location: ' . rtrim(appBaseUrl(), '/') . '/donate.php?payment_error=1');
    exit;
}

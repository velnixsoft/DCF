<?php
// ============================================================
// api/recurring_donations.php
// REST API endpoint for managing and viewing recurring subscriptions
// ============================================================

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/razorpay_subscription_helper.php';

$method = api_method();

// ── GET: View recurring donation details & transaction history ──
if ($method === 'GET') {
    $subscriptionId = trim((string)api_input('subscription_id', ''));
    $email = filter_var(api_input('email', ''), FILTER_VALIDATE_EMAIL);
    $mobile = preg_replace('/[^0-9]/', '', (string)api_input('mobile', ''));

    if ($subscriptionId === '' && !$email && strlen($mobile) !== 10) {
        api_error('Please provide subscription_id, email, or 10-digit mobile.', 422);
    }

    try {
        if ($subscriptionId !== '') {
            $stmt = $pdo->prepare("
                SELECT r.*, p.title AS project_title 
                FROM recurring_donations r
                LEFT JOIN projects p ON r.project_id = p.id
                WHERE r.razorpay_subscription_id = ?
            ");
            $stmt->execute([$subscriptionId]);
            $recurring = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$recurring) {
                api_error('Subscription not found.', 404);
            }

            // Fetch transactions
            $txStmt = $pdo->prepare("
                SELECT id, cycle_number, amount, charge_date, status, receipt_no, razorpay_payment_id, created_at 
                FROM recurring_donation_transactions
                WHERE recurring_donation_id = ?
                ORDER BY cycle_number DESC
            ");
            $txStmt->execute([$recurring['id']]);
            $transactions = $txStmt->fetchAll(PDO::FETCH_ASSOC);

            api_ok([
                'subscription' => $recurring,
                'transactions' => $transactions
            ]);
        } else {
            $query = "SELECT r.*, p.title AS project_title FROM recurring_donations r LEFT JOIN projects p ON r.project_id = p.id WHERE ";
            $params = [];
            if ($email) {
                $query .= "r.donor_email = ?";
                $params[] = $email;
            } else {
                $query .= "r.donor_mobile = ?";
                $params[] = $mobile;
            }
            $query .= " ORDER BY r.id DESC";

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            api_ok(['subscriptions' => $list]);
        }
    } catch (PDOException $e) {
        error_log('api/recurring_donations fetch error: ' . $e->getMessage());
        api_error('Failed to retrieve recurring donation records.', 500);
    }
}

// ── POST: Actions (Pause, Resume, Cancel) ─────────────────────
if ($method === 'POST') {
    $action = strtolower(trim((string)api_input('action', '')));
    $subscriptionId = trim((string)api_input('subscription_id', ''));

    if ($subscriptionId === '') {
        api_error('Subscription ID is required.', 422);
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM recurring_donations WHERE razorpay_subscription_id = ?");
        $stmt->execute([$subscriptionId]);
        $recurring = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$recurring) {
            api_error('Subscription not found.', 404);
        }

        switch ($action) {
            case 'cancel':
            case 'stop':
                $reason = trim((string)api_input('reason', 'Cancelled via donor API request'));
                processSubscriptionCancelled($pdo, $subscriptionId, $reason);
                api_ok(['status' => 'stopped'], 'Recurring donation stopped successfully.');
                break;

            case 'pause':
                $reason = trim((string)api_input('reason', 'Paused via donor API request'));
                processSubscriptionHalted($pdo, $subscriptionId, $reason);
                api_ok(['status' => 'paused'], 'Recurring donation paused successfully.');
                break;

            case 'resume':
            case 'activate':
                processSubscriptionActivated($pdo, $subscriptionId);
                api_ok(['status' => 'active'], 'Recurring donation resumed successfully.');
                break;

            default:
                api_error('Invalid action. Allowed actions: pause, resume, cancel.', 400);
                break;
        }
    } catch (PDOException $e) {
        error_log('api/recurring_donations action error: ' . $e->getMessage());
        api_error('Failed to execute subscription action.', 500);
    }
}

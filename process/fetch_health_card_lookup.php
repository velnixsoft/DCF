<?php
// ============================================================
// process/fetch_health_card_lookup.php
// Lookup existing Health Card details for Auto-filling Renewals
// ============================================================

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$query = cleanInput($_REQUEST['query'] ?? '');
$cardId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (empty($query) && empty($cardId)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a Card Number, Mobile Number, or Card ID.']);
    exit;
}

try {
    if ($cardId) {
        $stmt = $pdo->prepare("SELECT * FROM health_cards WHERE id = ? LIMIT 1");
        $stmt->execute([$cardId]);
    } else {
        // Match exact card number or contact phone
        $stmt = $pdo->prepare("
            SELECT * FROM health_cards 
            WHERE card_number = ? OR contact = ? OR REPLACE(contact, ' ', '') = ?
            ORDER BY id DESC LIMIT 1
        ");
        $cleanQuery = str_replace(' ', '', $query);
        $stmt->execute([$query, $query, $cleanQuery]);
    }

    $card = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$card) {
        echo json_encode([
            'success' => false,
            'message' => 'No matching Health Card record found for "' . htmlspecialchars($query) . '". Please check and try again.'
        ]);
        exit;
    }

    // Expiry calculation
    $isExpired = false;
    $isExpiringSoon = false;
    $daysRemaining = null;
    if (!empty($card['expiry_date'])) {
        $expTime = strtotime($card['expiry_date']);
        $todayTime = strtotime(date('Y-m-d'));
        $daysRemaining = (int)ceil(($expTime - $todayTime) / 86400);
        if ($daysRemaining < 0 || $card['status'] === 'expired') {
            $isExpired = true;
        } elseif ($daysRemaining <= 60) {
            $isExpiringSoon = true;
        }
    }

    echo json_encode([
        'success' => true,
        'card' => [
            'id' => (int)$card['id'],
            'card_number' => $card['card_number'],
            'applicant_name' => $card['applicant_name'],
            'contact' => $card['contact'],
            'email' => $card['email'] ?? '',
            'dob' => $card['dob'] ?? '',
            'gender' => $card['gender'] ?? 'Male',
            'blood_group' => $card['blood_group'] ?? '',
            'aadhaar_no' => $card['aadhaar_no'] ?? '',
            'emergency_contact' => $card['emergency_contact'] ?? '',
            'state' => $card['state'] ?? '',
            'district' => $card['district'] ?? '',
            'block' => $card['block'] ?? '',
            'pincode' => $card['pincode'] ?? '',
            'address' => $card['address'] ?? '',
            'photo' => $card['photo'] ?? '',
            'issue_date' => $card['issue_date'] ?? '',
            'expiry_date' => $card['expiry_date'] ?? '',
            'status' => $card['status'] ?? 'active',
            'is_expired' => $isExpired,
            'is_expiring_soon' => $isExpiringSoon,
            'days_remaining' => $daysRemaining,
            'pdf_path' => $card['pdf_path'] ?? ''
        ]
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

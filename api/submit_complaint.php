<?php
// ============================================================
// api/submit_complaint.php
// REST API Endpoint for Complaint / Suggestion Registration
// Response format: JSON standard { success: bool, message: string, data: object }
// ============================================================

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/member_module.php';

api_require_method(['POST']);

$name = cleanInput(api_input('name', ''));
$contact = cleanInput(api_input('contact', ''));
$email = filter_var((string)api_input('email', ''), FILTER_SANITIZE_EMAIL);
$type = in_array(strtolower((string)api_input('type', '')), ['complaint', 'suggestion'], true) ? strtolower((string)api_input('type')) : 'complaint';
$subject = cleanInput(api_input('subject', ''));
$description = cleanInput(api_input('description', ''));
$priority = in_array(strtolower((string)api_input('priority', '')), ['low', 'medium', 'high', 'urgent'], true) ? strtolower((string)api_input('priority')) : 'medium';

if ($name === '' || $contact === '' || $subject === '' || $description === '') {
    api_error('Please fill all required fields (name, contact, subject, description).', 422);
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    api_error('Please provide a valid email address.', 422);
}

$year = date('Y');
$prefix = ($type === 'suggestion') ? "SUG-{$year}-" : "TKT-{$year}-";
$countToday = (int)$pdo->query("SELECT COUNT(*) FROM `complaints` WHERE YEAR(created_at) = {$year}")->fetchColumn() + 1;
$ticketNo = $prefix . str_pad($countToday, 4, '0', STR_PAD_LEFT);

$userIp = $_SERVER['REMOTE_ADDR'] ?? null;

try {
    $stmt = $pdo->prepare("
        INSERT INTO `complaints` (
            `ticket_no`, `name`, `contact`, `email`, `type`, `subject`, 
            `description`, `status`, `priority`, `user_ip`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)
    ");

    $stmt->execute([
        $ticketNo, $name, $contact, $email !== '' ? $email : null, $type, $subject,
        $description, $priority, $userIp
    ]);

    $complaintId = (int)$pdo->lastInsertId();

    api_ok([
        'complaint_id' => $complaintId,
        'ticket_no' => $ticketNo,
        'type' => $type,
        'status' => 'pending'
    ], "Your {$type} has been submitted successfully with Ticket Reference {$ticketNo}.");

} catch (Throwable $e) {
    api_error('Failed to submit ticket: ' . $e->getMessage(), 500);
}

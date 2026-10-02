<?php
// ============================================================
// api/track_complaint.php
// Public REST API endpoint for Tracking Complaint / Suggestion Status
// ============================================================

require_once __DIR__ . '/_bootstrap.php';

api_require_method(['GET', 'POST']);

$ticketNo = trim((string)api_input('ticket_no', ''));
if ($ticketNo === '') {
    $ticketNo = trim((string)api_input('ticket', ''));
}

if ($ticketNo === '') {
    api_error('Please enter a valid ticket reference number.', 422);
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            c.id, c.ticket_no, c.name, c.contact, c.email, c.type, 
            c.subject, c.description, c.status, c.priority, c.admin_reply, 
            c.replied_at, c.resolved_at, c.attachment_path, c.created_at, c.updated_at,
            u.name AS replied_by_name, u.role AS replied_by_role
        FROM `complaints` c
        LEFT JOIN `users` u ON c.reply_by = u.id
        WHERE UPPER(TRIM(c.ticket_no)) = UPPER(?)
        LIMIT 1
    ");
    $stmt->execute([$ticketNo]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        api_error("No ticket record found matching '{$ticketNo}'. Please verify your reference number.", 404);
    }

    // Privacy masking for phone/email if needed
    $maskedContact = preg_replace('/(\d{2})\d+(\d{2})/', '$1******$2', $ticket['contact']);
    $maskedEmail = '';
    if (!empty($ticket['email'])) {
        $parts = explode('@', $ticket['email']);
        $namePart = $parts[0];
        $domain = $parts[1] ?? '';
        $maskedEmail = substr($namePart, 0, 2) . '***@' . $domain;
    }

    api_ok([
        'ticket_no' => $ticket['ticket_no'],
        'type' => $ticket['type'],
        'name' => $ticket['name'],
        'contact_masked' => $maskedContact,
        'email_masked' => $maskedEmail,
        'subject' => $ticket['subject'],
        'description' => $ticket['description'],
        'status' => $ticket['status'],
        'priority' => $ticket['priority'],
        'admin_reply' => $ticket['admin_reply'],
        'replied_by' => $ticket['replied_by_name'],
        'replied_at' => $ticket['replied_at'],
        'resolved_at' => $ticket['resolved_at'],
        'attachment_path' => $ticket['attachment_path'],
        'created_at' => $ticket['created_at'],
        'updated_at' => $ticket['updated_at']
    ], 'Ticket status retrieved successfully.');

} catch (Throwable $e) {
    api_error('Failed to retrieve ticket status: ' . $e->getMessage(), 500);
}

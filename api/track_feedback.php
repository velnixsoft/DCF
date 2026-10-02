<?php
// ============================================================
// api/track_feedback.php
// Public API Endpoint to Track Feedback Reference Status
// ============================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$ticketNo = cleanInput($_GET['ticket'] ?? ($_GET['feedback_no'] ?? ''));

if (empty($ticketNo)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a feedback reference number.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            f.id,
            f.feedback_no,
            f.submitter_type,
            f.name,
            f.department,
            f.category,
            f.rating,
            f.subject,
            f.message,
            f.is_anonymous,
            f.status,
            f.priority,
            f.admin_reply,
            f.replied_at,
            f.resolved_at,
            f.created_at,
            u.name AS replied_by_name
        FROM `feedbacks` f
        LEFT JOIN `users` u ON f.reply_by = u.id
        WHERE UPPER(TRIM(f.feedback_no)) = UPPER(?)
        LIMIT 1
    ");

    $stmt->execute([$ticketNo]);
    $feedback = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$feedback) {
        echo json_encode(['success' => false, 'message' => "No feedback record found for reference: '{$ticketNo}'. Please verify your code."]);
        exit;
    }

    if ($feedback['is_anonymous']) {
        $feedback['name'] = 'Anonymous Contributor (' . ucfirst($feedback['submitter_type']) . ')';
    }

    echo json_encode([
        'success' => true,
        'data' => $feedback
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

<?php
require_once __DIR__ . '/_bootstrap.php';

$token = api_bearer_token();
if ($token !== '') {
    $tokenRow = api_find_access_token($pdo, $token, 'member');
    if ($tokenRow) {
        try {
            $stmt = $pdo->prepare("
                SELECT m.*, d.title AS designation_title
                FROM members m
                LEFT JOIN member_designations d ON d.id = m.designation_id
                WHERE m.id = ?
                LIMIT 1
            ");
            $stmt->execute([(int)$tokenRow['user_id']]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $member = false;
        }

        if (!$member) {
            api_error('Member not found.', 404);
        }

        $memberId = (int)$member['id'];
        $referrals = 0;
        $donationTotal = 0.0;
        $messages = [];
        $inquiries = [];
        $documents = [];
        $events = [];

        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE referred_by_member_id = ?");
            $stmt->execute([$memberId]);
            $referrals = (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
        }

        try {
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM donations WHERE referral_code = ? AND payment_status = 'Success'");
            $stmt->execute([(string)($member['donation_ref_code'] ?? '')]);
            $donationTotal = (float)$stmt->fetchColumn();
        } catch (Throwable $e) {
        }

        try {
            $stmt = $pdo->prepare("
                SELECT md.id AS delivery_id, md.dashboard_status, md.created_at, mm.subject, mm.message_body, mm.message_type
                FROM member_message_deliveries md
                INNER JOIN member_messages mm ON mm.id = md.message_id
                WHERE md.member_id = ?
                ORDER BY md.created_at DESC
                LIMIT 100
            ");
            $stmt->execute([$memberId]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
        }

        try {
            $stmt = $pdo->prepare("
                SELECT id, problem_description, status, created_at, admin_notes, category, urgency, attachment_path
                FROM inquiries
                WHERE member_id = ?
                ORDER BY created_at DESC
                LIMIT 50
            ");
            $stmt->execute([$memberId]);
            $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            try {
                $stmt = $pdo->prepare("
                    SELECT id, problem_description, status, created_at, admin_notes, category, urgency, attachment_path
                    FROM inquiries
                    WHERE submitter_email = ?
                    ORDER BY created_at DESC
                    LIMIT 50
                ");
                $stmt->execute([(string)$member['email']]);
                $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e2) {
            }
        }

        try {
            $stmt = $pdo->prepare("
                SELECT id, doc_type, doc_no, issued_at, issued_by, meta_json
                FROM member_documents
                WHERE member_id = ?
                ORDER BY id DESC
            ");
            $stmt->execute([$memberId]);
            $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
        }

        try {
            $today = date('Y-m-d');
            $stmt = $pdo->prepare("
                SELECT id, title, event_date, location, status
                FROM events
                WHERE event_date >= ? AND status IN ('Upcoming','Live')
                ORDER BY event_date ASC
                LIMIT 8
            ");
            $stmt->execute([$today]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
        }

        api_ok([
            'profile' => $member,
            'stats' => [
                'referrals' => $referrals,
                'donation_total' => $donationTotal,
            ],
            'messages' => $messages,
            'inquiries' => $inquiries,
            'documents' => $documents,
            'upcoming_events' => $events,
            'referral_links' => [
                'membership' => rtrim(appBaseUrl(), '/') . '/member-register.php?ref=' . urlencode((string)($member['referral_code'] ?? '')),
                'donation' => rtrim(appBaseUrl(), '/') . '/donate.php?mref=' . urlencode((string)($member['donation_ref_code'] ?? '')),
            ],
        ], 'Member dashboard loaded.');
    }
}

$memberId = api_int('id');
$memberNo = api_trim('member_no');
$email = api_trim('email');

try {
    if ($memberId > 0) {
        $stmt = $pdo->prepare("
            SELECT m.*, d.title AS designation_title
            FROM members m
            LEFT JOIN member_designations d ON d.id = m.designation_id
            WHERE m.id = ?
            LIMIT 1
        ");
        $stmt->execute([$memberId]);
    } elseif ($memberNo !== '' && $email !== '') {
        $stmt = $pdo->prepare("
            SELECT m.*, d.title AS designation_title
            FROM members m
            LEFT JOIN member_designations d ON d.id = m.designation_id
            WHERE m.member_no = ? AND m.email = ?
            LIMIT 1
        ");
        $stmt->execute([$memberNo, $email]);
    } else {
        api_error('Provide id or member_no with email.', 400);
    }

    $member = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $member = false;
}

if (!$member) {
    api_error('Member not found.', 404);
}

$memberId = (int)$member['id'];

$referrals = 0;
$donationTotal = 0.0;
$messages = [];
$inquiries = [];
$documents = [];
$events = [];

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE referred_by_member_id = ?");
    $stmt->execute([$memberId]);
    $referrals = (int)$stmt->fetchColumn();
} catch (Throwable $e) {
}

try {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM donations WHERE referral_code = ? AND payment_status = 'Success'");
    $stmt->execute([(string)($member['donation_ref_code'] ?? '')]);
    $donationTotal = (float)$stmt->fetchColumn();
} catch (Throwable $e) {
}

try {
    $stmt = $pdo->prepare("
        SELECT md.id AS delivery_id, md.dashboard_status, md.created_at, mm.subject, mm.message_body, mm.message_type
        FROM member_message_deliveries md
        INNER JOIN member_messages mm ON mm.id = md.message_id
        WHERE md.member_id = ?
        ORDER BY md.created_at DESC
        LIMIT 100
    ");
    $stmt->execute([$memberId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
}

try {
    $stmt = $pdo->prepare("
        SELECT id, problem_description, status, created_at, admin_notes, category, urgency, attachment_path
        FROM inquiries
        WHERE member_id = ?
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$memberId]);
    $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, problem_description, status, created_at, admin_notes, category, urgency, attachment_path
            FROM inquiries
            WHERE submitter_email = ?
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $stmt->execute([(string)($member['email'] ?? '')]);
        $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e2) {
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT id, doc_type, doc_no, issued_at, issued_by, meta_json
        FROM member_documents
        WHERE member_id = ?
        ORDER BY id DESC
    ");
    $stmt->execute([$memberId]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
}

try {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT id, title, event_date, location, status
        FROM events
        WHERE event_date >= ? AND status IN ('Upcoming','Live')
        ORDER BY event_date ASC
        LIMIT 8
    ");
    $stmt->execute([$today]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
}

api_ok([
    'profile' => $member,
    'stats' => [
        'referrals' => $referrals,
        'donation_total' => $donationTotal,
    ],
    'messages' => $messages,
    'inquiries' => $inquiries,
    'documents' => $documents,
    'upcoming_events' => $events,
    'referral_links' => [
        'membership' => rtrim(appBaseUrl(), '/') . '/member-register.php?ref=' . urlencode((string)($member['referral_code'] ?? '')),
        'donation' => rtrim(appBaseUrl(), '/') . '/donate.php?mref=' . urlencode((string)($member['donation_ref_code'] ?? '')),
    ],
], 'Member dashboard loaded.');

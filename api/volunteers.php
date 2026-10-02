<?php
require_once __DIR__ . '/_bootstrap.php';

$token = api_bearer_token();
if ($token !== '') {
    $tokenRow = api_find_access_token($pdo, $token, 'volunteer');
    if ($tokenRow) {
        try {
            $stmt = $pdo->prepare("SELECT id, name, email, phone, blood_group, address, photo, status, id_card_no, valid_from, valid_until, created_at FROM volunteers WHERE id = ? LIMIT 1");
            $stmt->execute([(int)$tokenRow['user_id']]);
            $volunteer = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $volunteer = false;
        }

        if (!$volunteer) {
            api_error('Volunteer not found.', 404);
        }

        $activities = [];
        try {
            $stmt = $pdo->prepare("
                SELECT id, activity_type, description, event_id, hours_spent, created_at
                FROM volunteer_activities
                WHERE volunteer_id = ?
                ORDER BY created_at DESC
                LIMIT 100
            ");
            $stmt->execute([(int)$volunteer['id']]);
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
        }

        $volunteer['photo_url'] = api_public_url($volunteer['photo'] ?? '');

        api_ok([
            'profile' => $volunteer,
            'activities' => $activities,
        ], 'Volunteer dashboard loaded.');
    }
}

$id = api_int('id');
$email = api_trim('email');
$idCardNo = api_trim('id_card_no');

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT id, name, email, phone, blood_group, address, photo, status, id_card_no, valid_from, valid_until, created_at FROM volunteers WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
    } elseif ($idCardNo !== '') {
        $stmt = $pdo->prepare("SELECT id, name, email, phone, blood_group, address, photo, status, id_card_no, valid_from, valid_until, created_at FROM volunteers WHERE id_card_no = ? LIMIT 1");
        $stmt->execute([$idCardNo]);
    } elseif ($email !== '') {
        $stmt = $pdo->prepare("SELECT id, name, email, phone, blood_group, address, photo, status, id_card_no, valid_from, valid_until, created_at FROM volunteers WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
    } else {
        api_error('Provide id, email, or id_card_no.', 400);
    }

    $volunteer = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $volunteer = false;
}

if (!$volunteer) {
    api_error('Volunteer not found.', 404);
}

$activities = [];
try {
    $stmt = $pdo->prepare("
        SELECT id, activity_type, description, event_id, hours_spent, created_at
        FROM volunteer_activities
        WHERE volunteer_id = ?
        ORDER BY created_at DESC
        LIMIT 100
    ");
    $stmt->execute([(int)$volunteer['id']]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
}

$volunteer['photo_url'] = api_public_url($volunteer['photo'] ?? '');

api_ok([
    'profile' => $volunteer,
    'activities' => $activities,
], 'Volunteer dashboard loaded.');

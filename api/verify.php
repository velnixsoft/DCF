<?php
require_once __DIR__ . '/_bootstrap.php';

$memberNo = api_trim('member_no');
$memberEmail = api_trim('email');
$docNo = api_trim('doc_no');
$certificateNo = api_trim('certificate_no');
$volunteerIdCard = api_trim('id_card_no');

if ($memberNo !== '') {
    try {
        $stmt = $pdo->prepare("
            SELECT m.*, d.title AS designation_title
            FROM members m
            LEFT JOIN member_designations d ON d.id = m.designation_id
            WHERE m.member_no = ?
            LIMIT 1
        ");
        $stmt->execute([$memberNo]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $member = false;
    }

    if (!$member) {
        api_error('Member record not found.', 404);
    }

    api_ok([
        'type' => 'member',
        'valid' => (($member['status'] ?? '') === 'Active'),
        'record' => $member,
    ], 'Member verified.');
}

if ($docNo !== '') {
    try {
        $stmt = $pdo->prepare("
            SELECT md.*, m.full_name, m.member_no
            FROM member_documents md
            INNER JOIN members m ON m.id = md.member_id
            WHERE md.doc_no = ?
            ORDER BY md.id DESC
            LIMIT 1
        ");
        $stmt->execute([$docNo]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $document = false;
    }

    if (!$document) {
        api_error('Document record not found.', 404);
    }

    api_ok([
        'type' => 'document',
        'valid' => true,
        'record' => $document,
    ], 'Document verified.');
}

if ($certificateNo !== '') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM visitor_certificates WHERE certificate_no = ? LIMIT 1");
        $stmt->execute([$certificateNo]);
        $certificate = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $certificate = false;
    }

    if (!$certificate) {
        api_error('Certificate record not found.', 404);
    }

    api_ok([
        'type' => 'visitor_certificate',
        'valid' => true,
        'record' => $certificate,
    ], 'Visitor certificate verified.');
}

if ($volunteerIdCard !== '') {
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, phone, photo, blood_group, address, status, id_card_no, valid_from, valid_until, created_at FROM volunteers WHERE id_card_no = ? LIMIT 1");
        $stmt->execute([$volunteerIdCard]);
        $volunteer = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $volunteer = false;
    }

    if (!$volunteer) {
        api_error('Volunteer record not found.', 404);
    }

    $volunteer['photo_url'] = api_public_url($volunteer['photo'] ?? '');

    api_ok([
        'type' => 'volunteer',
        'valid' => (($volunteer['status'] ?? '') === 'Active'),
        'record' => $volunteer,
    ], 'Volunteer verified.');
}

if ($memberEmail !== '') {
    api_error('Provide member_no, doc_no, certificate_no, or id_card_no for verification.', 400);
}

api_error('No verification parameter supplied.', 400);

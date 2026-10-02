<?php

if (!function_exists('qa_client_ip')) {
    function qa_client_ip()
    {
        $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            $value = trim((string)($_SERVER[$key] ?? ''));
            if ($value === '') {
                continue;
            }
            if ($key === 'HTTP_X_FORWARDED_FOR') {
                $parts = array_map('trim', explode(',', $value));
                $value = (string)($parts[0] ?? '');
            }
            if ($value !== '') {
                return substr($value, 0, 64);
            }
        }
        return '';
    }
}

if (!function_exists('qa_device_info')) {
    function qa_device_info()
    {
        return substr(trim((string)($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 255);
    }
}

if (!function_exists('qa_status_is_active')) {
    function qa_status_is_active($status)
    {
        $value = trim((string)$status);
        if ($value === '') {
            return false;
        }

        if (is_numeric($status)) {
            return ((int)$status) === 1;
        }

        return in_array(strtolower($value), ['1', 'active', 'verified', 'success'], true);
    }
}

if (!function_exists('qa_verify_base_url')) {
    function qa_verify_base_url(array $settings)
    {
        $website = trim((string)($settings['ngo_website'] ?? ''));
        if ($website !== '') {
            return rtrim($website, '/');
        }
        return rtrim(appBaseUrl(), '/');
    }
}

if (!function_exists('qa_generate_token_value')) {
    function qa_generate_token_value()
    {
        return bin2hex(random_bytes(32));
    }
}

if (!function_exists('qa_extract_token')) {
   function qa_extract_token($rawValue)
{
    $rawValue = trim((string)$rawValue);

    if ($rawValue === '') {
        return '';
    }

    if (preg_match('/^[a-f0-9]{64}$/i', $rawValue)) {
        return strtolower($rawValue);
    }

    $query = parse_url($rawValue, PHP_URL_QUERY);
    if (is_string($query) && $query !== '') {
        parse_str($query, $params);
        $token = trim((string)($params['token'] ?? ''));
        if ($token !== '' && preg_match('/^[a-f0-9]{64}$/i', $token)) {
            return strtolower($token);
        }
    }

    if (preg_match('#(?:^|[?&])token=([a-f0-9]{64})(?:$|[&#])#i', $rawValue, $match)) {
        return strtolower($match[1]);
    }

    return '';
}
}

if (!function_exists('qa_extract_member_no')) {
    function qa_extract_member_no($rawValue)
    {
        $rawValue = trim((string)$rawValue);
        if ($rawValue === '') {
            return '';
        }

        $patterns = [
            '/(?:^|[?&])member=([A-Z0-9\-]+)/i',
            '/(?:^|[|;\s])MEMBER\s*(?:NO)?\s*:\s*([A-Z0-9\-]+)/i',
            '/\bMEM-[A-Z0-9\-]+\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $rawValue, $match)) {
                $value = $match[1] ?? $match[0] ?? '';
                return strtoupper(trim((string)$value));
            }
        }

        return '';
    }
}

if (!function_exists('qa_extract_doc_no')) {
    function qa_extract_doc_no($rawValue)
    {
        $rawValue = trim((string)$rawValue);
        if ($rawValue === '') {
            return '';
        }

        $patterns = [
            '/(?:^|[?&])doc=([A-Z0-9\-]+)/i',
            '/(?:^|[|;\s])DOC\s*:\s*([A-Z0-9\-]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $rawValue, $match)) {
                return strtoupper(trim((string)$match[1]));
            }
        }

        return '';
    }
}

if (!function_exists('qa_extract_volunteer_id_card_no')) {
    function qa_extract_volunteer_id_card_no($rawValue)
    {
        $rawValue = trim((string)$rawValue);
        if ($rawValue === '') {
            return '';
        }

        if (preg_match('/(?:^|[|;\s])ID\s*:\s*([A-Z0-9\-]+)/i', $rawValue, $match)) {
            return strtoupper(trim((string)$match[1]));
        }

        if (preg_match('/\bVOL-[A-Z0-9\-]+\b/i', $rawValue, $match)) {
            return strtoupper(trim((string)$match[0]));
        }

        return '';
    }
}

if (!function_exists('qa_rate_limit')) {
    function qa_rate_limit($bucket, $windowSeconds = 2, $maxHits = 8)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $bucket = preg_replace('/[^a-z0-9_\-]/i', '_', (string)$bucket);
        if (!isset($_SESSION['qa_rate_limit']) || !is_array($_SESSION['qa_rate_limit'])) {
            $_SESSION['qa_rate_limit'] = [];
        }

        $now = time();
        $hits = array_values(array_filter((array)($_SESSION['qa_rate_limit'][$bucket] ?? []), static function ($ts) use ($now, $windowSeconds) {
            return ((int)$ts) >= ($now - $windowSeconds);
        }));
        $hits[] = $now;
        $_SESSION['qa_rate_limit'][$bucket] = $hits;

        return count($hits) <= $maxHits;
    }
}

if (!function_exists('qa_token_document_type')) {
    function qa_token_document_type($documentType)
    {
        $documentType = strtolower(trim((string)$documentType));
        return preg_replace('/[^a-z0-9_\-]/', '_', $documentType !== '' ? $documentType : 'member_profile');
    }
}

if (!function_exists('qa_ensure_member_qr_token')) {
    function qa_ensure_member_qr_token(PDO $pdo, $memberId, $documentType = 'member_profile', $expiresAt = null)
    {
        $memberId = (int)$memberId;
        $documentType = qa_token_document_type($documentType);
        if ($memberId <= 0 || !dbTableExists($pdo, 'qr_tokens')) {
            return null;
        }

        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("
            SELECT *
            FROM qr_tokens
            WHERE member_id = ?
              AND document_type = ?
              AND status = 'active'
              AND (expires_at IS NULL OR expires_at >= ?)
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$memberId, $documentType, $now]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            return $existing;
        }

        $token = qa_generate_token_value();
        $insert = $pdo->prepare("
            INSERT INTO qr_tokens (member_id, token, document_type, expires_at, status, created_at)
            VALUES (?, ?, ?, ?, 'active', NOW())
        ");
        $insert->execute([$memberId, $token, $documentType, $expiresAt ?: null]);

        return [
            'id' => (int)$pdo->lastInsertId(),
            'member_id' => $memberId,
            'token' => $token,
            'document_type' => $documentType,
            'expires_at' => $expiresAt,
            'status' => 'active',
            'created_at' => $now,
        ];
    }
}

if (!function_exists('qa_member_verify_url')) {
    function qa_member_verify_url(PDO $pdo, array $settings, $memberId, $documentType = 'member_profile', $expiresAt = null)
    {
        $tokenRow = qa_ensure_member_qr_token($pdo, $memberId, $documentType, $expiresAt);
        if (!$tokenRow || empty($tokenRow['token'])) {
            return '';
        }

        return qa_verify_base_url($settings) . '/verify.php?token=' . urlencode((string)$tokenRow['token']);
    }
}

if (!function_exists('qa_fetch_token_record')) {
    function qa_fetch_token_record(PDO $pdo, $token)
    {
        if (!dbTableExists($pdo, 'qr_tokens')) {
            return null;
        }

        $token = qa_extract_token($token);
        if ($token === '') {
            return null;
        }

        $stmt = $pdo->prepare("
            SELECT qt.*, m.full_name, m.member_no, m.phone, m.email, m.address, m.photo, m.status AS member_status,
                   m.member_since, m.valid_until, d.title AS designation_title
            FROM qr_tokens qt
            INNER JOIN members m ON m.id = qt.member_id
            LEFT JOIN member_designations d ON d.id = m.designation_id
            WHERE qt.token = ?
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('qa_log_verification_scan')) {
    function qa_log_verification_scan(PDO $pdo, $tokenId, $memberId, $status)
    {
        if (!dbTableExists($pdo, 'qr_scan_logs')) {
            return date('Y-m-d H:i:s');
        }

        $scannedAt = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("
            INSERT INTO qr_scan_logs (qr_token_id, member_id, scanned_at, ip_address, device_info, result_status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $tokenId ?: null,
            $memberId ?: null,
            $scannedAt,
            qa_client_ip() ?: null,
            qa_device_info() ?: null,
            $status
        ]);

        return $scannedAt;
    }
}

if (!function_exists('qa_validate_member_token')) {
    function qa_validate_member_token(PDO $pdo, $token)
    {
        $record = qa_fetch_token_record($pdo, $token);
        if (!$record) {
            return [
                'status' => 'invalid',
                'record' => null,
                'scan_time' => qa_log_verification_scan($pdo, null, null, 'invalid'),
            ];
        }

        $now = date('Y-m-d H:i:s');
        if (($record['status'] ?? '') !== 'active') {
            return [
                'status' => 'invalid',
                'record' => $record,
                'scan_time' => qa_log_verification_scan($pdo, $record['id'] ?? null, $record['member_id'] ?? null, 'invalid'),
            ];
        }

        if (!empty($record['expires_at']) && strtotime((string)$record['expires_at']) < strtotime($now)) {
            try {
                $pdo->prepare("UPDATE qr_tokens SET status = 'expired' WHERE id = ?")->execute([(int)$record['id']]);
            } catch (Throwable $e) {
            }
            $record['status'] = 'expired';
            return [
                'status' => 'expired',
                'record' => $record,
                'scan_time' => qa_log_verification_scan($pdo, $record['id'] ?? null, $record['member_id'] ?? null, 'expired'),
            ];
        }

        $resultStatus = (strcasecmp((string)($record['member_status'] ?? ''), 'Active') === 0) ? 'verified' : 'inactive';
        return [
            'status' => $resultStatus,
            'record' => $record,
            'scan_time' => qa_log_verification_scan($pdo, $record['id'] ?? null, $record['member_id'] ?? null, $resultStatus),
        ];
    }
}

if (!function_exists('qa_fetch_volunteer_record_by_qr')) {
    function qa_fetch_volunteer_record_by_qr(PDO $pdo, $rawValue)
    {
        if (!dbTableExists($pdo, 'volunteers')) {
            return null;
        }

        $idCardNo = qa_extract_volunteer_id_card_no($rawValue);
        if ($idCardNo === '') {
            return null;
        }

        $stmt = $pdo->prepare("
            SELECT id AS volunteer_id, name, id_card_no, phone, email, photo, status AS volunteer_status,
                   valid_from, valid_until, blood_group
            FROM volunteers
            WHERE id_card_no = ?
            LIMIT 1
        ");
        $stmt->execute([$idCardNo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('qa_fetch_member_record_by_legacy_qr')) {
    function qa_fetch_member_record_by_legacy_qr(PDO $pdo, $rawValue)
    {
        if (!dbTableExists($pdo, 'members')) {
            return null;
        }

        $memberNo = qa_extract_member_no($rawValue);
        if ($memberNo !== '') {
            $stmt = $pdo->prepare("
                SELECT m.id AS member_id, m.full_name, m.member_no, m.phone, m.email, m.address, m.photo,
                       m.status AS member_status, m.member_since, m.valid_until, d.title AS designation_title
                FROM members m
                LEFT JOIN member_designations d ON d.id = m.designation_id
                WHERE UPPER(m.member_no) = ?
                LIMIT 1
            ");
            $stmt->execute([$memberNo]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        }

        $docNo = qa_extract_doc_no($rawValue);
        if ($docNo !== '' && dbTableExists($pdo, 'member_documents')) {
            $stmt = $pdo->prepare("
                SELECT m.id AS member_id, m.full_name, m.member_no, m.phone, m.email, m.address, m.photo,
                       m.status AS member_status, m.member_since, m.valid_until, d.title AS designation_title
                FROM member_documents md
                INNER JOIN members m ON m.id = md.member_id
                LEFT JOIN member_designations d ON d.id = m.designation_id
                WHERE UPPER(md.doc_no) = ?
                ORDER BY md.id DESC
                LIMIT 1
            ");
            $stmt->execute([$docNo]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        }

        return null;
    }
}

if (!function_exists('qa_attendance_url')) {
    function qa_attendance_url(array $settings, $attendanceEventId)
    {
        return qa_verify_base_url($settings) . '/admin/event-report.php?id=' . (int)$attendanceEventId;
    }
}

if (!function_exists('qa_attendance_event')) {
    function qa_attendance_event(PDO $pdo, $attendanceEventId)
    {
        if (!dbTableExists($pdo, 'attendance_events')) {
            return null;
        }

        $stmt = $pdo->prepare("
            SELECT ae.*, e.title AS source_event_title, e.status AS source_event_status
            FROM attendance_events ae
            LEFT JOIN events e ON e.id = ae.event_id
            WHERE ae.id = ?
            LIMIT 1
        ");
        $stmt->execute([(int)$attendanceEventId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('qa_attendance_report_rows')) {
    function qa_attendance_report_rows(PDO $pdo, $attendanceEventId, $markedOnly = true)
    {
        $attendanceEventId = (int)$attendanceEventId;
        $statusSql = $markedOnly ? " AND al.status = 'marked'" : '';

        if (!dbTableExists($pdo, 'attendance_logs')) {
            return [];
        }

        if (dbColumnExists($pdo, 'attendance_logs', 'volunteer_id')) {
            $stmt = $pdo->prepare("
                SELECT *
                FROM (
                    SELECT latest.id, latest.scan_time, latest.status, 'member' AS attendee_type,
                           m.full_name, m.member_no, m.gender, m.phone, d.title AS designation_title
                    FROM (
                        SELECT MAX(al.id) AS id
                        FROM attendance_logs al
                        WHERE al.event_id = ?{$statusSql}
                          AND al.member_id IS NOT NULL
                        GROUP BY al.member_id
                    ) member_latest
                    INNER JOIN attendance_logs latest ON latest.id = member_latest.id
                    INNER JOIN members m ON m.id = latest.member_id
                    LEFT JOIN member_designations d ON d.id = m.designation_id

                    UNION ALL

                    SELECT latest.id, latest.scan_time, latest.status, 'volunteer' AS attendee_type,
                           v.name AS full_name, v.id_card_no AS member_no, NULL AS gender, v.phone, 'Volunteer' AS designation_title
                    FROM (
                        SELECT MAX(al.id) AS id
                        FROM attendance_logs al
                        WHERE al.event_id = ?{$statusSql}
                          AND al.volunteer_id IS NOT NULL
                        GROUP BY al.volunteer_id
                    ) volunteer_latest
                    INNER JOIN attendance_logs latest ON latest.id = volunteer_latest.id
                    INNER JOIN volunteers v ON v.id = latest.volunteer_id
                ) attendance_rows
                ORDER BY scan_time DESC, id DESC
            ");
            $stmt->execute([$attendanceEventId, $attendanceEventId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = $pdo->prepare("
            SELECT latest.id, latest.scan_time, latest.status, 'member' AS attendee_type,
                   m.full_name, m.member_no, m.gender, m.phone, d.title AS designation_title
            FROM (
                SELECT MAX(al.id) AS id
                FROM attendance_logs al
                WHERE al.event_id = ?{$statusSql}
                  AND al.member_id IS NOT NULL
                GROUP BY al.member_id
            ) member_latest
            INNER JOIN attendance_logs latest ON latest.id = member_latest.id
            INNER JOIN members m ON m.id = latest.member_id
            LEFT JOIN member_designations d ON d.id = m.designation_id
            ORDER BY latest.scan_time DESC, latest.id DESC
        ");
        $stmt->execute([$attendanceEventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('qa_mark_attendance_by_token')) {
    function qa_mark_attendance_by_token(PDO $pdo, $attendanceEventId, $token)
    {
        if (!qa_rate_limit('attendance_scan_' . qa_client_ip(), 3, 10)) {
            return ['success' => false, 'status' => 'rate_limited', 'message' => 'Too many scan requests. Please slow down.'];
        }

        $attendanceEvent = qa_attendance_event($pdo, $attendanceEventId);
        if (!$attendanceEvent) {
            return ['success' => false, 'status' => 'invalid_event', 'message' => 'Attendance event not found.'];
        }
        if (!in_array((string)($attendanceEvent['status'] ?? ''), ['active'], true)) {
            return ['success' => false, 'status' => 'event_closed', 'message' => 'Attendance for this event is closed.'];
        }

        return qa_mark_member_attendance_by_token($pdo, $attendanceEvent, $token);
    }
}

if (!function_exists('qa_mark_member_attendance_by_token')) {
    function qa_mark_member_attendance_by_token(PDO $pdo, array $attendanceEvent, $token)
    {
        $validation = qa_validate_member_token($pdo, $token);
        $member = $validation['record'] ?? null;
        if (!$member || !in_array($validation['status'], ['verified', 'inactive'], true)) {
            return ['success' => false, 'status' => $validation['status'], 'message' => $validation['status'] === 'expired' ? 'QR token has expired.' : 'Invalid member QR code.'];
        }
        return qa_mark_member_attendance_record($pdo, $attendanceEvent, $member);
    }
}

if (!function_exists('qa_mark_member_attendance_record')) {
    function qa_mark_member_attendance_record(PDO $pdo, array $attendanceEvent, array $member)
    {
        if (!dbTableExists($pdo, 'attendance_logs')) {
            return ['success' => false, 'status' => 'schema_missing', 'message' => 'Attendance log table is missing. Run the QR attendance migration.'];
        }

        if (!qa_status_is_active($member['member_status'] ?? '')) {
            if (dbTableExists($pdo, 'attendance_logs')) {
                qa_insert_attendance_log($pdo, (int)$attendanceEvent['id'], 'member', (int)$member['member_id'], 'blocked');
            }
            return ['success' => false, 'status' => 'inactive', 'message' => 'Member is not active. Attendance not marked.', 'member' => $member];
        }

        $existingMarked = false;
        if (dbTableExists($pdo, 'attendance_logs')) {
            $check = $pdo->prepare("SELECT id FROM attendance_logs WHERE event_id = ? AND member_id = ? AND status = 'marked' ORDER BY id DESC LIMIT 1");
            $check->execute([(int)$attendanceEvent['id'], (int)$member['member_id']]);
            $existingMarked = (bool)$check->fetchColumn();
        }

        if ($existingMarked && ($attendanceEvent['qr_mode'] ?? 'single') === 'single') {
            qa_insert_attendance_log($pdo, (int)$attendanceEvent['id'], 'member', (int)$member['member_id'], 'duplicate');
            return ['success' => false, 'status' => 'duplicate', 'message' => 'Attendance already marked for this member.', 'member' => $member];
        }

        qa_insert_attendance_log($pdo, (int)$attendanceEvent['id'], 'member', (int)$member['member_id'], 'marked');

        if (!empty($attendanceEvent['event_id']) && dbTableExists($pdo, 'event_registrations')) {
            try {
                $update = $pdo->prepare("
                    UPDATE event_registrations
                    SET status = 'Attended'
                    WHERE event_id = ?
                      AND member_id = ?
                ");
                $update->execute([(int)$attendanceEvent['event_id'], (int)$member['member_id']]);
            } catch (Throwable $e) {
            }
        }

        return ['success' => true, 'status' => 'marked', 'message' => 'Attendance marked successfully.', 'member' => $member];
    }
}

if (!function_exists('qa_insert_attendance_log')) {
    function qa_insert_attendance_log(PDO $pdo, $attendanceEventId, $attendeeType, $attendeeId, $status)
    {
        $attendanceEventId = (int)$attendanceEventId;
        $attendeeId = (int)$attendeeId;
        $attendeeType = $attendeeType === 'volunteer' ? 'volunteer' : 'member';
        $status = in_array($status, ['marked', 'duplicate', 'blocked', 'invalid'], true) ? $status : 'invalid';

        if (!dbTableExists($pdo, 'attendance_logs')) {
            throw new RuntimeException('Attendance log table is missing.');
        }

        if ($attendeeType === 'volunteer' && dbColumnExists($pdo, 'attendance_logs', 'volunteer_id')) {
            $stmt = $pdo->prepare("
                INSERT INTO attendance_logs (event_id, member_id, volunteer_id, attendee_type, scan_time, ip_address, device_info, status, created_at)
                VALUES (?, NULL, ?, 'volunteer', NOW(), ?, ?, ?, NOW())
            ");
            $stmt->execute([$attendanceEventId, $attendeeId, qa_client_ip() ?: null, qa_device_info() ?: null, $status]);
            return;
        }

        if (dbColumnExists($pdo, 'attendance_logs', 'attendee_type')) {
            $stmt = $pdo->prepare("
                INSERT INTO attendance_logs (event_id, member_id, volunteer_id, attendee_type, scan_time, ip_address, device_info, status, created_at)
                VALUES (?, ?, NULL, 'member', NOW(), ?, ?, ?, NOW())
            ");
            $stmt->execute([$attendanceEventId, $attendeeId, qa_client_ip() ?: null, qa_device_info() ?: null, $status]);
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO attendance_logs (event_id, member_id, scan_time, ip_address, device_info, status, created_at)
            VALUES (?, ?, NOW(), ?, ?, ?, NOW())
        ");
        $stmt->execute([$attendanceEventId, $attendeeId, qa_client_ip() ?: null, qa_device_info() ?: null, $status]);
    }
}

if (!function_exists('qa_mark_attendance_by_scan')) {
    function qa_mark_attendance_by_scan(PDO $pdo, $attendanceEventId, $rawValue)
    {
        if (!qa_rate_limit('attendance_scan_' . qa_client_ip(), 3, 10)) {
            return ['success' => false, 'status' => 'rate_limited', 'message' => 'Too many scan requests. Please slow down.'];
        }

        $attendanceEvent = qa_attendance_event($pdo, $attendanceEventId);
        if (!$attendanceEvent) {
            return ['success' => false, 'status' => 'invalid_event', 'message' => 'Attendance event not found.'];
        }
        if (!in_array((string)($attendanceEvent['status'] ?? ''), ['active'], true)) {
            return ['success' => false, 'status' => 'event_closed', 'message' => 'Attendance for this event is closed.'];
        }

        $token = qa_extract_token($rawValue);
        if ($token !== '') {
            return qa_mark_member_attendance_by_token($pdo, $attendanceEvent, $token);
        }

        $member = qa_fetch_member_record_by_legacy_qr($pdo, $rawValue);
        if ($member) {
            return qa_mark_member_attendance_record($pdo, $attendanceEvent, $member);
        }

        $volunteer = qa_fetch_volunteer_record_by_qr($pdo, $rawValue);
        if (!$volunteer) {
            return ['success' => false, 'status' => 'invalid', 'message' => 'Invalid member or volunteer QR code.'];
        }
        if (!dbColumnExists($pdo, 'attendance_logs', 'volunteer_id')) {
            return ['success' => false, 'status' => 'schema_missing', 'message' => 'Volunteer attendance columns are missing. Run the QR attendance migration.'];
        }

        if (!qa_status_is_active($volunteer['volunteer_status'] ?? '')) {
            if (dbTableExists($pdo, 'attendance_logs')) {
                qa_insert_attendance_log($pdo, (int)$attendanceEvent['id'], 'volunteer', (int)$volunteer['volunteer_id'], 'blocked');
            }
            return ['success' => false, 'status' => 'inactive', 'message' => 'Volunteer is not active. Attendance not marked.', 'volunteer' => $volunteer];
        }

        if (!empty($volunteer['valid_until']) && strtotime((string)$volunteer['valid_until']) < strtotime(date('Y-m-d'))) {
            if (dbTableExists($pdo, 'attendance_logs')) {
                qa_insert_attendance_log($pdo, (int)$attendanceEvent['id'], 'volunteer', (int)$volunteer['volunteer_id'], 'blocked');
            }
            return ['success' => false, 'status' => 'expired', 'message' => 'Volunteer ID has expired. Attendance not marked.', 'volunteer' => $volunteer];
        }

        $existingMarked = false;
        if (dbTableExists($pdo, 'attendance_logs') && dbColumnExists($pdo, 'attendance_logs', 'volunteer_id')) {
            $check = $pdo->prepare("SELECT id FROM attendance_logs WHERE event_id = ? AND volunteer_id = ? AND status = 'marked' ORDER BY id DESC LIMIT 1");
            $check->execute([(int)$attendanceEvent['id'], (int)$volunteer['volunteer_id']]);
            $existingMarked = (bool)$check->fetchColumn();
        }

        if ($existingMarked && ($attendanceEvent['qr_mode'] ?? 'single') === 'single') {
            qa_insert_attendance_log($pdo, (int)$attendanceEvent['id'], 'volunteer', (int)$volunteer['volunteer_id'], 'duplicate');
            return ['success' => false, 'status' => 'duplicate', 'message' => 'Attendance already marked for this volunteer.', 'volunteer' => $volunteer];
        }

        qa_insert_attendance_log($pdo, (int)$attendanceEvent['id'], 'volunteer', (int)$volunteer['volunteer_id'], 'marked');
        return ['success' => true, 'status' => 'marked', 'message' => 'Attendance marked successfully.', 'volunteer' => $volunteer];
    }
}

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

function api_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

function api_ok(array $data = [], string $message = 'OK'): void
{
    api_response([
        'success' => true,
        'message' => $message,
        'data' => $data,
    ]);
}

function api_error(string $message, int $statusCode = 400, array $extra = []): void
{
    api_response(array_merge([
        'success' => false,
        'message' => $message,
    ], $extra), $statusCode);
}

function api_method(): string
{
    return strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
}

function api_require_method(array $allowedMethods): void
{
    $allowed = array_map('strtoupper', $allowedMethods);
    if (!in_array(api_method(), $allowed, true)) {
        api_error('Method not allowed.', 405);
    }
}

function api_param(string $key, $default = '')
{
    return $_REQUEST[$key] ?? $default;
}

function api_int(string $key, int $default = 0): int
{
    return (int)api_param($key, $default);
}

function api_float(string $key, float $default = 0.0): float
{
    return (float)api_param($key, $default);
}

function api_trim(string $key, string $default = ''): string
{
    return trim((string)api_param($key, $default));
}

function api_settings(PDO $pdo): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $cache = [];
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cache[(string)$row['setting_key']] = (string)$row['setting_value'];
        }
    } catch (Throwable $e) {
        $cache = [];
    }

    return $cache;
}

function api_setting(PDO $pdo, string $key, string $default = ''): string
{
    $settings = api_settings($pdo);
    return (string)($settings[$key] ?? $default);
}

function api_public_url(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $path) || strpos($path, '//') === 0) {
        return $path;
    }

    return rtrim(appBaseUrl(), '/') . '/' . ltrim($path, '/');
}

function api_excerpt(?string $text, int $limit = 160): string
{
    $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string)$text)));
    if ($plain === '') {
        return '';
    }

    if (function_exists('mb_strlen') && mb_strlen($plain) > $limit) {
        return mb_substr($plain, 0, $limit) . '...';
    }

    if (strlen($plain) > $limit) {
        return substr($plain, 0, $limit) . '...';
    }

    return $plain;
}

function api_percent(float $raised, float $goal): int
{
    if ($goal <= 0) {
        return 0;
    }

    return (int)round(min(100, max(0, ($raised / $goal) * 100)));
}

function api_group_by_department(array $rows): array
{
    $groups = [];
    foreach ($rows as $row) {
        $dept = trim((string)($row['department'] ?? '')) ?: 'General';
        if (!isset($groups[$dept])) {
            $groups[$dept] = [];
        }
        $groups[$dept][] = $row;
    }

    return $groups;
}

function api_request_data(): array
{
    static $data = null;
    if (is_array($data)) {
        return $data;
    }

    $data = $_POST;
    $raw = file_get_contents('php://input');
    if ($raw !== false && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $data = array_merge($data, $decoded);
        }
    }

    return $data;
}

function api_input(string $key, $default = null)
{
    $data = api_request_data();
    return $data[$key] ?? $default;
}

function api_header_value(string $name): string
{
    $target = strtolower($name);
    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $key => $value) {
            if (strtolower((string)$key) === $target) {
                return trim((string)$value);
            }
        }
    }

    $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    return trim((string)($_SERVER[$serverKey] ?? ''));
}

function api_bearer_token(): string
{
    $header = api_header_value('Authorization');
    if ($header !== '' && preg_match('/Bearer\s+(.+)/i', $header, $m)) {
        return trim((string)$m[1]);
    }

    return trim((string)api_input('access_token', ''));
}

function api_generate_token(int $bytes = 32): string
{
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}

function api_token_hash(string $token): string
{
    return hash('sha256', trim($token));
}

function api_ensure_app_tables(PDO $pdo): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS api_auth_tokens (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_type VARCHAR(20) NOT NULL,
                user_id INT NOT NULL,
                token_hash CHAR(64) NOT NULL,
                device_name VARCHAR(120) DEFAULT NULL,
                device_id VARCHAR(120) DEFAULT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_used_at DATETIME DEFAULT NULL,
                revoked_at DATETIME DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_api_auth_tokens_hash (token_hash),
                KEY idx_api_auth_tokens_user (user_type, user_id),
                KEY idx_api_auth_tokens_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (Throwable $e) {
    }

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS api_otp_challenges (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                purpose VARCHAR(50) NOT NULL,
                identifier VARCHAR(190) NOT NULL,
                otp_hash CHAR(64) NOT NULL,
                payload_json LONGTEXT NULL,
                attempts INT NOT NULL DEFAULT 0,
                max_attempts INT NOT NULL DEFAULT 5,
                expires_at DATETIME NOT NULL,
                verified_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_api_otp_lookup (purpose, identifier),
                KEY idx_api_otp_expires (expires_at),
                KEY idx_api_otp_verified (verified_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (Throwable $e) {
    }

    $initialized = true;
}

function api_issue_access_token(PDO $pdo, string $userType, int $userId, array $meta = [], int $ttlDays = 30): array
{
    api_ensure_app_tables($pdo);

    $userType = strtolower(trim($userType));
    $token = api_generate_token(32);
    $hash = api_token_hash($token);
    $expiresAt = date('Y-m-d H:i:s', time() + max(1, $ttlDays) * 86400);
    $deviceName = trim((string)($meta['device_name'] ?? ''));
    $deviceId = trim((string)($meta['device_id'] ?? ''));

    try {
        $cleanup = $pdo->prepare("UPDATE api_auth_tokens SET revoked_at = NOW() WHERE user_type = ? AND user_id = ? AND revoked_at IS NULL");
        $cleanup->execute([$userType, $userId]);
    } catch (Throwable $e) {
    }

    $stmt = $pdo->prepare("
        INSERT INTO api_auth_tokens (user_type, user_id, token_hash, device_name, device_id, expires_at, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$userType, $userId, $hash, $deviceName !== '' ? $deviceName : null, $deviceId !== '' ? $deviceId : null, $expiresAt]);

    return [
        'access_token' => $token,
        'token_type' => 'Bearer',
        'expires_at' => $expiresAt,
        'expires_in' => max(1, $ttlDays) * 86400,
    ];
}

function api_find_access_token(PDO $pdo, string $token, ?string $userType = null): ?array
{
    api_ensure_app_tables($pdo);

    $hash = api_token_hash($token);
    $sql = "SELECT * FROM api_auth_tokens WHERE token_hash = ? LIMIT 1";
    $params = [$hash];
    if ($userType !== null && $userType !== '') {
        $sql = "SELECT * FROM api_auth_tokens WHERE token_hash = ? AND user_type = ? LIMIT 1";
        $params[] = strtolower(trim($userType));
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tokenRow = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $tokenRow = false;
    }

    if (!$tokenRow) {
        return null;
    }

    if (!empty($tokenRow['revoked_at']) || strtotime((string)$tokenRow['expires_at']) < time()) {
        return null;
    }

    try {
        $pdo->prepare("UPDATE api_auth_tokens SET last_used_at = NOW() WHERE id = ?")->execute([(int)$tokenRow['id']]);
    } catch (Throwable $e) {
    }

    return $tokenRow;
}

function api_revoke_access_token(PDO $pdo, string $token): bool
{
    api_ensure_app_tables($pdo);
    $hash = api_token_hash($token);

    try {
        $stmt = $pdo->prepare("UPDATE api_auth_tokens SET revoked_at = NOW() WHERE token_hash = ? AND revoked_at IS NULL");
        $stmt->execute([$hash]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function api_create_otp_challenge(PDO $pdo, string $purpose, string $identifier, string $otp, array $payload = [], int $ttlSeconds = 300, int $maxAttempts = 5): int
{
    api_ensure_app_tables($pdo);

    $purpose = strtolower(trim($purpose));
    $identifier = strtolower(trim($identifier));
    $otpHash = api_token_hash((string)$otp);
    $payloadJson = !empty($payload) ? json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
    $expiresAt = date('Y-m-d H:i:s', time() + max(60, $ttlSeconds));

    try {
        $pdo->prepare("DELETE FROM api_otp_challenges WHERE purpose = ? AND identifier = ? AND verified_at IS NULL")
            ->execute([$purpose, $identifier]);
    } catch (Throwable $e) {
    }

    $stmt = $pdo->prepare("
        INSERT INTO api_otp_challenges (purpose, identifier, otp_hash, payload_json, attempts, max_attempts, expires_at, created_at)
        VALUES (?, ?, ?, ?, 0, ?, ?, NOW())
    ");
    $stmt->execute([$purpose, $identifier, $otpHash, $payloadJson, max(1, $maxAttempts), $expiresAt]);

    return (int)$pdo->lastInsertId();
}

function api_latest_otp_challenge(PDO $pdo, string $purpose, string $identifier): ?array
{
    api_ensure_app_tables($pdo);

    try {
        $stmt = $pdo->prepare("
            SELECT *
            FROM api_otp_challenges
            WHERE purpose = ? AND identifier = ? AND verified_at IS NULL
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([strtolower(trim($purpose)), strtolower(trim($identifier))]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $row = false;
    }

    return $row ?: null;
}

function api_verify_otp_challenge(PDO $pdo, string $purpose, string $identifier, string $otp): array
{
    $challenge = api_latest_otp_challenge($pdo, $purpose, $identifier);
    if (!$challenge) {
        return ['success' => false, 'message' => 'Invalid or expired OTP.'];
    }

    if (!empty($challenge['expires_at']) && strtotime((string)$challenge['expires_at']) < time()) {
        return ['success' => false, 'message' => 'Invalid or expired OTP.'];
    }

    $attempts = (int)($challenge['attempts'] ?? 0);
    $maxAttempts = max(1, (int)($challenge['max_attempts'] ?? 5));
    if ($attempts >= $maxAttempts) {
        return ['success' => false, 'message' => 'Too many invalid attempts. Please request a new OTP.'];
    }

    if (!hash_equals((string)$challenge['otp_hash'], api_token_hash($otp))) {
        try {
            $pdo->prepare("UPDATE api_otp_challenges SET attempts = attempts + 1 WHERE id = ?")->execute([(int)$challenge['id']]);
        } catch (Throwable $e) {
        }
        return ['success' => false, 'message' => 'Invalid or expired OTP.'];
    }

    try {
        $pdo->prepare("UPDATE api_otp_challenges SET verified_at = NOW() WHERE id = ?")->execute([(int)$challenge['id']]);
    } catch (Throwable $e) {
    }

    return [
        'success' => true,
        'challenge' => $challenge,
    ];
}

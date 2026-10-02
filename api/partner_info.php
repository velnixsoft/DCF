<?php
require_once __DIR__ . '/_bootstrap.php';

$settings = api_settings($pdo);
$configuredKey = trim((string)($settings['partner_api_key'] ?? 'suchi_partner_key'));

$incomingKey = api_trim(api_input('api_key', ''));
if ($incomingKey === '') {
    $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $incomingKey = trim($matches[1]);
    } elseif ($authHeader !== '') {
        $incomingKey = trim($authHeader);
    }
}

if ($incomingKey === '' || $incomingKey !== $configuredKey) {
    api_error('Unauthorized API key provided.', 401);
}

if (!dbTableExists($pdo, 'sa_partners')) {
    api_error('Partner module not installed.', 503);
}

$city = api_trim(api_input('city', ''));
$activityType = api_trim(api_input('activity_type', ''));
$status = api_trim(api_input('status', 'Active'));
$limit = max(1, min(500, api_int('limit', 100)));

$where = ['status = ?', 'api_enabled = 1'];
$params = [$status !== '' ? $status : 'Active'];

if ($city !== '') {
    $where[] = 'city_name = ?';
    $params[] = $city;
}
if (in_array($activityType, ['retail', 'physical_activity_center'], true)) {
    $where[] = 'activity_type = ?';
    $params[] = $activityType;
}

$sql = 'SELECT id, partner_code, owner_name, business_name, owner_mobile, business_mobile, owner_email,
               activity_type, latitude, longitude, business_address, city_name, state_name, status, approved_at
        FROM sa_partners WHERE ' . implode(' AND ', $where) . ' ORDER BY business_name ASC LIMIT ' . (int)$limit;

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = [];
    foreach ($rows as $row) {
        $formatted[] = [
            'id' => (int)$row['id'],
            'partner_code' => $row['partner_code'],
            'business_name' => $row['business_name'],
            'owner_name' => $row['owner_name'],
            'owner_email' => $row['owner_email'],
            'owner_mobile' => $row['owner_mobile'],
            'business_mobile' => $row['business_mobile'] ?? '',
            'activity_type' => $row['activity_type'],
            'activity_label' => $row['activity_type'] === 'physical_activity_center' ? 'Physical Activity Center' : 'Retail',
            'latitude' => $row['latitude'] !== null ? (float)$row['latitude'] : null,
            'longitude' => $row['longitude'] !== null ? (float)$row['longitude'] : null,
            'business_address' => $row['business_address'] ?? '',
            'city' => $row['city_name'] ?? '',
            'state' => $row['state_name'] ?? '',
            'map_url' => ($row['latitude'] && $row['longitude'])
                ? ('https://www.google.com/maps?q=' . urlencode($row['latitude'] . ',' . $row['longitude']))
                : '',
            'status' => $row['status'],
        ];
    }

    api_ok([
        'count' => count($formatted),
        'partners' => $formatted,
    ], 'Partner information retrieved for firm integration.');
} catch (Throwable $e) {
    api_error('Database error: ' . $e->getMessage(), 500);
}

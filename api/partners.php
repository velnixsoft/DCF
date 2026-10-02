<?php
require_once __DIR__ . '/_bootstrap.php';

// Fetch the configured API key from settings or use default
$settings = api_settings($pdo);
$configuredKey = trim((string)($settings['partner_api_key'] ?? 'suchi_partner_key'));

// Check incoming API key (via query string or header)
$incomingKey = api_trim(api_input('api_key', ''));
if ($incomingKey === '') {
    // Check Authorization header
    $headers = apache_request_headers();
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

try {
    $formattedPartners = [];

    $stmt = $pdo->query("SELECT id, name, logo_path, website_url, priority FROM sponsors ORDER BY priority DESC, name ASC");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $partner) {
        $formattedPartners[] = [
            'id' => (int)$partner['id'],
            'type' => 'sponsor',
            'name' => $partner['name'],
            'logo_url' => api_public_url($partner['logo_path'] ?? ''),
            'website_url' => $partner['website_url'] ?: '',
            'priority' => (int)$partner['priority'],
        ];
    }

    if (dbTableExists($pdo, 'sa_partners')) {
        $biz = $pdo->query("
            SELECT id, partner_code, business_name, owner_name, activity_type, latitude, longitude,
                   business_address, city_name, state_name, owner_email, owner_mobile
            FROM sa_partners
            WHERE status = 'Active' AND api_enabled = 1
            ORDER BY business_name ASC
            LIMIT 500
        ");
        foreach ($biz->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $formattedPartners[] = [
                'id' => (int)$row['id'],
                'type' => 'business_partner',
                'partner_code' => $row['partner_code'],
                'name' => $row['business_name'],
                'owner_name' => $row['owner_name'],
                'activity_type' => $row['activity_type'],
                'latitude' => $row['latitude'] !== null ? (float)$row['latitude'] : null,
                'longitude' => $row['longitude'] !== null ? (float)$row['longitude'] : null,
                'address' => $row['business_address'] ?? '',
                'city' => $row['city_name'] ?? '',
                'state' => $row['state_name'] ?? '',
                'email' => $row['owner_email'] ?? '',
                'phone' => $row['owner_mobile'] ?? '',
                'logo_url' => '',
                'website_url' => '',
                'priority' => 0,
            ];
        }
    }

    api_ok($formattedPartners, 'Sponsors and business partners list retrieved successfully.');
} catch (Throwable $e) {
    api_error('Database error occurred: ' . $e->getMessage(), 500);
}

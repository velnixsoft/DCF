<?php

require_once __DIR__ . '/../functions.php';

if (!function_exists('partner_portal_require_session')) {
    function partner_portal_require_session(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

if (!function_exists('partner_portal_require_partner')) {
    function partner_portal_require_partner(PDO $pdo): array
    {
        partner_portal_require_session();

        if (empty($_SESSION['partner_logged_in']) || empty($_SESSION['partner_id'])) {
            setFlash('error', 'Please login to access the partner portal.');
            header('Location: partner-login.php');
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM sa_partners WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$_SESSION['partner_id']]);
        $partner = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$partner) {
            setFlash('error', 'Partner account not found.');
            header('Location: process/partner_logout.php');
            exit;
        }

        if (($partner['status'] ?? '') !== 'Active') {
            setFlash('warning', 'Your partner account is not active yet. Please wait for admin approval.');
            header('Location: process/partner_logout.php');
            exit;
        }

        return $partner;
    }
}

if (!function_exists('partner_activity_label')) {
    function partner_activity_label(string $type): string
    {
        return match ($type) {
            'physical_activity_center' => 'Physical Activity Center',
            'retail' => 'Retail',
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }
}

if (!function_exists('partner_generate_code')) {
    function partner_generate_code(PDO $pdo): string
    {
        for ($i = 0; $i < 15; $i++) {
            $code = 'PTR-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM sa_partners WHERE partner_code = ?');
            $stmt->execute([$code]);
            if ((int)$stmt->fetchColumn() === 0) {
                return $code;
            }
        }
        return 'PTR-' . date('Y') . '-' . strtoupper(uniqid());
    }
}

if (!function_exists('partner_generate_api_key')) {
    function partner_generate_api_key(): string
    {
        return 'pk_' . bin2hex(random_bytes(24));
    }
}

<?php
// ============================================================
// admin/download_visitor_qr.php
// Direct Download / Preview of Website Visitor QR Code in PNG and PDF
// Author: VELNIX SOFT / Antigravity AI
// Date: 2026-09-12
// ============================================================

session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/website_visitor_qr_helper.php';

// Verify Admin / Coordinator / Manager Access
if (!checkRole($pdo, 'coordinator')) {
    http_response_code(403);
    echo "Unauthorized access. Please login to admin dashboard.";
    exit;
}

$format = cleanInput($_GET['format'] ?? 'png');
$customUrl = cleanInput($_GET['url'] ?? '');
$targetUrl = get_website_visitor_qr_url($customUrl ?: null);

// ── FORMAT 1: PDF POSTER / STANDEE ──────────────────────────
if ($format === 'pdf') {
    generate_website_visitor_qr_pdf($pdo, $targetUrl, 'D');
    exit;
}

// ── FORMAT 2: INLINE PDF PREVIEW ────────────────────────────
if ($format === 'preview') {
    generate_website_visitor_qr_pdf($pdo, $targetUrl, 'I');
    exit;
}

// ── FORMAT 3: HIGH-RES PNG DOWNLOAD ─────────────────────────
$localFile = get_website_visitor_qr_local_file($targetUrl, 1000);

if ($localFile && file_exists($localFile)) {
    $filename = 'Website_Visitor_QR_' . date('Ymd') . '.png';
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($localFile));
    header('Pragma: no-cache');
    header('Expires: 0');
    readfile($localFile);
    exit;
}

// Fallback: Redirect directly to QR API stream if local cache fails
$fallbackUrl = get_website_visitor_qr_image_url($targetUrl, 1000);
header('Location: ' . $fallbackUrl);
exit;

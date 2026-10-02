<?php
/**
 * ═══════════════════════════════════════════════════════════
 *  INDEX.PHP — THEME ROUTER  v4.0
 *  Paste this AFTER all your existing DB queries in index.php
 * ═══════════════════════════════════════════════════════════
 *
 * FILE PLACEMENT:
 *   themes/
 *     home_theme1.php   ← Warm Earth & Impact
 *     home_theme2.php   ← Bold Impact
 *     home_theme3.php   ← Festive Seva
 *
 * ADMIN PANEL — Settings Table:
 *   setting_key  = home_theme
 *   Values       = warmearth | boldimpact | festivaseva | classic
 *
 *   SQL to activate:
 *   UPDATE settings SET setting_value='warmearth'  WHERE setting_key='home_theme';
 *   UPDATE settings SET setting_value='boldimpact' WHERE setting_key='home_theme';
 *   UPDATE settings SET setting_value='festivaseva' WHERE setting_key='home_theme';
 *
 * ─────────────────────────────────────────────────────────── */

// ── ALREADY IN YOUR index.php ──
require 'includes/header.php';

$slides_raw = $pdo->query("SELECT * FROM sliders WHERE is_active = 1 ORDER BY priority ASC, id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$slides_json = htmlspecialchars(json_encode($slides_raw), ENT_QUOTES, 'UTF-8');
$about_desc  = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'about_desc'")->fetchColumn();
$about_image = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'about_image'")->fetchColumn();
$totalDonation    = (int)($pdo->query("SELECT SUM(amount) FROM donations WHERE payment_status = 'Success'")->fetchColumn() ?: 0);
$activeVolunteers = (int)($pdo->query("SELECT COUNT(*) FROM volunteers WHERE status = 'Active'")->fetchColumn() ?: 0);
$ongoingProjects  = (int)($pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'Active'")->fetchColumn() ?: 0);
$projects         = $pdo->query("SELECT * FROM projects WHERE status = 'Active' ORDER BY created_at DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
$galleryImages    = $pdo->query("SELECT * FROM gallery WHERE type='image' ORDER BY id DESC LIMIT 14")->fetchAll(PDO::FETCH_ASSOC);
$sponsors         = $pdo->query("SELECT * FROM sponsors ORDER BY priority ASC")->fetchAll(PDO::FETCH_ASSOC);
$birthdayMembers  = [];
try {
    $birthdayMembers = $pdo->query("
        SELECT full_name FROM members
        WHERE status='Active' AND dob IS NOT NULL
        AND DATE_FORMAT(dob,'%m-%d')=DATE_FORMAT(CURDATE(),'%m-%d')
        ORDER BY full_name ASC LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e){ $birthdayMembers = []; }

// ── FETCH EVENTS (for all new themes) ──
$events = [];
try {
    $events = $pdo->query("
        SELECT id, title, event_date, venue, category
        FROM events
        WHERE event_date >= CURDATE()
        ORDER BY event_date ASC
        LIMIT 4
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e){ $events = []; }

// ═══════════════ THEME ROUTER ═══════════════
$homeTheme = strtolower(trim((string)($settings['home_theme'] ?? 'classic')));

if ($homeTheme === 'warmearth') {
    require __DIR__ . '/themes/home_theme1.php';
    require 'includes/footer.php';
    exit;
}
if ($homeTheme === 'boldimpact') {
    require __DIR__ . '/themes/home_theme2.php';
    require 'includes/footer.php';
    exit;
}
if ($homeTheme === 'festivaseva') {
    require __DIR__ . '/themes/home_theme3.php';
    require 'includes/footer.php';
    exit;
}

// ── YOUR EXISTING CLASSIC/MODERN THEMES BELOW ──
// (original code continues here unchanged)
if ($homeTheme === 'modern') {
    require __DIR__ . '/themes/home_modern.php';
    require 'includes/footer.php';
    exit;
}
// ... rest of your original index.php

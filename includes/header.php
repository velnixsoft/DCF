<?php

if (!function_exists('cleanUrl')) {
    function cleanUrl($url) {
        $base = appUrlPath();
        if ($url === 'index.php') {
            return $base . '/';
        }
        if ($url === 'about-us.php') {
            return $base . '/about';
        }
        if (substr($url, -4) === '.php') {
            return $base . '/' . substr($url, 0, -4);
        }
        return $base . '/' . ltrim($url, '/');
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';
if (isProductionEnvironment()) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

if (!isset($pdo)) require_once 'config/db.php';
$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) $settings[$row['setting_key']] = $row['setting_value'];

$currentPage = basename($_SERVER['PHP_SELF']);
$seoMeta = seo_page_meta($currentPage, $settings);
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (int)($_SERVER['SERVER_PORT'] ?? 80) === 443;
$scheme = $https ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$currentUrl = $scheme . '://' . $host . ($_SERVER['REQUEST_URI'] ?? ('/' . $currentPage));
$canonicalUrl = strtok($currentUrl, '?') ?: $currentUrl;
$logoUrl = '';
if (!empty($seoMeta['image'])) {
    if (preg_match('#^https?://#i', (string)$seoMeta['image'])) {
        $logoUrl = (string)$seoMeta['image'];
    } else {
        $logoUrl = rtrim(appBaseUrl(), '/') . '/' . ltrim((string)$seoMeta['image'], '/');
    }
}

$menuItems = [
    'Home' => 'index.php',
    'About' => [
        'About Us' => 'about-us.php',
        'Management Body' =>'management.php',
        'Organization Structure' => 'organization-structure.php',
        'Objectives' => 'objectives.php',
        'Awards' => 'awards.php',
    ],
    'Certificates' => 'certificates.php',
    'Projects' => 'projects.php',
    'Healthcare' => 'healthcare-directory.php',
    'Careers' => 'careers.php',
    
    'Donation' => [
        'Donate Money' => 'donate.php',
        'Donate Items' => 'donate-items.php',
        'My Auto Pay & History' => 'donor-history.php',
    ],
    'Contact' => 'contact.php',
    'Events' => 'events.php',
    // 'Student Ambassadors' => [
    //     'Register' => 'student-register.php',
    //     'Login' => 'student-login.php',
    //     'Dashboard' => 'student-dashboard.php',
    //     'Verify Certificate' => 'student-certificate-verify.php',
    // ],
    'Join Us' => 'join-us.php',
    'Volunteer' => [
        'Join Us (Unified Portal)' => 'join-us.php',
        'Our Team' => 'team.php',
        'Register' => 'volunteer-register.php',
        'Volunteer Login' => 'volunteer-login.php',
        'Verify ID' => 'volunteer-verify.php',
    ],
    'Membership' => [
        'Register Member' => 'member-register.php',
        'Verify Member' => 'member-verify.php',
        'Member Login' => 'member-login.php',
        'Member Dashboard' => 'member-dashboard.php',
    ],
    'Gallery' => 'gallery.php',
    'Documents' => 'documents.php',
    'Inquiry' => 'inquiry.php',
    'Training'=>'training.php',
   
    
    'Crowdfunding' => 'crowdfunding.php',
    'News' => 'news.php'
];

$desktopPrimaryMenu = $menuItems;
$desktopMoreMenu = [];
$maxPrimaryItems = 7;
if (count($menuItems) > $maxPrimaryItems) {
    $keep = $maxPrimaryItems - 1;
    $desktopPrimaryMenu = array_slice($menuItems, 0, $keep, true);
    $desktopMoreMenu = array_slice($menuItems, $keep, null, true);
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars((string)($seoMeta['title'] ?? ($settings['site_name'] ?? 'NGO'))); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars((string)($seoMeta['description'] ?? '')); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars((string)($seoMeta['keywords'] ?? '')); ?>">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl); ?>">
    <link rel="sitemap" type="application/xml" title="Sitemap" href="<?php echo htmlspecialchars(rtrim(appBaseUrl(), '/') . '/sitemap.php'); ?>">
    <meta property="og:locale" content="en_IN">
    <meta property="og:type" content="<?php echo htmlspecialchars((string)($seoMeta['type'] ?? 'website')); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars((string)($seoMeta['title'] ?? '')); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars((string)($seoMeta['description'] ?? '')); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonicalUrl); ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars((string)($seoMeta['site_name'] ?? ($settings['site_name'] ?? 'NGO'))); ?>">
    <?php if ($logoUrl !== ''): ?>
        <meta property="og:image" content="<?php echo htmlspecialchars($logoUrl); ?>">
        <meta name="twitter:image" content="<?php echo htmlspecialchars($logoUrl); ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars((string)($seoMeta['title'] ?? '')); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars((string)($seoMeta['description'] ?? '')); ?>">
    <meta name="theme-color" content="#16a34a">
    <link rel="manifest" href="manifest.json">

    <?php if (!empty($settings['site_favicon'])): ?>
        <link rel="icon" href="<?php echo $settings['site_favicon']; ?>" type="image/x-icon">
    <?php endif; ?>

    <script type="application/ld+json">
        <?php
        $organizationSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'NGO',
            'name' => (string)($seoMeta['site_name'] ?? ($settings['site_name'] ?? 'NGO')),
            'url' => $canonicalUrl,
            'description' => (string)($seoMeta['description'] ?? ''),
            'keywords' => (string)($seoMeta['keywords'] ?? ''),
        ];
        if ($logoUrl !== '') {
            $organizationSchema['logo'] = $logoUrl;
            $organizationSchema['image'] = $logoUrl;
        }
        if (!empty($settings['ngo_phone'])) {
            $organizationSchema['telephone'] = (string)$settings['ngo_phone'];
        }
        if (!empty($settings['ngo_email'])) {
            $organizationSchema['email'] = (string)$settings['ngo_email'];
        }
        if (!empty($settings['ngo_address'])) {
            $organizationSchema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => (string)$settings['ngo_address'],
            ];
        }
        echo json_encode($organizationSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        ?>
    </script>

    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/theme-palette.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <!-- Google Translate API (hidden engine) -->
    <script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

    <style>
        body { font-family: 'Outfit', sans-serif; }
        [x-cloak] { display: none !important; }
        html { scroll-behavior: smooth; }

        .glass-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(229, 231, 235, 0.8);
        }
        .glass-header::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, rgba(15,139,141,0), rgba(244,166,64,0.9) 50%, rgba(15,139,141,0));
        }
        .nav-link { position: relative; }
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0; height: 2px;
            bottom: -4px; left: 50%;
            transform: translateX(-50%);
            background-color: #1070B0;
            transition: width 0.3s ease;
        }
        .nav-link:hover::after, .nav-link.active::after { width: 100%; }

        /* Page loader */
        .loader-container {
            display: flex; justify-content: center; align-items: center;
            height: 100vh; width: 100%;
            background-color: #ffffff;
            position: fixed; top: 0; left: 0; z-index: 9999;
            transition: opacity 0.5s ease-out, visibility 0.5s ease-out;
        }
        .loader-circle {
            width: 60px; height: 60px; border-radius: 50%;
            display: inline-block; position: relative;
            border: 3px solid;
            border-color: #1070B0 #1070B0 transparent transparent;
            animation: rotation 1s linear infinite;
        }
        .loader-circle::after, .loader-circle::before {
            content: ''; position: absolute;
            left: 0; right: 0; top: 0; bottom: 0; margin: auto;
            border: 3px solid; border-radius: 50%;
            width: 50px; height: 50px;
            border-color: transparent transparent #F0A010 #F0A010;
            animation: rotationBack 0.5s linear infinite;
        }
        .loader-circle::before {
            width: 40px; height: 40px;
            border-color: #1070B0 #1070B0 transparent transparent;
            animation: rotation 1.5s linear infinite;
        }
        @keyframes rotation { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }
        @keyframes rotationBack { 0%{transform:rotate(0deg)} 100%{transform:rotate(-360deg)} }
        @keyframes marquee { 0%{transform:translateX(0%)} 100%{transform:translateX(-50%)} }
        .animate-marquee { animation: marquee 30s linear infinite; }
        @keyframes scroll-left { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }
        .animate-scroll-left { animation: scroll-left 10s linear infinite; }
        .sponsor-container:hover .animate-scroll-left { animation-play-state: paused; }

        /* ── Google Translate: suppress all injected UI ─────────────────── */
        .goog-te-banner-frame,
        .skiptranslate > iframe    { display: none !important; }
        body                       { top: 0 !important; }
        .goog-logo-link,
        .goog-te-gadget > span,
        .goog-te-gadget img        { display: none !important; }
        .goog-te-gadget            { font-size: 0 !important; }
        #gt-hidden-engine          { display: none !important; }

        /* ── Custom language picker ────────────────────────────────────── */
        .lang-picker-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
            flex-shrink: 0;
        }

        /* Desktop button */
        .lang-picker-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: #fff;
            font-family: 'Outfit', sans-serif;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .lang-picker-btn:hover { background: rgba(255,255,255,0.28); }
        .lang-picker-btn .lang-flag { font-size: 1rem; }
        .lang-picker-btn .lang-caret {
            font-size: 0.55rem;
            opacity: 0.8;
            transition: transform 0.2s;
        }
        .lang-picker-wrapper.open .lang-caret { transform: rotate(180deg); }

        /* Dropdown panel */
        .lang-picker-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            overflow: hidden;
            z-index: 9999;
            min-width: 175px;
        }
        .lang-picker-wrapper.open .lang-picker-dropdown { display: block; }

        .lang-picker-dropdown .lang-header {
            padding: 8px 12px 6px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #9ca3af;
            border-bottom: 1px solid #f3f4f6;
        }
        .lang-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            font-size: 0.8rem;
            font-family: 'Outfit', sans-serif;
            color: #374151;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }
        .lang-option:hover  { background: #f0fdf4; color: #16a34a; }
        .lang-option.active { background: #dcfce7; color: #15803d; font-weight: 600; }
        .lang-option .lf    { font-size: 1.1rem; flex-shrink: 0; }
        .lang-option .ln    { flex: 1; }
        .lang-option .lsub  { font-size: 0.7rem; color: #9ca3af; }
        .lang-option.active .lsub { color: #4ade80; }

        /* Mobile variant — full-width select-style */
        .lang-picker-mobile {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .lang-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-family: 'Outfit', sans-serif;
            font-weight: 500;
            color: #374151;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.15s;
        }
        .lang-chip:hover  { background: #dcfce7; border-color: #86efac; color: #15803d; }
        .lang-chip.active { background: #16a34a; border-color: #16a34a; color: #fff; }
    </style>
        <script src="https://cdn.tailwindcss.com"></script>

<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        transparent: 'transparent',
        current: 'currentColor',
        white: '#ffffff',
        black: '#1F2937',
        brandOrange: {
          DEFAULT: '#F0A010',
          hover: '#d48b0a',
          dark: '#c07e0c',
          light: '#fffcf5'
        },
        brandTeal: {
          DEFAULT: '#1070B0',
          hover: '#20A0D0',
          dark: '#0d5b90',
          light: '#f0f7fc'
        },
        green: {
          50:  '#f2fcf7',
          100: '#e1f8ec',
          200: '#c3f2d9',
          500: '#00B060',
          600: '#00B060',
          700: '#009c54',
          800: '#008447',
          900: '#005c31'
        },
        amber: {
          50:  '#fffcf5',
          100: '#fef5e7',
          200: '#fde7c9',
          500: '#F0A010',
          600: '#d48b0a',
          700: '#c07e0c',
          800: '#a36704',
          900: '#805002'
        },
        blue: {
          50:  '#f0f7fc',
          100: '#d8ecf8',
          500: '#1070B0',
          600: '#1070B0',
          700: '#0d5b90',
          800: '#0a4a75',
          900: '#073554'
        }
      }
    }
  }
}
</script>

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-9F6K4WP8KS"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());

gtag('config', 'G-9F6K4WP8KS');
</script>
</head>

<body class="bg-[#FFF8F1]/40 text-[#1F2937]"
    x-data="{ mobileMenuOpen: false, pageLoading: true }"
    x-init="if (document.readyState === 'complete') { setTimeout(() => pageLoading = false, 300); } else { window.addEventListener('load', () => { setTimeout(() => pageLoading = false, 300); }); }">

    <?php if (isset($_SESSION['flash'])): ?>
        <div id="flashMessage" class="fixed top-24 left-1/2 -translate-x-1/2 z-[9999] max-w-xl w-[calc(100%-2rem)]">
            <div class="rounded-xl shadow-lg border px-4 py-3 text-sm font-medium <?php echo ($_SESSION['flash']['type'] ?? '') === 'success' ? 'bg-[#f0f7fc] border-[#d8ecf8] text-[#1070B0]' : 'bg-red-50 border-red-200 text-red-800'; ?>">
                <?php echo htmlspecialchars($_SESSION['flash']['message'] ?? ''); ?>
            </div>
        </div>
        <script>setTimeout(() => { const el = document.getElementById('flashMessage'); if (el) el.remove(); }, 4500);</script>
    <?php unset($_SESSION['flash']); endif; ?>

    <!-- Page loader -->
    <div class="loader-container" x-show="pageLoading" x-transition:leave="opacity-0">
        <div class="flex flex-col items-center gap-4">
            <span class="loader-circle"></span>
            <span class="text-[#1070B0] font-bold tracking-widest text-sm animate-pulse">LOADING...</span>
        </div>
    </div>

    <!-- ── Desktop top bar ── -->
        <div class="hidden lg:block bg-[#0F8B8D] text-white">
            <div class="container mx-auto !p-[10px] flex justify-between items-center text-xs">

                <!-- Phone + Email -->
                <div class="flex items-center gap-6">
                    <?php if (!empty($settings['ngo_phone'])): ?>
                        <a href="tel:<?php echo $settings['ngo_phone']; ?>"
                           class="flex items-center gap-2 hover:text-[#F4A640] transition">
                            <i class="fas fa-phone fa-fw"></i>
                            <span><?php echo $settings['ngo_phone']; ?></span>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['ngo_email'])): ?>
                        <a href="mailto:<?php echo $settings['ngo_email']; ?>"
                           class="flex items-center gap-2 hover:text-[#F4A640] transition">
                            <i class="fas fa-envelope fa-fw"></i>
                            <span><?php echo $settings['ngo_email']; ?></span>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Socials + Translate -->
                <div class="flex items-center gap-4">
                    <span>Follow us:</span>
                    <?php
                    $socials = ['facebook' => 'fa-facebook-f', 'instagram' => 'fa-instagram', 'youtube' => 'fa-youtube'];
                    foreach ($socials as $key => $icon): if (!empty($settings['social_' . $key])): ?>
                        <a href="<?php echo $settings['social_' . $key]; ?>" target="_blank"
                           class="text-white hover:text-[#F4A640] transition transform hover:scale-110">
                            <i class="fab <?php echo $icon; ?> fa-lg"></i>
                        </a>
                    <?php endif; endforeach; ?>

                    <!-- ✅ Custom Language Picker — desktop -->
                    <div class="flex items-center gap-2 border-l border-white/20 pl-4">
                        <div class="lang-picker-wrapper" id="langPickerDesktop">
                            <button class="lang-picker-btn" onclick="toggleLangPicker('langPickerDesktop')">
                                <span class="lang-flag" id="activeFlagDesktop">🇮🇳</span>
                                <span id="activeLangDesktop">English</span>
                                <span class="lang-caret">▼</span>
                            </button>
                            <div class="lang-picker-dropdown">
                                <div class="lang-header">Select Language</div>
                                <!-- populated by JS -->
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    <header class="sticky top-0 w-full z-50 glass-header">
        <!-- ── Main nav ── -->
        <nav class="py-3 bg-white">
          <div class="container mx-auto px-4 xl:px-6 flex justify-between items-center gap-4">

                <!-- Logo -->
                <a href="/" class="flex items-center gap-2 sm:gap-3 group min-w-0 max-w-[180px] xs:max-w-[240px] sm:max-w-[320px] xl:max-w-[420px]">

    <?php if (!empty($settings['ngo_logo'])): ?>
        <img 
            src="<?php echo $settings['ngo_logo']; ?>" 
            alt="Logo"
            class="h-14 xl:h-16 w-auto object-contain flex-shrink-0"
        >
    <?php endif; ?>

    <span 
        class="font-bold text-[#1F2937] group-hover:text-[#0F8B8D] transition tracking-tight leading-tight
               text-xs sm:text-base lg:text-lg xl:text-xl
               block break-words line-clamp-2"
        style="font-family:'Book Antiqua', serif;"
    >
        <?php echo mb_strtoupper($settings['site_name'] ?? 'HOPE NGO'); ?>
    </span>

</a>
                <!-- Desktop links -->
                <div class="hidden lg:flex items-center gap-4 xl:gap-6 flex-shrink min-w-0 ">
                    <?php foreach ($desktopPrimaryMenu as $name => $item): ?>
                        <?php if (is_array($item)): ?>
                            <div class="relative" x-data="{ open: false, timer: null }"
                                 @mouseenter="clearTimeout(timer); open = true"
                                 @mouseleave="timer = setTimeout(() => { open = false }, 200)">
                                <?php $isParentActive = in_array($currentPage, array_values($item)); ?>
                                <button class="nav-link text-sm transition flex items-center gap-1
                                    <?php echo $isParentActive ? 'active text-[#0F8B8D] font-bold' : 'text-[#1F2937] hover:text-[#0F8B8D] font-medium'; ?>">
                                    <?php echo $name; ?> <i class="fas fa-chevron-down fa-xs"></i>
                                </button>
                                <div x-show="open" x-transition
                                     class="absolute top-full mt-3 w-56 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-[70] max-h-[70vh] overflow-y-auto">
                                    <?php foreach ($item as $childName => $childUrl): ?>
                                        <a href="<?php echo cleanUrl($childUrl); ?>"
                                           class="block px-4 py-3 text-sm text-[#1F2937] hover:bg-[#FFF8F1] hover:text-[#F4A640] transition">
                                            <?php echo $childName; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <a href="<?php echo cleanUrl($item); ?>"
                               class="nav-link text-sm transition
                               <?php echo ($currentPage == $item) ? 'active text-[#0F8B8D] font-bold' : 'text-[#1F2937] hover:text-[#0F8B8D] font-medium'; ?>">
                                <?php echo $name; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if (!empty($desktopMoreMenu)): ?>
                        <div class="relative" x-data="{ open: false, timer: null }"
                             @mouseenter="clearTimeout(timer); open = true"
                             @mouseleave="timer = setTimeout(() => { open = false }, 200)">
                            <?php
                            $moreUrls = [];
                            foreach ($desktopMoreMenu as $mn => $mi) {
                                if (is_array($mi)) $moreUrls = array_merge($moreUrls, array_values($mi));
                                else $moreUrls[] = $mi;
                            }
                            $isMoreActive = in_array($currentPage, $moreUrls, true);
                            ?>
                            <button class="nav-link text-sm transition flex items-center gap-1
                                <?php echo $isMoreActive ? 'active text-[#0F8B8D] font-bold' : 'text-[#1F2937] hover:text-[#0F8B8D] font-medium'; ?>">
                                More <i class="fas fa-chevron-down fa-xs"></i>
                            </button>
                            <div x-show="open" x-transition
                                 class="absolute right-0 top-full mt-3 w-72 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-[70] max-h-[70vh] overflow-y-auto">
                                <?php foreach ($desktopMoreMenu as $moreName => $moreItem): ?>
                                    <?php if (is_array($moreItem)): ?>
                                        <div class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-[#4B5563] bg-[#FFF8F1] border-b border-gray-100">
                                            <?php echo htmlspecialchars($moreName); ?>
                                        </div>
                                        <?php foreach ($moreItem as $childName => $childUrl): ?>
                                            <a href="<?php echo cleanUrl($childUrl); ?>"
                                               class="block px-4 py-3 text-sm text-[#1F2937] hover:bg-[#FFF8F1] hover:text-[#F4A640] transition">
                                                <?php echo htmlspecialchars($childName); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <a href="<?php echo cleanUrl($moreItem); ?>"
                                           class="block px-4 py-3 text-sm text-[#1F2937] hover:bg-[#FFF8F1] hover:text-[#F4A640] transition">
                                            <?php echo htmlspecialchars($moreName); ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

                <!-- Donate button -->
                <div class="hidden lg:flex items-center gap-4">
                    <a href="/donate"
                       class="bg-[#F4A640] hover:bg-[#D98E2B] text-white px-6 py-2.5 rounded-full font-bold shadow-lg shadow-[#F4A640]/25 transition transform hover:-translate-y-0.5">
                        Donate Now
                    </a>
                </div>

                <!-- Mobile hamburger -->
                <button @click="mobileMenuOpen = true"
                        class="lg:hidden text-[#1F2937] hover:text-[#0F8B8D] focus:outline-none p-2 rounded-md">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

            </div>
        </nav>
    </header>

    <!-- ── Mobile slide-over ── -->
    <div class="fixed inset-0 z-[100] lg:hidden"
         x-show="mobileMenuOpen"
         style="display: none;"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="mobileMenuOpen = false"></div>

        <div class="fixed inset-y-0 right-0 z-[110] w-full max-w-xs bg-white shadow-2xl p-6 flex flex-col overflow-y-auto"
             x-show="mobileMenuOpen"
             x-transition:enter="transition ease-in-out duration-300 transform"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in-out duration-300 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full">

            <div class="flex justify-between items-center mb-6 border-b pb-4">
                <span class="text-xl font-bold text-blue-800">Menu</span>
                <button @click="mobileMenuOpen = false"
                        class="p-2 rounded-full bg-gray-100 hover:bg-red-50 text-gray-500 hover:text-red-500 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- ✅ Custom Language Picker — mobile -->
            <div class="mb-5 bg-blue-50 border border-blue-100 rounded-lg px-3 py-3">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-globe text-blue-600 text-sm"></i>
                    <span class="text-xs font-semibold text-blue-700">Select Language</span>
                </div>
                <div class="lang-picker-mobile" id="langChipsMobile">
                    <!-- populated by JS -->
                </div>
            </div>

            <!-- Nav links -->
            <div class="flex flex-col space-y-2">
                <?php foreach ($menuItems as $name => $item): ?>
                    <?php if (is_array($item)): $isParentActive = in_array($currentPage, array_values($item)); ?>
                        <div x-data="{ open: <?php echo $isParentActive ? 'true' : 'false'; ?> }">
                            <button @click="open = !open"
                                    class="w-full flex justify-between items-center px-4 py-3 rounded-lg text-lg transition
                                    <?php echo $isParentActive ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-600 hover:bg-gray-50'; ?>">
                                <span><?php echo $name; ?></span>
                                <svg class="w-5 h-5 transform transition-transform duration-200"
                                     :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" x-collapse class="pl-4 mt-1 space-y-1">
                                <?php foreach ($item as $childName => $childUrl): ?>
                                    <a href="<?php echo cleanUrl($childUrl); ?>"
                                       class="block py-2 px-4 rounded-md text-sm transition
                                       <?php echo ($currentPage == $childUrl) ? 'text-blue-600 font-bold bg-blue-50/50' : 'text-gray-500 hover:text-blue-600'; ?>">
                                        <?php echo $childName; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo cleanUrl($item); ?>"
                           class="block px-4 py-3 rounded-lg text-lg transition
                           <?php echo ($currentPage == $item) ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-600 hover:bg-gray-50'; ?>">
                            <?php echo $name; ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="mt-auto pt-6 border-t space-y-3">
                <a href="/donate"
                   class="block w-full bg-amber-500 hover:bg-amber-600 text-white text-center py-3 rounded-lg font-bold shadow-lg transition">
                    <i class="fas fa-heart mr-2"></i> Donate Now
                </a>
            </div>

        </div>
    </div>

   

    <!-- Hidden GT engine container -->
    <div id="gt-hidden-engine" aria-hidden="true"></div>

    <!-- ════════════════════════════════════════════════════
         Custom Indian Language Picker + Google Translate
         ════════════════════════════════════════════════════ -->
    <script>
        // ── 1. Language list (Indian-focused) ───────────────────────────
        const LANGUAGES = [
            { code: 'en',    flag: '🇮🇳', name: 'English',    native: 'English'    },
            { code: 'hi',    flag: '🇮🇳', name: 'Hindi',      native: 'हिन्दी'      },
            { code: 'bn',    flag: '🇮🇳', name: 'Bengali',    native: 'বাংলা'       },
            { code: 'te',    flag: '🇮🇳', name: 'Telugu',     native: 'తెలుగు'      },
            { code: 'mr',    flag: '🇮🇳', name: 'Marathi',    native: 'मराठी'       },
            { code: 'ta',    flag: '🇮🇳', name: 'Tamil',      native: 'தமிழ்'       },
            { code: 'gu',    flag: '🇮🇳', name: 'Gujarati',   native: 'ગુજરાતી'     },
            { code: 'kn',    flag: '🇮🇳', name: 'Kannada',    native: 'ಕನ್ನಡ'       },
            { code: 'ml',    flag: '🇮🇳', name: 'Malayalam',  native: 'മലയാളം'      },
            { code: 'pa',    flag: '🇮🇳', name: 'Punjabi',    native: 'ਪੰਜਾਬੀ'      },
            { code: 'or',    flag: '🇮🇳', name: 'Odia',       native: 'ଓଡ଼ିଆ'       },
            { code: 'ur',    flag: '🇮🇳', name: 'Urdu',       native: 'اردو'        },
        ];

        let activeLang = 'en';

        // ── 2. Google Translate init (hidden engine) ─────────────────────
        function googleTranslateElementInit() {
            new google.translate.TranslateElement(
                { pageLanguage: 'en', autoDisplay: false },
                'gt-hidden-engine'
            );
            buildPickers();
        }

        // ── 3. Trigger translation via the hidden GT select ──────────────
        function doTranslate(langCode) {
            activeLang = langCode;

            const select = document.querySelector('#gt-hidden-engine select');
            if (!select) {
                // GT not ready yet — retry after a moment
                setTimeout(() => doTranslate(langCode), 500);
                return;
            }

            if (langCode === 'en') {
                // Restore original language
                const restore = document.querySelector('.goog-te-menu-value') || select;
                select.value = '/en/en';
                select.dispatchEvent(new Event('change'));
                // Some GT versions need cookie cleared
                document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
                document.cookie = 'googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=' + location.hostname + ';';
                location.reload();
                return;
            }

            // Find the matching option (GT uses "/en/hi" format)
            let matched = false;
            for (let opt of select.options) {
                if (opt.value.endsWith('/' + langCode)) {
                    select.value = opt.value;
                    select.dispatchEvent(new Event('change'));
                    matched = true;
                    break;
                }
            }

            // Fallback: set cookie directly and reload
            if (!matched) {
                document.cookie = `googtrans=/en/${langCode}; path=/`;
                document.cookie = `googtrans=/en/${langCode}; path=/; domain=.${location.hostname}`;
                location.reload();
            }

            updatePickerUI();
        }

        // ── 4. Build desktop dropdown + mobile chips ─────────────────────
        function buildPickers() {
            // Detect current language from cookie
            const match = document.cookie.match(/googtrans=\/en\/([a-z]+)/);
            if (match) activeLang = match[1];

            buildDesktopDropdown();
            buildMobileChips();
            updatePickerUI();
        }

        function buildDesktopDropdown() {
            const dropdown = document.querySelector('#langPickerDesktop .lang-picker-dropdown');
            if (!dropdown) return;

            // Clear existing options (keep header)
            const header = dropdown.querySelector('.lang-header');
            dropdown.innerHTML = '';
            if (header) dropdown.appendChild(header);

            LANGUAGES.forEach(lang => {
                const btn = document.createElement('button');
                btn.className = 'lang-option' + (lang.code === activeLang ? ' active' : '');
                btn.dataset.code = lang.code;
                btn.innerHTML = `
                    <span class="lf">${lang.flag}</span>
                    <span class="ln">
                        ${lang.name}
                        <span class="lsub block">${lang.native}</span>
                    </span>
                    ${lang.code === activeLang ? '<i class="fas fa-check text-green-500 text-xs"></i>' : ''}
                `;
                btn.onclick = () => {
                    closeLangPicker('langPickerDesktop');
                    doTranslate(lang.code);
                };
                dropdown.appendChild(btn);
            });
        }

        function buildMobileChips() {
            const container = document.getElementById('langChipsMobile');
            if (!container) return;
            container.innerHTML = '';

            LANGUAGES.forEach(lang => {
                const chip = document.createElement('button');
                chip.className = 'lang-chip' + (lang.code === activeLang ? ' active' : '');
                chip.dataset.code = lang.code;
                chip.innerHTML = `<span>${lang.flag}</span><span>${lang.name}</span>`;
                chip.onclick = () => doTranslate(lang.code);
                container.appendChild(chip);
            });
        }

        function updatePickerUI() {
            const lang = LANGUAGES.find(l => l.code === activeLang) || LANGUAGES[0];

            // Desktop button label
            const flagEl = document.getElementById('activeFlagDesktop');
            const nameEl = document.getElementById('activeLangDesktop');
            if (flagEl) flagEl.textContent = lang.flag;
            if (nameEl) nameEl.textContent = lang.name;

            // Desktop options active state
            document.querySelectorAll('#langPickerDesktop .lang-option').forEach(opt => {
                const isActive = opt.dataset.code === activeLang;
                opt.classList.toggle('active', isActive);
                // Add / remove checkmark
                const existing = opt.querySelector('.fa-check');
                if (isActive && !existing) {
                    opt.insertAdjacentHTML('beforeend', '<i class="fas fa-check text-green-500 text-xs"></i>');
                } else if (!isActive && existing) {
                    existing.remove();
                }
            });

            // Mobile chips active state
            document.querySelectorAll('#langChipsMobile .lang-chip').forEach(chip => {
                chip.classList.toggle('active', chip.dataset.code === activeLang);
            });
        }

        // ── 5. Dropdown open/close helpers ───────────────────────────────
        function toggleLangPicker(id) {
            const wrapper = document.getElementById(id);
            if (!wrapper) return;
            const isOpen = wrapper.classList.contains('open');
            // Close all first
            document.querySelectorAll('.lang-picker-wrapper.open').forEach(w => w.classList.remove('open'));
            if (!isOpen) wrapper.classList.add('open');
        }

        function closeLangPicker(id) {
            const wrapper = document.getElementById(id);
            if (wrapper) wrapper.classList.remove('open');
        }

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.lang-picker-wrapper')) {
                document.querySelectorAll('.lang-picker-wrapper.open')
                        .forEach(w => w.classList.remove('open'));
            }
        });
    </script>


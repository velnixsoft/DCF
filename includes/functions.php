<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('setFlash')) {
    function setFlash($type, $message)
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('getFlash')) {
    function getFlash(?string $type = null)
    {
        if (!isset($_SESSION['flash'])) {
            return null;
        }

        $flash = $_SESSION['flash'];
        if ($type !== null) {
            if (isset($flash['type']) && $flash['type'] === $type) {
                unset($_SESSION['flash']);
                return $flash['message'] ?? '';
            }
            return null;
        }

        unset($_SESSION['flash']);
        return $flash;
    }
}

if (!function_exists('displayFlash')) {
    function displayFlash(): void
    {
        if (!isset($_SESSION['flash'])) {
            return;
        }

        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);

        $type = $flash['type'] ?? 'info';
        $message = $flash['message'] ?? '';
        if ($message === '') {
            return;
        }

        $bgColor = match ($type) {
            'success' => 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-900/30 dark:border-emerald-700 dark:text-emerald-300',
            'error', 'danger' => 'bg-rose-50 border-rose-200 text-rose-800 dark:bg-rose-900/30 dark:border-rose-700 dark:text-rose-300',
            'warning' => 'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-900/30 dark:border-amber-700 dark:text-amber-300',
            default => 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/30 dark:border-blue-700 dark:text-blue-300',
        };

        $icon = match ($type) {
            'success' => 'fa-circle-check text-emerald-500',
            'error', 'danger' => 'fa-circle-exclamation text-rose-500',
            'warning' => 'fa-triangle-exclamation text-amber-500',
            default => 'fa-circle-info text-blue-500',
        };

        echo '<div class="mb-6 p-4 rounded-xl border flex items-center justify-between gap-3 shadow-xs ' . $bgColor . '" role="alert">';
        echo '  <div class="flex items-center gap-3">';
        echo '    <i class="fa-solid ' . $icon . ' text-lg shrink-0"></i>';
        echo '    <span class="text-sm font-medium leading-relaxed">' . htmlspecialchars((string)$message) . '</span>';
        echo '  </div>';
        echo '  <button type="button" onclick="this.parentElement.remove()" class="opacity-60 hover:opacity-100 transition p-1 text-lg leading-none">&times;</button>';
        echo '</div>';
    }
}

if (!function_exists('render_admin_pagination')) {
    function render_admin_pagination(int $totalItems, int $currentPage = 1, int $perPage = 10, array $extraParams = []): string
    {
        if ($totalItems <= 0) {
            return '';
        }

        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $currentPage = max(1, min($currentPage, $totalPages));

        $startItem = ($currentPage - 1) * $perPage + 1;
        $endItem = min($currentPage * $perPage, $totalItems);

        $buildUrl = function(int $p) use ($extraParams, $perPage) {
            $params = array_merge($_GET, $extraParams, ['page' => $p, 'limit' => $perPage]);
            return '?' . http_build_query($params);
        };

        $html = '<div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-500 dark:text-gray-400">';
        
        // Left info: Showing X to Y of Z
        $html .= '<div class="flex items-center gap-2">';
        $html .= '  <span>Showing</span>';
        $html .= '  <span class="font-bold text-gray-900 dark:text-white">' . $startItem . '</span>';
        $html .= '  <span>to</span>';
        $html .= '  <span class="font-bold text-gray-900 dark:text-white">' . $endItem . '</span>';
        $html .= '  <span>of</span>';
        $html .= '  <span class="font-bold text-gray-900 dark:text-white">' . $totalItems . '</span>';
        $html .= '  <span>entries</span>';
        $html .= '</div>';

        // Right pagination buttons
        if ($totalPages > 1) {
            $html .= '<div class="flex items-center gap-1.5">';
            
            // First page button
            $firstDisabled = ($currentPage <= 1);
            $html .= '<a href="' . ($firstDisabled ? 'javascript:void(0)' : htmlspecialchars($buildUrl(1))) . '" class="p-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 ' . ($firstDisabled ? 'opacity-40 pointer-events-none' : '') . '" title="First Page"><i class="fa-solid fa-angles-left text-[10px]"></i></a>';
            
            // Previous button
            $html .= '<a href="' . ($firstDisabled ? 'javascript:void(0)' : htmlspecialchars($buildUrl($currentPage - 1))) . '" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold ' . ($firstDisabled ? 'opacity-40 pointer-events-none' : '') . '">Previous</a>';

            // Page numbers
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);

            if ($startPage > 1) {
                $html .= '<a href="' . htmlspecialchars($buildUrl(1)) . '" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 font-semibold text-gray-700 dark:text-gray-200">1</a>';
                if ($startPage > 2) {
                    $html .= '<span class="px-1 text-gray-400">...</span>';
                }
            }

            for ($p = $startPage; $p <= $endPage; $p++) {
                $isActive = ($p === $currentPage);
                if ($isActive) {
                    $html .= '<span class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-600 text-white font-bold shadow-xs">' . $p . '</span>';
                } else {
                    $html .= '<a href="' . htmlspecialchars($buildUrl($p)) . '" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 font-semibold text-gray-700 dark:text-gray-200">' . $p . '</a>';
                }
            }

            if ($endPage < $totalPages) {
                if ($endPage < $totalPages - 1) {
                    $html .= '<span class="px-1 text-gray-400">...</span>';
                }
                $html .= '<a href="' . htmlspecialchars($buildUrl($totalPages)) . '" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 font-semibold text-gray-700 dark:text-gray-200">' . $totalPages . '</a>';
            }

            // Next button
            $lastDisabled = ($currentPage >= $totalPages);
            $html .= '<a href="' . ($lastDisabled ? 'javascript:void(0)' : htmlspecialchars($buildUrl($currentPage + 1))) . '" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 font-semibold ' . ($lastDisabled ? 'opacity-40 pointer-events-none' : '') . '">Next</a>';
            
            // Last page button
            $html .= '<a href="' . ($lastDisabled ? 'javascript:void(0)' : htmlspecialchars($buildUrl($totalPages))) . '" class="p-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 ' . ($lastDisabled ? 'opacity-40 pointer-events-none' : '') . '" title="Last Page"><i class="fa-solid fa-angles-right text-[10px]"></i></a>';

            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }
}

if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken(?string $token): bool
    {
        $token = trim((string)$token);
        if ($token === '' || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals((string)$_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('validateCsrfToken')) {
    function validateCsrfToken(?string $token): bool
    {
        return verifyCsrfToken($token);
    }
}

if (!function_exists('isProductionEnvironment')) {
    function isProductionEnvironment(): bool
    {
        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === '' || php_sapi_name() === 'cli') {
            return false;
        }
        return !in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            && strpos($host, 'localhost:') !== 0
            && strpos($host, '127.0.0.1:') !== 0;
    }
}

if (!function_exists('rate_limit_check')) {
    function rate_limit_check(string $bucket, int $maxHits = 10, int $windowSeconds = 900): bool
    {
        if (!isset($_SESSION['rate_limit']) || !is_array($_SESSION['rate_limit'])) {
            $_SESSION['rate_limit'] = [];
        }

        $now = time();
        $hits = array_values(array_filter(
            (array)($_SESSION['rate_limit'][$bucket] ?? []),
            static function ($ts) use ($now, $windowSeconds) {
                return ($now - (int)$ts) < $windowSeconds;
            }
        ));

        if (count($hits) >= $maxHits) {
            $_SESSION['rate_limit'][$bucket] = $hits;
            return false;
        }

        $hits[] = $now;
        $_SESSION['rate_limit'][$bucket] = $hits;

        return true;
    }
}

if (!function_exists('cleanInput')) {
    function cleanInput($data)
    {
        return htmlspecialchars(stripslashes(trim($data)));
    }
}

if (!function_exists('seo_site_keyword_terms')) {
    function seo_site_keyword_terms(): array
    {
        return [
            'Sustainable',
            'Economy',
            'Employment',
            'Development',
            'Education',
            'Skill',
            'Council',
            'Seed Council',
            'Sustainable Economy Employment Development Education Skill Council',
        ];
    }
}

if (!function_exists('seo_trim_text')) {
    function seo_trim_text($text, $limit = 160): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string)$text)));
        if ($text === '') {
            return '';
        }
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text) <= $limit) {
                return $text;
            }
            return rtrim(mb_substr($text, 0, max(1, $limit - 3))) . '...';
        }
        if (strlen($text) <= $limit) {
            return $text;
        }
        return rtrim(substr($text, 0, max(1, $limit - 3))) . '...';
    }
}

if (!function_exists('seo_page_meta')) {
    function seo_page_meta($currentPage, array $settings = []): array
    {
        $siteName = trim((string)($settings['site_name'] ?? 'Seed Council'));
        $siteName = $siteName !== '' ? $siteName : 'Seed Council';
        $siteDescription = trim((string)($settings['about_desc'] ?? ''));
        $siteDescription = $siteDescription !== ''
            ? seo_trim_text($siteDescription, 180)
            : 'Seed Council promotes sustainable economy, employment, development, education, and skill growth through community programs and public initiatives.';

        $pageMeta = [
            'index.php' => [
                'title' => $siteName . ' | Sustainable Economy, Employment, Development, Education & Skill Council',
                'description' => 'Explore ' . $siteName . ' initiatives in sustainable development, economy, employment, education, skill programs, volunteering, donations, and community impact.',
            ],
            'about-us.php' => [
                'title' => 'About ' . $siteName . ' | Sustainable Development, Education & Skill Mission',
                'description' => 'Learn about ' . $siteName . ', our sustainable development mission, education programs, employment support, skill initiatives, and community-focused leadership.',
            ],
            'management.php' => [
                'title' => $siteName . ' Management Body | Council Leadership & Development Team',
                'description' => 'Meet the leadership and management body of ' . $siteName . ', guiding sustainable economy, education, skill development, employment, and social impact programs.',
            ],
            'objectives.php' => [
                'title' => $siteName . ' Objectives | Sustainable Economy, Education & Skill Goals',
                'description' => 'Read the objectives of ' . $siteName . ' for sustainable development, employment generation, education access, skill building, and community empowerment.',
            ],
            'projects.php' => [
                'title' => $siteName . ' Projects | Development, Employment & Education Initiatives',
                'description' => 'Discover active projects by ' . $siteName . ' focused on sustainable development, employment, education, skill training, and local economic growth.',
            ],
            'project-details.php' => [
                'title' => 'Project Details | ' . $siteName . ' Development Programs',
                'description' => 'View project details, goals, funding needs, and impact updates from ' . $siteName . ' development, education, employment, and skill initiatives.',
            ],
            'events.php' => [
                'title' => $siteName . ' Events | Education, Skill & Development Programs',
                'description' => 'Join events, workshops, campaigns, and community programs from ' . $siteName . ' supporting education, skill development, sustainability, and employment.',
            ],
            'event-details.php' => [
                'title' => 'Event Details | ' . $siteName,
                'description' => 'See full event details, schedules, and participation information for ' . $siteName . ' education, development, and community activities.',
            ],
            'training.php' => [
                'title' => $siteName . ' Training | Skill Development, Education & Employment Learning',
                'description' => 'Access ' . $siteName . ' training programs for skill development, education, employment readiness, and sustainable community growth.',
            ],
            'documents.php' => [
                'title' => $siteName . ' Documents | Council Reports, Certificates & Public Files',
                'description' => 'Browse official documents, reports, certificates, and public resources published by ' . $siteName . ' for transparency and community access.',
            ],
            'certificates.php' => [
                'title' => $siteName . ' Certificates | Council Recognition & Official Documents',
                'description' => 'View certificates and official recognitions from ' . $siteName . ' highlighting our development, education, and social impact work.',
            ],
            'team.php' => [
                'title' => $siteName . ' Team | Volunteers, Community Leaders & Skill Network',
                'description' => 'Meet the volunteer team and community members of ' . $siteName . ' driving sustainable development, education, employment, and skill outreach.',
            ],
            'volunteer-register.php' => [
                'title' => 'Volunteer with ' . $siteName . ' | Join Education, Skill & Development Programs',
                'description' => 'Register as a volunteer with ' . $siteName . ' and support sustainable development, education, employment, and skill-building programs.',
            ],
            'volunteer-login.php' => [
                'title' => 'Volunteer Login | ' . $siteName,
                'description' => 'Volunteer login portal for ' . $siteName . ' programs, ID access, updates, and participation in development, education, and skill initiatives.',
            ],
            'volunteer-dashboard.php' => [
                'title' => 'Volunteer Dashboard | ' . $siteName,
                'description' => 'Access the volunteer dashboard for ' . $siteName . ' to manage participation, documents, and engagement in development and skill programs.',
            ],
            'volunteer-verify.php' => [
                'title' => 'Verify Volunteer ID | ' . $siteName,
                'description' => 'Verify official volunteer identity records issued by ' . $siteName . ' for secure and trusted participation in council programs.',
            ],
            'member-register.php' => [
                'title' => 'Member Registration | ' . $siteName . ' Council Network',
                'description' => 'Register as a member of ' . $siteName . ' and join our network for sustainable economy, education, employment, and skill development.',
            ],
            'member-login.php' => [
                'title' => 'Member Login | ' . $siteName,
                'description' => 'Secure member login for ' . $siteName . ' programs, updates, ID access, receipts, and community development records.',
            ],
            'member-dashboard.php' => [
                'title' => 'Member Dashboard | ' . $siteName,
                'description' => 'Member dashboard for ' . $siteName . ' with access to documents, participation records, and development program updates.',
            ],
            'member-verify.php' => [
                'title' => 'Verify Member ID | ' . $siteName,
                'description' => 'Verify official member identity records issued by ' . $siteName . ' for secure participation and document validation.',
            ],
            'donate.php' => [
                'title' => 'Donate to ' . $siteName . ' | Support Sustainable Development & Education',
                'description' => 'Support ' . $siteName . ' through donations for sustainable development, employment, education, skill training, and community welfare initiatives.',
            ],
            'contact.php' => [
                'title' => 'Contact ' . $siteName . ' | Council Support, Programs & Inquiries',
                'description' => 'Contact ' . $siteName . ' for program details, development partnerships, education support, volunteering, documents, and community inquiries.',
            ],
            'news.php' => [
                'title' => $siteName . ' News | Development, Education & Council Updates',
                'description' => 'Read the latest news, announcements, and updates from ' . $siteName . ' on sustainable development, education, employment, and skills.',
            ],
            'news-details.php' => [
                'title' => 'News Details | ' . $siteName,
                'description' => 'Read detailed updates and announcements from ' . $siteName . ' covering council activities, education, development, and community impact.',
            ],
            'gallery.php' => [
                'title' => $siteName . ' Gallery | Council Activities, Events & Development Work',
                'description' => 'Browse photos from ' . $siteName . ' events, education drives, skill programs, volunteer work, and development initiatives.',
            ],
            'crowdfunding.php' => [
                'title' => $siteName . ' Crowdfunding | Support Community Development Projects',
                'description' => 'Contribute to crowdfunding campaigns by ' . $siteName . ' for sustainable development, education, employment, and public welfare projects.',
            ],
            'inquiry.php' => [
                'title' => $siteName . ' Inquiry | Ask About Development, Education & Skill Programs',
                'description' => 'Send an inquiry to ' . $siteName . ' about programs, documents, volunteering, community development, education, employment, and skills.',
            ],
            'awards.php' => [
                'title' => $siteName . ' Awards | Recognition for Development & Community Impact',
                'description' => 'See awards and recognitions received by ' . $siteName . ' for sustainable development, education, skill advancement, and social service.',
            ],
            'certificates.php' => [
                'title' => $siteName . ' Certificates | Official Council Certificates & Recognition',
                'description' => 'Review certificates, credentials, and official recognition related to ' . $siteName . ' and its public development work.',
            ],
            'healthcare-directory.php' => [
                'title' => 'Healthcare Directory | Empaneled Hospitals, Clinics & Doctors - ' . $siteName,
                'description' => 'Find trusted hospitals, eye & dental clinics, pathology labs, pharmacies, and partner doctors offering quality healthcare, camps, and NGO concessions.',
            ],
            'healthcare.php' => [
                'title' => 'Healthcare Directory | Empaneled Hospitals, Clinics & Doctors - ' . $siteName,
                'description' => 'Find trusted hospitals, eye & dental clinics, pathology labs, pharmacies, and partner doctors offering quality healthcare, camps, and NGO concessions.',
            ],
            'apply-health-card.php' => [
                'title' => 'Apply for Health Card | Swasthya Seva Card Registration - ' . $siteName,
                'description' => 'Register for NGO Health Card to receive subsidized medical treatments, diagnostic discounts, and free health checkups.',
            ],
            'health-card.php' => [
                'title' => 'Apply for Health Card | Swasthya Seva Card Registration - ' . $siteName,
                'description' => 'Register for NGO Health Card to receive subsidized medical treatments, diagnostic discounts, and free health checkups.',
            ],
        ];

        $meta = $pageMeta[$currentPage] ?? [
            'title' => $siteName . ' | Sustainable Development, Education & Skill Council',
            'description' => $siteDescription,
        ];

        $baseKeywords = array_merge(
            [$siteName, $siteName . ' website', $siteName . ' council'],
            seo_site_keyword_terms()
        );

        $pageSpecific = [
            'index.php' => ['NGO', 'community development', 'volunteer', 'donation'],
            'about-us.php' => ['about ' . $siteName, 'council mission', 'community vision'],
            'projects.php' => ['development projects', 'education projects', 'employment projects'],
            'training.php' => ['skill development training', 'employment skills', 'education training'],
            'documents.php' => ['official documents', 'public reports', 'council files'],
            'contact.php' => ['contact ' . $siteName, 'support', 'inquiry'],
            'donate.php' => ['donate', 'support education', 'support development'],
        ];

        $keywords = array_unique(array_merge($baseKeywords, $pageSpecific[$currentPage] ?? []));
        $meta['keywords'] = implode(', ', $keywords);
        $meta['site_name'] = $siteName;
        $meta['image'] = trim((string)($settings['ngo_logo'] ?? ''));
        $meta['type'] = $currentPage === 'index.php' ? 'website' : 'article';
        return $meta;
    }
}

if (!function_exists('appUrlPath')) {
    function appUrlPath(): string
    {
        $project_root = str_replace('\\', '/', dirname(__DIR__));
        $script_filename = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
        $script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

        if (strpos($script_filename, $project_root) === 0) {
            $relative_path = substr($script_filename, strlen($project_root));
            if ($relative_path !== '' && strcasecmp(substr($script_name, -strlen($relative_path)), $relative_path) === 0) {
                $base_path = substr($script_name, 0, strlen($script_name) - strlen($relative_path));
                return rtrim($base_path, '/');
            }
        }
        return '';
    }
}

if (!function_exists('appBaseUrl')) {
    function appBaseUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (int)($_SERVER['SERVER_PORT'] ?? 80) === 443;
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . appUrlPath();
    }
}

if (!function_exists('dbColumnExists')) {
    function dbColumnExists(PDO $pdo, $table, $column)
    {
        static $cache = [];
        $key = strtolower($table . '.' . $column);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        try {
            $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
            $stmt->execute([$table, $column]);
            $cache[$key] = ((int)$stmt->fetchColumn()) > 0;
            return $cache[$key];
        } catch (Throwable $e) {
            $cache[$key] = false;
            return false;
        }
    }
}

if (!function_exists('dbTableExists')) {
    function dbTableExists(PDO $pdo, $table)
    {
        static $cache = [];
        $key = 'table:' . strtolower($table);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
            ");
            $stmt->execute([$table]);
            $cache[$key] = ((int)$stmt->fetchColumn()) > 0;
            return $cache[$key];
        } catch (Throwable $e) {
            $cache[$key] = false;
            return false;
        }
    }
}

if (!function_exists('dbViewExists')) {
    function dbViewExists(PDO $pdo, $viewName)
    {
        static $cache = [];
        $key = 'view:' . strtolower($viewName);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        try {
            $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.VIEWS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
        ");
            $stmt->execute([$viewName]);
            $cache[$key] = ((int)$stmt->fetchColumn()) > 0;
            return $cache[$key];
        } catch (Throwable $e) {
            $cache[$key] = false;
            return false;
        }
    }
}

if (!function_exists('normalizeHierarchyRole')) {
    function normalizeHierarchyRole($value)
    {
        $value = strtolower(trim((string)$value));
        if ($value === 'super admin' || $value === 'admin') {
            return 'admin';
        }
        if (strpos($value, 'manager') !== false || $value === 'accountant') {
            return 'manager';
        }
        if ($value === 'coordinator') {
            return 'coordinator';
        }
        return $value !== '' ? 'coordinator' : 'coordinator';
    }
}

if (!function_exists('checkRole')) {
    function checkRole($pdo, $required_role)
    {
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            return false;
        }

        $user_id = (int)$_SESSION['user_id'];

        $hasHierarchyLevel = false;
        try {
            $hasHierarchyLevel = dbColumnExists($pdo, 'users', 'hierarchy_level');
        } catch (Throwable $e) {
            $hasHierarchyLevel = false;
        }

        try {
            if ($hasHierarchyLevel) {
                $stmt = $pdo->prepare("SELECT role, hierarchy_level FROM users WHERE id = ? AND status = 1");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ? AND status = 1");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (Throwable $e) {
            // If schema is behind (missing columns), fall back to a minimal query.
            try {
                $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Throwable $e2) {
                return false;
            }
        }

        if (!$user) return false;

        $hierarchy = ['admin' => 3, 'manager' => 2, 'coordinator' => 1];

        $userHierarchyLevel = null;
        if ($hasHierarchyLevel && !empty($user['hierarchy_level'])) {
            $userHierarchyLevel = strtolower((string)$user['hierarchy_level']);
            if ($userHierarchyLevel === 'super admin') {
                $userHierarchyLevel = 'admin';
            }
        } else {
            $role = strtolower((string)($user['role'] ?? ''));
            if ($role === 'super admin' || $role === 'admin') {
                $userHierarchyLevel = 'admin';
            } elseif (strpos($role, 'manager') !== false || $role === 'accountant') {
                $userHierarchyLevel = 'manager';
            } elseif ($role !== '') {
                $userHierarchyLevel = 'coordinator';
            }
        }

        $user_level = $hierarchy[$userHierarchyLevel] ?? 0;
        $req_level = $hierarchy[$required_role] ?? 0;

        return $user_level >= $req_level;
    }
}

if (!function_exists('checkPermission')) {
    function checkPermission(PDO $pdo, $permissionKey)
    {
        return getUserPermissionState($pdo, (int)($_SESSION['user_id'] ?? 0), $permissionKey) === true;
    }
}

if (!function_exists('getUserPermissionState')) {
    function getUserPermissionState(PDO $pdo, $userId, $permissionKey)
    {
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            return null;
        }

        $userId = (int)$userId;
        $permissionKey = trim((string)$permissionKey);
        if ($userId <= 0 || $permissionKey === '' || !dbTableExists($pdo, 'user_permissions')) {
            return null;
        }

        try {
            $statusStmt = $pdo->prepare("SELECT status FROM users WHERE id = ? LIMIT 1");
            $statusStmt->execute([$userId]);
            if ((int)($statusStmt->fetchColumn() ?: 0) !== 1) {
                return false;
            }

            $stmt = $pdo->prepare("SELECT is_allowed FROM user_permissions WHERE user_id = ? AND permission_key = ? LIMIT 1");
            $stmt->execute([$userId, $permissionKey]);
            $value = $stmt->fetchColumn();
            if ($value === false) {
                return null;
            }

            return (int)$value === 1;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('canAccessModule')) {
    function canAccessModule(PDO $pdo, $required_role, $permissionKey = null)
    {
        $permissionKey = trim((string)$permissionKey);
        $hasPermissionTable = dbTableExists($pdo, 'user_permissions');

        if ($hasPermissionTable && $permissionKey !== '') {
            $state = getUserPermissionState($pdo, (int)($_SESSION['user_id'] ?? 0), $permissionKey);
            if ($state !== null) {
                return $state;
            }

            return checkRole($pdo, $required_role);
        }

        if (checkRole($pdo, $required_role)) {
            return true;
        }

        if ($permissionKey !== '' && checkPermission($pdo, $permissionKey)) {
            return true;
        }

        return false;
    }
}

if (!function_exists('getAccessModuleCatalog')) {
    function getAccessModuleCatalog()
    {
        return [
            [
                'group' => 'Core',
                'key' => 'page.dashboard',
                'label' => 'Dashboard',
                'description' => 'Open the main overview dashboard.',
                'default_role' => 'coordinator',
            ],
            [
                'group' => 'Core',
                'key' => 'page.coordinator_reports',
                'label' => 'Coordinator Reports',
                'description' => 'View coordinator performance and verification reports.',
                'default_role' => 'coordinator',
            ],
            [
                'group' => 'Users',
                'key' => 'page.access_control',
                'label' => 'Access Control',
                'description' => 'Grant or revoke page and action access for staff accounts.',
                'default_role' => 'admin',
            ],
            [
                'group' => 'Users',
                'key' => 'page.user_security',
                'label' => 'User Security Actions',
                'description' => 'Block, delete, or reset passwords for staff accounts.',
                'default_role' => 'admin',
            ],
            [
                'group' => 'Memberships',
                'key' => 'page.memberships',
                'label' => 'Membership Management',
                'description' => 'Verify members, issue receipts, and manage member records.',
                'default_role' => 'coordinator',
            ],
            [
                'group' => 'Memberships',
                'key' => 'page.member_messages',
                'label' => 'Member Messages',
                'description' => 'Send notices, birthday wishes, and member communications.',
                'default_role' => 'coordinator',
            ],
            [
                'group' => 'Memberships',
                'key' => 'page.member_documents',
                'label' => 'Member Documents',
                'description' => 'Generate ID cards, certificates, appointment letters, and achievements.',
                'default_role' => 'coordinator',
            ],
            [
                'group' => 'Memberships',
                'key' => 'page.visitor_certificates',
                'label' => 'Visitor Certificates',
                'description' => 'Issue branded visitor certificates with QR verification.',
                'default_role' => 'coordinator',
            ],
            [
                'group' => 'Memberships',
                'key' => 'page.inquiries',
                'label' => 'Inquiries',
                'description' => 'View and manage member inquiries and support requests.',
                'default_role' => 'coordinator',
            ],
            [
                'group' => 'Memberships',
                'key' => 'page.volunteers',
                'label' => 'Volunteers',
                'description' => 'Approve and manage volunteer records.',
                'default_role' => 'coordinator',
            ],
            [
                'group' => 'Memberships',
                'key' => 'page.volunteer_activities',
                'label' => 'Volunteer Activities',
                'description' => 'Track volunteer activity logs and hours.',
                'default_role' => 'coordinator',
            ],
            [
                'group' => 'Operations',
                'key' => 'page.manager_panel',
                'label' => 'Manager Panel',
                'description' => 'High-level operational dashboard and KPI overview.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Operations',
                'key' => 'page.projects',
                'label' => 'Projects & Funds',
                'description' => 'Create, update, and track projects and funding flows.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Operations',
                'key' => 'page.events',
                'label' => 'Events',
                'description' => 'Manage events, galleries, and registrations.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Operations',
                'key' => 'page.donations',
                'label' => 'Donations',
                'description' => 'Handle donation records and payment tracking.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Operations',
                'key' => 'page.recurring_donations',
                'label' => 'Auto Pay / Recurring Donations',
                'description' => 'Manage recurring donation subscriptions, pause/resume mandates, and view cycles.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Operations',
                'key' => 'page.item_categories',
                'label' => 'Item Donation Categories',
                'description' => 'Manage item donation categories, units, and icons for in-kind giving.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Operations',
                'key' => 'page.documents',
                'label' => 'Documents Library',
                'description' => 'Upload and manage public NGO documents.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Operations',
                'key' => 'page.management_body',
                'label' => 'Management Body',
                'description' => 'Edit leadership and management body details.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Operations',
                'key' => 'page.donation_analytics',
                'label' => 'Donation Analytics',
                'description' => 'View donation trends and analytics.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Operations',
                'key' => 'page.crowdfunding',
                'label' => 'Crowdfunding',
                'description' => 'Manage fundraising campaigns and public donation drives.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Content',
                'key' => 'page.page_manager',
                'label' => 'Page Manager',
                'description' => 'Manage about, objectives, awards, gallery, and legal pages.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Content',
                'key' => 'page.slider_manager',
                'label' => 'Slider Manager',
                'description' => 'Manage homepage sliders and banners.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Content',
                'key' => 'page.sponsors',
                'label' => 'Sponsors',
                'description' => 'Manage sponsor / partner listings.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Content',
                'key' => 'page.about_manager',
                'label' => 'About Page',
                'description' => 'Edit About Us content and hero sections.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Content',
                'key' => 'page.objectives_manager',
                'label' => 'Objectives Page',
                'description' => 'Edit objectives and mission content.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Content',
                'key' => 'page.awards_manager',
                'label' => 'Awards Page',
                'description' => 'Manage award listings and highlight content.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Content',
                'key' => 'page.gallery_manager',
                'label' => 'Gallery Page',
                'description' => 'Manage public gallery content.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Content',
                'key' => 'page.contact_manager',
                'label' => 'Contact Page',
                'description' => 'Edit contact details and contact page content.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Content',
                'key' => 'page.certificate_manager',
                'label' => 'Certificate & Legal',
                'description' => 'Manage certificate and legal copy on the site.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Content',
                'key' => 'page.privacy_manager',
                'label' => 'Privacy Policy',
                'description' => 'Edit privacy policy content.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Content',
                'key' => 'page.terms_manager',
                'label' => 'Terms & Conditions',
                'description' => 'Edit terms and conditions content.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Content',
                'key' => 'page.refund_manager',
                'label' => 'Refund Policy',
                'description' => 'Edit refund policy content.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Content',
                'key' => 'page.training_videos',
                'label' => 'Training Videos',
                'description' => 'Manage training video resources and embeds.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Content',
                'key' => 'page.news',
                'label' => 'News / Updates',
                'description' => 'Publish news and organization updates.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Analytics',
                'key' => 'page.reports',
                'label' => 'Reports',
                'description' => 'Access reports and analytics pages.',
                'default_role' => 'manager',
            ],
            [
                'group' => 'Analytics',
                'key' => 'page.system_info',
                'label' => 'System Info',
                'description' => 'View system diagnostics and environment information.',
                'default_role' => 'admin',
            ],
            [
                'group' => 'System',
                'key' => 'page.settings',
                'label' => 'Settings',
                'description' => 'Update branding, email, payment, and security settings.',
                'default_role' => 'admin',
            ],
            [
                'group' => 'System',
                'key' => 'page.access_control',
                'label' => 'Access Control',
                'description' => 'Grant or revoke page and action access for staff accounts.',
                'default_role' => 'admin',
            ],
            [
                'group' => 'System',
                'key' => 'page.backup_db',
                'label' => 'Database Backup',
                'description' => 'Export a full SQL backup of the database.',
                'default_role' => 'admin',
            ],
        ];
    }
}

if (!function_exists('getDefaultAdminLandingPage')) {
    function getDefaultAdminLandingPage(PDO $pdo)
    {
        $candidates = [
            ['path' => '/admin/dashboard', 'role' => 'coordinator', 'permission' => 'page.dashboard'],
            ['path' => '/admin/manager-panel', 'role' => 'manager', 'permission' => 'page.manager_panel'],
            ['path' => '/admin/memberships', 'role' => 'coordinator', 'permission' => 'page.memberships'],
            ['path' => '/admin/document-studio', 'role' => 'coordinator', 'permission' => 'page.member_documents'],
            ['path' => '/admin/visitor-certificates', 'role' => 'coordinator', 'permission' => 'page.visitor_certificates'],
            ['path' => '/admin/inquiries', 'role' => 'coordinator', 'permission' => 'page.inquiries'],
            ['path' => '/admin/volunteers', 'role' => 'coordinator', 'permission' => 'page.volunteers'],
            ['path' => '/admin/volunteer-activities', 'role' => 'coordinator', 'permission' => 'page.volunteer_activities'],
            ['path' => '/admin/donations', 'role' => 'manager', 'permission' => 'page.donations'],
            ['path' => '/admin/projects', 'role' => 'manager', 'permission' => 'page.projects'],
            ['path' => '/admin/events', 'role' => 'manager', 'permission' => 'page.events'],
            ['path' => '/admin/reports', 'role' => 'manager', 'permission' => 'page.reports'],
            ['path' => '/admin/news', 'role' => 'manager', 'permission' => 'page.news'],
            ['path' => '/admin/crowdfunding', 'role' => 'manager', 'permission' => 'page.crowdfunding'],
            ['path' => '/admin/training-videos', 'role' => 'manager', 'permission' => 'page.training_videos'],
            ['path' => '/admin/settings', 'role' => 'admin', 'permission' => 'page.settings'],
            ['path' => '/admin/system-info', 'role' => 'admin', 'permission' => 'page.system_info'],
            ['path' => '/admin/access-control', 'role' => 'admin', 'permission' => 'page.access_control'],
        ];

        foreach ($candidates as $candidate) {
            if (canAccessModule($pdo, $candidate['role'], $candidate['permission'])) {
                return appUrlPath() . $candidate['path'];
            }
        }

        return appUrlPath() . '/admin/logout';
    }
}

if (!function_exists('generateNextDonationReceiptNumber')) {
    function generateNextDonationReceiptNumber(PDO $pdo): string
    {
        $prefix = 'RCP-';
        try {
            $prefixStmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'receipt_prefix'");
            $prefixVal = $prefixStmt ? $prefixStmt->fetchColumn() : null;
            if (!empty($prefixVal)) $prefix = (string)$prefixVal;
        } catch (Throwable $e) {
        }

        $currentYear = date('Y');
        $yearPrefix = $prefix . $currentYear . '-';

        $lastNum = 0;
        try {
            $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(receipt_no, '-', -1) AS UNSIGNED)) FROM donations WHERE receipt_no LIKE ?");
            $stmt->execute([$yearPrefix . '%']);
            $lastNum = (int)($stmt->fetchColumn() ?: 0);
        } catch (Throwable $e) {
            $lastNum = 0;
        }

        $nextNum = $lastNum + 1;
        return $yearPrefix . str_pad((string)$nextNum, 5, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('generateNextReceiptNumber')) {
    function generateNextReceiptNumber(PDO $pdo): string
    {
        return generateNextDonationReceiptNumber($pdo);
    }
}

if (!function_exists('getRazorpayCredentials')) {
    function getRazorpayCredentials(PDO $pdo, string $scope = 'donation'): array
    {
        $scope = preg_replace('/[^a-z0-9_]/i', '', strtolower($scope)) ?: 'donation';
        $settings = [];

        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'razorpay_%' OR setting_key = 'site_name'");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {
            $settings = [];
        }

        $keyId = trim((string)($settings['razorpay_' . $scope . '_key_id'] ?? ''));
        $keySecret = trim((string)($settings['razorpay_' . $scope . '_key_secret'] ?? ''));

        if ($keyId === '' || $keySecret === '') {
            $keyId = trim((string)($settings['razorpay_key_id'] ?? RAZORPAY_KEY_ID));
            $keySecret = trim((string)($settings['razorpay_key_secret'] ?? RAZORPAY_KEY_SECRET));
        }

        return [
            'key_id' => $keyId !== '' ? $keyId : RAZORPAY_KEY_ID,
            'key_secret' => $keySecret !== '' ? $keySecret : RAZORPAY_KEY_SECRET,
            'company_name' => trim((string)($settings['site_name'] ?? RAZORPAY_COMPANY_NAME)) ?: RAZORPAY_COMPANY_NAME,
        ];
    }
}

if (!function_exists('getReceiptDownloadSecret')) {
    function getReceiptDownloadSecret(): string
    {
        if (defined('RECEIPT_DOWNLOAD_SECRET') && trim((string)RECEIPT_DOWNLOAD_SECRET) !== '') {
            return (string) RECEIPT_DOWNLOAD_SECRET;
        }

        if (defined('RAZORPAY_WEBHOOK_SECRET') && trim((string)RAZORPAY_WEBHOOK_SECRET) !== '') {
            return (string) RAZORPAY_WEBHOOK_SECRET;
        }

        if (defined('RAZORPAY_KEY_SECRET') && trim((string)RAZORPAY_KEY_SECRET) !== '') {
            return (string) RAZORPAY_KEY_SECRET;
        }

        return 'receipt-download-secret';
    }
}

if (!function_exists('generateDonationReceiptToken')) {
    function generateDonationReceiptToken($donationId, $receiptNo, $donorEmail, $amount, $createdAt = ''): string
    {
        $payload = implode('|', [
            (string) ((int) $donationId),
            strtolower(trim((string) $donorEmail)),
            trim((string) $receiptNo),
            number_format((float) $amount, 2, '.', ''),
            trim((string) $createdAt),
        ]);

        return hash_hmac('sha256', $payload, getReceiptDownloadSecret());
    }
}

if (!function_exists('isValidDonationReceiptToken')) {
    function isValidDonationReceiptToken($donationId, $receiptNo, $donorEmail, $amount, $createdAt, $token): bool
    {
        $token = trim((string) $token);
        if ($token === '') {
            return false;
        }

        $expected = generateDonationReceiptToken($donationId, $receiptNo, $donorEmail, $amount, $createdAt);
        return hash_equals($expected, $token);
    }
}

if (!function_exists('generateItemDonationReceiptToken')) {
    function generateItemDonationReceiptToken($id, $donationCode, $receiptNo, $donorEmail, $createdAt = ''): string
    {
        $payload = implode('|', [
            'item_donation',
            (string) ((int) $id),
            strtolower(trim((string) $donorEmail)),
            trim((string) $donationCode),
            trim((string) $receiptNo),
            trim((string) $createdAt),
        ]);

        return hash_hmac('sha256', $payload, getReceiptDownloadSecret());
    }
}

if (!function_exists('isValidItemDonationReceiptToken')) {
    function isValidItemDonationReceiptToken($id, $donationCode, $receiptNo, $donorEmail, $createdAt, $token): bool
    {
        $token = trim((string) $token);
        if ($token === '') {
            return false;
        }

        $expected = generateItemDonationReceiptToken($id, $donationCode, $receiptNo, $donorEmail, $createdAt);
        return hash_equals($expected, $token);
    }
}

if (!function_exists('getPaymentGatewayManager')) {
    function getPaymentGatewayManager(?PDO $pdo = null): \App\Payments\PaymentGatewayManager
    {
        global $pdo;
        require_once __DIR__ . '/payments/PaymentGatewayManager.php';
        return new \App\Payments\PaymentGatewayManager($pdo);
    }
}



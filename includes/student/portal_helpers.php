<?php

require_once __DIR__ . '/../functions.php';

if (!function_exists('student_portal_require_session')) {
    function student_portal_require_session(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

if (!function_exists('student_portal_require_student')) {
    function student_portal_require_student(PDO $pdo, ?string $activePageKey = null): array
    {
        student_portal_require_session();

        if (empty($_SESSION['student_logged_in']) || empty($_SESSION['student_id'])) {
            setFlash('error', 'Please login to access the student portal.');
            header('Location: student-login.php');
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM sa_students WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$_SESSION['student_id']]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            setFlash('error', 'Student account not found.');
            header('Location: process/student_logout.php');
            exit;
        }

        if (($student['status'] ?? '') !== 'Active') {
            if (($student['status'] ?? '') === 'Suspended') {
                setFlash('error', 'Your account has been suspended.');
            } elseif (($student['status'] ?? '') === 'Rejected') {
                setFlash('error', 'Your application was rejected.');
            } elseif (($student['status'] ?? '') === 'Inactive') {
                setFlash('error', 'Your account is inactive.');
            } else {
                setFlash('error', 'Your account is pending verification.');
            }
            header('Location: process/student_logout.php');
            exit;
        }

        if ($activePageKey !== null && $activePageKey !== 'profile') {
            $progress = student_portal_profile_progress($student);
            if (!$progress['complete']) {
                setFlash('warning', 'Please complete your profile to continue using the portal.');
                header('Location: student-profile.php');
                exit;
            }
        }

        return $student;
    }
}

if (!function_exists('student_portal_profile_fields')) {
    function student_portal_profile_fields(): array
    {
        return [
            'full_name' => 'Full name',
            'email' => 'Email address',
            'mobile' => 'Mobile number',
            'gender' => 'Gender',
            'college_name' => 'College / institution',
            'department_name' => 'Department',
            'year_of_study' => 'Year of study',
            'state_name' => 'State',
            'city_name' => 'City / district',
            'address' => 'Address',
        ];
    }
}

if (!function_exists('student_portal_profile_progress')) {
    function student_portal_profile_progress(array $student): array
    {
        $requiredFields = student_portal_profile_fields();
        $filled = 0;
        $missing = [];

        foreach ($requiredFields as $field => $label) {
            $value = trim((string)($student[$field] ?? ''));
            if ($value !== '') {
                $filled++;
            } else {
                $missing[] = $label;
            }
        }

        $total = count($requiredFields);
        $percent = $total > 0 ? (int)round(($filled / $total) * 100) : 0;

        return [
            'percent' => $percent,
            'filled' => $filled,
            'total' => $total,
            'missing' => $missing,
            'complete' => $percent >= 100,
        ];
    }
}

if (!function_exists('student_portal_unread_count')) {
    function student_portal_unread_count(PDO $pdo, int $studentId): int
    {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sa_notifications WHERE student_id = ? AND is_read = 0");
            $stmt->execute([$studentId]);
            return (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists('student_portal_active_program')) {
    function student_portal_active_program(PDO $pdo, ?int $programId = null): ?array
    {
        try {
            if ($programId !== null) {
                $stmt = $pdo->prepare("SELECT * FROM sa_programs WHERE id = ? LIMIT 1");
                $stmt->execute([$programId]);
                $program = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($program) {
                    return $program;
                }
            }

            $stmt = $pdo->query("SELECT * FROM sa_programs WHERE status = 'active' ORDER BY start_date DESC, id DESC LIMIT 1");
            $program = $stmt->fetch(PDO::FETCH_ASSOC);
            return $program ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('student_portal_navigation_items')) {
    function student_portal_navigation_items(): array
    {
        return [
            ['label' => 'Dashboard', 'href' => 'student-dashboard.php', 'key' => 'dashboard', 'icon' => 'fa-gauge-high'],
            ['label' => 'Profile', 'href' => 'student-profile.php', 'key' => 'profile', 'icon' => 'fa-user-gear'],
            ['label' => 'Program', 'href' => 'student-program.php', 'key' => 'program', 'icon' => 'fa-briefcase'],
            ['label' => 'Referrals', 'href' => 'student-referrals.php', 'key' => 'referrals', 'icon' => 'fa-share-nodes'],
            ['label' => 'Events', 'href' => 'student-events.php', 'key' => 'events', 'icon' => 'fa-calendar-days'],
            ['label' => 'Tasks', 'href' => 'student-dashboard.php', 'key' => 'tasks', 'icon' => 'fa-list-check'],
            ['label' => 'Attendance', 'href' => 'student-attendance.php', 'key' => 'attendance', 'icon' => 'fa-calendar-check'],
            ['label' => 'Payment QR', 'href' => 'student-payment-qr.php', 'key' => 'payment', 'icon' => 'fa-qrcode'],
            ['label' => 'Leaderboard', 'href' => 'student-leaderboard.php', 'key' => 'leaderboard', 'icon' => 'fa-ranking-star'],
            ['label' => 'Points', 'href' => 'student-points.php', 'key' => 'points', 'icon' => 'fa-coins'],
            ['label' => 'Achievements', 'href' => 'student-achievements.php', 'key' => 'achievements', 'icon' => 'fa-medal'],
            ['label' => 'Promotion', 'href' => 'student-promotion.php', 'key' => 'promotion', 'icon' => 'fa-chart-line'],
            ['label' => 'Certificates', 'href' => 'student-certificate-details.php', 'key' => 'certificates', 'icon' => 'fa-award'],
            ['label' => 'Notifications', 'href' => 'student-notifications.php', 'key' => 'notifications', 'icon' => 'fa-bell'],
        ];
    }
}

if (!function_exists('student_portal_render_shell_start')) {
    function student_portal_render_shell_start(PDO $pdo, array $student, string $title, string $activePage, array $pageData = []): void
    {
        $profileProgress = $pageData['profile_progress'] ?? student_portal_profile_progress($student);
        $unreadCount = student_portal_unread_count($pdo, (int)$student['id']);
        
        $groups = [
            'Overview' => [
                ['label' => 'Dashboard', 'href' => 'student-dashboard.php', 'key' => 'dashboard', 'icon' => 'fa-gauge-high'],
                ['label' => 'Profile Settings', 'href' => 'student-profile.php', 'key' => 'profile', 'icon' => 'fa-user-gear'],
                ['label' => 'Notifications', 'href' => 'student-notifications.php', 'key' => 'notifications', 'icon' => 'fa-bell', 'badge' => $unreadCount],
            ],
            'Internship' => [
                ['label' => 'Active Program', 'href' => 'student-program.php', 'key' => 'program', 'icon' => 'fa-briefcase'],
                ['label' => 'Daily Attendance', 'href' => 'student-attendance.php', 'key' => 'attendance', 'icon' => 'fa-calendar-check'],
                ['label' => 'Events & Projects', 'href' => 'student-events.php', 'key' => 'events', 'icon' => 'fa-calendar-days'],
            ],
            'Fundraising' => [
                ['label' => 'Donation QR', 'href' => 'student-payment-qr.php', 'key' => 'payment', 'icon' => 'fa-qrcode'],
                ['label' => 'My Referrals', 'href' => 'student-referrals.php', 'key' => 'referrals', 'icon' => 'fa-share-nodes'],
                ['label' => 'Leaderboard', 'href' => 'student-leaderboard.php', 'key' => 'leaderboard', 'icon' => 'fa-ranking-star'],
            ],
            'Rewards' => [
                ['label' => 'Points Ledger', 'href' => 'student-points.php', 'key' => 'points', 'icon' => 'fa-coins'],
                ['label' => 'Achievements', 'href' => 'student-achievements.php', 'key' => 'achievements', 'icon' => 'fa-medal'],
                ['label' => 'Promotion Rules', 'href' => 'student-promotion.php', 'key' => 'promotion', 'icon' => 'fa-chart-line'],
                ['label' => 'Certificates', 'href' => 'student-certificate-details.php', 'key' => 'certificates', 'icon' => 'fa-award'],
            ],
            'NGO Portal' => [
                ['label' => 'Back to Website', 'href' => 'index.php', 'key' => 'back_to_website', 'icon' => 'fa-house'],
            ],
        ];
        ?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> | Student Ambassador Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <style>
        body { font-family: 'Outfit', sans-serif; }
        [x-cloak] { display: none !important; }
        .glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 9999px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.18);
        }
    </style>
</head>
<body class="h-full overflow-hidden text-slate-800" x-data="studentPortalShell()" @student-toast.window="showToast($event.detail)">
<?php if (isset($_SESSION['flash'])): ?>
    <div id="portal-flash" data-type="<?php echo htmlspecialchars((string)($_SESSION['flash']['type'] ?? 'info')); ?>" data-message="<?php echo htmlspecialchars((string)($_SESSION['flash']['message'] ?? '')); ?>" class="hidden"></div>
<?php unset($_SESSION['flash']); endif; ?>

<!-- Notification Toast -->
<div x-show="toast.visible" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2 md:translate-y-0 md:translate-x-4" x-transition:enter-end="opacity-100 translate-y-0 md:translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed top-5 right-5 z-[100] w-full max-w-md rounded-2xl border p-4 shadow-2xl flex items-start gap-3 glass"
    :class="toast.type === 'success' ? 'border-emerald-100 bg-emerald-50/90 text-emerald-800' : (toast.type === 'warning' ? 'border-amber-100 bg-amber-50/90 text-amber-800' : 'border-rose-100 bg-rose-50/90 text-rose-800')">
    <div class="mt-0.5">
        <i class="fa-solid text-lg" :class="toast.type === 'success' ? 'fa-circle-check text-emerald-600' : (toast.type === 'warning' ? 'fa-triangle-exclamation text-amber-600' : 'fa-circle-xmark text-rose-600')"></i>
    </div>
    <div class="flex-1">
        <p class="text-sm font-bold capitalize" x-text="toast.type"></p>
        <p class="text-xs font-medium text-slate-600 mt-0.5" x-text="toast.message"></p>
    </div>
    <button @click="toast.visible = false" class="text-slate-400 hover:text-slate-600 transition">
        <i class="fa-solid fa-xmark"></i>
    </button>
</div>

<!-- Left Sidebar (Desktop Only) -->
<aside class="hidden lg:flex flex-col w-64 fixed inset-y-0 left-0 bg-slate-950 border-r border-slate-900 text-white z-50">
    <div class="h-16 flex items-center px-6 border-b border-slate-900 bg-slate-950/80">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-2xl bg-gradient-to-tr from-emerald-500 to-green-600 flex items-center justify-center shadow-lg shadow-emerald-500/20 text-white font-black text-sm">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
            <div>
                <h2 class="font-extrabold text-sm leading-tight tracking-wider text-white uppercase">SEED COUNCIL</h2>
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest block -mt-0.5">Ambassador</span>
            </div>
        </div>
    </div>

    <!-- Navigation List (Grouped) -->
    <div id="student-sidebar-nav" class="flex-1 overflow-y-auto py-6 px-4 space-y-7 custom-scrollbar">
        <?php foreach ($groups as $groupName => $items): ?>
            <div class="space-y-2">
                <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3"><?php echo $groupName; ?></h3>
                <div class="space-y-1">
                    <?php foreach ($items as $item): ?>
                        <?php $isActive = $activePage === $item['key']; ?>
                        <a href="<?php echo htmlspecialchars($item['href']); ?>" 
                           class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition duration-200 group <?php echo $isActive ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-lg shadow-emerald-500/20' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900'; ?>">
                            <div class="flex items-center gap-3">
                                <i class="fa-solid <?php echo htmlspecialchars($item['icon']); ?> text-sm transition-transform duration-200 group-hover:scale-110 <?php echo $isActive ? 'text-white' : 'text-slate-500 group-hover:text-slate-300'; ?>"></i>
                                <span><?php echo htmlspecialchars($item['label']); ?></span>
                            </div>
                            <?php if (isset($item['badge']) && $item['badge'] > 0): ?>
                                <span class="bg-rose-500 text-white font-extrabold text-[10px] px-2 py-0.5 rounded-full ring-2 ring-slate-950"><?php echo $item['badge']; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Sidebar Footer -->
    <div class="p-4 border-t border-slate-900 bg-slate-950/40">
        <div class="flex items-center gap-3 rounded-2xl bg-slate-900/50 p-2 border border-slate-900">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-green-600 text-white flex items-center justify-center font-bold text-sm shadow">
                <?php echo strtoupper(substr((string)($student['full_name'] ?? 'S'), 0, 1)); ?>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold text-white truncate"><?php echo htmlspecialchars($student['full_name']); ?></p>
                <p class="text-[10px] text-slate-400 truncate">ID: <?php echo htmlspecialchars($student['student_no']); ?></p>
            </div>
            <a href="process/student_logout.php" class="text-slate-400 hover:text-rose-400 transition p-1.5 rounded-lg hover:bg-white/5" title="Logout">
                <i class="fa-solid fa-power-off text-sm"></i>
            </a>
        </div>
    </div>
</aside>

<!-- Mobile Slide-over Drawer -->
<div x-show="mobileMenuOpen" class="fixed inset-0 z-[60] flex lg:hidden" x-cloak>
    <!-- Backdrop overlay -->
    <div x-show="mobileMenuOpen" x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="mobileMenuOpen = false"></div>
    
    <!-- Drawer panel -->
    <div x-show="mobileMenuOpen" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative flex flex-col w-64 max-w-xs bg-slate-950 text-white z-50 shadow-2xl h-full border-r border-slate-900">
        <div class="h-16 flex items-center justify-between px-6 border-b border-slate-900 bg-slate-950">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-emerald-500 to-green-600 flex items-center justify-center text-white font-black text-xs">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <h2 class="font-extrabold text-sm tracking-wider">SEED COUNCIL</h2>
            </div>
            <button @click="mobileMenuOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-900">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div id="student-mobile-sidebar-nav" class="flex-1 overflow-y-auto py-6 px-4 space-y-7 custom-scrollbar">
            <?php foreach ($groups as $groupName => $items): ?>
                <div class="space-y-2">
                    <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3"><?php echo $groupName; ?></h3>
                    <div class="space-y-1">
                        <?php foreach ($items as $item): ?>
                            <?php $isActive = $activePage === $item['key']; ?>
                            <a href="<?php echo htmlspecialchars($item['href']); ?>" 
                               class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold transition duration-200 <?php echo $isActive ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900'; ?>">
                                <div class="flex items-center gap-3">
                                    <i class="fa-solid <?php echo htmlspecialchars($item['icon']); ?> text-sm"></i>
                                    <span><?php echo htmlspecialchars($item['label']); ?></span>
                                </div>
                                <?php if (isset($item['badge']) && $item['badge'] > 0): ?>
                                    <span class="bg-rose-500 text-white font-extrabold text-[10px] px-2 py-0.5 rounded-full"><?php echo $item['badge']; ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="p-4 border-t border-slate-900 bg-slate-950">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-slate-800 text-slate-300 flex items-center justify-center font-bold text-sm">
                    <?php echo strtoupper(substr((string)($student['full_name'] ?? 'S'), 0, 1)); ?>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-white truncate"><?php echo htmlspecialchars($student['full_name']); ?></p>
                </div>
                <a href="process/student_logout.php" class="text-slate-400 hover:text-rose-400 p-1.5 rounded-lg hover:bg-white/5">
                    <i class="fa-solid fa-power-off"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Main Section Wrapper -->
<div class="flex-1 flex flex-col lg:pl-64 h-full overflow-hidden">
    <!-- Top Navbar Header -->
    <header class="h-16 border-b border-slate-100 bg-white/95 sticky top-0 z-40 glass flex items-center justify-between px-4 md:px-8">
        <div class="flex items-center gap-3 min-w-0">
            <button type="button" class="lg:hidden text-slate-500 hover:text-slate-800 p-2 rounded-xl hover:bg-slate-50 transition" @click="mobileMenuOpen = true">
                <i class="fa-solid fa-bars-staggered text-lg"></i>
            </button>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 truncate">
                <span class="uppercase tracking-wider text-emerald-600 font-bold hidden sm:inline-block">Ambassador portal</span>
                <span class="text-slate-300 hidden sm:inline-block">/</span>
                <span class="text-slate-800 font-bold text-sm sm:text-xs truncate"><?php echo htmlspecialchars($title); ?></span>
            </div>
        </div>

        <!-- Header actions -->
        <div class="flex items-center gap-4">
            <a href="student-notifications.php" class="relative w-9 h-9 rounded-xl border border-slate-200 bg-white text-slate-600 flex items-center justify-center hover:bg-slate-50 transition shadow-sm" title="Notifications">
                <i class="fa-solid fa-bell text-sm"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="absolute -top-1 -right-1 flex h-4 w-4">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-4 w-4 bg-rose-500 text-white font-extrabold text-[9px] items-center justify-center"><?php echo $unreadCount; ?></span>
                    </span>
                <?php endif; ?>
            </a>
            
            <div class="h-8 w-[1px] bg-slate-200"></div>

            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold text-slate-900"><?php echo htmlspecialchars($student['full_name']); ?></p>
                    <p class="text-[10px] font-semibold text-slate-400"><?php echo htmlspecialchars($student['student_no']); ?></p>
                </div>
                <a href="student-profile.php" class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-50 to-green-100 border border-emerald-200 text-emerald-700 font-bold flex items-center justify-center shadow-sm hover:scale-105 transition" title="My Profile">
                    <?php echo strtoupper(substr((string)($student['full_name'] ?? 'S'), 0, 1)); ?>
                </a>
            </div>
        </div>
    </header>

    <!-- Scrollable content area -->
    <div class="flex-1 overflow-y-auto bg-slate-50/50 custom-scrollbar">
        <main class="mx-auto w-full max-w-7xl px-4 py-6 md:px-8">
            <!-- Global Info banner -->
            <div class="mb-8 rounded-3xl bg-gradient-to-br from-slate-900 via-emerald-950 to-slate-950 p-6 md:p-8 text-white shadow-xl relative overflow-hidden">
                <div class="absolute inset-0 bg-[linear-gradient(to_right,rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.02)_1px,transparent_1px)] bg-[size:16px_16px] pointer-events-none"></div>
                <div class="absolute top-0 right-0 w-80 h-80 bg-emerald-500/10 rounded-full blur-3xl -mr-20 -mt-20"></div>
                
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between relative z-10">
                    <div>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 text-[10px] font-bold uppercase tracking-widest">Student Ambassador Portal</span>
                        <h1 class="mt-2 text-2xl md:text-3xl font-extrabold tracking-tight text-white"><?php echo htmlspecialchars($title); ?></h1>
                        <p class="text-xs text-slate-400 mt-1">Manage tasks, track daily attendance, check referrals, and claim milestone achievements.</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:w-auto w-full">
                        <div class="bg-white/5 border border-white/10 rounded-2xl px-5 py-3 shadow-inner hover:bg-white/10 transition">
                            <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Total Points</p>
                            <p class="text-xl font-extrabold text-amber-400 mt-1"><?php echo (int)($student['total_points'] ?? 0); ?> pts</p>
                        </div>
                        <div class="bg-white/5 border border-white/10 rounded-2xl px-5 py-3 shadow-inner hover:bg-white/10 transition">
                            <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Current Level</p>
                            <p class="text-sm font-extrabold text-emerald-300 mt-1.5 truncate"><?php echo htmlspecialchars($student['level_name'] ?? 'Student Ambassador'); ?></p>
                        </div>
                        <div class="bg-white/5 border border-white/10 rounded-2xl px-5 py-3 shadow-inner hover:bg-white/10 transition">
                            <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Profile Complete</p>
                            <div class="flex items-center gap-2 mt-1.5">
                                <p class="text-sm font-extrabold text-slate-200"><?php echo (int)$profileProgress['percent']; ?>%</p>
                                <div class="w-10 bg-white/10 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-emerald-400 h-full rounded-full" style="width: <?php echo (int)$profileProgress['percent']; ?>%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white/5 border border-white/10 rounded-2xl px-5 py-3 shadow-inner hover:bg-white/10 transition">
                            <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Verification</p>
                            <p class="text-xs font-extrabold mt-1.5 <?php echo ($student['status'] === 'Active') ? 'text-emerald-400' : 'text-amber-400'; ?>">
                                <i class="fa-solid <?php echo ($student['status'] === 'Active') ? 'fa-circle-check' : 'fa-clock'; ?> mr-1"></i>
                                <?php echo ($student['status'] === 'Active') ? 'Approved' : 'Pending'; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
<?php
    }
}

if (!function_exists('student_portal_render_shell_end')) {
    function student_portal_render_shell_end(): void
    {
        ?>
        </main>
    </div>
</div>

<script>
function studentPortalShell() {
    return {
        mobileMenuOpen: false,
        toast: { visible: false, message: '', type: 'success' },
        showToast(detail) {
            this.toast.message = detail?.message || '';
            this.toast.type = detail?.type || 'success';
            this.toast.visible = true;
            clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => { this.toast.visible = false; }, 4000);
        },
        init() {
            const flash = document.getElementById('portal-flash');
            if (flash) {
                this.showToast({ message: flash.dataset.message, type: flash.dataset.type || 'info' });
            }

            // Restore sidebar scroll positions
            const desktopNav = document.getElementById("student-sidebar-nav");
            const mobileNav = document.getElementById("student-mobile-sidebar-nav");

            if (desktopNav) {
                const desktopScroll = localStorage.getItem("student-sidebar-scroll-desktop");
                if (desktopScroll) {
                    desktopNav.scrollTop = parseInt(desktopScroll, 10);
                    setTimeout(() => { desktopNav.scrollTop = parseInt(desktopScroll, 10); }, 50);
                    setTimeout(() => { desktopNav.scrollTop = parseInt(desktopScroll, 10); }, 150);
                }
                desktopNav.addEventListener("scroll", function() {
                    localStorage.setItem("student-sidebar-scroll-desktop", desktopNav.scrollTop);
                });
            }

            if (mobileNav) {
                const mobileScroll = localStorage.getItem("student-sidebar-scroll-mobile");
                if (mobileScroll) {
                    mobileNav.scrollTop = parseInt(mobileScroll, 10);
                    setTimeout(() => { mobileNav.scrollTop = parseInt(mobileScroll, 10); }, 50);
                    setTimeout(() => { mobileNav.scrollTop = parseInt(mobileScroll, 10); }, 150);
                }
                mobileNav.addEventListener("scroll", function() {
                    localStorage.setItem("student-sidebar-scroll-mobile", mobileNav.scrollTop);
                });
            }
        }
    };
}
window.studentShowToast = function(message, type = 'success') {
    window.dispatchEvent(new CustomEvent('student-toast', { detail: { message, type } }));
};
</script>
</body>
</html>
<?php
    }
}

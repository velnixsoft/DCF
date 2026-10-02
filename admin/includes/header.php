<?php

if (session_status() === PHP_SESSION_NONE) session_start();

$currentPage = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if ($currentPage !== 'index.php') {
        header('Location: ' . appUrlPath() . '/admin');
        exit;
    }
}

if (!isset($pdo)) {

    $configPath = file_exists('../config/db.php') ? '../config/db.php' : '../../config/db.php';
    if (file_exists($configPath)) require_once $configPath;
}

require_once __DIR__ . '/../../includes/functions.php';

// Ensure a CSRF token is always present for all admin forms.
$csrfToken = generateCsrfToken();

// Central Role-Based Access Control (RBAC) for admin pages.
// Roles (low -> high): coordinator < manager < admin
if ($currentPage !== 'index.php') {
    $pageAccessMap = [
        'dashboard.php' => ['role' => 'coordinator', 'permission' => 'page.dashboard'],
        'ai_assistant.php' => ['role' => 'coordinator', 'permission' => 'page.dashboard'],
        'manager_panel.php' => ['role' => 'manager', 'permission' => 'page.manager_panel'],
        'coordinator_reports.php' => ['role' => 'coordinator', 'permission' => 'page.coordinator_reports'],
        'logout.php' => ['role' => 'coordinator', 'permission' => null],

        'memberships.php' => ['role' => 'coordinator', 'permission' => 'page.memberships'],
        'member_messages.php' => ['role' => 'coordinator', 'permission' => 'page.member_messages'],
        'visitor_certificates.php' => ['role' => 'coordinator', 'permission' => 'page.visitor_certificates'],
        'beneficiaries.php' => ['role' => 'coordinator', 'permission' => 'page.beneficiaries'],
        'beneficiary_profile.php' => ['role' => 'coordinator', 'permission' => 'page.beneficiaries'],
        'beneficiary_assistance.php' => ['role' => 'coordinator', 'permission' => 'page.beneficiaries'],
        'beneficiary_reports.php' => ['role' => 'coordinator', 'permission' => 'page.beneficiaries'],
        'expenses.php' => ['role' => 'coordinator', 'permission' => 'page.expenses'],
        'document_studio.php' => ['role' => 'coordinator', 'permission' => 'page.member_documents'],
        'template-builder.php' => ['role' => 'coordinator', 'permission' => 'page.member_documents'],
        'letters.php' => ['role' => 'coordinator', 'permission' => 'page.letters'],
        'letter_editor.php' => ['role' => 'coordinator', 'permission' => 'page.letters'],
        'download_letter.php' => ['role' => 'coordinator', 'permission' => 'page.letters'],
        'staff_letters.php' => ['role' => 'coordinator', 'permission' => 'page.letters'],
        'staff_letter_composer.php' => ['role' => 'coordinator', 'permission' => 'page.letters'],
        'agreements.php' => ['role' => 'coordinator', 'permission' => 'page.letters'],
        'agreement_composer.php' => ['role' => 'coordinator', 'permission' => 'page.letters'],
        'inquiries.php' => ['role' => 'coordinator', 'permission' => 'page.inquiries'],
        'join_applications.php' => ['role' => 'coordinator', 'permission' => 'page.volunteers'],
        'volunteers.php' => ['role' => 'coordinator', 'permission' => 'page.volunteers'],
        'volunteer_activities.php' => ['role' => 'coordinator', 'permission' => 'page.volunteer_activities'],
        'admin_management_body.php' => ['role' => 'manager', 'permission' => 'page.management_body'],
        'org_structure.php' => ['role' => 'manager', 'permission' => 'page.management_body'],
        'generate_member_receipt.php' => ['role' => 'coordinator', 'permission' => 'page.member_documents'],
        'generate_member_document.php' => ['role' => 'coordinator', 'permission' => 'page.member_documents'],
        'generate_idcard.php' => ['role' => 'coordinator', 'permission' => 'page.member_documents'],
        'sanstha_certificates.php' => ['role' => 'coordinator', 'permission' => 'page.member_documents'],

        'donations.php' => ['role' => 'manager', 'permission' => 'page.donations'],
        'generate_custom_receipt.php' => ['role' => 'coordinator', 'permission' => 'page.donations'],
        'custom_receipts.php' => ['role' => 'coordinator', 'permission' => 'page.donations'],
        'download_custom_receipt.php' => ['role' => 'coordinator', 'permission' => 'page.donations'],
        'complaints.php' => ['role' => 'coordinator', 'permission' => 'page.complaints'],
        'feedbacks.php' => ['role' => 'coordinator', 'permission' => 'page.complaints'],
        'healthcare_directory.php' => ['role' => 'coordinator', 'permission' => 'page.healthcare'],
        'doctor_agreements.php' => ['role' => 'coordinator', 'permission' => 'page.healthcare'],
        'health_card_applications.php' => ['role' => 'coordinator', 'permission' => 'page.healthcare'],
        'health_cards.php' => ['role' => 'coordinator', 'permission' => 'page.healthcare'],
        'download_health_card.php' => ['role' => 'coordinator', 'permission' => 'page.healthcare'],
        'jobs.php' => ['role' => 'coordinator', 'permission' => 'page.jobs'],
        'job_postings.php' => ['role' => 'coordinator', 'permission' => 'page.jobs'],
        'job_applications.php' => ['role' => 'coordinator', 'permission' => 'page.jobs'],
        'reports.php' => ['role' => 'manager', 'permission' => 'page.reports'],
        'income_vs_expense.php' => ['role' => 'manager', 'permission' => 'page.reports'],
        'generate_receipt.php' => ['role' => 'manager', 'permission' => 'page.donations'],

        'projects.php' => ['role' => 'manager', 'permission' => 'page.projects'],
        'project_edit.php' => ['role' => 'manager', 'permission' => 'page.projects'],
        'events.php' => ['role' => 'manager', 'permission' => 'page.events'],
        'create-event.php' => ['role' => 'manager', 'permission' => 'page.events'],
        'event-report.php' => ['role' => 'manager', 'permission' => 'page.events'],
        'event_registrations.php' => ['role' => 'manager', 'permission' => 'page.events'],
        'event_gallery.php' => ['role' => 'manager', 'permission' => 'page.events'],

        'slider_manager.php' => ['role' => 'manager', 'permission' => 'page.slider_manager'],
        'sponsor_manager.php' => ['role' => 'manager', 'permission' => 'page.sponsors'],
        'gallery_manager.php' => ['role' => 'manager', 'permission' => 'page.gallery_manager'],
        'about_manager.php' => ['role' => 'manager', 'permission' => 'page.about_manager'],
        'career_guidance_manager.php' => ['role' => 'coordinator', 'permission' => 'page.about_manager'],
        'objectives_manager.php' => ['role' => 'manager', 'permission' => 'page.objectives_manager'],
        'awards_manager.php' => ['role' => 'manager', 'permission' => 'page.awards_manager'],
        'contact_manager.php' => ['role' => 'manager', 'permission' => 'page.contact_manager'],
        'certificate_manager.php' => ['role' => 'manager', 'permission' => 'page.certificate_manager'],
        'privacy_manager.php' => ['role' => 'manager', 'permission' => 'page.privacy_manager'],
        'terms_manager.php' => ['role' => 'manager', 'permission' => 'page.terms_manager'],
        'refund_manager.php' => ['role' => 'manager', 'permission' => 'page.refund_manager'],

        'settings.php' => ['role' => 'admin', 'permission' => 'page.settings'],
        'system_info.php' => ['role' => 'admin', 'permission' => 'page.system_info'],
        'access_control.php' => ['role' => 'admin', 'permission' => 'page.access_control'],

        'news.php' => ['role' => 'manager', 'permission' => 'page.news'],
        'crowdfunding.php' => ['role' => 'manager', 'permission' => 'page.crowdfunding'],
        'documents.php' => ['role' => 'manager', 'permission' => 'page.documents'],
        'hr_policies.php' => ['role' => 'manager', 'permission' => 'page.documents'],
        'donation_analytics.php' => ['role' => 'manager', 'permission' => 'page.donation_analytics'],
        'training_videos.php' => ['role' => 'manager', 'permission' => 'page.training_videos'],
    ];

    $requiredRole = $pageAccessMap[$currentPage]['role'] ?? 'manager';
    $permissionKey = $pageAccessMap[$currentPage]['permission'] ?? null;
    if (!canAccessModule($pdo, $requiredRole, $permissionKey)) {
        setFlash('error', 'Access denied. Insufficient permissions.');
        header('Location: ' . getDefaultAdminLandingPage($pdo));
        exit;
    }
}

$settings = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM settings");
        while ($row = $stmt->fetch()) $settings[$row['setting_key']] = $row['setting_value'];
    } catch (PDOException $e) {
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $settings['site_name'] ?? 'NGO System'; ?> - Admin Panel</title>

    <?php if (!empty($settings['site_favicon']) && file_exists(__DIR__ . '/../../' . $settings['site_favicon'])): ?>
        <link rel="icon" href="../<?php echo $settings['site_favicon']; ?>" type="image/x-icon">
    <?php else: ?>
        <link rel="icon" href="https://cdn-icons-png.flaticon.com/512/906/906343.png" type="image/png">
    <?php endif; ?>

    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/theme-palette.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brandOrange: { DEFAULT: '#F0A010', hover: '#d48b0a' },
            brandTeal: { DEFAULT: '#1070B0', hover: '#20A0D0' },
            green: { 50: '#f2fcf7', 100: '#e1f8ec', 500: '#00B060', 600: '#00B060', 700: '#009c54', 800: '#008447' },
            amber: { 50: '#fffcf5', 100: '#fef5e7', 500: '#F0A010', 600: '#d48b0a', 700: '#c07e0c' }
          }
        }
      }
    }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <style>
        [x-cloak] {
            display: none !important;
        }

        body,
        div,
        nav,
        aside,
        table,
        tr,
        td,
        th {
            transition: background-color 0.3s, color 0.3s, border-color 0.3s;
        }

        @keyframes shrink {
            from {
                width: 100%;
            }

            to {
                width: 0%;
            }
        }

        .animate-shrink {
            animation: shrink 3s linear forwards;
        }

        .dark ::-webkit-scrollbar {
            width: 8px;
        }

        .dark ::-webkit-scrollbar-track {
            background: #1e293b;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 4px;
        }

        .dark ::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
    </style>

    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        document.addEventListener('alpine:init', () => {

            Alpine.store('sidebar', {
                open: false,
                toggle() {
                    this.open = !this.open
                }
            });

            Alpine.store('theme', {
                isDark: localStorage.getItem('theme') === 'dark',
                toggle() {
                    this.isDark = !this.isDark;
                    if (this.isDark) {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('theme', 'dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('theme', 'light');
                    }
                }
            });

            Alpine.store('toast', {
                visible: false,
                message: '',
                type: 'success',
                show(msg, type) {
                    this.message = msg;
                    this.type = type;
                    this.visible = true;
                    setTimeout(() => {
                        this.visible = false
                    }, 3000);
                }
            });
        });
    </script>
</head>

<body class="bg-gray-100 dark:bg-dark-bg text-gray-800 dark:text-dark-text font-sans antialiased" x-data>

    <div x-show="$store.toast.visible"
        class="fixed top-5 right-5 z-[100] max-w-sm w-full bg-white dark:bg-dark-card shadow-2xl rounded-lg overflow-hidden border-l-4"
        :class="$store.toast.type === 'success' ? 'border-green-500' : 'border-red-500'"
        x-transition:enter="transform ease-out duration-300 transition"
        x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak>

        <div class="p-4 flex items-center">
            <div class="flex-shrink-0">

                <svg x-show="$store.toast.type === 'success'" class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>

                <svg x-show="$store.toast.type === 'error'" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="ml-3 w-0 flex-1 pt-0.5">
                <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="$store.toast.type === 'success' ? 'Success' : 'Error'"></p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="$store.toast.message"></p>
            </div>
            <div class="ml-4 flex-shrink-0 flex">
                <button @click="$store.toast.visible = false" class="bg-white dark:bg-dark-card rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                    <span class="sr-only">Close</span>
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="h-1 bg-gray-100 dark:bg-gray-700 w-full">
            <div class="h-full animate-shrink" :class="$store.toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'"></div>
        </div>
    </div>

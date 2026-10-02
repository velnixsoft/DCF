<?php

$currentPage = basename($_SERVER['PHP_SELF']);
$currentUserName = $_SESSION['user_name'] ?? 'Admin';
$currentUserRole = $_SESSION['user_role'] ?? 'Administrator';
$siteName = $settings['site_name'] ?? 'NGO System';

$sidebarSections = [
    [
        'title' => 'Overview',
        'items' => [
            [
                'label' => 'Dashboard',
                'href' => '/admin/dashboard',
                'icon' => 'fa-gauge-high',
                'role' => 'coordinator',
                'permission' => 'page.dashboard',
                'pages' => ['dashboard.php'],
            ],
            [
                'label' => 'AI Copilot',
                'href' => '/admin/ai-assistant',
                'icon' => 'fa-wand-magic-sparkles',
                'badge' => 'AI',
                'role' => 'coordinator',
                'permission' => 'page.dashboard',
                'pages' => ['ai_assistant.php'],
            ],
            // [
            //     'label' => 'Manager Panel',
            //     'href' => '/admin/manager-panel',
            //     'icon' => 'fa-shield-halved',
            //     'role' => 'manager',
            //     'permission' => 'page.manager_panel',
            //     'pages' => ['manager_panel.php'],
            // ],
            // [
            //     'label' => 'Coordinator Reports',
            //     'href' => '/admin/coordinator-reports',
            //     'icon' => 'fa-chart-line',
            //     'role' => 'coordinator',
            //     'permission' => 'page.coordinator_reports',
            //     'pages' => ['coordinator_reports.php'],
            // ],
        ],
    ],
    [
        'title' => 'Membership',
        'items' => [
            [
                'label' => 'Memberships',
                'href' => '/admin/memberships',
                'icon' => 'fa-id-card',
                'role' => 'coordinator',
                'permission' => 'page.memberships',
                'pages' => ['memberships.php', 'generate_member_receipt.php', 'generate_member_document.php', 'generate_idcard.php'],
            ],
            [
                'label' => 'Document Studio',
                'href' => '/admin/document-studio',
                'icon' => 'fa-file-signature',
                'role' => 'coordinator',
                'permission' => 'page.member_documents',
                'pages' => ['document_studio.php', 'generate_member_document.php', 'send_member_document.php'],
            ],
            [
                'label' => 'Letters & Letterhead',
                'href' => '/admin/letters',
                'icon' => 'fa-envelope-open-text',
                'role' => 'coordinator',
                'permission' => 'page.letters',
                'pages' => ['letters.php', 'letter_editor.php', 'download_letter.php'],
            ],
            [
                'label' => 'Staff & Volunteer Letters',
                'href' => '/admin/staff_letters.php',
                'icon' => 'fa-user-pen',
                'role' => 'coordinator',
                'permission' => 'page.letters',
                'pages' => ['staff_letters.php', 'staff_letter_composer.php'],
            ],
            [
                'label' => 'Template Builder',
                'href' => '/admin/template-builder',
                'icon' => 'fa-pen-ruler',
                'role' => 'coordinator',
                'permission' => 'page.member_documents',
                'pages' => ['template-builder.php'],
            ],
            [
                'label' => 'Visitor Certificates',
                'href' => '/admin/visitor-certificates',
                'icon' => 'fa-certificate',
                'role' => 'coordinator',
                'permission' => 'page.visitor_certificates',
                'pages' => ['visitor_certificates.php'],
            ],
            // [
            //     'label' => 'Sanstha Authorization',
            //     'href' => '/admin/sanstha-certificates',
            //     'icon' => 'fa-stamp',
            //     'role' => 'coordinator',
            //     'permission' => 'page.member_documents',
            //     'pages' => ['sanstha_certificates.php'],
            // ],
            [
                'label' => 'Member Messages',
                'href' => '/admin/member-messages',
                'icon' => 'fa-envelope',
                'role' => 'coordinator',
                'permission' => 'page.member_messages',
                'pages' => ['member_messages.php'],
            ],
            [
                'label' => 'Inquiries',
                'href' => '/admin/inquiries',
                'icon' => 'fa-circle-question',
                'role' => 'coordinator',
                'permission' => 'page.inquiries',
                'pages' => ['inquiries.php'],
            ],
            [
                'label' => 'Complaints & Suggestions',
                'href' => '/admin/complaints',
                'icon' => 'fa-comments',
                'role' => 'coordinator',
                'permission' => 'page.complaints',
                'pages' => ['complaints.php'],
            ],
            [
                'label' => 'Member & Staff Feedback',
                'href' => '/admin/feedbacks.php',
                'icon' => 'fa-comment-dots',
                'role' => 'coordinator',
                'permission' => 'page.complaints',
                'pages' => ['feedbacks.php'],
            ],
            [
                'label' => 'Join Applications',
                'href' => '/admin/join-applications',
                'icon' => 'fa-id-card',
                'role' => 'coordinator',
                'permission' => 'page.volunteers',
                'pages' => ['join_applications.php'],
            ],
            [
                'label' => 'Volunteers',
                'href' => '/admin/volunteers',
                'icon' => 'fa-people-group',
                'role' => 'coordinator',
                'permission' => 'page.volunteers',
                'pages' => ['volunteers.php'],
            ],
            [
                'label' => 'Volunteer Activities',
                'href' => '/admin/volunteer-activities',
                'icon' => 'fa-list-check',
                'role' => 'coordinator',
                'permission' => 'page.volunteer_activities',
                'pages' => ['volunteer_activities.php'],
            ],
        ],
    ],
    // [
    //     'title' => 'Ambassadors',
    //     'items' => [
    //         [
    //             'label' => 'Student Directory',
    //             'href' => '/admin/student-directory',
    //             'icon' => 'fa-graduation-cap',
    //             'role' => 'coordinator',
    //             'permission' => 'page.volunteers',
    //             'pages' => ['student_directory.php'],
    //         ],
    //         [
    //             'label' => 'Campaign Tasks',
    //             'href' => '/admin/student-tasks',
    //             'icon' => 'fa-tasks',
    //             'role' => 'manager',
    //             'permission' => 'page.events',
    //             'pages' => ['student_tasks.php'],
    //         ],
    //         [
    //             'label' => 'Event Requests',
    //             'href' => '/admin/student-event-requests',
    //             'icon' => 'fa-calendar-plus',
    //             'role' => 'coordinator',
    //             'permission' => 'page.events',
    //             'pages' => ['student_event_requests.php'],
    //         ],
    //         [
    //             'label' => 'Task Submissions',
    //             'href' => '/admin/student-submissions',
    //             'icon' => 'fa-clipboard-check',
    //             'role' => 'coordinator',
    //             'permission' => 'page.volunteers',
    //             'pages' => ['student_submissions.php'],
    //         ],
    //         [
    //             'label' => 'Referral Verification',
    //             'href' => '/admin/student-referrals',
    //             'icon' => 'fa-share-nodes',
    //             'role' => 'coordinator',
    //             'permission' => 'page.volunteers',
    //             'pages' => ['student_referrals.php'],
    //         ],
    //         [
    //             'label' => 'Vendor Leads',
    //             'href' => '/admin/student-vendor-leads',
    //             'icon' => 'fa-handshake-angle',
    //             'role' => 'coordinator',
    //             'permission' => 'page.volunteers',
    //             'pages' => ['student_vendor_leads.php'],
    //         ],
    //         [
    //             'label' => 'Notification Queue',
    //             'href' => '/admin/notification-queue',
    //             'icon' => 'fa-bell',
    //             'role' => 'coordinator',
    //             'permission' => 'page.volunteers',
    //             'pages' => ['notification_queue.php'],
    //         ],
    //         [
    //             'label' => 'QR Donation Sync',
    //             'href' => '/admin/qr-donations-tracking',
    //             'icon' => 'fa-qrcode',
    //             'role' => 'manager',
    //             'permission' => 'page.donations',
    //             'pages' => ['qr_donations_tracking.php'],
    //         ],
    //         [
    //             'label' => 'Ambassador Reports',
    //             'href' => '/admin/ambassador-reports',
    //             'icon' => 'fa-chart-column',
    //             'role' => 'coordinator',
    //             'permission' => 'page.volunteers',
    //             'pages' => ['ambassador_reports.php'],
    //         ],
    //         [
    //             'label' => 'Student Certificates',
    //             'href' => '/admin/student-certificates',
    //             'icon' => 'fa-award',
    //             'role' => 'coordinator',
    //             'permission' => 'page.member_documents',
    //             'pages' => ['student_certificates.php', 'generate_student_certificate.php'],
    //         ],
    //         [
    //             'label' => 'Partner Directory',
    //             'href' => '/admin/partner-directory',
    //             'icon' => 'fa-handshake',
    //             'role' => 'coordinator',
    //             'permission' => 'page.sponsors',
    //             'pages' => ['partner_directory.php'],
    //         ],
    //         [
    //             'label' => 'Ambassador Events',
    //             'href' => '/admin/student-sa-events',
    //             'icon' => 'fa-calendar-check',
    //             'role' => 'coordinator',
    //             'permission' => 'page.events',
    //             'pages' => ['student_sa_events.php'],
    //         ],
    //         [
    //             'label' => 'Announcements',
    //             'href' => '/admin/student-announcements',
    //             'icon' => 'fa-bullhorn',
    //             'role' => 'coordinator',
    //             'permission' => 'page.volunteers',
    //             'pages' => ['student_announcements.php'],
    //         ],
    //         [
    //             'label' => 'Penalties',
    //             'href' => '/admin/student-penalties',
    //             'icon' => 'fa-gavel',
    //             'role' => 'coordinator',
    //             'permission' => 'page.volunteers',
    //             'pages' => ['student_penalties.php'],
    //         ],
    //         [
    //             'label' => 'Point Rules',
    //             'href' => '/admin/student-point-rules',
    //             'icon' => 'fa-coins',
    //             'role' => 'manager',
    //             'permission' => 'page.volunteers',
    //             'pages' => ['student_point_rules.php'],
    //         ],
    //     ],
    // ],
    [
        'title' => 'Operations',
        'items' => [
            [
                'label' => 'Projects & Funds',
                'href' => '/admin/projects',
                'icon' => 'fa-seedling',
                'role' => 'manager',
                'permission' => 'page.projects',
                'pages' => ['projects.php', 'project_edit.php'],
            ],
            [
                'label' => 'Donations',
                'href' => '/admin/donations',
                'icon' => 'fa-sack-dollar',
                'role' => 'manager',
                'permission' => 'page.donations',
                'pages' => ['donations.php', 'generate_receipt.php'],
            ],
            [
                'label' => 'Custom Receipts',
                'href' => '/admin/generate_custom_receipt.php',
                'icon' => 'fa-receipt',
                'role' => 'coordinator',
                'permission' => 'page.donations',
                'pages' => ['generate_custom_receipt.php', 'custom_receipts.php', 'download_custom_receipt.php'],
            ],
            [
                'label' => 'Auto Pay / Recurring',
                'href' => '/admin/recurring-donations',
                'icon' => 'fa-arrows-rotate',
                'role' => 'manager',
                'permission' => 'page.recurring_donations',
                'pages' => ['recurring_donations.php'],
            ],
            [
                'label' => 'Item Categories',
                'href' => '/admin/item-donation-categories',
                'icon' => 'fa-boxes-stacked',
                'role' => 'manager',
                'permission' => 'page.item_categories',
                'pages' => ['item_donation_categories.php'],
            ],
            [
                'label' => 'Beneficiaries',
                'href' => '/admin/beneficiaries',
                'icon' => 'fa-hands-holding-child',
                'role' => 'coordinator',
                'permission' => 'page.beneficiaries',
                'pages' => ['beneficiaries.php', 'beneficiary_profile.php'],
            ],
            [
                'label' => 'Healthcare Directory',
                'href' => '/admin/healthcare_directory.php',
                'icon' => 'fa-hospital-user',
                'role' => 'coordinator',
                'permission' => 'page.healthcare',
                'pages' => ['healthcare_directory.php', 'healthcare_providers.php'],
            ],
            [
                'label' => 'Doctor Agreements & MOUs',
                'href' => '/admin/doctor_agreements.php',
                'icon' => 'fa-file-contract',
                'role' => 'coordinator',
                'permission' => 'page.healthcare',
                'pages' => ['doctor_agreements.php'],
            ],
            [
                'label' => 'Health Cards',
                'href' => '/admin/health_card_applications.php',
                'icon' => 'fa-id-card-clip',
                'role' => 'coordinator',
                'permission' => 'page.healthcare',
                'pages' => ['health_card_applications.php', 'health_cards.php', 'download_health_card.php'],
            ],
            [
                'label' => 'Job Postings',
                'href' => '/admin/jobs.php',
                'icon' => 'fa-briefcase',
                'role' => 'coordinator',
                'permission' => 'page.jobs',
                'pages' => ['jobs.php', 'job_postings.php'],
            ],
            [
                'label' => 'Job Applications',
                'href' => '/admin/job_applications.php',
                'icon' => 'fa-users-viewfinder',
                'role' => 'coordinator',
                'permission' => 'page.jobs',
                'pages' => ['job_applications.php'],
            ],
            [
                'label' => 'Assistance Log',
                'href' => '/admin/beneficiary-assistance',
                'icon' => 'fa-hand-holding-heart',
                'role' => 'coordinator',
                'permission' => 'page.beneficiaries',
                'pages' => ['beneficiary_assistance.php'],
            ],
            [
                'label' => 'Beneficiary Reports',
                'href' => '/admin/beneficiary-reports',
                'icon' => 'fa-file-invoice',
                'role' => 'coordinator',
                'permission' => 'page.beneficiaries',
                'pages' => ['beneficiary_reports.php'],
            ],
            [
                'label' => 'Expense Tracker',
                'href' => '/admin/expenses',
                'icon' => 'fa-receipt',
                'role' => 'coordinator',
                'permission' => 'page.expenses',
                'pages' => ['expenses.php'],
            ],
            [
                'label' => 'Generated Letters',
                'href' => '/admin/letters.php',
                'icon' => 'fa-file-signature',
                'role' => 'coordinator',
                'permission' => 'page.letters',
                'pages' => ['letters.php', 'letter_editor.php', 'letterhead_settings.php'],
            ],
            [
                'label' => 'Staff Letters',
                'href' => '/admin/staff_letters.php',
                'icon' => 'fa-user-pen',
                'role' => 'coordinator',
                'permission' => 'page.letters',
                'pages' => ['staff_letters.php', 'staff_letter_composer.php'],
            ],
            [
                'label' => 'Agreements & MoUs',
                'href' => '/admin/agreements.php',
                'icon' => 'fa-file-contract',
                'role' => 'coordinator',
                'permission' => 'page.letters',
                'pages' => ['agreements.php', 'agreement_composer.php'],
            ],
            [
                'label' => 'Events',
                'href' => '/admin/events',
                'icon' => 'fa-calendar-days',
                'role' => 'manager',
                'permission' => 'page.events',
                'pages' => ['events.php', 'create-event.php', 'event-report.php', 'event_registrations.php', 'event_gallery.php'],
            ],
            [
                'label' => 'Crowdfunding',
                'href' => '/admin/crowdfunding',
                'icon' => 'fa-bullhorn',
                'role' => 'manager',
                'permission' => 'page.crowdfunding',
                'pages' => ['crowdfunding.php'],
            ],
            [
                'label' => 'News / Updates',
                'href' => '/admin/news',
                'icon' => 'fa-newspaper',
                'role' => 'manager',
                'permission' => 'page.news',
                'pages' => ['news.php'],
            ],
            [
                'label' => 'Documents Library',
                'href' => '/admin/documents',
                'icon' => 'fa-folder-open',
                'role' => 'manager',
                'permission' => 'page.documents',
                'pages' => ['documents.php'],
            ],
            [
                'label' => 'HR Policies',
                'href' => '/admin/hr_policies.php',
                'icon' => 'fa-book-open-reader',
                'role' => 'manager',
                'permission' => 'page.documents',
                'pages' => ['hr_policies.php'],
            ],
            [
                'label' => 'Donation Analytics',
                'href' => '/admin/donation-analytics',
                'icon' => 'fa-chart-pie',
                'role' => 'manager',
                'permission' => 'page.donation_analytics',
                'pages' => ['donation_analytics.php'],
            ],
            [
                'label' => 'Training Videos',
                'href' => '/admin/training-videos',
                'icon' => 'fa-video',
                'role' => 'manager',
                'permission' => 'page.training_videos',
                'pages' => ['training_videos.php'],
            ],
            [
                'label' => 'Management Body',
                'href' => '/admin/admin_management_body',
                'icon' => 'fa-user-tie',
                'role' => 'manager',
                'permission' => 'page.management_body',
                'pages' => ['admin_management_body.php'],
            ],
            [
                'label' => 'Org Structure Tree',
                'href' => '/admin/org_structure.php',
                'icon' => 'fa-sitemap',
                'role' => 'manager',
                'permission' => 'page.management_body',
                'pages' => ['org_structure.php'],
            ],
        ],
    ],
    [
        'title' => 'Content',
        'items' => [
            [
                'type' => 'group',
                'label' => 'Page Manager',
                'icon' => 'fa-pen-to-square',
                'children' => [
                    [
                        'label' => 'About Page',
                        'href' => '/admin/about-manager',
                        'icon' => 'fa-circle-info',
                        'role' => 'manager',
                        'permission' => 'page.about_manager',
                        'pages' => ['about_manager.php'],
                    ],
                    [
                        'label' => 'Career Guidance CMS',
                        'href' => '/admin/career_guidance_manager.php',
                        'icon' => 'fa-graduation-cap',
                        'role' => 'coordinator',
                        'permission' => 'page.about_manager',
                        'pages' => ['career_guidance_manager.php'],
                    ],
                    [
                        'label' => 'Objectives Page',
                        'href' => '/admin/objectives-manager',
                        'icon' => 'fa-bullseye',
                        'role' => 'manager',
                        'permission' => 'page.objectives_manager',
                        'pages' => ['objectives_manager.php'],
                    ],
                    [
                        'label' => 'Awards Page',
                        'href' => '/admin/awards-manager',
                        'icon' => 'fa-award',
                        'role' => 'manager',
                        'permission' => 'page.awards_manager',
                        'pages' => ['awards_manager.php'],
                    ],
                    [
                        'label' => 'Gallery Page',
                        'href' => '/admin/gallery-manager',
                        'icon' => 'fa-images',
                        'role' => 'manager',
                        'permission' => 'page.gallery_manager',
                        'pages' => ['gallery_manager.php'],
                    ],
                    [
                        'label' => 'Contact Page',
                        'href' => '/admin/contact-manager',
                        'icon' => 'fa-address-book',
                        'role' => 'manager',
                        'permission' => 'page.contact_manager',
                        'pages' => ['contact_manager.php'],
                    ],
                    [
                        'label' => 'Certificate & Legal',
                        'href' => '/admin/certificate-manager',
                        'icon' => 'fa-scroll',
                        'role' => 'manager',
                        'permission' => 'page.certificate_manager',
                        'pages' => ['certificate_manager.php'],
                    ],
                    [
                        'label' => 'Privacy Policy',
                        'href' => '/admin/privacy-manager',
                        'icon' => 'fa-user-shield',
                        'role' => 'manager',
                        'permission' => 'page.privacy_manager',
                        'pages' => ['privacy_manager.php'],
                    ],
                    [
                        'label' => 'Terms & Conditions',
                        'href' => '/admin/terms-manager',
                        'icon' => 'fa-file-contract',
                        'role' => 'manager',
                        'permission' => 'page.terms_manager',
                        'pages' => ['terms_manager.php'],
                    ],
                    [
                        'label' => 'Refund Policy',
                        'href' => '/admin/refund-manager',
                        'icon' => 'fa-rotate-left',
                        'role' => 'manager',
                        'permission' => 'page.refund_manager',
                        'pages' => ['refund_manager.php'],
                    ],
                ],
            ],
            [
                'label' => 'Slider Manager',
                'href' => '/admin/slider-manager',
                'icon' => 'fa-sliders',
                'role' => 'manager',
                'permission' => 'page.slider_manager',
                'pages' => ['slider_manager.php'],
            ],
            [
                'label' => 'Sponsors',
                'href' => '/admin/sponsor-manager',
                'icon' => 'fa-handshake',
                'role' => 'manager',
                'permission' => 'page.sponsors',
                'pages' => ['sponsor_manager.php'],
            ],
            [
                'label' => 'Testimonials',
                'href' => '/admin/testimonials',
                'icon' => 'fa-quote-left',
                'role' => 'coordinator',
                'permission' => 'page.testimonials',
                'pages' => ['testimonials.php'],
            ],
        ],
    ],

    [
        'title' => 'Analytics',
        'items' => [
            [
                'label' => 'Reports',
                'href' => '/admin/reports',
                'icon' => 'fa-chart-column',
                'role' => 'manager',
                'permission' => 'page.reports',
                'pages' => ['reports.php'],
            ],
            [
                'label' => 'Income vs Expense',
                'href' => '/admin/income-vs-expense',
                'icon' => 'fa-scale-balanced',
                'role' => 'manager',
                'permission' => 'page.reports',
                'pages' => ['income_vs_expense.php'],
            ],
            [
                'label' => 'System Info',
                'href' => '/admin/system-info',
                'icon' => 'fa-circle-info',
                'role' => 'admin',
                'permission' => 'page.system_info',
                'pages' => ['system_info.php'],
            ],
        ],
    ],
    [
        'title' => 'System',
        'items' => [
            [
                'label' => 'Settings',
                'href' => '/admin/settings',
                'icon' => 'fa-gear',
                'role' => 'admin',
                'permission' => 'page.settings',
                'pages' => ['settings.php'],
            ],
            [
                'label' => 'Access Control',
                'href' => '/admin/access-control',
                'icon' => 'fa-user-shield',
                'role' => 'admin',
                'permission' => 'page.access_control',
                'pages' => ['access_control.php'],
            ],
            [
                'label' => 'Audit Logs',
                'href' => '/admin/audit-logs',
                'icon' => 'fa-clipboard-list',
                'role' => 'manager',
                'permission' => 'page.system_info',
                'pages' => ['audit_logs.php'],
            ],
        ],
    ],
];

$canSeeItem = function (array $item) use ($pdo, &$canSeeItem): bool {
    if (($item['type'] ?? 'link') === 'group') {
        foreach ($item['children'] ?? [] as $child) {
            if ($canSeeItem($child)) {
                return true;
            }
        }
        return false;
    }

    return canAccessModule($pdo, $item['role'] ?? 'coordinator', $item['permission'] ?? null);
};

$isActivePage = function (array $pages) use ($currentPage): bool {
    return in_array($currentPage, $pages, true);
};

$renderLink = function (array $item, bool $nested = false) use ($isActivePage): void {
    $isActive = $isActivePage($item['pages'] ?? []);
    $baseClasses = $nested ? 'pl-9' : 'px-3';
    $stateClasses = $isActive
        ? 'bg-[#1070B0] text-white shadow-sm font-semibold'
        : 'text-gray-700 dark:text-gray-300 hover:bg-[#fffcf5] hover:text-[#F0A010] dark:hover:bg-gray-800 dark:hover:text-[#F0A010]';
    $iconClasses = $isActive
        ? 'text-white'
        : 'text-gray-500 group-hover:text-[#F0A010]';
    ?>
    <a href="<?php echo htmlspecialchars(appUrlPath() . $item['href']); ?>"
        class="group flex items-center gap-2.5 rounded-lg py-2 pr-3 text-[13px] font-medium transition-all duration-150 <?php echo $baseClasses . ' ' . $stateClasses; ?>">
        <span class="flex h-7 w-7 items-center justify-center rounded-md flex-shrink-0">
            <i class="fa-solid <?php echo htmlspecialchars($item['icon'] ?? 'fa-circle'); ?> text-sm <?php echo $iconClasses; ?>"></i>
        </span>
        <span class="flex-1 truncate"><?php echo htmlspecialchars($item['label']); ?></span>
    </a>
    <?php
};

$renderGroup = function (array $group) use (&$renderLink, $canSeeItem, $isActivePage): void {
    $visibleChildren = array_values(array_filter($group['children'] ?? [], $canSeeItem));
    if (empty($visibleChildren)) {
        return;
    }

    $groupActive = false;
    foreach ($visibleChildren as $child) {
        if ($isActivePage($child['pages'] ?? [])) {
            $groupActive = true;
            break;
        }
    }
    ?>
    <div x-data="{ open: <?php echo $groupActive ? 'true' : 'false'; ?> }" class="space-y-0.5">
        <button type="button"
            @click="open = !open"
            class="group flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-[13px] font-medium transition-all duration-150 <?php echo $groupActive ? 'bg-[#fffcf5] text-[#1070B0] dark:bg-gray-800 dark:text-white font-semibold' : 'text-gray-700 dark:text-gray-300 hover:bg-[#fffcf5] hover:text-[#F0A010] dark:hover:bg-gray-800 dark:hover:text-[#F0A010]'; ?>">
            <span class="flex h-7 w-7 items-center justify-center rounded-md flex-shrink-0">
                <i class="fa-solid <?php echo htmlspecialchars($group['icon'] ?? 'fa-folder-tree'); ?> text-sm <?php echo $groupActive ? 'text-[#1070B0]' : 'text-gray-500 group-hover:text-[#F0A010]'; ?>"></i>
            </span>
            <span class="flex-1 truncate"><?php echo htmlspecialchars($group['label']); ?></span>
            <i class="fa-solid fa-chevron-down text-[10px] text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
        </button>

        <div x-show="open" x-collapse class="space-y-0.5 ml-2">
            <?php foreach ($visibleChildren as $child): ?>
                <?php $renderLink($child, true); ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
};

$renderSection = function (array $section) use (&$renderLink, &$renderGroup, $canSeeItem): void {
    $visibleItems = array_values(array_filter($section['items'] ?? [], $canSeeItem));
    if (empty($visibleItems)) {
        return;
    }
    ?>
    <section class="space-y-1">
        <p class="px-3 text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-1.5">
            <?php echo htmlspecialchars($section['title']); ?>
        </p>
        <div class="space-y-0.5">
            <?php foreach ($visibleItems as $item): ?>
                <?php if (($item['type'] ?? 'link') === 'group'): ?>
                    <?php $renderGroup($item); ?>
                <?php else: ?>
                    <?php $renderLink($item); ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
};
?>

<!-- Desktop Sidebar -->
<div class="hidden md:flex fixed inset-y-0 left-0 z-30 w-56 flex-col border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
    <!-- Logo Section - More Compact -->
    <div class="relative overflow-hidden border-b border-gray-200 dark:border-gray-800 bg-[#1F2937] px-4 py-3.5">
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at top right, rgba(244,166,64,0.3), transparent 40%);"></div>
        <div class="relative">
            <p class="truncate text-sm font-bold tracking-wide text-white"><?php echo htmlspecialchars($siteName); ?></p>
            <p class="text-[10px] uppercase tracking-widest text-[#F4A640] mt-0.5 font-semibold">Admin Panel</p>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div id="desktop-sidebar-nav" class="flex-1 overflow-y-auto px-2.5 py-3 space-y-4 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-700">
        <?php foreach ($sidebarSections as $section): ?>
            <?php $renderSection($section); ?>
        <?php endforeach; ?>
    </div>

    <!-- Logout Button -->
    <div class="border-t border-gray-200 dark:border-gray-800 p-2.5">
        <a href="<?php echo appUrlPath(); ?>/admin/logout" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 transition-all duration-150">
            <span class="flex h-7 w-7 items-center justify-center rounded-md bg-red-50 dark:bg-red-900/30 flex-shrink-0">
                <i class="fa-solid fa-right-from-bracket text-sm"></i>
            </span>
            <span>Logout</span>
        </a>
    </div>
</div>

<!-- Mobile Sidebar -->
<div class="fixed inset-0 z-50 md:hidden" x-show="$store.sidebar.open" x-cloak>
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="$store.sidebar.toggle()"></div>
    <aside class="relative flex h-full w-64 max-w-[85vw] flex-col bg-white dark:bg-gray-900 shadow-2xl border-r border-gray-200 dark:border-gray-800">
        <!-- Mobile Header -->
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 px-4 py-3 bg-[#1F2937]">
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-white"><?php echo htmlspecialchars($siteName); ?></p>
                <p class="text-[10px] uppercase tracking-widest text-[#F4A640] font-semibold">Admin Panel</p>
            </div>
            <button type="button" @click="$store.sidebar.toggle()" class="rounded-lg bg-white/10 p-1.5 text-white hover:bg-white/20 transition-colors">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <!-- Mobile Navigation -->
        <div id="mobile-sidebar-nav" class="flex-1 overflow-y-auto px-2.5 py-3 space-y-4">
            <?php foreach ($sidebarSections as $section): ?>
                <?php $renderSection($section); ?>
            <?php endforeach; ?>
        </div>

        <!-- Mobile Logout -->
        <div class="border-t border-gray-200 dark:border-gray-800 p-2.5">
            <a href="<?php echo appUrlPath(); ?>/admin/logout" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13px] font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 transition-all duration-150">
                <span class="flex h-7 w-7 items-center justify-center rounded-md bg-red-50 dark:bg-red-900/30">
                    <i class="fa-solid fa-right-from-bracket text-sm"></i>
                </span>
                <span>Logout</span>
            </a>
        </div>
    </aside>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const desktopNav = document.getElementById("desktop-sidebar-nav");
        const mobileNav = document.getElementById("mobile-sidebar-nav");

        if (desktopNav) {
            const desktopScroll = localStorage.getItem("sidebar-scroll-desktop");
            if (desktopScroll) {
                desktopNav.scrollTop = parseInt(desktopScroll, 10);
                setTimeout(() => { desktopNav.scrollTop = parseInt(desktopScroll, 10); }, 50);
                setTimeout(() => { desktopNav.scrollTop = parseInt(desktopScroll, 10); }, 150);
            }
            desktopNav.addEventListener("scroll", function() {
                localStorage.setItem("sidebar-scroll-desktop", desktopNav.scrollTop);
            });
        }

        if (mobileNav) {
            const mobileScroll = localStorage.getItem("sidebar-scroll-mobile");
            if (mobileScroll) {
                mobileNav.scrollTop = parseInt(mobileScroll, 10);
                setTimeout(() => { mobileNav.scrollTop = parseInt(mobileScroll, 10); }, 50);
                setTimeout(() => { mobileNav.scrollTop = parseInt(mobileScroll, 10); }, 150);
            }
            mobileNav.addEventListener("scroll", function() {
                localStorage.setItem("sidebar-scroll-mobile", mobileNav.scrollTop);
            });
        }
    });
</script>

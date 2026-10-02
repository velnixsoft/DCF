<?php
/**
 * includes/join_application_helper.php
 * Helper functions for Unified Join Foundation, Join Project, and Job Applications.
 * Author: VELNIX SOFT / Antigravity AI
 * Date: 2026-09-12
 */

if (!function_exists('generate_join_application_no')) {
    /**
     * Generates sequential unique application number based on application type.
     * e.g., JOIN-2026-0001, PROJ-JOIN-2026-0001, JOB-APP-2026-0001
     */
    function generate_join_application_no(PDO $pdo, string $type = 'join_foundation'): string
    {
        $year = date('Y');
        $prefix = match ($type) {
            'join_project' => 'PROJ-JOIN-' . $year . '-',
            'job_application' => 'JOB-APP-' . $year . '-',
            default => 'JOIN-' . $year . '-',
        };

        try {
            $stmt = $pdo->prepare("SELECT application_no FROM join_applications WHERE application_no LIKE ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$prefix . '%']);
            $lastNo = $stmt->fetchColumn();

            if ($lastNo) {
                $lastNum = (int) substr($lastNo, strlen($prefix));
                $nextNum = $lastNum + 1;
            } else {
                $nextNum = 1;
            }

            return $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
        } catch (Throwable $e) {
            return $prefix . str_pad((string)random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        }
    }
}

if (!function_exists('get_active_projects_for_join')) {
    /**
     * Retrieves active projects for project-specific volunteer joining.
     */
    function get_active_projects_for_join(PDO $pdo): array
    {
        try {
            $stmt = $pdo->query("SELECT id, title, description, thumbnail_image, target_amount, raised_amount, status FROM projects WHERE status = 'Active' ORDER BY id DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('get_active_jobs_for_join')) {
    /**
     * Retrieves active job openings for career applications.
     */
    function get_active_jobs_for_join(PDO $pdo): array
    {
        try {
            $stmt = $pdo->query("SELECT id, job_code, title, category, category_custom, description, requirements, responsibilities, location, state, district, block, openings_count, salary_range, job_type, experience_required, min_qualification, last_date FROM job_openings WHERE status = 'active' ORDER BY id DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('get_join_application_types_meta')) {
    /**
     * Returns descriptive metadata for the 3 application streams.
     */
    function get_join_application_types_meta(): array
    {
        return [
            'join_foundation' => [
                'type' => 'join_foundation',
                'title' => 'Join Foundation',
                'subtitle' => 'General NGO Membership & Social Volunteer',
                'badge' => 'Foundation Member',
                'icon' => 'fa-hands-holding-child',
                'color' => 'teal',
                'color_hex' => '#0F8B8D',
                'accent_bg' => 'bg-teal-50',
                'border_color' => 'border-teal-200',
                'default_fee' => 500.00,
                'allows_free' => true,
                'description' => 'Become an official volunteer or honorary foundation member to drive community welfare, education, healthcare, and humanitarian initiatives.'
            ],
            'join_project' => [
                'type' => 'join_project',
                'title' => 'Join a Project',
                'subtitle' => 'Targeted Project & Campaign Volunteering',
                'badge' => 'Project Volunteer',
                'icon' => 'fa-seedling',
                'color' => 'emerald',
                'color_hex' => '#059669',
                'accent_bg' => 'bg-emerald-50',
                'border_color' => 'border-emerald-200',
                'default_fee' => 0.00,
                'allows_free' => true,
                'description' => 'Contribute your time, skills, and energy directly to an active social mission such as Green India, Stationery Bank, or School Chalo Abhiyan.'
            ],
            'job_application' => [
                'type' => 'job_application',
                'title' => 'Apply for Job',
                'subtitle' => 'Careers, Full-time Roles & Paid Internships',
                'badge' => 'Career Opportunity',
                'icon' => 'fa-briefcase',
                'color' => 'amber',
                'color_hex' => '#D97706',
                'accent_bg' => 'bg-amber-50',
                'border_color' => 'border-amber-200',
                'default_fee' => 0.00,
                'allows_free' => true,
                'description' => 'Join our professional team across state, district, or block offices and build a purpose-driven career in the development and NGO sector.'
            ]
        ];
    }
}

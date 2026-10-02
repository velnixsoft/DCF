<?php
// management.php — Public: Our Management Body
// Matches contact.php theme exactly
// -----------------------------------------------------------------
require_once 'config/db.php';
require_once 'includes/functions.php';

// Automatically create table if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `health_programs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT NOT NULL,
        `image` VARCHAR(255) NULL,
        `category` VARCHAR(100) NOT NULL DEFAULT 'Health Awareness',
        `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Seed default programs if table is empty
    $count = $pdo->query("SELECT COUNT(*) FROM health_programs")->fetchColumn();
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO health_programs (title, description, category, image, status) VALUES (?, ?, ?, ?, 'Active')");
        $stmt->execute([
            'Free Medical & Diagnostic Camp',
            'Comprehensive healthcare camps providing free health checkups, blood tests, sugar tests, and consultations with qualified doctors for the general public.',
            'Medical Camp',
            'https://images.unsplash.com/photo-1576091160550-2173dba999ef?q=80&w=600&auto=format&fit=crop'
        ]);
        $stmt->execute([
            'Mental Health & Stress Seminars',
            'Interactive mental wellbeing and stress management sessions held in local colleges and communities to eliminate stigma and teach positive coping tools.',
            'Mental Health',
            'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?q=80&w=600&auto=format&fit=crop'
        ]);
        $stmt->execute([
            'Women Hygiene & Health Drive',
            'Special awareness campaigns focused on women health, nutrition, sanitisation practices, and distribution of wellness kits to underprivileged areas.',
            'Women Wellness',
            'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?q=80&w=600&auto=format&fit=crop'
        ]);
    } else {
        // Update existing default entries to use doctor/medical images
        $pdo->exec("UPDATE health_programs SET image = 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?q=80&w=600&auto=format&fit=crop' WHERE title LIKE '%Medical%' AND (image LIKE '%unsplash.com%' OR image IS NULL)");
        $pdo->exec("UPDATE health_programs SET image = 'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?q=80&w=600&auto=format&fit=crop' WHERE title LIKE '%Mental%' AND (image LIKE '%unsplash.com%' OR image IS NULL)");
        $pdo->exec("UPDATE health_programs SET image = 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?q=80&w=600&auto=format&fit=crop' WHERE title LIKE '%Women%' AND (image LIKE '%unsplash.com%' OR image IS NULL)");
    }
} catch (Exception $e) {
    error_log("Failed to create/seed table health_programs: " . $e->getMessage());
}

// Fetch all active members
$stmt = $pdo->query("
    SELECT * FROM management_body
    WHERE is_active = 1
    ORDER BY sort_order ASC, id ASC
");
$allMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch active health programs
$healthPrograms = [];
try {
    $hpStmt = $pdo->query("SELECT * FROM health_programs WHERE status = 'Active' ORDER BY id DESC");
    $healthPrograms = $hpStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Failed to fetch health programs: " . $e->getMessage());
}

// Group by department (null/empty → 'General')
$groups = [];
foreach ($allMembers as $m) {
    $dept = trim($m['department'] ?? '') ?: 'General';
    $groups[$dept][] = $m;
}

require 'includes/header.php';
?>

<div class="bg-white">

    <!-- ── Hero — same green-50 header style as contact.php ──────── -->
    <div class="bg-green-50 py-16 text-center border-b border-green-100">
        <div class="container mx-auto px-6">
            <span class="inline-block bg-green-100 text-green-700 text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-widest mb-4">
                Leadership
            </span>
            <h1 class="text-4xl md:text-5xl font-extrabold text-gray-800">Our Management Body</h1>
            <p class="mt-3 max-w-2xl mx-auto text-lg text-gray-600">
                Meet the dedicated leaders who guide our mission, uphold our values, and drive meaningful change in communities across India.
            </p>
            <!-- Breadcrumb -->
            <nav class="mt-5 text-sm text-gray-500">
                <a href="index.php" class="hover:text-green-600 transition">Home</a>
                <span class="mx-2 opacity-40">/</span>
                <span class="text-gray-700 font-medium">Management Body</span>
            </nav>

            <!-- Switcher Tabs -->
            <div class="mt-8 flex items-center justify-center">
                <div class="inline-flex p-1 bg-white rounded-2xl shadow-sm border border-green-200">
                    <a href="management.php" class="px-5 py-2 rounded-xl text-xs font-bold bg-green-600 text-white shadow-sm transition flex items-center gap-2">
                        <i class="fa-solid fa-users"></i>
                        <span>Management Body</span>
                    </a>
                    <a href="organization-structure.php" class="px-5 py-2 rounded-xl text-xs font-bold text-gray-600 hover:text-gray-900 transition flex items-center gap-2">
                        <i class="fa-solid fa-sitemap text-teal-600"></i>
                        <span>Organization Structure</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Stats row — same card style as contact info cards ─────── -->
    <div class="container mx-auto px-4 py-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-3xl mx-auto">

            <div class="flex items-start gap-4 p-6 bg-white rounded-xl shadow-md border border-gray-100 text-center justify-center flex-col items-center">
                <div class="bg-green-100 text-green-600 p-3 rounded-full">
                    <i class="fas fa-users fa-lg fa-fw"></i>
                </div>
                <div>
                    <p class="text-3xl font-extrabold text-gray-800"><?= count($allMembers) ?></p>
                    <p class="text-sm text-gray-500 font-medium uppercase tracking-wide mt-0.5">Total Members</p>
                </div>
            </div>

            <div class="flex flex-col items-center p-6 bg-white rounded-xl shadow-md border border-gray-100 text-center">
                <div class="bg-green-100 text-green-600 p-3 rounded-full mb-3">
                    <i class="fas fa-sitemap fa-lg fa-fw"></i>
                </div>
                <div>
                    <p class="text-3xl font-extrabold text-gray-800"><?= count($groups) ?></p>
                    <p class="text-sm text-gray-500 font-medium uppercase tracking-wide mt-0.5">Departments</p>
                </div>
            </div>

            <div class="flex flex-col items-center p-6 bg-white rounded-xl shadow-md border border-gray-100 text-center">
                <div class="bg-green-100 text-green-600 p-3 rounded-full mb-3">
                    <i class="fas fa-award fa-lg fa-fw"></i>
                </div>
                <div>
                    <p class="text-3xl font-extrabold text-gray-800">20+</p>
                    <p class="text-sm text-gray-500 font-medium uppercase tracking-wide mt-0.5">Years Experience</p>
                </div>
            </div>

        </div>
    </div>

    <!-- ── Filter + Members Section ────────────────────────────────── -->
    <?php
    // Build flat members array with dept for JS filtering
    $deptList = array_keys($groups);
    $membersJson = json_encode(array_map(fn($m) => [
        'id'          => $m['id'],
        'name'        => $m['name'],
        'designation' => $m['designation'],
        'department'  => trim($m['department'] ?? '') ?: 'General',
        'phone'       => $m['phone'] ?? '',
        'email'       => $m['email'] ?? '',
        'bio'         => $m['bio'] ?? '',
        'photo'       => $m['photo'] ?? '',
        'fb_url'      => $m['fb_url'] ?? '',
        'linkedin_url'=> $m['linkedin_url'] ?? '',
    ], $allMembers), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
    $deptJson = json_encode(array_values($deptList));
    ?>

    <div class="bg-gray-50 py-14"
         x-data="memberFilter()"
         x-init="init()">

        <?php if (empty($allMembers)): ?>
            <div class="container mx-auto px-4">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 py-24 text-center text-gray-400">
                    <i class="fas fa-users fa-4x mb-4 opacity-30"></i>
                    <p class="text-lg font-medium">Management body information coming soon.</p>
                </div>
            </div>
        <?php else: ?>

        <!-- ── Filter tabs — centered ────────────────────────────── -->
        <div class="container mx-auto px-4 mb-10">
            <div class="flex flex-wrap justify-center gap-2">

                <!-- All tab -->
                <button @click="setFilter('all')"
                        :class="activeFilter === 'all'
                            ? 'bg-green-600 text-white shadow-md shadow-green-500/20'
                            : 'bg-white text-gray-600 border border-gray-200 hover:border-green-400 hover:text-green-600'"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-sm font-semibold transition-all duration-200">
                    <i class="fas fa-th-large text-xs"></i>
                    All
                    <span :class="activeFilter === 'all' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500'"
                          class="text-xs font-bold px-1.5 py-0.5 rounded-full">
                        <?= count($allMembers) ?>
                    </span>
                </button>

                <!-- Department tabs — generated from PHP -->
                <?php foreach ($deptList as $dept): ?>
                <button @click="setFilter('<?= htmlspecialchars(addslashes($dept)) ?>')"
                        :class="activeFilter === '<?= htmlspecialchars(addslashes($dept)) ?>'
                            ? 'bg-green-600 text-white shadow-md shadow-green-500/20'
                            : 'bg-white text-gray-600 border border-gray-200 hover:border-green-400 hover:text-green-600'"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-full text-sm font-semibold transition-all duration-200">
                    <?= htmlspecialchars($dept) ?>
                    <span :class="activeFilter === '<?= htmlspecialchars(addslashes($dept)) ?>' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500'"
                          class="text-xs font-bold px-1.5 py-0.5 rounded-full">
                        <?= count($groups[$dept]) ?>
                    </span>
                </button>
                <?php endforeach; ?>

            </div>
        </div>

        <!-- ── Cards grid — centered, max 4 cols ─────────────────── -->
        <div class="container mx-auto px-4">

            <!-- Visible count -->
            <p class="text-center text-sm text-gray-400 mb-6">
                Showing <span class="font-semibold text-gray-600" x-text="filtered.length"></span> member<span x-show="filtered.length !== 1">s</span>
            </p>

            <!-- Grid — justify-center so fewer cards don't stretch -->
            <div class="flex flex-wrap justify-center gap-6">
                <template x-for="m in filtered" :key="m.id">
                    <div class="group bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden
                                hover:shadow-xl transition-all duration-300 hover:-translate-y-1
                                w-full sm:w-[calc(50%-12px)] lg:w-[calc(33.333%-16px)] xl:w-[calc(25%-18px)]"
                          style="max-width: 300px; min-width: 220px;">

                        <!-- Photo -->
                        <div class="relative overflow-hidden bg-green-50 w-full flex items-center justify-center" style="aspect-ratio: 4/5;">

                            <template x-if="m.photo">
                                <img :src="m.photo" :alt="m.name"
                                     @load="if ($el.naturalWidth > $el.naturalHeight) { $el.style.objectFit = 'contain'; } else { $el.style.objectFit = 'cover'; $el.style.objectPosition = 'center 15%'; }"
                                     class="w-full h-full group-hover:scale-105 transition-transform duration-500"
                                     style="object-fit: cover; object-position: center 15%;">
                            </template>
                            <template x-if="!m.photo">
                                <div class="w-24 h-24 rounded-full bg-green-100 flex items-center justify-center">
                                    <span class="text-green-700 font-extrabold text-4xl" x-text="m.name.charAt(0).toUpperCase()"></span>
                                </div>
                            </template>

                            <!-- Slide-up designation bar -->
                            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-gray-900/80 to-transparent px-4 py-3
                                        translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                                <p class="text-white text-xs font-semibold truncate" x-text="m.designation"></p>
                            </div>

                            <!-- Social icons top-right on hover -->
                            <div class="absolute top-3 right-3 flex flex-col gap-2
                                        opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                <template x-if="m.fb_url">
                                    <a :href="m.fb_url" target="_blank" rel="noopener"
                                       class="w-8 h-8 rounded-full bg-white shadow-md flex items-center justify-center text-blue-600 hover:bg-blue-600 hover:text-white transition-colors duration-200"
                                       @click.stop>
                                        <i class="fab fa-facebook-f text-xs"></i>
                                    </a>
                                </template>
                                <template x-if="m.linkedin_url">
                                    <a :href="m.linkedin_url" target="_blank" rel="noopener"
                                       class="w-8 h-8 rounded-full bg-white shadow-md flex items-center justify-center text-blue-700 hover:bg-blue-700 hover:text-white transition-colors duration-200"
                                       @click.stop>
                                        <i class="fab fa-linkedin-in text-xs"></i>
                                    </a>
                                </template>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="p-5">
                            <h3 class="font-bold text-lg text-gray-800 leading-tight" x-text="m.name"></h3>
                            <p class="text-green-600 text-sm font-semibold mt-0.5" x-text="m.designation"></p>

                            <!-- Department badge -->
                            <span class="inline-block mt-2 bg-green-50 text-green-700 text-xs font-medium px-2.5 py-0.5 rounded-full border border-green-100"
                                  x-text="m.department"></span>

                            <template x-if="m.bio">
                                <p class="text-gray-600 text-sm leading-relaxed mt-2 line-clamp-3" x-text="m.bio"></p>
                            </template>

                            <!-- Contact links -->
                            <template x-if="m.phone || m.email">
                                <div class="mt-4 pt-4 border-t border-gray-100 space-y-1.5">
                                    <template x-if="m.phone">
                                        <a :href="'tel:' + m.phone"
                                           class="flex items-center gap-2 text-sm text-gray-600 hover:text-green-600 transition">
                                            <i class="fas fa-phone fa-xs text-green-500 w-3"></i>
                                            <span x-text="m.phone"></span>
                                        </a>
                                    </template>
                                    <template x-if="m.email">
                                        <a :href="'mailto:' + m.email"
                                           class="flex items-center gap-2 text-sm text-green-600 hover:underline transition">
                                            <i class="fas fa-envelope fa-xs text-green-500 w-3"></i>
                                            <span class="truncate" x-text="m.email"></span>
                                        </a>
                                    </template>
                                </div>
                            </template>
                        </div>

                    </div>
                </template>
            </div>

            <!-- Empty state when filter has 0 results -->
            <div x-show="filtered.length === 0"
                 class="py-20 text-center text-gray-400">
                <i class="fas fa-user-slash fa-3x mb-4 opacity-30"></i>
                <p class="font-medium">No members found in this department.</p>
            </div>

        </div>

        <?php endif; ?>
    </div>

    <script>
    // Members data from PHP
    const MEMBERS_DATA  = <?= $membersJson ?>;

    function memberFilter() {
        return {
            all:          MEMBERS_DATA,
            filtered:     [],
            activeFilter: 'all',

            init() {
                this.filtered = this.all;
            },

            setFilter(dept) {
                this.activeFilter = dept;
                if (dept === 'all') {
                    this.filtered = this.all;
                } else {
                    this.filtered = this.all.filter(m => m.department === dept);
                }
            }
        }
    }

    // Adjust image styling dynamically to prevent clipping of cached images
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.health-prog-img').forEach(function(img) {
            function fixFit() {
                img.style.objectFit = (img.naturalWidth > img.naturalHeight) ? 'cover' : 'contain';
                img.style.backgroundColor = '#ffffff';
                img.style.objectPosition = 'center 30%';
            }
            if (img.complete) {
                fixFit();
            }
            img.addEventListener('load', fixFit);
        });
    });
    </script>


    <!-- ── CTA — exact same as contact.php bottom section ────────── -->
    <div class="bg-green-800">
        <div class="container mx-auto px-4 py-16 text-center mt-8">
            <h2 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight">
                Want to Join Our Team?
            </h2>
            <p class="mt-4 max-w-2xl mx-auto text-lg text-green-100">
                We're always looking for passionate individuals to contribute to our cause and serve communities.
            </p>

            <div class="mt-8 flex flex-row items-center justify-center gap-3">
                <a href="volunteer-register.php"
                   class="inline-flex items-center justify-center bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-5 md:px-8 rounded-full shadow-lg transition transform hover:scale-105 active:scale-95 text-sm md:text-base">
                    <i class="fas fa-hands-helping mr-2"></i>
                    <span>Become a Volunteer</span>
                </a>
                <a href="contact.php"
                   class="inline-flex items-center justify-center bg-white hover:bg-gray-100 text-green-800 font-bold py-3 px-5 md:px-8 rounded-full shadow-lg transition transform hover:scale-105 active:scale-95 text-sm md:text-base whitespace-nowrap">
                    <span>Contact Us</span>
                </a>
            </div>
        </div>
    </div>

</div>

<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .line-clamp-4 {
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<?php require 'includes/footer.php'; ?>
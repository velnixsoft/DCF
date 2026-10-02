<?php
if (php_sapi_name() === 'cli-server') {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = urldecode($uri);
    if ($uri !== '/' && $uri !== '/index.php' && $uri !== '') {
        if ($uri === '/about') {
            $target = 'about-us.php';
        } else {
            $target = ltrim($uri, '/') . '.php';
        }
        $php_file = __DIR__ . '/' . $target;
        if (file_exists($php_file)) {
            $_SERVER['SCRIPT_NAME'] = '/' . $target;
            $_SERVER['PHP_SELF'] = '/' . $target;
            $_SERVER['SCRIPT_FILENAME'] = $php_file;
            chdir(__DIR__);
            require $php_file;
            exit;
        }
        if (strncmp($uri, '/admin/', 7) === 0) {
            $admin_target = substr($uri, 7);
            $admin_target_underscore = str_replace('-', '_', $admin_target);
            $php_file = __DIR__ . '/admin/' . $admin_target . '.php';
            $php_file_underscore = __DIR__ . '/admin/' . $admin_target_underscore . '.php';
            if (file_exists($php_file)) {
                $chosen = $php_file;
                $script_name = '/admin/' . $admin_target . '.php';
            } elseif (file_exists($php_file_underscore)) {
                $chosen = $php_file_underscore;
                $script_name = '/admin/' . $admin_target_underscore . '.php';
            } else {
                $chosen = null;
            }
            if ($chosen) {
                $_SERVER['SCRIPT_NAME'] = $script_name;
                $_SERVER['PHP_SELF'] = $script_name;
                $_SERVER['SCRIPT_FILENAME'] = $chosen;
                chdir(dirname($chosen));
                require $chosen;
                exit;
            }
        }
    }
}

require 'includes/header.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$slides_raw = $pdo->query("SELECT * FROM sliders WHERE is_active = 1 ORDER BY priority ASC, id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$slides_json = htmlspecialchars(json_encode($slides_raw), ENT_QUOTES, 'UTF-8');

$about_desc = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'about_desc'")->fetchColumn();
$about_image = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'about_image'")->fetchColumn();

$totalDonation = $pdo->query("SELECT SUM(amount) FROM donations WHERE payment_status = 'Success'")->fetchColumn() ?: 0;
$activeVolunteers = $pdo->query("SELECT COUNT(*) FROM volunteers WHERE status = 'Active'")->fetchColumn() ?: 0;
$ongoingProjects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'Active'")->fetchColumn() ?: 0;

$projects = $pdo->query("SELECT * FROM projects WHERE status = 'Active' ORDER BY created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

$galleryImages = $pdo->query("SELECT * FROM gallery WHERE type='image' ORDER BY id DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);

$sponsors = $pdo->query("SELECT * FROM sponsors ORDER BY priority ASC")->fetchAll(PDO::FETCH_ASSOC);

$homeTheme = strtolower((string)($settings['home_theme'] ?? 'classic'));
if (!in_array($homeTheme, ['classic', 'modern','modern1','modern2','modern3'], true)) $homeTheme = 'classic';

$birthdayMembers = [];
try {
    $stmt = $pdo->query("
        SELECT full_name
        FROM members
        WHERE status = 'Active'
          AND dob IS NOT NULL
          AND DATE_FORMAT(dob, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
        ORDER BY full_name ASC
        LIMIT 10
    ");
    $birthdayMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $birthdayMembers = [];
}
?>

<?php if ($homeTheme === 'modern'): ?>
    <?php require __DIR__ . '/themes/home_theme1_final.php'; ?>
    <?php require 'includes/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>
<?php if ($homeTheme === 'modern1'): ?>
    <?php require __DIR__ . '/themes/home_theme2_final.php'; ?>
    <?php require 'includes/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>
<?php if ($homeTheme === 'modern2'): ?>
    <?php require __DIR__ . '/themes/home_theme3_final.php'; ?>
    <?php require 'includes/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>
<?php if ($homeTheme === 'modern3'): ?>
    <?php require __DIR__ . '/themes/home_theme1_v3.php'; ?>
    <?php require 'includes/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<style>
    @keyframes scroll {
        0% {
            transform: translateX(0);
        }

        100% {
            transform: translateX(-50%);
        }
    }

    .animate-scroll {
        animation: scroll 60s linear infinite;
    }
</style>

<div class="bg-white">

    <section class="relative h-[550px] md:h-[650px] overflow-hidden"
        x-data="{ 
                 activeSlide: 0, 
                 slides: [],
                 next() { if (this.slides.length > 1) this.activeSlide = (this.activeSlide + 1) % this.slides.length },
                 prev() { if (this.slides.length > 1) this.activeSlide = (this.activeSlide - 1 + this.slides.length) % this.slides.length }
             }"
        x-init="
                slides = JSON.parse('<?php echo $slides_json; ?>');
                if (slides.length > 1) {
                    setInterval(() => next(), 5000)
                }
             ">

        <template x-for="(slide, index) in slides" :key="index">
            <div class="absolute inset-0"
                x-show="activeSlide === index"
                x-transition:enter="transition ease-out duration-[2000ms]"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-[2000ms]"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0">

                <img :src="slide.image_path"
                    class="w-full h-full object-cover transition-transform duration-[6000ms] ease-linear"
                    :class="{ 'scale-110': activeSlide === index, 'scale-100': activeSlide !== index }"
                    alt="Slider Image">

                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-black/20"></div>

                <div class="absolute inset-0 flex items-center justify-center text-center px-4">
                    <div class="max-w-3xl"
                        x-show="activeSlide === index"
<?php
require 'includes/header.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$slides_raw = $pdo->query("SELECT * FROM sliders WHERE is_active = 1 ORDER BY priority ASC, id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$slides_json = htmlspecialchars(json_encode($slides_raw), ENT_QUOTES, 'UTF-8');

$about_desc = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'about_desc'")->fetchColumn();
$about_image = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'about_image'")->fetchColumn();

$totalDonation = $pdo->query("SELECT SUM(amount) FROM donations WHERE payment_status = 'Success'")->fetchColumn() ?: 0;
$activeVolunteers = $pdo->query("SELECT COUNT(*) FROM volunteers WHERE status = 'Active'")->fetchColumn() ?: 0;
$ongoingProjects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'Active'")->fetchColumn() ?: 0;

$projects = $pdo->query("SELECT * FROM projects WHERE status = 'Active' ORDER BY created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

$galleryImages = $pdo->query("SELECT * FROM gallery WHERE type='image' ORDER BY id DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);

$sponsors = $pdo->query("SELECT * FROM sponsors ORDER BY priority ASC")->fetchAll(PDO::FETCH_ASSOC);

$homeTheme = strtolower((string)($settings['home_theme'] ?? 'classic'));
if (!in_array($homeTheme, ['classic', 'modern','modern1','modern2','modern3'], true)) $homeTheme = 'classic';

$birthdayMembers = [];
try {
    $stmt = $pdo->query("
        SELECT full_name
        FROM members
        WHERE status = 'Active'
          AND dob IS NOT NULL
          AND DATE_FORMAT(dob, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
        ORDER BY full_name ASC
        LIMIT 10
    ");
    $birthdayMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $birthdayMembers = [];
}
?>

<?php if ($homeTheme === 'modern'): ?>
    <?php require __DIR__ . '/themes/home_theme1_final.php'; ?>
    <?php require 'includes/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>
<?php if ($homeTheme === 'modern1'): ?>
    <?php require __DIR__ . '/themes/home_theme2_final.php'; ?>
    <?php require 'includes/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>
<?php if ($homeTheme === 'modern2'): ?>
    <?php require __DIR__ . '/themes/home_theme3_final.php'; ?>
    <?php require 'includes/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>
<?php if ($homeTheme === 'modern3'): ?>
    <?php require __DIR__ . '/themes/home_theme1_v3.php'; ?>
    <?php require 'includes/footer.php'; ?>
    <?php exit; ?>
<?php endif; ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<style>
    @keyframes scroll {
        0% {
            transform: translateX(0);
        }

        100% {
            transform: translateX(-50%);
        }
    }

    .animate-scroll {
        animation: scroll 60s linear infinite;
    }
</style>

<div class="bg-white">

    <section class="relative h-[550px] md:h-[650px] overflow-hidden"
        x-data="{ 
                 activeSlide: 0, 
                 slides: [],
                 next() { if (this.slides.length > 1) this.activeSlide = (this.activeSlide + 1) % this.slides.length },
                 prev() { if (this.slides.length > 1) this.activeSlide = (this.activeSlide - 1 + this.slides.length) % this.slides.length }
             }"
        x-init="
                slides = JSON.parse('<?php echo $slides_json; ?>');
                if (slides.length > 1) {
                    setInterval(() => next(), 5000)
                }
             ">

        <template x-for="(slide, index) in slides" :key="index">
            <div class="absolute inset-0"
                x-show="activeSlide === index"
                x-transition:enter="transition ease-out duration-[2000ms]"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-[2000ms]"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0">

                <img :src="slide.image_path"
                    class="w-full h-full object-cover transition-transform duration-[6000ms] ease-linear"
                    :class="{ 'scale-110': activeSlide === index, 'scale-100': activeSlide !== index }"
                    alt="Slider Image">

                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-black/20"></div>

                <div class="absolute inset-0 flex items-center justify-center text-center px-4">
                    <div class="max-w-3xl"
                        x-show="activeSlide === index"
                        x-transition:enter="transition ease-out duration-1000 delay-500"
                        x-transition:enter-start="opacity-0 translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0">

                        <h2 class="text-4xl md:text-6xl font-bold text-white mb-6 leading-tight" x-text="slide.title"></h2>
                        <p class="text-lg md:text-xl text-gray-200 mb-8" x-text="slide.subtitle"></p>
                        <div class="flex gap-4 justify-center">
                            <a href="projects" class="bg-[#1070B0] hover:bg-[#0d5b90] text-white font-bold py-3 px-8 rounded-full transition shadow-lg">View Projects</a>
                            <a href="volunteer-register" class="bg-[#F0A010] hover:bg-[#d48b0a] text-white font-bold py-3 px-8 rounded-full transition shadow-lg">Join Us</a>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="slides.length === 0">
            <div class="absolute inset-0 bg-[#1F2937] flex items-center justify-center text-white">
                <p>Slider is being updated.</p>
            </div>
        </template>
        <template x-if="slides.length > 1">
            <div>
                <button @click="prev()" class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/20 p-3 rounded-full"><i class="fas fa-chevron-left text-xl text-white"></i></button>
                <button @click="next()" class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/20 p-3 rounded-full"><i class="fas fa-chevron-right text-xl text-white"></i></button>
            </div>
        </template>
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex space-x-2">
            <template x-for="(slide, i) in slides" :key="i">
                <button @click="activeSlide = i"
                    class="w-3 h-3 rounded-full transition-all duration-300"
                    :class="activeSlide === i ? 'bg-[#F0A010] w-8' : 'bg-white/50 hover:bg-white'"></button>
            </template>
        </div>
    </section>

    <?php if (!empty($birthdayMembers)): ?>
        <div class="bg-[#fffcf5] border-y border-[#fef5e7]">
            <div class="container mx-auto px-6 py-4">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                    <p class="font-extrabold text-[#d48b0a]">Happy Birthday to our members!</p>
                    <p class="text-sm text-[#1F2937]">
                        <?php
                        $names = array_map(fn($m) => (string)($m['full_name'] ?? ''), $birthdayMembers);
                        $names = array_filter($names, fn($n) => trim($n) !== '');
                        echo htmlspecialchars(implode(', ', $names));
                        ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-[#1070B0] text-white py-4 font-semibold overflow-hidden whitespace-nowrap relative">
        <div class="animate-marquee inline-block">
            <span class="mx-8">Together, We Can Make a Difference</span>
            <span class="text-[#F0A010] mx-4">•</span>
            <span class="mx-8">Join Our Mission Today</span>
            <span class="text-[#F0A010] mx-4">•</span>
            <span class="mx-8">Your Support Creates Hope</span>
            <span class="text-[#F0A010] mx-4">•</span>
            <span class="mx-8">Donate Now</span>
            <span class="text-[#F0A010] mx-4">•</span>
            <span class="mx-8">Together, We Can Make a Difference</span>
            <span class="text-[#F0A010] mx-4">•</span>
            <span class="mx-8">Join Our Mission Today</span>
            <span class="text-[#F0A010] mx-4">•</span>
            <span class="mx-8">Your Support Creates Hope</span>
            <span class="text-[#F0A010] mx-4">•</span>
            <span class="mx-8">Donate Now</span>
            <span class="text-[#F0A010] mx-4">•</span>
        </div>
    </div>

    <section class="bg-white border-b border-gray-100">
        <div class="container mx-auto px-6 py-10 max-w-5xl text-center">
            <h2 class="text-2xl md:text-3xl font-extrabold text-[#1070B0]">Seed Council for Sustainable Economy, Employment, Development, Education and Skill Growth</h2>
            <p class="mt-4 text-[#4B5563] leading-relaxed">
                <?php echo htmlspecialchars((string)($settings['site_name'] ?? 'Seed Council')); ?> supports sustainable development through community projects, employment initiatives, education outreach, skill training, volunteer engagement, and public service programs designed to strengthen the economy and local opportunity.
            </p>
        </div>
    </section>

    <section class="py-16 md:py-24 bg-white overflow-hidden"
        x-data="{ visible: false }"
        x-intersect.once.threshold.0.3="visible = true">
            if (sponsorCount > 0) {
                new Swiper('.sponsor-swiper', {
                    loop: sponsorCount > 2,
                    grabCursor: true,
                    slidesPerView: 2,
                    spaceBetween: 20,
                    autoplay: {
                        delay: 3000,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true
                    },
                    breakpoints: {
                        640: {
                            slidesPerView: 3,
                            spaceBetween: 30
                        },
                        768: {
                            slidesPerView: 4,
                            spaceBetween: 40
                        },
                        1024: {
                            slidesPerView: 5,
                            spaceBetween: 50
                        },
                    }
                });
            }
        });

        document.addEventListener('alpine:init', () => {
            Alpine.data('statsCounter', () => ({

                donationCount: 0,
                volunteerCount: 0,
                projectCount: 0,

                targets: {
                    donation: <?php echo (int)$totalDonation; ?>,
                    volunteer: <?php echo (int)$activeVolunteers; ?>,
                    project: <?php echo (int)$ongoingProjects; ?>
                },

                format(value) {
                    return new Intl.NumberFormat('en-IN').format(Math.floor(value));
                },

                animate(key, endValue) {
                    if (endValue === 0) return;

                    const duration = 2000;
                    const startValue = 0;
                    const startTime = performance.now();

                    const step = (currentTime) => {
                        const elapsed = currentTime - startTime;
                        const progress = Math.min(elapsed / duration, 1);
                        const ease = 1 - Math.pow(1 - progress, 3);

                        this[key] = startValue + (endValue - startValue) * ease;

                        if (progress < 1) {
                            requestAnimationFrame(step);
                        } else {
                            this[key] = endValue;
                        }
                    };

                    requestAnimationFrame(step);
                },

                startCounters() {
                    this.animate('donationCount', this.targets.donation);
                    this.animate('volunteerCount', this.targets.volunteer);
                    this.animate('projectCount', this.targets.project);
                }
            }));
        });
    </script>
    <?php require 'includes/footer.php'; ?>

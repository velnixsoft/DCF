<?php
require 'includes/header.php';

$id = $_GET['id'] ?? 0;
if (!$id) {
    echo "<p class='text-center py-20'>Invalid Project ID.</p>";
    require 'includes/footer.php';
    exit;
}

$project = $pdo->query("SELECT * FROM projects WHERE id = $id")->fetch();
if (!$project) {
    echo "<p class='text-center py-20'>Project not found.</p>";
    require 'includes/footer.php';
    exit;
}

$gallery = $pdo->query("SELECT * FROM project_gallery WHERE project_id = $id")->fetchAll();

$percent_raw = ($project['target_amount'] > 0) ? round(($project['raised_amount'] / $project['target_amount']) * 100) : 0;
$percent_display = min($percent_raw, 100);
?>

<?php
$galleryPaths = [];
foreach ($gallery as $img) {
    $galleryPaths[] = $img['image_path'];
}
?>

<div class="bg-white min-h-screen" x-data="{ 
    lightboxOpen: false, 
    lightboxIndex: 0, 
    galleryList: <?php echo json_encode($galleryPaths); ?>,
    downloadModalOpen: false,
    downloadCardNo: '',
    downloadError: '',
    openLightbox(index) {
        this.lightboxIndex = index;
        this.lightboxOpen = true;
    },
    prevImage() {
        if (this.galleryList.length === 0) return;
        this.lightboxIndex = (this.lightboxIndex - 1 + this.galleryList.length) % this.galleryList.length;
    },
    nextImage() {
        if (this.galleryList.length === 0) return;
        this.lightboxIndex = (this.lightboxIndex + 1) % this.galleryList.length;
    },
    openDownloadModal() {
        this.downloadCardNo = '';
        this.downloadError = '';
        this.downloadModalOpen = true;
    },
    triggerDownload(mode) {
        let card = this.downloadCardNo.trim();
        if (!card) {
            this.downloadError = 'Please enter your Health Card Number or Mobile Number.';
            return;
        }
        this.downloadError = '';
        if (mode === 'download') {
            window.open('download-health-card.php?card=' + encodeURIComponent(card) + '&mode=download', '_blank');
        } else {
            window.location.href = 'verify-health-card.php?card=' + encodeURIComponent(card);
        }
        this.downloadModalOpen = false;
    }
}">

    <div class="bg-green-50 pt-16 pb-12 border-b border-green-100">
        <div class="container mx-auto px-6 text-center">
            <h1 class="text-4xl md:text-5xl font-extrabold text-green-800 tracking-tight"><?php echo htmlspecialchars($project['title']); ?></h1>
        </div>
    </div>

    <div class="container mx-auto px-4 py-16">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

            <div class="lg:col-span-2 space-y-8">
                <div class="rounded-xl shadow-lg overflow-hidden border border-gray-100">
                    <?php
                    if (!empty($project['video_url'])) {
                        $video_id = '';
                        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $project['video_url'], $match)) {
                            $video_id = $match[1];
                        }
                        echo "<div class='aspect-video'><iframe class='w-full h-full' src='https://www.youtube.com/embed/{$video_id}' frameborder='0' allowfullscreen></iframe></div>";
                    } else {
                        echo "<img src='{$project['thumbnail_image']}' class='w-full object-cover aspect-video'>";
                    }
                    ?>
                </div>

                <?php if (count($gallery) > 0): ?>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">Project Gallery</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                            <?php $gIndex = 0; foreach ($gallery as $img): ?>
                                <div @click="openLightbox(<?php echo $gIndex; ?>)" class="cursor-pointer overflow-hidden rounded-lg shadow-md group">
                                    <img src="<?php echo $img['image_path']; ?>" class="w-full h-full aspect-square object-cover transform group-hover:scale-110 transition duration-300">
                                </div>
                            <?php $gIndex++; endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-3">About the Project</h3>
                    <div class="text-gray-600 leading-relaxed space-y-4">
                        <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                    </div>
                </div>

                <!-- ── Dedicated Health Card Module 33 Section ── -->
                <div class="bg-gradient-to-br from-teal-500/10 via-emerald-500/5 to-cyan-500/10 rounded-2xl p-6 sm:p-8 border border-teal-200/80 shadow-sm relative overflow-hidden">
                    <div class="absolute -right-8 -bottom-8 opacity-10 text-teal-800 pointer-events-none">
                        <i class="fa-solid fa-id-card-clip text-9xl"></i>
                    </div>

                    <div class="relative z-10">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-600 text-white text-xs font-bold uppercase tracking-wider shadow-xs">
                                <i class="fa-solid fa-id-card-clip"></i>
                                Healthcare Assistance • Module 33
                            </span>
                            <span class="text-xs font-semibold text-teal-800 bg-teal-100/80 px-3 py-1 rounded-full">
                                Subsidized Medical Treatment
                            </span>
                        </div>

                        <h3 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight mb-2">
                            Apply / Renew / Download <span class="text-teal-700">Health Card</span>
                        </h3>
                        <p class="text-sm text-gray-600 leading-relaxed mb-6 max-w-2xl">
                            Looking for subsidized doctor consultations, discounts on lab tests, medicines, and hospital concessions? Our NGO Health Card provides affordable healthcare protection for you and your family across verified network hospitals.
                        </p>

                        <!-- Key Benefits Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 mb-6">
                            <div class="bg-white/90 backdrop-blur-xs p-3.5 rounded-xl border border-teal-100 flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center shrink-0 text-sm font-bold">
                                    <i class="fa-solid fa-percent"></i>
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-gray-900">Up to 50% Concession</h5>
                                    <p class="text-[11px] text-gray-500 leading-tight mt-0.5">Discounts on OPD, pathology tests, medicines & IPD.</p>
                                </div>
                            </div>

                            <div class="bg-white/90 backdrop-blur-xs p-3.5 rounded-xl border border-teal-100 flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 text-sm font-bold">
                                    <i class="fa-solid fa-users"></i>
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-gray-900">Family Coverage</h5>
                                    <p class="text-[11px] text-gray-500 leading-tight mt-0.5">Include family members with instant Digital QR ID.</p>
                                </div>
                            </div>

                            <div class="bg-white/90 backdrop-blur-xs p-3.5 rounded-xl border border-teal-100 flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-cyan-100 text-cyan-700 flex items-center justify-center shrink-0 text-sm font-bold">
                                    <i class="fa-solid fa-hospital"></i>
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-gray-900">Partner Hospitals</h5>
                                    <p class="text-[11px] text-gray-500 leading-tight mt-0.5">Direct cashless & discount referral network.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons Row -->
                        <div class="flex flex-wrap items-center gap-3">
                            <a href="apply-health-card.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-sm shadow-md hover:shadow-lg transition">
                                <i class="fa-solid fa-plus-circle"></i>
                                <span>Apply New Card</span>
                            </a>

                            <a href="apply-health-card.php?renew_card=1" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm shadow-md hover:shadow-lg transition">
                                <i class="fa-solid fa-arrows-rotate"></i>
                                <span>Renew Card</span>
                            </a>

                            <button type="button" @click="openDownloadModal()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gray-900 hover:bg-black text-white font-bold text-sm shadow-md hover:shadow-lg transition">
                                <i class="fa-solid fa-file-arrow-down text-teal-400"></i>
                                <span>Download Card PDF</span>
                            </button>

                            <a href="healthcare-directory.php" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white hover:bg-gray-50 text-gray-700 font-semibold text-sm border border-gray-200 transition">
                                <i class="fa-solid fa-map-location-dot text-teal-600"></i>
                                <span>Network Directory</span>
                            </a>
                        </div>
                    </div>
                </div>

            </div>

            <div class="lg:col-span-1 space-y-8">
                <div class="bg-gray-50 p-6 rounded-xl border border-gray-200">
                    <h3 class="text-xl font-bold text-gray-800">Fundraising Progress</h3>
                    <div class="mt-4">
                        <div class="flex justify-between text-sm font-medium text-gray-600">
                            <span>Raised: ₹<?php echo number_format($project['raised_amount']); ?></span>
                            <span class="text-green-600 font-bold"><?php echo $percent_raw; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3 mt-1 overflow-hidden">
                            <div class="bg-green-500 h-3 rounded-full" style="width: <?php echo $percent_display; ?>%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1 text-right">Target: ₹<?php echo number_format($project['target_amount']); ?></p>
                    </div>
                </div>

                <?php if ($project['status'] !== 'Completed'): ?>
                <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 text-center sticky top-28 space-y-5">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Donate for this Cause</h3>
                        <p class="text-xs text-gray-500 mt-1">Support this community initiative with your contribution.</p>
                    </div>

                    <?php if (!empty($project['upi_qr_image'])): ?>
                        <div class="bg-gray-100 p-2 rounded-lg inline-block">
                            <img src="<?php echo $project['upi_qr_image']; ?>" class="w-48 h-auto object-contain">
                        </div>
                    <?php endif; ?>

                    <a href="donate.php?project_id=<?php echo $project['id']; ?>" class="block w-full bg-amber-500 text-white font-bold py-3 rounded-lg hover:bg-amber-600 transition shadow">
                        <i class="fa-solid fa-heart mr-1.5"></i> Donate Now
                    </a>

                    <!-- Health Card Quick Actions Widget -->
                    <div class="pt-5 border-t border-gray-100 text-left bg-teal-50/50 p-4 rounded-xl border border-teal-100/80">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-teal-600 text-white flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-id-card-clip"></i>
                                </span>
                                <h4 class="text-xs font-black uppercase tracking-wider text-teal-950">Swasthya Health Card</h4>
                            </div>
                            <span class="text-[10px] bg-teal-200/60 text-teal-800 font-bold px-2 py-0.5 rounded-full">Module 33</span>
                        </div>
                        
                        <p class="text-[11px] text-gray-600 mb-3 leading-relaxed">
                            Need medical concessions or subsidized treatment? Apply, renew, or download your official NGO Health Card here.
                        </p>

                        <div class="space-y-2">
                            <div class="grid grid-cols-2 gap-2">
                                <a href="apply-health-card.php" class="inline-flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs transition text-center shadow-xs">
                                    <i class="fa-solid fa-plus text-[10px]"></i>
                                    <span>Apply Card</span>
                                </a>
                                <a href="apply-health-card.php?renew_card=1" class="inline-flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs transition text-center shadow-xs">
                                    <i class="fa-solid fa-arrows-rotate text-[10px]"></i>
                                    <span>Renew Card</span>
                                </a>
                            </div>

                            <button type="button" @click="openDownloadModal()" class="w-full inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-lg bg-gray-900 hover:bg-black text-white font-bold text-xs transition text-center shadow-xs">
                                <i class="fa-solid fa-file-arrow-down text-teal-400 text-xs"></i>
                                <span>Download Health Card (PDF)</span>
                            </button>

                            <a href="healthcare-directory.php" class="w-full inline-flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-lg bg-white hover:bg-gray-50 text-gray-700 font-semibold text-[11px] border border-gray-200 transition text-center">
                                <i class="fa-solid fa-hospital text-teal-600 text-[10px]"></i>
                                <span>Search Network Hospitals & Doctors</span>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div x-show="lightboxOpen" 
         @click="lightboxOpen = false" 
         @keydown.window.escape="lightboxOpen = false"
         @keydown.window.arrow-left="prevImage()"
         @keydown.window.arrow-right="nextImage()"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm" 
         x-cloak>
         
        <!-- Left Arrow Button -->
        <button type="button" @click.stop="prevImage()" class="absolute left-4 md:left-8 text-white/70 hover:text-white p-3 rounded-full hover:bg-white/10 transition z-50">
            <svg class="w-8 h-8 md:w-12 md:h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
            </svg>
        </button>

        <div class="max-w-4xl max-h-[85vh] relative flex items-center justify-center" @click.stop>
            <img :src="galleryList[lightboxIndex]" class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl">
            <!-- Image index display -->
            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/60 px-4 py-1.5 rounded-full text-white text-xs font-bold font-mono">
                <span x-text="lightboxIndex + 1"></span> / <span x-text="galleryList.length"></span>
            </div>
        </div>

        <!-- Right Arrow Button -->
        <button type="button" @click.stop="nextImage()" class="absolute right-4 md:right-8 text-white/70 hover:text-white p-3 rounded-full hover:bg-white/10 transition z-50">
            <svg class="w-8 h-8 md:w-12 md:h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
            </svg>
        </button>

        <!-- Close button top-right -->
        <button type="button" @click="lightboxOpen = false" class="absolute top-4 right-4 text-white/70 hover:text-white p-2 hover:bg-white/10 rounded-full transition z-50">
            <svg class="w-6 h-6 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <!-- ── Quick Download / Verify Health Card Modal ── -->
    <div x-show="downloadModalOpen" 
         @keydown.window.escape="downloadModalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-xs" 
         x-cloak>
        <div @click.away="downloadModalOpen = false" 
             class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 sm:p-7 relative border border-gray-100 transform transition-all">
            
            <!-- Close Button -->
            <button type="button" @click="downloadModalOpen = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 p-1.5 rounded-full hover:bg-gray-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>

            <div class="text-center mb-5">
                <div class="w-12 h-12 bg-teal-100 text-teal-700 rounded-2xl flex items-center justify-center mx-auto mb-3 text-xl shadow-inner">
                    <i class="fa-solid fa-id-card-clip"></i>
                </div>
                <h3 class="text-xl font-black text-gray-900 tracking-tight">Download Health Card</h3>
                <p class="text-xs text-gray-500 mt-1">Enter your Health Card Number to stream or download your official PDF card.</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Health Card Number</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-hashtag text-xs"></i>
                        </span>
                        <input type="text" 
                               x-model="downloadCardNo" 
                               @keydown.enter="triggerDownload('download')"
                               placeholder="e.g., HC-2026-0001" 
                               class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-500 font-mono text-sm uppercase">
                    </div>
                    <p x-show="downloadError" x-text="downloadError" class="text-xs text-rose-600 font-medium mt-1.5"></p>
                </div>

                <div class="grid grid-cols-2 gap-2.5 pt-1">
                    <button type="button" 
                            @click="triggerDownload('download')" 
                            class="inline-flex items-center justify-center gap-1.5 py-2.5 px-4 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs shadow-md transition">
                        <i class="fa-solid fa-file-arrow-down"></i>
                        <span>Download PDF</span>
                    </button>
                    <button type="button" 
                            @click="triggerDownload('view')" 
                            class="inline-flex items-center justify-center gap-1.5 py-2.5 px-4 rounded-xl bg-gray-900 hover:bg-black text-white font-bold text-xs shadow-md transition">
                        <i class="fa-solid fa-shield-halved text-teal-400"></i>
                        <span>Verify Online</span>
                    </button>
                </div>

                <div class="pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                    <span>Don't have a card yet?</span>
                    <a href="apply-health-card.php" class="text-teal-700 font-bold hover:underline">Apply for Card &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-green-800">
        <div class="container mx-auto px-6 py-16 text-center">
            <h2 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight">
                Ready to Make a Difference?
            </h2>
            <p class="mt-4 max-w-2xl mx-auto text-lg text-green-100">
                Your support can turn our vision into reality. Join us in our mission to create a better world.
            </p>

            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">

                <a href="donate.php"
                    class="inline-flex items-center justify-center bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-6 md:px-8 rounded-full shadow-lg transition transform hover:scale-105 active:scale-95 text-base w-full sm:w-auto">
                    <i class="fas fa-heart mr-2"></i>
                    <span>Donate Now</span>
                </a>

                <a href="volunteer-register.php"
                    class="inline-flex items-center justify-center bg-white hover:bg-gray-100 text-green-800 font-bold py-3 px-6 md:px-8 rounded-full shadow-lg transition transform hover:scale-105 active:scale-95 text-base w-full sm:w-auto">
                    <span>Join as Volunteer</span>
                </a>

            </div>
        </div>
    </div>

</div>

<?php require 'includes/footer.php'; ?>
<?php
require 'includes/header.php';
$projects = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$allCount = count($projects);
$activeCount = 0;
$completedCount = 0;
foreach ($projects as $p) {
    if (strtolower($p['status']) === 'active') {
        $activeCount++;
    } elseif (strtolower($p['status']) === 'completed') {
        $completedCount++;
    }
}
?>

<div class="bg-white min-h-screen" x-data="{ tab: 'all', allCount: <?php echo $allCount; ?>, activeCount: <?php echo $activeCount; ?>, completedCount: <?php echo $completedCount; ?> }">

    <div class="bg-[#FFF8F1] py-16 text-center border-b border-[#FEECDC]">
        <div class="container mx-auto px-6">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-[#0F8B8D] tracking-tight">Our Initiatives</h1>
            <p class="mt-4 max-w-3xl mx-auto text-lg text-[#4B5563]">
                Explore the ongoing and completed projects that are making a real impact in our communities.
            </p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-10">

        <!-- Flagship Healthcare Initiative Banner (Apply / Renew / Download Health Card) -->
        <div class="mb-12 rounded-3xl bg-gradient-to-r from-[#0F8B8D] via-[#0C6E70] to-[#0A5354] p-6 md:p-8 text-white shadow-xl relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 relative z-10">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-2xl flex-shrink-0 border border-white/30 text-emerald-300 shadow-inner">
                        <i class="fa-solid fa-hand-holding-medical"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-400/20 text-emerald-200 border border-emerald-300/30 text-[10px] font-black uppercase tracking-wider">
                                Flagship Healthcare Initiative
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full bg-amber-400/20 text-amber-200 border border-amber-300/30 text-[10px] font-black uppercase tracking-wider">
                                100+ Partner Hospitals
                            </span>
                        </div>
                        <h2 class="text-xl md:text-2xl font-black text-white mt-1.5">
                            Swasthya Seva & Digital Health Card Project
                        </h2>
                        <p class="text-xs md:text-sm text-teal-100/90 mt-1 max-w-2xl leading-relaxed">
                            Avail subsidized medical treatments, free eye & dental checkups, surgery concessions, and generic medicine discounts across our empaneled healthcare network.
                        </p>
                    </div>
                </div>

                <!-- Direct Health Card Actions: Apply / Renew / Download -->
                <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto justify-start lg:justify-end">
                    <a href="apply-health-card.php" 
                       class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-white hover:bg-gray-100 text-[#0F8B8D] text-xs font-black uppercase tracking-wider shadow-lg transition transform hover:-translate-y-0.5">
                        <i class="fa-solid fa-plus text-sm"></i>
                        <span>Apply Health Card</span>
                    </a>

                    <a href="apply-health-card.php?renew_card=1" 
                       class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-xl bg-[#F4A640] hover:bg-[#D98E2B] text-white text-xs font-black uppercase tracking-wider shadow-lg transition transform hover:-translate-y-0.5">
                        <i class="fa-solid fa-arrows-rotate text-sm"></i>
                        <span>Renew Card</span>
                    </a>

                    <a href="member-dashboard.php" 
                       class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-white/15 hover:bg-white/25 border border-white/30 text-white text-xs font-bold transition">
                        <i class="fa-solid fa-file-arrow-down text-sm"></i>
                        <span>Download Card</span>
                    </a>

                    <a href="healthcare-directory.php" 
                       class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-teal-100 text-xs font-semibold transition">
                        <i class="fa-solid fa-hospital-user text-sm"></i>
                        <span>Hospital Directory</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="flex justify-center mb-12 border-b border-gray-200">
            <nav class="flex space-x-2" aria-label="Tabs">
                <button @click="tab = 'all'"
                    :class="tab === 'all' ? 'border-[#0F8B8D] text-[#0F8B8D]' : 'border-transparent text-[#4B5563] hover:text-[#0F8B8D]'"
                    class="px-5 py-3 font-bold border-b-2 transition">
                    All Projects
                </button>
                <button @click="tab = 'active'"
                    :class="tab === 'active' ? 'border-[#0F8B8D] text-[#0F8B8D]' : 'border-transparent text-[#4B5563] hover:text-[#0F8B8D]'"
                    class="px-5 py-3 font-bold border-b-2 transition">
                    Active
                </button>
                <button @click="tab = 'completed'"
                    :class="tab === 'completed' ? 'border-[#0F8B8D] text-[#0F8B8D]' : 'border-transparent text-[#4B5563] hover:text-[#0F8B8D]'"
                    class="px-5 py-3 font-bold border-b-2 transition">
                    Completed
                </button>
            </nav>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <div x-show="tab === 'all' && allCount === 0" class="text-center py-16 text-[#4B5563] col-span-full">
                <p class="text-lg font-medium">No Projects yet.</p>
            </div>
            <div x-show="tab === 'active' && activeCount === 0" class="text-center py-16 text-[#4B5563] col-span-full" x-cloak>
                <p class="text-lg font-medium">No Active Projects yet.</p>
            </div>
            <div x-show="tab === 'completed' && completedCount === 0" class="text-center py-16 text-[#4B5563] col-span-full" x-cloak>
                <p class="text-lg font-medium">No Completed Projects yet.</p>
            </div>
            <?php foreach ($projects as $p):
                $status_lower = strtolower($p['status']);
                $percent_raw = ($p['target_amount'] > 0) ? round(($p['raised_amount'] / $p['target_amount']) * 100) : 0;
                $percent_display = min($percent_raw, 100);
            ?>
                <div x-show="tab === 'all' || tab === '<?php echo $status_lower; ?>'" class="bg-white rounded-[18px] shadow-lg border border-gray-100 overflow-hidden flex flex-col group transform hover:-translate-y-2 hover:border-[#F4A640] transition-all duration-300" x-transition.opacity>
                    <div class="aspect-video bg-gray-100 overflow-hidden">
                        <img src="<?php echo $p['thumbnail_image'] ?: 'https://placehold.co/800x600/e2e8f0/64748b?text=Project'; ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-105">
                    </div>
                    <div class="p-5 flex-1 flex flex-col">
                        <span class="px-3 py-1 text-xs font-semibold rounded-full w-fit <?php echo $p['status'] == 'Active' ? 'bg-[#F0FDFD] text-[#0F8B8D]' : 'bg-gray-100 text-[#4B5563]'; ?>"><?php echo $p['status']; ?></span>
                        <h4 class="font-bold text-xl text-[#1F2937] mt-3 flex-1"><?php echo htmlspecialchars($p['title']); ?></h4>

                        <div class="mt-4">
                            <div class="flex justify-between text-sm font-medium text-[#4B5563]">
                                <span>Raised: ₹<?php echo number_format($p['raised_amount']); ?></span>
                                <span class="text-[#0F8B8D] font-bold"><?php echo $percent_raw; ?>%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2.5 mt-1 overflow-hidden">
                                <div class="bg-[#F4A640] h-2.5 rounded-full" style="width: <?php echo $percent_display; ?>%"></div>
                            </div>
                        </div>

                        <a href="project-details.php?id=<?php echo $p['id']; ?>" class="mt-6 block w-full bg-[#F4A640] hover:bg-[#D98E2B] text-white text-center font-bold py-3 rounded-full transition duration-300 shadow-md"> View Details </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bg-[#0F8B8D]">
        <div class="container mx-auto px-4 py-16 text-center">
            <h2 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight">
                Ready to Make a Difference?
            </h2>
            <p class="mt-4 max-w-2xl mx-auto text-lg text-white/90">
                Your support can turn our vision into reality. Join us in our mission to create a better world.
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3.5">

                <a href="donate.php"
                     class="inline-flex items-center justify-center bg-[#F4A640] hover:bg-[#D98E2B] text-white font-bold py-3 px-6 rounded-full shadow-lg transition transform hover:scale-105 active:scale-95 text-sm md:text-base">
                    <i class="fas fa-heart mr-2"></i>
                    <span>Donate Now</span>
                </a>

                <a href="apply-health-card.php"
                    class="inline-flex items-center justify-center bg-white hover:bg-gray-100 text-[#0F8B8D] font-black py-3 px-6 rounded-full shadow-lg transition transform hover:scale-105 active:scale-95 text-sm md:text-base whitespace-nowrap gap-2">
                    <i class="fa-solid fa-id-card-clip"></i>
                    <span>Apply / Renew Health Card</span>
                </a>

                <a href="volunteer-register.php"
                    class="inline-flex items-center justify-center bg-teal-800/80 hover:bg-teal-900 text-white font-bold py-3 px-6 rounded-full shadow-lg border border-teal-500/40 transition transform hover:scale-105 active:scale-95 text-sm md:text-base whitespace-nowrap">
                    <span>Join as Volunteer</span>
                </a>

            </div>
        </div>
    </div>

</div>

<?php require 'includes/footer.php'; ?>
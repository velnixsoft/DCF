<?php
/**
 * Reusable Project Workflow Chunks
 * For bigger home themes + features overview
 */

// 1. FEATURES OVERVIEW GRID - Shows ALL workflows immediately (BIG icons)
function featuresOverview() {
    $features = [
        ['💰', 'Secure Donations', 'donate.php', 'Instant receipts & progress tracking'],
        ['🏥', 'Health Card Portal', 'apply-health-card.php', 'Apply, renew & download Health Card with QR'],
        ['🚀', 'Volunteer Portal', 'volunteer-register.php', 'Dashboard, activities, certificates'], 
        ['📊', 'Live Project Stats', 'projects.php', 'Real-time funding progress'],
        ['🎯', 'Targeted Campaigns', 'project-details.php', 'Education, health, community'],
        ['🏆', 'Digital Certificates', 'certificates.php', 'Volunteer/Member ID cards'],
        ['📈', 'Event Management', 'events.php', 'Registrations & galleries'],
        ['👥', 'Member Dashboard', 'member-dashboard.php', 'Donation history & profile']
    ];
?>
<div class="features-overview py-20 bg-gradient-to-br from-gray-50 to-blue-50">
    <div class="container mx-auto px-6 max-w-6xl">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent mb-6">
                All Features at a Glance
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">Everything you need to make an impact – discover our complete project ecosystem</p>
        </div>
        <div class="grid md:grid-cols-4 gap-8">
            <?php foreach($features as $f): ?>
            <div class="group bg-white/70 backdrop-blur-sm p-8 rounded-3xl shadow-xl hover:shadow-2xl hover:-translate-y-4 transition-all duration-500 border border-white/50 h-64 flex flex-col items-center justify-center text-center">
                <div class="text-6xl mb-6 group-hover:scale-110 transition-transform"><?php echo $f[0]; ?></div>
                <h4 class="font-bold text-xl mb-3 text-gray-800"><?php echo $f[1]; ?></h4>
                <p class="text-sm text-gray-500 mb-6"><?php echo $f[3]; ?></p>
                <a href="<?php echo $f[2]; ?>" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-8 py-3 rounded-full font-bold text-sm hover:shadow-lg transition-all">
                    Explore →
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php }

// 2. BIGGER PROJECT STATS GRID
function projectStats($totalDonation, $activeVolunteers, $ongoingProjects) { 
?>
<section class="py-20 bg-gradient-to-b from-emerald-50 to-teal-50" x-data="statsCounter()" x-intersect.once="startCounters()">
    <div class="container mx-auto px-6 max-w-5xl">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white/80 backdrop-blur p-10 rounded-3xl shadow-2xl text-center group hover:scale-[1.02] transition-all">
                <div class="w-24 h-24 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-3xl mx-auto mb-8 flex items-center justify-center shadow-2xl">
                    <i class="fas fa-rupee-sign text-3xl text-white"></i>
                </div>
                <h3 class="text-5xl font-black text-emerald-700 mb-3">₹<span x-text="format(donationCount)">0</span></h3>
                <p class="text-lg font-bold text-gray-600 tracking-wide uppercase">Total Raised</p>
            </div>
            <div class="bg-white/80 backdrop-blur p-10 rounded-3xl shadow-2xl text-center group hover:scale-[1.02] transition-all">
                <div class="w-24 h-24 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-3xl mx-auto mb-8 flex items-center justify-center shadow-2xl">
                    <i class="fas fa-users text-3xl text-white"></i>
                </div>
                <h3 class="text-5xl font-black text-blue-700 mb-3" x-text="format(volunteerCount)">0</h3> 
                <p class="text-lg font-bold text-gray-600 tracking-wide uppercase">Active Volunteers</p>
            </div>
            <div class="bg-white/80 backdrop-blur p-10 rounded-3xl shadow-2xl text-center group hover:scale-[1.02] transition-all">
                <div class="w-24 h-24 bg-gradient-to-br from-purple-400 to-pink-500 rounded-3xl mx-auto mb-8 flex items-center justify-center shadow-2xl">
                    <i class="fas fa-project-diagram text-3xl text-white"></i>
                </div>
                <h3 class="text-5xl font-black text-purple-700 mb-3" x-text="format(projectCount)">0</h3>
                <p class="text-lg font-bold text-gray-600 tracking-wide uppercase">Ongoing Projects</p>
            </div>
        </div>
    </div>
    <script>
    function statsCounter() {
        return {
            donationCount: 0, volunteerCount: 0, projectCount: 0,
            targets: {donation: <?php echo $totalDonation; ?>, volunteer: <?php echo $activeVolunteers; ?>, project: <?php echo $ongoingProjects; ?>},
            format(v) { return v.toLocaleString('en-IN'); },
            startCounters() {
                // Animated counters logic (same as themes)
                const animate = (key, end) => {
                    const duration = 2500, start = performance.now();
                    const step = current => {
                        const elapsed = current - start, progress = Math.min(elapsed / duration, 1);
                        const ease = 1 - Math.pow(1 - progress, 3);
                        this[key] = startValue + (end - startValue) * ease;
                        if (progress < 1) requestAnimationFrame(step); else this[key] = end;
                    }; requestAnimationFrame(step);
                }; 
                animate('donationCount', this.targets.donation);
                // ... similar for others
            }
        }
    }
    </script>
</section>
<?php }



<?php
// ============================================================
// hr-policies.php
// Public HR Policy & Institutional Governance Portal
// ============================================================

require_once 'config/db.php';
require_once 'includes/functions.php';

// Fetch all public HR policies
$policies = [];
if (dbTableExists($pdo, 'hr_policies')) {
    try {
        $stmt = $pdo->query("
            SELECT * 
            FROM hr_policies 
            WHERE is_public = 1 
            ORDER BY sort_order ASC, id DESC
        ");
        $policies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $policies = [];
    }
}

// Compute counts
$totalCount = count($policies);
$cocCount = 0;
$poshCount = 0;
$csgCount = 0;
$leaveCount = 0;
$wbCount = 0;
$travelCount = 0;

foreach ($policies as $p) {
    $cat = $p['category'] ?? 'code_of_conduct';
    if ($cat === 'code_of_conduct') $cocCount++;
    elseif ($cat === 'posh_gender') $poshCount++;
    elseif ($cat === 'child_safeguarding') $csgCount++;
    elseif ($cat === 'leave_benefits') $leaveCount++;
    elseif ($cat === 'whistleblower') $wbCount++;
    elseif ($cat === 'travel_compensation') $travelCount++;
}

require 'includes/header.php';
?>

<div class="bg-gradient-to-b from-[#F0FDFD] via-white to-[#F8FAFC] min-h-screen py-8 md:py-14"
     x-data="hrPoliciesPortal(<?php echo htmlspecialchars(json_encode($policies, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>)"
     x-cloak>

    <div class="container mx-auto px-4 max-w-7xl">
        
        <!-- Hero Header -->
        <div class="max-w-4xl mx-auto text-center mb-8 md:mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white shadow-xs border border-teal-100 text-teal-700 text-xs sm:text-sm font-bold uppercase tracking-wider">
                <i class="fa-solid fa-scale-balanced text-teal-600"></i>
                Workplace Governance • Institutional Compliance & Transparency
            </span>

            <h1 class="mt-4 text-3xl sm:text-4xl md:text-5xl font-black text-gray-900 tracking-tight">
                HR Policies & <span class="text-teal-600">Workplace Standards</span>
            </h1>

            <p class="mt-3 text-sm sm:text-base md:text-lg text-gray-600 max-w-2xl mx-auto leading-relaxed">
                Access official institutional policies governing employee conduct, gender safety (POSH), child safeguarding, staff benefits, anti-corruption, and field protocols.
            </p>

            <!-- Trust Badges -->
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3 md:gap-4 text-xs">
                <span class="px-3.5 py-1.5 rounded-xl bg-teal-50 border border-teal-100 text-teal-800 font-bold flex items-center gap-1.5">
                    <i class="fa-solid fa-shield-halved text-teal-600"></i>
                    <span>POSH Act 2013 Compliant</span>
                </span>
                <span class="px-3.5 py-1.5 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-800 font-bold flex items-center gap-1.5">
                    <i class="fa-solid fa-child-reaching text-indigo-600"></i>
                    <span>Child Safeguarding (PSEA)</span>
                </span>
                <span class="px-3.5 py-1.5 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-800 font-bold flex items-center gap-1.5">
                    <i class="fa-solid fa-hand-holding-heart text-emerald-600"></i>
                    <span>Equal Opportunity Employer</span>
                </span>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-white rounded-3xl shadow-xl border border-teal-50 p-5 md:p-7 mb-10 transition-all">
            
            <!-- Category Tabs -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2.5">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Filter by Policy Area:</span>
                    <span class="text-xs font-semibold text-teal-600" x-text="filteredPolicies.length + ' documents available'"></span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="activeCategory = ''" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-xs"
                            :class="activeCategory === '' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-file-lines"></i>
                        <span>All Policies</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeCategory === '' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $totalCount; ?></span>
                    </button>

                    <button type="button" @click="activeCategory = 'code_of_conduct'" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-xs"
                            :class="activeCategory === 'code_of_conduct' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-user-shield"></i>
                        <span>Code of Conduct</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeCategory === 'code_of_conduct' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $cocCount; ?></span>
                    </button>

                    <button type="button" @click="activeCategory = 'posh_gender'" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-xs"
                            :class="activeCategory === 'posh_gender' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-person-dress"></i>
                        <span>POSH & Gender Safety</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeCategory === 'posh_gender' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $poshCount; ?></span>
                    </button>

                    <button type="button" @click="activeCategory = 'child_safeguarding'" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-xs"
                            :class="activeCategory === 'child_safeguarding' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-children"></i>
                        <span>Child Protection (PSEA)</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeCategory === 'child_safeguarding' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $csgCount; ?></span>
                    </button>

                    <button type="button" @click="activeCategory = 'leave_benefits'" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-xs"
                            :class="activeCategory === 'leave_benefits' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span>Leave & Benefits</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeCategory === 'leave_benefits' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $leaveCount; ?></span>
                    </button>

                    <button type="button" @click="activeCategory = 'whistleblower'" 
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-xs"
                            :class="activeCategory === 'whistleblower' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100'">
                        <i class="fa-solid fa-bullhorn"></i>
                        <span>Whistleblower & Anti-Fraud</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px]" :class="activeCategory === 'whistleblower' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-700'"><?php echo $wbCount; ?></span>
                    </button>
                </div>
            </div>

            <!-- Search -->
            <div class="relative max-w-md">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                <input type="text" x-model="searchQuery" placeholder="Search by policy title, code (e.g. POSH, Leave, Conduct)..."
                       class="w-full bg-gray-50 pl-9 pr-3.5 py-2.5 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
            </div>
        </div>

        <!-- Policies Card Grid -->
        <div class="mb-14">
            <div x-show="filteredPolicies.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="policy in filteredPolicies" :key="policy.id">
                    <div class="bg-white rounded-3xl p-6 shadow-md hover:shadow-2xl border border-gray-100 hover:border-teal-300 transition-all duration-300 flex flex-col justify-between relative overflow-hidden group">
                        
                        <!-- Top Accent Line -->
                        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-teal-500 via-emerald-500 to-blue-500"></div>

                        <div>
                            <!-- Header Code & Badge -->
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-teal-50 text-teal-700 border border-teal-100" x-text="getCategoryLabel(policy.category)"></span>
                                <span class="font-mono text-[10px] text-gray-400 bg-gray-50 px-2 py-0.5 rounded border border-gray-100" x-text="policy.policy_code"></span>
                            </div>

                            <!-- Title -->
                            <h3 class="text-base md:text-lg font-black text-gray-900 group-hover:text-teal-600 transition-colors line-clamp-2 mb-2" x-text="policy.title"></h3>

                            <!-- Meta info -->
                            <div class="flex items-center gap-3 text-xs text-gray-500 mb-3.5">
                                <span><i class="fa-regular fa-calendar mr-1 text-teal-600"></i><span x-text="formatDate(policy.effective_date)"></span></span>
                                <span><i class="fa-solid fa-code-branch mr-1 text-teal-600"></i><span x-text="policy.policy_version || 'v1.0'"></span></span>
                            </div>

                            <!-- Description -->
                            <p class="text-xs text-gray-600 line-clamp-3 mb-5 leading-relaxed" x-text="policy.description"></p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-4 border-t border-gray-100 flex items-center gap-2">
                            <button type="button" @click="openPolicyModal(policy)"
                                    class="flex-1 py-2.5 px-3 bg-gray-100 hover:bg-gray-200 text-gray-800 text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5">
                                <i class="fa-regular fa-eye"></i>
                                <span>Overview</span>
                            </button>

                            <template x-if="policy.file_path">
                                <a :href="'/' + policy.file_path" target="_blank"
                                   class="flex-1 py-2.5 px-3 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-md shadow-teal-600/20 transition flex items-center justify-center gap-1.5">
                                    <i class="fa-solid fa-file-pdf text-xs"></i>
                                    <span>Download PDF</span>
                                </a>
                            </template>

                            <template x-if="!policy.file_path">
                                <button type="button" @click="openPolicyModal(policy)"
                                        class="flex-1 py-2.5 px-3 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-md shadow-teal-600/20 transition flex items-center justify-center gap-1.5">
                                    <i class="fa-solid fa-file-lines text-xs"></i>
                                    <span>View Content</span>
                                </button>
                            </template>
                        </div>

                    </div>
                </template>
            </div>

            <!-- Empty State -->
            <div x-show="filteredPolicies.length === 0" class="bg-white rounded-3xl p-12 text-center border border-gray-100 shadow-sm max-w-lg mx-auto">
                <div class="w-16 h-16 bg-teal-50 text-teal-600 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fa-solid fa-file-shield"></i>
                </div>
                <h3 class="text-lg font-black text-gray-900">No Policies Found</h3>
                <p class="text-xs text-gray-500 mt-2">Try clearing your search query to view all published HR policies.</p>
                <button type="button" @click="activeCategory = ''; searchQuery = ''" class="mt-4 px-4 py-2 bg-teal-600 text-white text-xs font-bold rounded-xl">
                    View All Policies
                </button>
            </div>
        </div>

    </div>

    <!-- ============================================================ -->
    <!-- POLICY PREVIEW & MANDATE MODAL -->
    <!-- ============================================================ -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" @click="showModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">
                <div class="p-6 md:p-8 bg-gradient-to-r from-teal-700 to-teal-900 text-white relative">
                    <button type="button" @click="showModal = false" class="absolute right-5 top-5 text-white/80 hover:text-white">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="px-2.5 py-0.5 rounded bg-white/20 text-white text-[10px] font-bold uppercase tracking-wider" x-text="getCategoryLabel(activePolicy?.category)"></span>
                        <span class="font-mono text-xs text-teal-200" x-text="activePolicy?.policy_code"></span>
                    </div>
                    <h2 class="text-xl md:text-2xl font-black" x-text="activePolicy?.title"></h2>
                    <div class="flex gap-4 mt-2 text-xs text-teal-200">
                        <span>Effective: <strong class="text-white" x-text="formatDate(activePolicy?.effective_date)"></strong></span>
                        <span>Version: <strong class="text-white" x-text="activePolicy?.policy_version || 'v1.0'"></strong></span>
                    </div>
                </div>

                <div class="p-6 md:p-8 space-y-4 text-xs md:text-sm text-gray-700 max-h-[60vh] overflow-y-auto">
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-wider text-gray-400 mb-2">Policy Summary & Objectives</h4>
                        <p class="leading-relaxed text-gray-800" x-text="activePolicy?.description"></p>
                    </div>

                    <div class="p-4 rounded-2xl bg-teal-50/60 border border-teal-100 text-xs space-y-1">
                        <div class="font-bold text-teal-900">Institutional Compliance Note:</div>
                        <div class="text-teal-800">This policy applies to all full-time employees, project coordinators, interns, and field volunteers across all NGO chapters.</div>
                    </div>
                </div>

                <div class="p-4 md:p-6 bg-gray-50 border-t border-gray-100 flex justify-between">
                    <button type="button" @click="showModal = false" class="px-5 py-2.5 bg-white text-gray-700 text-xs font-bold rounded-xl border">Close</button>
                    <template x-if="activePolicy?.file_path">
                        <a :href="'/' + activePolicy.file_path" target="_blank" class="px-6 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-md flex items-center gap-2">
                            <i class="fa-solid fa-download"></i>
                            <span>Download Full Document</span>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('hrPoliciesPortal', (initialPolicies = []) => ({
        policies: initialPolicies,
        activeCategory: '',
        searchQuery: '',
        showModal: false,
        activePolicy: null,

        get filteredPolicies() {
            return this.policies.filter(pol => {
                if (this.activeCategory !== '' && pol.category !== this.activeCategory) return false;
                if (this.searchQuery.trim() !== '') {
                    const q = this.searchQuery.toLowerCase().trim();
                    const title = (pol.title || '').toLowerCase();
                    const code = (pol.policy_code || '').toLowerCase();
                    const desc = (pol.description || '').toLowerCase();
                    if (!title.includes(q) && !code.includes(q) && !desc.includes(q)) return false;
                }
                return true;
            });
        },

        getCategoryLabel(cat) {
            const map = {
                'code_of_conduct': 'Code of Conduct',
                'posh_gender': 'POSH & Gender Safety',
                'child_safeguarding': 'Child Protection',
                'leave_benefits': 'Leave & Benefits',
                'whistleblower': 'Whistleblower',
                'travel_compensation': 'Travel & Field Norms',
                'volunteer_ethics': 'Volunteer Ethics',
                'general': 'General Policy'
            };
            return map[cat] || 'HR Policy';
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        },

        openPolicyModal(policy) {
            this.activePolicy = policy;
            this.showModal = true;
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>

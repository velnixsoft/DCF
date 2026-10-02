<?php require 'includes/header.php'; ?>
<?php require '../config/db.php'; ?>

<?php
if (!checkRole($pdo, 'coordinator')) {
    setFlash('error', 'Unauthorized access.');
    header('Location: dashboard.php');
    exit;
}

$leads = $pdo->query("
    SELECT vl.*,
           st.full_name AS student_name,
           st.student_no,
           st.email AS student_email,
           st.mobile AS student_mobile
    FROM sa_vendor_leads vl
    JOIN sa_students st ON st.id = vl.student_id
    ORDER BY vl.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$leadLabels = [
    'vendor_meeting' => 'Vendor Meeting',
    'vendor_onboarding' => 'Vendor Onboarding',
    'premium_vendor_onboarding' => 'Premium Vendor Onboarding',
    'sponsor_partnership' => 'Sponsor Lead',
    'business_partnership' => 'Sponsor Lead',
    'college_partnership' => 'College Partnership',
];

$csrfToken = generateCsrfToken();
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="vendorLeadManager">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            <div class="flex flex-col gap-4 mb-6">
                <div>
                    <h3 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Vendor Lead Verification</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Review vendor meetings, onboarding, sponsor leads, and college partnerships submitted by ambassadors.</p>
                </div>

                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border dark:border-gray-700 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
                    <div class="flex flex-wrap bg-gray-100 dark:bg-gray-900 p-1 rounded-xl w-full md:w-auto">
                        <button @click="tab = 'pending'" :class="tab === 'pending' ? 'bg-white shadow text-orange-655 dark:bg-gray-800 dark:text-orange-400 dark:shadow-md' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'" class="flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-bold transition">
                            Pending <span x-show="pendingCount > 0" class="bg-orange-100 text-orange-700 text-xs px-1.5 rounded-full" x-text="pendingCount"></span>
                        </button>
                        <button @click="tab = 'verified'" :class="tab === 'verified' ? 'bg-white shadow text-emerald-750 dark:bg-gray-800 dark:text-emerald-400 dark:shadow-md' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'" class="flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-bold transition">Verified</button>
                        <button @click="tab = 'all'" :class="tab === 'all' ? 'bg-white shadow text-blue-750 dark:bg-gray-800 dark:text-blue-400 dark:shadow-md' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'" class="flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-bold transition">All</button>
                    </div>

                    <div class="relative w-full md:w-80">
                        <input type="text" x-model="search" placeholder="Search student, business, city..." class="w-full pl-10 pr-4 py-2.5 border rounded-xl bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:bg-white dark:focus:bg-gray-700 outline-none transition text-sm">
                        <span class="absolute left-3.5 top-3 text-gray-400"><i class="fa-solid fa-magnifying-glass"></i></span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:hidden">
                <template x-for="lead in pagedItems" :key="'mobile-' + lead.id">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow border dark:border-gray-700 p-4 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white" x-text="lead.business_name"></p>
                                <p class="text-xs text-blue-700 font-semibold mt-1" x-text="leadLabels[lead.lead_type] || lead.lead_type"></p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="getStatusClass(lead.verification_status)" x-text="lead.verification_status"></span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <div><span class="text-gray-400 uppercase font-semibold">Student</span><p class="mt-1 font-bold" x-text="lead.student_name"></p><p class="text-[10px] text-gray-400 font-mono" x-text="lead.student_no"></p></div>
                            <div><span class="text-gray-400 uppercase font-semibold">Contact</span><p class="mt-1" x-text="lead.contact_name || 'Not provided'"></p><p x-text="lead.contact_phone || lead.contact_email || ''"></p></div>
                            <div class="sm:col-span-2"><span class="text-gray-400 uppercase font-semibold">Location</span><p class="mt-1" x-text="[lead.city_name, lead.state_name].filter(Boolean).join(', ')"></p></div>
                        </div>
                        <button @click="openReviewModal(lead)" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-2.5 rounded-xl text-sm transition">Review</button>
                    </div>
                </template>
                <div x-show="filteredItems.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow border dark:border-gray-700 p-8 text-center text-gray-400 font-medium">No vendor leads found.</div>
            </div>

            <div class="hidden lg:block bg-white dark:bg-gray-800 rounded-2xl shadow border dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase">
                            <tr>
                                <th class="p-4">Student</th>
                                <th class="p-4">Lead</th>
                                <th class="p-4">Contact</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <template x-for="lead in pagedItems" :key="lead.id">
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition">
                                    <td class="p-4">
                                        <p class="font-bold text-gray-900 dark:text-white" x-text="lead.student_name"></p>
                                        <p class="text-[10px] text-gray-400 font-mono" x-text="lead.student_no"></p>
                                    </td>
                                    <td class="p-4">
                                        <p class="font-bold text-gray-900 dark:text-white" x-text="lead.business_name"></p>
                                        <p class="text-xs text-blue-700 font-semibold mt-0.5" x-text="leadLabels[lead.lead_type] || lead.lead_type"></p>
                                        <p class="text-[10px] text-gray-400 mt-0.5" x-text="formatDate(lead.created_at)"></p>
                                    </td>
                                    <td class="p-4 text-xs">
                                        <p class="font-semibold text-gray-800" x-text="lead.contact_name || 'Not provided'"></p>
                                        <p class="text-gray-500" x-text="lead.contact_phone || lead.contact_email || ''"></p>
                                        <p class="text-gray-400" x-text="[lead.city_name, lead.state_name].filter(Boolean).join(', ')"></p>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="getStatusClass(lead.verification_status)" x-text="lead.verification_status"></span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <button @click="openReviewModal(lead)" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition">
                                            Review
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredItems.length === 0">
                                <td colspan="5" class="p-8 text-center text-gray-400 font-medium">No vendor leads found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination UI Controls -->
            <div x-show="filteredItems.length > pageSize" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6 px-4 py-3 bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-2xl shadow-sm">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                    Showing <span class="font-bold text-gray-900 dark:text-white" x-text="filteredItems.length === 0 ? 0 : (page - 1) * pageSize + 1"></span> to 
                    <span class="font-bold text-gray-900 dark:text-white" x-text="Math.min(page * pageSize, filteredItems.length)"></span> of 
                    <span class="font-bold text-gray-900 dark:text-white" x-text="filteredItems.length"></span> entries
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="if (page > 1) page--" :disabled="page === 1" 
                        class="px-4 py-2 text-xs font-bold rounded-xl border dark:border-gray-700 bg-gray-50 dark:bg-gray-750 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:pointer-events-none transition flex items-center gap-1">
                        <i class="fa-solid fa-chevron-left"></i> Previous
                    </button>
                    <div class="flex items-center gap-1">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Page</span>
                        <span class="text-xs font-bold text-gray-900 dark:text-white" x-text="page"></span>
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">of</span>
                        <span class="text-xs font-bold text-gray-900 dark:text-white" x-text="totalPages || 1"></span>
                    </div>
                    <button type="button" @click="if (page < totalPages) page++" :disabled="page === totalPages || totalPages === 0" 
                        class="px-4 py-2 text-xs font-bold rounded-xl border dark:border-gray-700 bg-gray-50 dark:bg-gray-750 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 disabled:pointer-events-none transition flex items-center gap-1">
                        Next <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <div x-show="isReviewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4" x-cloak>
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl overflow-hidden border border-gray-100" @click.away="isReviewModalOpen = false">
                    <div class="p-6 border-b flex justify-between items-center bg-gray-50">
                        <h3 class="text-xl font-bold text-gray-900">Review Vendor Lead</h3>
                        <button @click="isReviewModalOpen = false" class="text-gray-400 hover:text-gray-600 font-black">x</button>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 max-h-[70vh] overflow-y-auto">
                        <div class="space-y-4 text-sm">
                            <div>
                                <label class="text-xs text-gray-400 uppercase font-bold">Student</label>
                                <p class="font-bold text-gray-900" x-text="activeData.student_name"></p>
                                <p class="text-xs text-gray-500 font-mono" x-text="activeData.student_no"></p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-400 uppercase font-bold">Lead Type</label>
                                <p class="font-bold text-blue-700" x-text="leadLabels[activeData.lead_type] || activeData.lead_type"></p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-400 uppercase font-bold">Business / Organization</label>
                                <p class="font-bold text-gray-900" x-text="activeData.business_name"></p>
                                <p class="text-xs text-gray-500" x-text="[activeData.city_name, activeData.state_name].filter(Boolean).join(', ')"></p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-400 uppercase font-bold">Meeting Date</label>
                                <p class="font-bold text-gray-900" x-text="activeData.meeting_date ? formatDate(activeData.meeting_date) : 'Not specified'"></p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-400 uppercase font-bold">Student Notes</label>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded-xl border mt-1" x-text="activeData.notes || 'No notes submitted.'"></p>
                            </div>
                        </div>

                        <div class="space-y-4 text-sm">
                            <div>
                                <label class="text-xs text-gray-400 uppercase font-bold">Contact Details</label>
                                <p class="font-semibold text-gray-900" x-text="activeData.contact_name || 'Not provided'"></p>
                                <p class="text-xs text-gray-600" x-text="activeData.contact_phone || ''"></p>
                                <p class="text-xs text-gray-600" x-text="activeData.contact_email || ''"></p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-400 uppercase font-bold">Proof</label>
                                <template x-if="activeData.proof_path">
                                    <a :href="'../' + activeData.proof_path" target="_blank" class="inline-flex items-center gap-2 bg-blue-50 text-blue-700 border border-blue-100 px-3 py-2 rounded-xl text-xs font-bold mt-1">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Proof
                                    </a>
                                </template>
                                <template x-if="!activeData.proof_path">
                                    <p class="text-gray-400 mt-1">No proof uploaded.</p>
                                </template>
                            </div>
                            <template x-if="activeData.verification_notes">
                                <div>
                                    <label class="text-xs text-gray-400 uppercase font-bold">Admin Notes</label>
                                    <p class="text-gray-700 bg-gray-50 p-3 rounded-xl border mt-1" x-text="activeData.verification_notes"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="p-4 bg-gray-50 border-t">
                        <template x-if="activeData.verification_status === 'pending'">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <form action="actions/vendor_lead_logic.php" method="POST" class="space-y-3">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="id" :value="activeData.id">
                                    <input type="text" name="verification_notes" required placeholder="Reason for rejection" class="w-full px-4 py-2.5 border rounded-xl outline-none focus:ring-2 focus:ring-red-500 text-sm">
                                    <button type="submit" class="w-full bg-white hover:bg-red-50 border border-red-200 text-red-600 font-bold py-2.5 rounded-xl text-sm transition">
                                        Reject Lead
                                    </button>
                                </form>
                                <form action="actions/vendor_lead_logic.php" method="POST" class="space-y-3">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="verify">
                                    <input type="hidden" name="id" :value="activeData.id">
                                    <input type="text" name="verification_notes" placeholder="Verification note, optional" class="w-full px-4 py-2.5 border rounded-xl outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-xl text-sm transition shadow-md shadow-emerald-500/10">
                                        Verify & Award Points
                                    </button>
                                </form>
                            </div>
                        </template>
                        <template x-if="activeData.verification_status !== 'pending'">
                            <div class="flex justify-end">
                                <button @click="isReviewModalOpen = false" class="px-5 py-2.5 bg-gray-200 text-gray-700 rounded-xl text-sm font-bold hover:bg-gray-300">Close</button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('vendorLeadManager', () => ({
        items: <?php echo json_encode($leads); ?>,
        leadLabels: <?php echo json_encode($leadLabels); ?>,
        search: '',
        tab: 'pending',
        page: 1,
        pageSize: 10,
        isReviewModalOpen: false,
        activeData: {},

        init() {
            this.$watch('search', () => this.page = 1);
            this.$watch('tab', () => this.page = 1);
        },

        get filteredItems() {
            const query = this.search.toLowerCase();
            const searched = this.items.filter(item =>
                String(item.student_name || '').toLowerCase().includes(query) ||
                String(item.student_no || '').toLowerCase().includes(query) ||
                String(item.business_name || '').toLowerCase().includes(query) ||
                String(item.city_name || '').toLowerCase().includes(query)
            );

            if (this.tab === 'pending') return searched.filter(item => item.verification_status === 'pending');
            if (this.tab === 'verified') return searched.filter(item => item.verification_status === 'verified');
            return searched;
        },

        get pagedItems() {
            const start = (this.page - 1) * this.pageSize;
            return this.filteredItems.slice(start, start + this.pageSize);
        },

        get totalPages() {
            return Math.ceil(this.filteredItems.length / this.pageSize);
        },

        get pendingCount() {
            return this.items.filter(item => item.verification_status === 'pending').length;
        },

        openReviewModal(lead) {
            this.activeData = lead;
            this.isReviewModalOpen = true;
        },

        getStatusClass(status) {
            return {
                'bg-emerald-50 border border-emerald-100 text-emerald-700': status === 'verified',
                'bg-orange-50 border border-orange-100 text-orange-700': status === 'pending',
                'bg-red-50 border border-red-100 text-red-700': status === 'rejected'
            };
        },

        formatDate(date) {
            if (!date) return '';
            const parts = date.split(' ');
            return (parts[0].split('-').reverse().join('-') + ' ' + (parts[1] || '').substring(0, 5)).trim();
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>

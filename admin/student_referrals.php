<?php require 'includes/header.php'; ?>
<?php require '../config/db.php'; ?>

<?php
if (!checkRole($pdo, 'coordinator')) {
    setFlash('error', 'Unauthorized access.');
    header('Location: dashboard.php');
    exit;
}

$referrals = [];
try {
    $referrals = $pdo->query("
        SELECT r.*,
               ref.full_name AS referrer_name,
               ref.student_no AS referrer_no,
               ref.status AS referrer_status,
               referred.full_name AS referred_name,
               referred.student_no AS referred_no,
               referred.status AS referred_status
        FROM sa_referrals r
        JOIN sa_students ref ON ref.id = r.referrer_student_id
        LEFT JOIN sa_students referred ON referred.id = r.referred_student_id
        WHERE r.deleted_at IS NULL
          AND ref.status = 'Active'
          AND ref.is_verified = 1
        ORDER BY r.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $referrals = [];
}

$csrfToken = generateCsrfToken();
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="referralManager">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            <div class="mb-6">
                <h3 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Referral Verification</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Verify ambassador referrals and award points through the point engine.</p>
            </div>

            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border dark:border-gray-700 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4 mb-6">
                <div class="flex flex-wrap bg-gray-100 dark:bg-gray-900 p-1 rounded-xl w-full md:w-auto">
                    <button @click="tab = 'pending'" :class="tab === 'pending' ? 'bg-white shadow text-orange-655 dark:bg-gray-800 dark:text-orange-400 dark:shadow-md' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'" class="px-4 py-2 rounded-lg text-sm font-bold">Pending</button>
                    <button @click="tab = 'verified'" :class="tab === 'verified' ? 'bg-white shadow text-emerald-750 dark:bg-gray-800 dark:text-emerald-400 dark:shadow-md' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'" class="px-4 py-2 rounded-lg text-sm font-bold">Verified</button>
                    <button @click="tab = 'all'" :class="tab === 'all' ? 'bg-white shadow text-blue-750 dark:bg-gray-800 dark:text-blue-400 dark:shadow-md' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'" class="px-4 py-2 rounded-lg text-sm font-bold">All</button>
                </div>
                <input type="text" x-model="search" placeholder="Search referrer, referred, email..." class="w-full md:w-80 px-4 py-2.5 border dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white dark:focus:bg-gray-700 text-sm">
            </div>

            <div class="grid grid-cols-1 gap-4 lg:hidden">
                <template x-for="item in pagedItems" :key="'mobile-' + item.id">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow border dark:border-gray-700 p-4 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white" x-text="item.referrer_name"></p>
                                <p class="text-[10px] text-gray-400 font-mono" x-text="item.referrer_no"></p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold" :class="item.verification_status === 'verified' ? 'bg-emerald-100 text-emerald-700' : (item.verification_status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700')" x-text="item.verification_status"></span>
                        </div>
                        <div class="text-xs">
                            <p class="font-semibold" x-text="item.referred_name || item.referred_full_name || '-'"></p>
                            <p class="text-gray-500 mt-1" x-text="item.referred_email || '-'"></p>
                            <p class="text-gray-500" x-text="item.referred_mobile || ''"></p>
                        </div>
                        <template x-if="item.verification_status === 'pending'">
                            <div class="flex flex-wrap gap-2">
                                <form action="actions/referral_logic.php" method="POST" class="flex-1 min-w-[120px]">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="action" value="verify">
                                    <input type="hidden" name="id" :value="item.id">
                                    <button class="w-full bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-2.5 rounded-xl text-sm font-bold">Verify</button>
                                </form>
                                <button @click="openReject(item.id)" class="flex-1 min-w-[120px] bg-rose-100 text-rose-700 px-3 py-2.5 rounded-xl text-sm font-bold">Reject</button>
                            </div>
                        </template>
                    </div>
                </template>
                <div x-show="filteredItems.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow border dark:border-gray-700 p-8 text-center text-gray-500">No referrals found.</div>
            </div>

            <div class="hidden lg:block bg-white dark:bg-gray-800 rounded-2xl shadow border dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase font-bold text-gray-500">
                            <tr>
                                <th class="p-4">Referrer</th>
                                <th class="p-4">Referred</th>
                                <th class="p-4">Contact</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <template x-for="item in pagedItems" :key="item.id">
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/40">
                                    <td class="p-4">
                                        <p class="font-bold text-gray-900 dark:text-white" x-text="item.referrer_name"></p>
                                        <p class="text-[10px] text-gray-400 font-mono" x-text="item.referrer_no"></p>
                                    </td>
                                    <td class="p-4">
                                        <p class="font-semibold" x-text="item.referred_name || item.referred_full_name || '—'"></p>
                                        <p class="text-[10px] text-gray-400 font-mono" x-text="item.referred_no || ''"></p>
                                    </td>
                                    <td class="p-4 text-gray-600">
                                        <p x-text="item.referred_email || '—'"></p>
                                        <p class="text-xs" x-text="item.referred_mobile || ''"></p>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold"
                                            :class="item.verification_status === 'verified' ? 'bg-emerald-100 text-emerald-700' : (item.verification_status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700')"
                                            x-text="item.verification_status"></span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <template x-if="item.verification_status === 'pending'">
                                            <div class="flex justify-end gap-2">
                                                <form action="actions/referral_logic.php" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="verify">
                                                    <input type="hidden" name="id" :value="item.id">
                                                    <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold">Verify</button>
                                                </form>
                                                <button @click="openReject(item.id)" class="bg-rose-100 text-rose-700 px-3 py-1.5 rounded-lg text-xs font-bold">Reject</button>
                                            </div>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div x-show="filteredItems.length === 0" class="p-10 text-center text-gray-500">No referrals found.</div>
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
        </main>
    </div>

    <div x-show="rejectOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-2xl">
            <h4 class="text-lg font-bold text-gray-900 dark:text-white">Reject Referral</h4>
            <form action="actions/referral_logic.php" method="POST" class="mt-4 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="id" x-model="rejectId">
                <textarea name="reason" rows="3" class="w-full border rounded-xl px-3 py-2 text-sm" placeholder="Reason (optional)"></textarea>
                <div class="flex flex-col sm:flex-row justify-end gap-2">
                    <button type="button" @click="rejectOpen = false" class="px-4 py-2 rounded-lg bg-gray-100 text-sm font-semibold">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-rose-600 text-white text-sm font-semibold">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('referralManager', () => ({
        tab: 'pending',
        search: '',
        page: 1,
        pageSize: 10,
        rejectOpen: false,
        rejectId: 0,
        items: <?php echo json_encode($referrals, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,

        init() {
            this.$watch('search', () => this.page = 1);
            this.$watch('tab', () => this.page = 1);
        },
        get filteredItems() {
            const q = this.search.trim().toLowerCase();
            return this.items.filter((item) => {
                if (this.tab === 'pending' && item.verification_status !== 'pending') return false;
                if (this.tab === 'verified' && item.verification_status !== 'verified') return false;
                if (!q) return true;
                const hay = [
                    item.referrer_name, item.referrer_no, item.referred_name, item.referred_full_name,
                    item.referred_email, item.referred_mobile, item.referral_code_used
                ].join(' ').toLowerCase();
                return hay.includes(q);
            });
        },
        get pagedItems() {
            const start = (this.page - 1) * this.pageSize;
            return this.filteredItems.slice(start, start + this.pageSize);
        },
        get totalPages() {
            return Math.ceil(this.filteredItems.length / this.pageSize);
        },
        openReject(id) {
            this.rejectId = id;
            this.rejectOpen = true;
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>

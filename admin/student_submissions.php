<?php require 'includes/header.php'; ?>
<?php require '../config/db.php'; ?>

<?php
if (!checkRole($pdo, 'coordinator')) {
    setFlash('error', 'Unauthorized access.');
    header('Location: dashboard.php');
    exit;
}

$submissions = $pdo->query("
    SELECT s.*, 
           t.title as task_title, t.points_reward,
           st.full_name as student_name, st.student_no, st.email as student_email
    FROM sa_task_submissions s
    JOIN sa_tasks t ON s.task_id = t.id
    JOIN sa_students st ON s.student_id = st.id
    ORDER BY s.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$csrfToken = generateCsrfToken();
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="submissionManager">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            
            <div class="flex flex-col gap-4 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h3 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Task Proof Submissions</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Review proofs of campaign completions uploaded by student ambassadors.</p>
                    </div>
                </div>

                <!-- Tabs & Filters -->
                <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border dark:border-gray-700 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4">
                    <div class="flex flex-wrap bg-gray-100 dark:bg-gray-900 p-1 rounded-xl w-full md:w-auto">
                        <button @click="tab = 'all'" :class="tab === 'all' ? 'bg-white shadow text-emerald-750 dark:bg-gray-800 dark:text-emerald-400 dark:shadow-md' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'" class="flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-bold transition">
                            All Submissions
                        </button>
                        <button @click="tab = 'pending'" :class="tab === 'pending' ? 'bg-white shadow text-orange-650 dark:bg-gray-800 dark:text-orange-400 dark:shadow-md' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'" class="flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-bold transition flex items-center justify-center gap-2">
                            Pending Review
                            <span x-show="pendingCount > 0" class="bg-orange-100 text-orange-700 text-xs px-1.5 rounded-full" x-text="pendingCount"></span>
                        </button>
                    </div>

                    <div class="relative w-full md:w-80">
                        <input type="text" x-model="search" placeholder="Search student or campaign..." 
                            class="w-full pl-10 pr-4 py-2.5 border rounded-xl bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:bg-white dark:focus:bg-gray-700 outline-none transition text-sm">
                        <span class="absolute left-3.5 top-3 text-gray-400">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:hidden">
                <template x-for="sub in pagedItems" :key="'mobile-' + sub.id">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow border dark:border-gray-700 p-4 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white" x-text="sub.student_name"></p>
                                <p class="text-[10px] text-gray-400 font-mono" x-text="sub.student_no"></p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="getStatusClass(sub.status)" x-text="sub.status"></span>
                        </div>
                        <div>
                            <p class="font-bold text-gray-800" x-text="sub.task_title"></p>
                            <p class="text-xs text-amber-600 font-semibold mt-1" x-text="'+' + sub.points_reward + ' Points'"></p>
                        </div>
                        <p class="text-xs text-gray-500 font-mono" x-text="formatDate(sub.created_at)"></p>
                        <button @click="openViewModal(sub)" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-2.5 rounded-xl text-sm transition">Review Proof</button>
                    </div>
                </template>
                <div x-show="filteredItems.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow border dark:border-gray-700 p-8 text-center text-gray-400 font-medium">No task submissions found.</div>
            </div>

            <!-- Table View -->
            <div class="hidden lg:block bg-white dark:bg-gray-800 rounded-2xl shadow border dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase">
                            <tr>
                                <th class="p-4">Student Ambassador</th>
                                <th class="p-4">Task Details</th>
                                <th class="p-4">Submitted Date</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <template x-for="sub in pagedItems" :key="sub.id">
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition">
                                    <td class="p-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-sm">
                                                <span x-text="sub.student_name.charAt(0).toUpperCase()"></span>
                                            </div>
                                            <div>
                                                <p class="font-bold text-gray-900 dark:text-white" x-text="sub.student_name"></p>
                                                <p class="text-[10px] text-gray-400 font-mono" x-text="sub.student_no"></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 text-xs">
                                        <p class="font-bold text-gray-800" x-text="sub.task_title"></p>
                                        <p class="text-amber-600 font-semibold mt-0.5" x-text="'+' + sub.points_reward + ' Points'"></p>
                                    </td>
                                    <td class="p-4 text-xs text-gray-500 font-mono" x-text="formatDate(sub.created_at)"></td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="getStatusClass(sub.status)" x-text="sub.status"></span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <button @click="openViewModal(sub)" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition">
                                            Review Proof
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredItems.length === 0">
                                <td colspan="5" class="p-8 text-center text-gray-400 font-medium">No task submissions found.</td>
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

            <!-- Proof Review Modal -->
            <div x-show="isViewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4" x-cloak>
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border border-gray-100" @click.away="isViewModalOpen = false">
                    <div class="p-6 border-b flex justify-between items-center bg-gray-50">
                        <h3 class="text-xl font-bold text-gray-900">Review Task Submission</h3>
                        <button @click="isViewModalOpen = false" class="text-gray-400 hover:text-gray-600 font-black">✕</button>
                    </div>
                    
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 max-h-[70vh] overflow-y-auto">
                        <!-- Proof Screenshot -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-gray-400 uppercase">Uploaded Screenshot Proof</label>
                            <div class="border rounded-2xl p-2 bg-gray-50 overflow-hidden flex items-center justify-center h-64 shadow-inner">
                                <a :href="'../' + activeData.proof_path" target="_blank" title="View Fullscreen">
                                    <img :src="'../' + activeData.proof_path" class="max-h-full max-w-full rounded-lg object-contain hover:scale-105 transition cursor-pointer">
                                </a>
                            </div>
                        </div>

                        <!-- Details -->
                        <div class="space-y-4 text-sm">
                            <div>
                                <label class="text-xs text-gray-400 uppercase font-bold">Student Name</label>
                                <p class="font-bold text-gray-900" x-text="activeData.student_name"></p>
                                <p class="text-xs text-gray-500 font-mono" x-text="activeData.student_no"></p>
                            </div>

                            <div>
                                <label class="text-xs text-gray-400 uppercase font-bold">Campaign Task</label>
                                <p class="font-bold text-gray-900" x-text="activeData.task_title"></p>
                                <p class="text-xs text-amber-600 font-black" x-text="'+' + activeData.points_reward + ' points reward'"></p>
                            </div>

                            <div>
                                <label class="text-xs text-gray-400 uppercase font-bold">Student Submission Notes</label>
                                <p class="text-gray-700 bg-gray-50 p-3 rounded-xl border mt-1" x-text="activeData.notes || '—'"></p>
                            </div>

                            <template x-if="activeData.admin_comment">
                                <div>
                                    <label class="text-xs text-gray-400 uppercase font-bold text-red-600">Rejection Feedback</label>
                                    <p class="text-red-700 bg-red-50 p-3 rounded-xl border border-red-100 mt-1" x-text="activeData.admin_comment"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="p-4 bg-gray-50 flex justify-end gap-2 border-t">
                        <template x-if="activeData.status === 'Submitted'">
                            <div class="flex flex-col sm:flex-row gap-2 w-full">
                                <button @click="openRejectModal(activeData)" class="flex-1 bg-white hover:bg-gray-100 border border-red-200 text-red-600 font-bold py-2.5 rounded-xl text-sm transition">
                                    Reject Proof
                                </button>
                                <form action="actions/submission_logic.php" method="POST" class="flex-1">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="id" :value="activeData.id">
                                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-xl text-sm transition shadow-md shadow-emerald-500/10">
                                        Approve & Reward
                                    </button>
                                </form>
                            </div>
                        </template>

                        <template x-if="activeData.status !== 'Submitted'">
                            <button @click="isViewModalOpen = false" class="px-5 py-2.5 bg-gray-200 text-gray-700 rounded-xl text-sm font-bold hover:bg-gray-300">
                                Close
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Rejection Modal -->
            <div x-show="isRejectModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" x-cloak>
                <div class="bg-white rounded-3xl shadow-xl w-full max-w-sm overflow-hidden" @click.away="isRejectModalOpen = false">
                    <div class="p-6 border-b bg-gray-50 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-900 text-red-600">Reject Task Proof?</h3>
                        <button @click="isRejectModalOpen = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form action="actions/submission_logic.php" method="POST" class="p-6 space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="id" :value="activeData.id">

                        <p class="text-xs text-gray-500">Provide feedback regarding why this proof is invalid or incorrect.</p>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Feedback Comment *</label>
                            <input type="text" name="admin_comment" required placeholder="e.g. Incomplete campaign details or blurred image"
                                class="w-full px-4 py-2.5 border rounded-xl outline-none focus:ring-2 focus:ring-red-500 text-sm">
                        </div>

                        <div class="pt-4 flex flex-col sm:flex-row justify-end gap-2 border-t">
                            <button type="button" @click="isRejectModalOpen = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 font-bold hover:bg-gray-50">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-bold shadow hover:bg-red-700">Confirm Rejection</button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('submissionManager', () => ({
        items: <?php echo json_encode($submissions); ?>,
        search: '',
        tab: 'pending',
        page: 1,
        pageSize: 10,
        isViewModalOpen: false,
        isRejectModalOpen: false,
        activeData: {},

        init() {
            this.$watch('search', () => this.page = 1);
            this.$watch('tab', () => this.page = 1);
        },

        get filteredItems() {
            const query = this.search.toLowerCase();
            const searched = this.items.filter(item => 
                item.student_name.toLowerCase().includes(query) || 
                item.student_no.toLowerCase().includes(query) ||
                item.task_title.toLowerCase().includes(query)
            );
            if (this.tab === 'pending') {
                return searched.filter(item => item.status === 'Submitted');
            }
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
            return this.items.filter(item => item.status === 'Submitted').length;
        },

        openViewModal(sub) {
            this.activeData = sub;
            this.isViewModalOpen = true;
        },

        openRejectModal(sub) {
            this.activeData = sub;
            this.isRejectModalOpen = true;
            this.isViewModalOpen = false;
        },

        getStatusClass(status) {
            return {
                'bg-emerald-50 border border-emerald-100 text-emerald-700': status === 'Approved',
                'bg-orange-50 border border-orange-100 text-orange-700': status === 'Submitted',
                'bg-red-50 border border-red-100 text-red-700': status === 'Rejected'
            };
        },

        formatDate(date) {
            if (!date) return '';
            // Split date space time
            const parts = date.split(' ');
            const d = parts[0].split('-').reverse().join('-');
            return d + ' ' + (parts[1] || '').substring(0, 5);
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>

<?php require 'includes/header.php'; ?>
<?php require '../config/db.php'; ?>
<?php require_once '../includes/partner/portal_helpers.php'; ?>

<?php
if (!checkRole($pdo, 'coordinator')) {
    setFlash('error', 'Unauthorized access.');
    header('Location: dashboard.php');
    exit;
}

$partners = [];
if (dbTableExists($pdo, 'sa_partners')) {
    $partners = $pdo->query('
        SELECT p.*, s.full_name AS student_name, s.student_no AS student_no 
        FROM sa_partners p
        LEFT JOIN sa_students s ON p.referred_by_student_id = s.id 
        ORDER BY p.created_at DESC
    ')->fetchAll(PDO::FETCH_ASSOC);
}
$csrfToken = generateCsrfToken();
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="partnerManager">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            <div class="mb-6">
                <h3 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Partner Portal Directory</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Approve retail and physical activity center partners, manage API connections for external firms.</p>
            </div>

            <!-- Search & Filters -->
            <div class="mb-6 flex flex-wrap gap-3 items-center">
                <div class="relative flex-1 min-w-[220px] max-w-md">
                    <input type="text" x-model="search" @input="page = 1" placeholder="Search business, code, owner, city..."
                           class="w-full pl-9 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-500">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                </div>
                <select x-model="statusFilter" @change="page = 1" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-700 dark:text-gray-200">
                    <option value="">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Pending">Pending</option>
                    <option value="Rejected">Rejected</option>
                    <option value="Suspended">Suspended</option>
                </select>
                <div class="flex items-center gap-2">
                    <label class="text-xs text-gray-500 dark:text-gray-400 font-medium">Per Page:</label>
                    <select x-model.number="perPage" @change="page = 1" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-2.5 py-1.5 text-xs text-gray-700 dark:text-gray-200">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow border dark:border-gray-700 overflow-hidden">
                <div class="divide-y dark:divide-gray-700 lg:hidden">
                    <template x-for="p in paginatedItems" :key="'mobile-' + p.id">
                        <div class="p-4 space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white" x-text="p.business_name"></p>
                                    <p class="text-xs font-mono text-blue-600" x-text="p.partner_code"></p>
                                </div>
                                <span class="px-2 py-1 rounded-full text-xs font-bold" :class="statusClass(p.status)" x-text="p.status"></span>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                <div>
                                    <p class="text-gray-400 uppercase font-semibold">Owner</p>
                                    <p class="font-semibold mt-1" x-text="p.owner_name"></p>
                                    <p x-text="p.owner_email"></p>
                                    <p x-text="p.owner_mobile"></p>
                                    <template x-if="p.student_name">
                                        <div class="mt-2 text-[10px] bg-blue-50 text-blue-700 px-2 py-1 rounded-lg border border-blue-100 font-semibold inline-block">
                                            Referred By: <span x-text="p.student_name + ' (' + p.student_no + ')'"></span>
                                        </div>
                                    </template>
                                </div>
                                <div>
                                    <p class="text-gray-400 uppercase font-semibold">Activity</p>
                                    <p class="mt-1" x-text="activityLabel(p.activity_type)"></p>
                                    <p class="text-gray-500 mt-2">API: <span x-text="parseInt(p.api_enabled) === 1 ? 'Enabled' : 'Off'"></span></p>
                                </div>
                                <div class="sm:col-span-2">
                                    <p class="text-gray-400 uppercase font-semibold">Location</p>
                                    <p class="mt-1" x-text="p.city_name + (p.state_name ? ', ' + p.state_name : '')"></p>
                                    <p class="font-mono text-[10px] text-gray-400 break-all" x-text="p.latitude + ', ' + p.longitude"></p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <template x-if="p.status === 'Pending'">
                                    <form action="actions/partner_logic.php" method="POST" class="flex-1 min-w-[120px]">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id" :value="p.id">
                                        <button class="w-full bg-emerald-600 text-white px-3 py-2.5 rounded-xl text-sm font-bold">Approve</button>
                                    </form>
                                </template>
                                <template x-if="p.status === 'Active'">
                                    <form action="actions/partner_logic.php" method="POST" class="flex-1 min-w-[160px]">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="action" value="toggle_api">
                                        <input type="hidden" name="id" :value="p.id">
                                        <button class="w-full bg-blue-600 text-white px-3 py-2.5 rounded-xl text-sm font-bold" x-text="parseInt(p.api_enabled) === 1 ? 'Disable API' : 'Enable API'"></button>
                                    </form>
                                </template>
                            </div>
                        </div>
                    </template>
                    <div x-show="paginatedItems.length === 0" class="p-8 text-center text-gray-400">No partner registrations found.</div>
                </div>
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase font-bold">
                            <tr>
                                <th class="p-4">Business</th>
                                <th class="p-4">Owner</th>
                                <th class="p-4">Activity</th>
                                <th class="p-4">Location</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">API</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <template x-for="p in paginatedItems" :key="p.id">
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/40">
                                    <td class="p-4">
                                        <p class="font-bold text-gray-900 dark:text-white" x-text="p.business_name"></p>
                                        <p class="text-xs font-mono text-blue-600" x-text="p.partner_code"></p>
                                    </td>
                                    <td class="p-4 text-xs">
                                        <p class="font-semibold" x-text="p.owner_name"></p>
                                        <p x-text="p.owner_email"></p>
                                        <p x-text="p.owner_mobile"></p>
                                        <template x-if="p.student_name">
                                            <div class="mt-1.5 text-[10px] bg-blue-50/75 text-blue-700 px-2 py-0.5 rounded border border-blue-100/50 inline-block font-semibold">
                                                Referred By: <span x-text="p.student_name + ' (' + p.student_no + ')'"></span>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="p-4 text-xs" x-text="activityLabel(p.activity_type)"></td>
                                    <td class="p-4 text-xs">
                                        <p x-text="p.city_name + (p.state_name ? ', ' + p.state_name : '')"></p>
                                        <p class="font-mono text-[10px] text-gray-400" x-text="p.latitude + ', ' + p.longitude"></p>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold" :class="statusClass(p.status)" x-text="p.status"></span>
                                    </td>
                                    <td class="p-4 text-xs">
                                        <span x-text="parseInt(p.api_enabled) === 1 ? 'Enabled' : 'Off'"></span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="inline-flex flex-wrap gap-1 justify-end">
                                            <template x-if="p.status === 'Pending'">
                                                <form action="actions/partner_logic.php" method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="id" :value="p.id">
                                                    <button class="bg-emerald-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold">Approve</button>
                                                </form>
                                            </template>
                                            <template x-if="p.status === 'Active'">
                                                <form action="actions/partner_logic.php" method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="toggle_api">
                                                    <input type="hidden" name="id" :value="p.id">
                                                    <button class="bg-blue-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold" x-text="parseInt(p.api_enabled) === 1 ? 'Disable API' : 'Enable API'"></button>
                                                </form>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="paginatedItems.length === 0">
                                <td colspan="7" class="p-8 text-center text-gray-400">No partner registrations found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4 bg-gray-50/50 dark:bg-gray-800/50" x-show="totalPages > 1">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Showing page <span class="font-bold text-gray-800 dark:text-gray-200" x-text="page"></span> of <span class="font-bold text-gray-800 dark:text-gray-200" x-text="totalPages"></span> (<span x-text="items.length"></span> total partners)
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="page--" :disabled="page <= 1" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs font-semibold hover:bg-white dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-chevron-left mr-1"></i> Prev
                        </button>
                        <template x-for="p in totalPages" :key="p">
                            <button type="button" @click="page = p" 
                                    x-show="p === 1 || p === totalPages || (p >= page - 1 && p <= page + 1)"
                                    :class="page === p ? 'bg-blue-600 text-white font-bold' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600'" 
                                    class="w-8 h-8 rounded-lg text-xs font-medium border border-gray-200 dark:border-gray-700 flex items-center justify-center transition" 
                                    x-text="p">
                            </button>
                        </template>
                        <button type="button" @click="page++" :disabled="page >= totalPages" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs font-semibold hover:bg-white dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition text-gray-700 dark:text-gray-200">
                            Next <i class="fa-solid fa-chevron-right ml-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('partnerManager', () => ({
        rawItems: <?php echo json_encode($partners); ?>,
        search: '',
        statusFilter: '',
        page: 1,
        perPage: 15,

        get items() {
            return this.rawItems.filter(p => {
                const matchesSearch = !this.search || 
                    (p.business_name && p.business_name.toLowerCase().includes(this.search.toLowerCase())) ||
                    (p.partner_code && p.partner_code.toLowerCase().includes(this.search.toLowerCase())) ||
                    (p.owner_name && p.owner_name.toLowerCase().includes(this.search.toLowerCase())) ||
                    (p.owner_email && p.owner_email.toLowerCase().includes(this.search.toLowerCase())) ||
                    (p.city_name && p.city_name.toLowerCase().includes(this.search.toLowerCase()));
                const matchesStatus = !this.statusFilter || p.status === this.statusFilter;
                return matchesSearch && matchesStatus;
            });
        },

        get totalPages() {
            return Math.ceil(this.items.length / this.perPage) || 1;
        },

        get paginatedItems() {
            const start = (this.page - 1) * this.perPage;
            return this.items.slice(start, start + this.perPage);
        },

        activityLabel(type) {
            return type === 'physical_activity_center' ? 'Physical Activity Center' : 'Retail';
        },
        statusClass(status) {
            return {
                'bg-emerald-100 text-emerald-800': status === 'Active',
                'bg-orange-100 text-orange-800': status === 'Pending',
                'bg-red-100 text-red-800': status === 'Rejected' || status === 'Suspended',
            };
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>

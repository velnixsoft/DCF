<?php require 'includes/header.php'; ?>
<?php require '../config/db.php'; ?>

<?php
if (!checkRole($pdo, 'manager')) {
    setFlash('error', 'Unauthorized access.');
    header('Location: dashboard.php');
    exit;
}

$tasks = $pdo->query("
    SELECT t.*, 
           (SELECT COUNT(*) FROM sa_task_submissions s WHERE s.task_id = t.id) as total_submissions,
           (SELECT COUNT(*) FROM sa_task_submissions s WHERE s.task_id = t.id AND s.status = 'Submitted') as pending_submissions
    FROM sa_tasks t 
    ORDER BY t.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$csrfToken = generateCsrfToken();
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="taskManager">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            
            <div class="flex flex-col gap-4 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h3 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Campaign Tasks Manager</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Design tasks for interns to perform social sharing, awareness camps, or local partnerships.</p>
                    </div>
                    <button @click="openModal('create')" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2.5 rounded-xl shadow-lg shadow-emerald-500/20 flex items-center gap-2 transition text-sm">
                        <i class="fa-solid fa-plus"></i> Add Campaign Task
                    </button>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow p-4 mb-6 flex flex-col md:flex-row gap-3 items-center justify-between">
                <div class="relative w-full md:w-80">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" x-model="searchQuery" @input="page = 1" placeholder="Search tasks, campaigns..."
                           class="w-full pl-10 pr-4 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <select x-model="statusFilter" @change="page = 1"
                            class="text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-800 dark:text-gray-200 px-3 py-2 outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Draft">Draft</option>
                        <option value="Completed">Completed</option>
                        <option value="Archived">Archived</option>
                    </select>
                    <span class="text-xs text-gray-500 whitespace-nowrap">
                        Total: <strong class="text-gray-800 dark:text-white" x-text="filteredTasks.length"></strong>
                    </span>
                </div>
            </div>

            <!-- Task List Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase">
                            <tr>
                                <th class="p-4">Task Details</th>
                                <th class="p-4">Campaign Name</th>
                                <th class="p-4">Type</th>
                                <th class="p-4">Points Reward</th>
                                <th class="p-4">Submissions</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <template x-for="task in paginatedTasks" :key="task.id">
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition">
                                    <td class="p-4">
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white" x-text="task.title"></p>
                                            <p class="text-xs text-gray-500 mt-0.5" x-text="task.description ? task.description.substring(0, 80) + '...' : 'No description'"></p>
                                            <template x-if="task.due_date">
                                                <p class="text-[10px] text-red-500 font-bold mt-1">
                                                    <i class="fa-regular fa-calendar-times"></i> Due: <span x-text="formatDate(task.due_date)"></span>
                                                </p>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="p-4 text-xs font-semibold text-blue-700" x-text="task.campaign_name || 'General'"></td>
                                    <td class="p-4 text-xs font-semibold text-gray-600" x-text="task.task_type"></td>
                                    <td class="p-4 text-xs font-black text-amber-600" x-text="task.points_reward + ' pts'"></td>
                                    <td class="p-4 text-xs font-medium">
                                        <a href="student_submissions.php" class="hover:underline">
                                            <span class="font-bold text-gray-900" x-text="task.total_submissions"></span> submissions
                                        </a>
                                        <template x-if="Number(task.pending_submissions) > 0">
                                            <span class="block text-[10px] text-orange-600 font-bold mt-0.5">
                                                ⚠️ <span x-text="task.pending_submissions"></span> pending review
                                            </span>
                                        </template>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold" 
                                            :class="task.status === 'Active' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'" 
                                            x-text="task.status">
                                        </span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="inline-flex gap-1">
                                            <button @click="openModal('edit', task)" class="bg-gray-100 hover:bg-gray-200 text-gray-700 p-2 rounded-lg text-xs font-bold transition" title="Edit Task">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button @click="openDeleteModal(task.id)" class="bg-red-50 hover:bg-red-100 text-red-600 p-2 rounded-lg text-xs font-bold transition" title="Delete Task">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredTasks.length === 0">
                                <td colspan="7" class="p-8 text-center text-gray-400 font-medium">No campaign tasks match your criteria.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-600 dark:text-gray-300" x-show="filteredTasks.length > 0">
                    <div>
                        Showing <span class="font-bold" x-text="((page - 1) * perPage) + 1"></span> to 
                        <span class="font-bold" x-text="Math.min(page * perPage, filteredTasks.length)"></span> of 
                        <span class="font-bold" x-text="filteredTasks.length"></span> tasks
                    </div>
                    <div class="flex items-center gap-1" x-show="totalPages > 1">
                        <button @click="changePage(page - 1)" :disabled="page <= 1"
                                class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            Prev
                        </button>
                        <template x-for="p in totalPages" :key="p">
                            <button @click="changePage(p)"
                                    x-show="p === 1 || p === totalPages || (p >= page - 1 && p <= page + 1)"
                                    :class="page === p ? 'bg-emerald-600 text-white font-bold' : 'hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300'"
                                    class="w-8 h-8 rounded-lg text-xs flex items-center justify-center transition"
                                    x-text="p">
                            </button>
                        </template>
                        <button @click="changePage(page + 1)" :disabled="page >= totalPages"
                                class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            Next
                        </button>
                    </div>
                </div>
            </div>

            <!-- Task Form Modal -->
            <div x-show="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" x-cloak>
                <div class="bg-white rounded-3xl shadow-xl w-full max-w-lg overflow-hidden border border-gray-100" @click.away="isModalOpen = false">
                    <div class="p-6 border-b bg-gray-50 flex justify-between items-center">
                        <h3 class="text-xl font-bold text-gray-900" x-text="mode === 'create' ? 'Create Campaign Task' : 'Edit Campaign Task'"></h3>
                        <button @click="isModalOpen = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>
                    <form action="actions/task_logic.php" method="POST" class="p-6 space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" :value="formData.id">

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Task Title *</label>
                            <input type="text" name="title" required x-model="formData.title" placeholder="e.g. Share NGO Awareness Reel"
                                pattern="[a-zA-Z\s]+" title="Only letters and spaces are allowed."
                                class="w-full px-4 py-2.5 border rounded-xl outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Campaign Name</label>
                            <input type="text" name="campaign_name" x-model="formData.campaign_name" placeholder="e.g. Swachh Bharat June Drive"
                                class="w-full px-4 py-2.5 border rounded-xl outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Task Type</label>
                                <select name="task_type" x-model="formData.task_type" class="w-full px-4 py-2.5 border rounded-xl outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                                    <option value="Social Media">Social Media</option>
                                    <option value="Awareness Drive">Awareness Drive</option>
                                    <option value="Volunteer Drive">Volunteer Drive</option>
                                    <option value="College Seminar">College Seminar</option>
                                    <option value="NGO Partner Visit">NGO Partner Visit</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Points Reward *</label>
                                <input type="number" name="points_reward" required x-model="formData.points_reward" placeholder="25"
                                    class="w-full px-4 py-2.5 border rounded-xl outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Due Date</label>
                                <input type="date" name="due_date" x-model="formData.due_date" min="<?php echo date('Y-m-d'); ?>" max="9999-12-31"
                                    class="w-full px-4 py-2.5 border rounded-xl outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Publish Status</label>
                                <select name="status" x-model="formData.status" class="w-full px-4 py-2.5 border rounded-xl outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                                    <option value="Draft">Draft</option>
                                    <option value="Active">Active / Publish</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Task Description / Instructions</label>
                            <textarea name="description" rows="3" x-model="formData.description" placeholder="Provide detailed steps interns should follow to complete the task..."
                                class="w-full px-4 py-2.5 border rounded-xl outline-none focus:ring-2 focus:ring-emerald-500 text-sm"></textarea>
                        </div>

                        <div class="pt-4 flex justify-end gap-2 border-t">
                            <button type="button" @click="isModalOpen = false" class="px-4 py-2 border rounded-lg text-sm text-gray-600 font-bold hover:bg-gray-50">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-bold shadow hover:bg-emerald-700">Save Task</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div x-show="isDeleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4" x-cloak>
                <div class="bg-white rounded-3xl p-6 w-full max-w-sm text-center">
                    <div class="w-12 h-12 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Delete Task?</h3>
                    <p class="text-xs text-gray-500 mt-2">Are you sure you want to permanently delete this task? This action cannot be undone.</p>
                    <div class="mt-6 flex gap-2">
                        <button @click="isDeleteModalOpen = false" class="flex-1 border py-2 rounded-xl text-sm font-bold text-gray-600">Cancel</button>
                        <form action="actions/task_logic.php" method="POST" class="flex-1">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" :value="deleteId">
                            <button type="submit" class="w-full bg-red-600 text-white py-2 rounded-xl text-sm font-bold hover:bg-red-700 transition">Confirm Delete</button>
                        </form>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('taskManager', () => ({
        items: <?php echo json_encode($tasks); ?>,
        searchQuery: '',
        statusFilter: '',
        page: 1,
        perPage: 10,
        isModalOpen: false,
        isDeleteModalOpen: false,
        mode: 'create',
        deleteId: null,
        formData: {
            id: '',
            title: '',
            campaign_name: '',
            task_type: 'Social Media',
            points_reward: 25,
            due_date: '',
            status: 'Draft',
            description: ''
        },

        get filteredTasks() {
            return this.items.filter(task => {
                const matchesSearch = !this.searchQuery || 
                    (task.title && task.title.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (task.campaign_name && task.campaign_name.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (task.task_type && task.task_type.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                    (task.description && task.description.toLowerCase().includes(this.searchQuery.toLowerCase()));
                const matchesStatus = !this.statusFilter || task.status === this.statusFilter;
                return matchesSearch && matchesStatus;
            });
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.filteredTasks.length / this.perPage));
        },

        get paginatedTasks() {
            const start = (this.page - 1) * this.perPage;
            return this.filteredTasks.slice(start, start + this.perPage);
        },

        changePage(p) {
            if (p >= 1 && p <= this.totalPages) {
                this.page = p;
            }
        },

        openModal(mode, data = null) {
            this.mode = mode;
            if (mode === 'edit' && data) {
                this.formData = { ...data };
            } else {
                this.formData = {
                    id: '',
                    title: '',
                    campaign_name: '',
                    task_type: 'Social Media',
                    points_reward: 25,
                    due_date: '',
                    status: 'Draft',
                    description: ''
                };
            }
            this.isModalOpen = true;
        },

        openDeleteModal(id) {
            this.deleteId = id;
            this.isDeleteModalOpen = true;
        },

        formatDate(date) {
            if (!date) return '';
            return date.split('-').reverse().join('-');
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>

<?php
require 'includes/header.php';
require '../config/db.php';

if (!canAccessModule($pdo, 'manager', 'page.item_categories') && !canAccessModule($pdo, 'manager', 'page.donations')) {
    setFlash('error', 'Access denied.');
    header('Location: dashboard.php');
    exit;
}

$csrfToken = generateCsrfToken();

// Fetch all categories with item counts
$query = "
    SELECT c.*, 
           COUNT(i.id) AS total_items_count,
           COALESCE(SUM(i.quantity), 0) AS total_quantity_received
    FROM item_donation_categories c
    LEFT JOIN item_donations i ON c.id = i.category_id
    GROUP BY c.id
    ORDER BY c.display_order ASC, c.id ASC
";
$categories = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Summary stats
$totalCategories = count($categories);
$activeCategories = count(array_filter($categories, fn($c) => (int)$c['is_active'] === 1));
$totalItemsLinked = array_sum(array_column($categories, 'total_items_count'));
?>

<div class="flex h-screen overflow-hidden bg-gray-100 dark:bg-dark-bg" 
     x-data="categoryManager(<?php echo htmlspecialchars(json_encode($categories, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>)">

    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">

            <!-- Page Title -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h3 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white flex items-center gap-3">
                        <i class="fa-solid fa-boxes-stacked text-[#0F8B8D]"></i>
                        Item Donation Categories
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Manage item donation categories, units, icons, and display ordering for in-kind giving.
                    </p>
                </div>
            </div>

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white dark:bg-dark-card p-5 rounded-2xl shadow-sm border dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Categories</p>
                            <h4 class="text-2xl font-black text-gray-800 dark:text-white mt-1"><?php echo $totalCategories; ?></h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] flex items-center justify-center text-xl">
                            <i class="fa-solid fa-list-check"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-dark-card p-5 rounded-2xl shadow-sm border dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Active Categories</p>
                            <h4 class="text-2xl font-black text-emerald-600 mt-1"><?php echo $activeCategories; ?></h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-dark-card p-5 rounded-2xl shadow-sm border dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Items Pledged</p>
                            <h4 class="text-2xl font-black text-amber-500 mt-1"><?php echo number_format($totalItemsLinked); ?></h4>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-500 flex items-center justify-center text-xl">
                            <i class="fa-solid fa-box-open"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Category Card (Designation Pattern) -->
            <div class="bg-white dark:bg-dark-card p-6 rounded-2xl shadow-sm border dark:border-gray-700 mb-6">
                <div class="flex items-center justify-between gap-3 mb-4 pb-3 border-b dark:border-gray-700">
                    <div>
                        <h4 class="font-bold text-gray-800 dark:text-white text-base flex items-center gap-2">
                            <i class="fa-solid fa-plus-circle text-[#0F8B8D]"></i> Add New Item Category
                        </h4>
                        <p class="text-xs text-gray-400 mt-0.5">Create a new item category with custom icons, units, and description.</p>
                    </div>
                </div>

                <form action="actions/item_donation_category_logic.php" method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="add_category">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                Category Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="category_name" required placeholder="e.g. Winter Clothing, School Bags" 
                                   class="w-full border rounded-xl p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                FontAwesome Icon Class
                            </label>
                            <div class="relative">
                                <input type="text" name="category_icon" x-model="newIcon" placeholder="e.g. fa-shirt, fa-bowl-rice" 
                                       class="w-full pl-9 pr-3 py-2.5 border rounded-xl text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                <span class="absolute left-3 top-3 text-gray-400">
                                    <i class="fa-solid" :class="newIcon || 'fa-box'"></i>
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                Suggested Units
                            </label>
                            <input type="text" name="unit_suggestions" placeholder="e.g. pcs, kg, boxes, sets" value="pcs, kg, boxes, sets"
                                   class="w-full border rounded-xl p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-3">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                Description / Examples
                            </label>
                            <input type="text" name="description" placeholder="e.g. Clean and wearable clothes for children, men, and women" 
                                   class="w-full border rounded-xl p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                Display Order
                            </label>
                            <div class="flex gap-2">
                                <input type="number" name="display_order" value="<?php echo $totalCategories + 1; ?>" min="0" 
                                       class="w-24 border rounded-xl p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none text-center">
                                <button type="submit" 
                                        class="flex-1 bg-[#0F8B8D] hover:bg-[#0c7274] text-white rounded-xl px-4 py-2.5 text-sm font-bold shadow-sm transition flex items-center justify-center gap-1.5">
                                    <i class="fa-solid fa-plus"></i> Add Category
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Icon Suggestions Chips -->
                    <div class="flex flex-wrap items-center gap-1.5 pt-1 text-xs">
                        <span class="text-gray-400 text-[11px] font-semibold">Popular Icons:</span>
                        <template x-for="ic in presetIcons" :key="ic.class">
                            <button type="button" @click="newIcon = ic.class" 
                                    class="px-2.5 py-1 rounded-lg bg-gray-50 dark:bg-gray-700/50 hover:bg-teal-50 hover:text-[#0F8B8D] border dark:border-gray-600 text-gray-600 dark:text-gray-300 transition flex items-center gap-1.5 text-[11px]">
                                <i class="fa-solid" :class="ic.class"></i>
                                <span x-text="ic.label"></span>
                            </button>
                        </template>
                    </div>
                </form>
            </div>

            <!-- Categories Management Table & List -->
            <div class="bg-white dark:bg-dark-card rounded-2xl shadow-sm border dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b dark:border-gray-700 flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3">
                    <div class="flex items-center gap-2">
                        <h4 class="font-bold text-gray-800 dark:text-white text-base">Configured Categories</h4>
                        <span class="px-2.5 py-0.5 rounded-full bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] text-xs font-bold" x-text="filteredItems.length"></span>
                    </div>

                    <div class="relative sm:w-72">
                        <input type="text" x-model="search" placeholder="Search category or units..." 
                               class="w-full pl-9 pr-4 py-2 border rounded-xl bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-xs outline-none">
                        <i class="fa-solid fa-magnifying-glass text-gray-400 absolute left-3 top-2.5 text-xs"></i>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 border-b dark:border-gray-700">
                                <th class="p-3.5 w-12 text-center">#</th>
                                <th class="p-3.5">Category Details</th>
                                <th class="p-3.5">Suggested Units</th>
                                <th class="p-3.5 text-center">Items Donated</th>
                                <th class="p-3.5 text-center">Status</th>
                                <th class="p-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <template x-for="cat in paginatedItems" :key="cat.id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/20 transition">
                                    <td class="p-3.5 text-center font-bold text-gray-400 text-xs" x-text="cat.display_order"></td>

                                    <td class="p-3.5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] flex items-center justify-center text-lg shadow-inner flex-shrink-0">
                                                <i class="fa-solid" :class="cat.category_icon || 'fa-box'"></i>
                                            </div>
                                            <div>
                                                <h5 class="font-bold text-gray-800 dark:text-white text-sm" x-text="cat.category_name"></h5>
                                                <p class="text-xs text-gray-400 font-mono" x-text="cat.category_slug"></p>
                                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1" x-text="cat.description || '—'"></p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="p-3.5">
                                        <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-2.5 py-1 rounded-lg font-mono" x-text="cat.unit_suggestions"></span>
                                    </td>

                                    <td class="p-3.5 text-center">
                                        <span class="font-bold text-gray-800 dark:text-white" x-text="cat.total_items_count"></span>
                                        <span class="text-xs text-gray-400 block">Donations</span>
                                    </td>

                                    <!-- Status Toggle (Designation Pattern) -->
                                    <td class="p-3.5 text-center">
                                        <form action="actions/item_donation_category_logic.php" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="toggle_category">
                                            <input type="hidden" name="id" :value="cat.id">
                                            <input type="hidden" name="is_active" :value="cat.is_active == 1 ? 0 : 1">
                                            <button type="submit" 
                                                    class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider transition"
                                                    :class="cat.is_active == 1 ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-400'"
                                                    x-text="cat.is_active == 1 ? 'Active' : 'Inactive'">
                                            </button>
                                        </form>
                                    </td>

                                    <!-- Action Buttons -->
                                    <td class="p-3.5 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <!-- Edit Button -->
                                            <button type="button" @click="openEditModal(cat)" 
                                                    class="p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/40 transition"
                                                    title="Edit Category">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            <!-- Delete Button -->
                                            <button type="button" @click="openDeleteModal(cat)" 
                                                    class="p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 dark:bg-rose-900/20 dark:hover:bg-rose-900/40 transition"
                                                    title="Delete Category">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div x-show="filteredItems.length === 0" class="p-12 text-center text-gray-400">
                        <i class="fa-solid fa-boxes-stacked text-3xl mb-2 opacity-40"></i>
                        <p class="font-medium">No item categories found matching your search.</p>
                    </div>
                </div>

                <!-- Pagination Footer -->
                <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 border-t dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4" x-show="filteredItems.length > 0">
                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                        <span>Showing <strong class="text-gray-800 dark:text-gray-200" x-text="(page - 1) * perPage + 1"></strong> to <strong class="text-gray-800 dark:text-gray-200" x-text="Math.min(page * perPage, filteredItems.length)"></strong> of <strong class="text-gray-800 dark:text-gray-200" x-text="filteredItems.length"></strong> results</span>
                        <div class="flex items-center gap-1.5 ml-2">
                            <label class="text-[11px] text-gray-400">Per page:</label>
                            <select x-model.number="perPage" @change="page = 1" class="text-xs py-1 px-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300">
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-1" x-show="totalPages > 1">
                        <button @click="setPage(1)" :disabled="page === 1" class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-angles-left text-[10px]"></i>
                        </button>
                        <button @click="setPage(page - 1)" :disabled="page === 1" class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-chevron-left text-[10px]"></i>
                        </button>
                        <template x-for="p in totalPages" :key="p">
                            <button x-show="p === 1 || p === totalPages || (p >= page - 2 && p <= page + 2)"
                                    @click="setPage(p)" 
                                    :class="page === p ? 'bg-[#0F8B8D] text-white font-bold border-[#0F8B8D] shadow-xs' : 'text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700'" 
                                    class="w-8 h-8 rounded-lg border text-xs flex items-center justify-center transition" 
                                    x-text="p">
                            </button>
                        </template>
                        <button @click="setPage(page + 1)" :disabled="page === totalPages" class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </button>
                        <button @click="setPage(totalPages)" :disabled="page === totalPages" class="px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-angles-right text-[10px]"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL: Edit Category -->
            <div x-show="isEditModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak x-transition>
                <div class="bg-white dark:bg-dark-card rounded-3xl shadow-2xl border dark:border-gray-700 w-full max-w-lg p-6">
                    <div class="flex justify-between items-center pb-3 border-b dark:border-gray-700 mb-4">
                        <h4 class="font-bold text-gray-800 dark:text-white text-base flex items-center gap-2">
                            <i class="fa-solid fa-pen-to-square text-[#0F8B8D]"></i> Edit Item Category
                        </h4>
                        <button type="button" @click="isEditModalOpen = false" class="text-gray-400 hover:text-gray-600 text-lg">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form action="actions/item_donation_category_logic.php" method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="update_category">
                        <input type="hidden" name="id" :value="editItem ? editItem.id : ''">

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                                <input type="text" name="category_name" required x-model="editItem ? editItem.category_name : ''" 
                                       class="w-full border rounded-xl p-2.5 text-xs dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Slug</label>
                                <input type="text" name="category_slug" x-model="editItem ? editItem.category_slug : ''" 
                                       class="w-full border rounded-xl p-2.5 text-xs dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none font-mono">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Icon Class</label>
                                <div class="relative">
                                    <input type="text" name="category_icon" x-model="editItem ? editItem.category_icon : ''" 
                                           class="w-full pl-8 pr-3 py-2 border rounded-xl text-xs dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none">
                                    <span class="absolute left-2.5 top-2 text-gray-400 text-xs">
                                        <i class="fa-solid" :class="editItem ? editItem.category_icon : 'fa-box'"></i>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Suggested Units</label>
                                <input type="text" name="unit_suggestions" x-model="editItem ? editItem.unit_suggestions : ''" 
                                       class="w-full border rounded-xl p-2 text-xs dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <textarea name="description" rows="2" x-model="editItem ? editItem.description : ''" 
                                      class="w-full border rounded-xl p-2.5 text-xs dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Display Order</label>
                                <input type="number" name="display_order" min="0" x-model="editItem ? editItem.display_order : 0" 
                                       class="w-full border rounded-xl p-2 text-xs dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none text-center">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                <select name="is_active" x-model="editItem ? editItem.is_active : 1" 
                                        class="w-full border rounded-xl p-2 text-xs dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex gap-2 pt-3">
                            <button type="button" @click="isEditModalOpen = false" 
                                    class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="flex-1 py-2.5 bg-[#0F8B8D] hover:bg-[#0c7274] text-white rounded-xl text-xs font-bold shadow-md">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: Delete Category Confirmation -->
            <div x-show="isDeleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak x-transition>
                <div class="bg-white dark:bg-dark-card rounded-3xl shadow-2xl border dark:border-gray-700 w-full max-w-sm p-6 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-3 text-2xl shadow-inner">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>

                    <h4 class="text-lg font-bold text-gray-800 dark:text-white">Delete Category?</h4>
                    <p class="text-xs text-gray-500 mt-1">
                        Are you sure you want to permanently delete <strong class="text-gray-700 dark:text-gray-200" x-text="deleteItem ? deleteItem.category_name : ''"></strong>?
                    </p>

                    <form action="actions/item_donation_category_logic.php" method="POST" class="mt-5 space-y-3">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="id" :value="deleteItem ? deleteItem.id : ''">

                        <div class="flex gap-2">
                            <button type="button" @click="isDeleteModalOpen = false" 
                                    class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-md">
                                Delete
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
function categoryManager(initialItems) {
    return {
        items: initialItems || [],
        search: '',
        page: 1,
        perPage: 10,
        newIcon: 'fa-shirt',
        isEditModalOpen: false,
        isDeleteModalOpen: false,
        editItem: null,
        deleteItem: null,

        get totalPages() {
            return Math.ceil(this.filteredItems.length / this.perPage) || 1;
        },

        get paginatedItems() {
            const start = (this.page - 1) * this.perPage;
            return this.filteredItems.slice(start, start + this.perPage);
        },

        setPage(p) {
            if (p >= 1 && p <= this.totalPages) this.page = p;
        },

        presetIcons: [
            { class: 'fa-shirt', label: 'Clothes' },
            { class: 'fa-bowl-rice', label: 'Ration' },
            { class: 'fa-book-open', label: 'Books' },
            { class: 'fa-pills', label: 'Medicines' },
            { class: 'fa-pen-ruler', label: 'Stationery' },
            { class: 'fa-bed', label: 'Blankets' },
            { class: 'fa-wheelchair', label: 'Wheelchairs' },
            { class: 'fa-apple-whole', label: 'Food' },
            { class: 'fa-box-open', label: 'Others' }
        ],

        get filteredItems() {
            if (!this.search.trim()) return this.items;
            const q = this.search.toLowerCase().trim();
            return this.items.filter(item => 
                (item.category_name || '').toLowerCase().includes(q) ||
                (item.category_slug || '').toLowerCase().includes(q) ||
                (item.unit_suggestions || '').toLowerCase().includes(q) ||
                (item.description || '').toLowerCase().includes(q)
            );
        },

        openEditModal(item) {
            this.editItem = { ...item };
            this.isEditModalOpen = true;
        },

        openDeleteModal(item) {
            this.deleteItem = item;
            this.isDeleteModalOpen = true;
        }
    };
}
</script>

<?php require 'includes/footer.php'; ?>

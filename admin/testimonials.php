<?php
require 'includes/header.php';
require '../config/db.php';
require '../includes/functions.php';

$csrfToken = generateCsrfToken();
if (!canAccessModule($pdo, 'coordinator', 'page.testimonials')) {
    setFlash('error', 'Access denied. Coordinator/Manager/Admin required.');
    header('Location: dashboard.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(t.name LIKE :search OR t.role LIKE :search OR t.content LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($statusFilter !== '') {
    $where[] = "t.status = :status";
    $params[':status'] = $statusFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM testimonials t $whereSql");
foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
$countStmt->execute();
$totalTestimonials = (int)$countStmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT t.*, 
           COALESCE(t.avatar_path, '') as avatar_path
    FROM testimonials t 
    $whereSql
    ORDER BY t.priority_order DESC, t.id DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300 min-w-0">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-3 sm:p-4 md:p-6">

            <!-- Page header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <div>
                    <h3 class="text-xl sm:text-2xl md:text-3xl font-medium text-gray-700 dark:text-white">Testimonials</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage community testimonials for homepage display.</p>
                </div>
                <button type="button"
                    class="shrink-0 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 self-start sm:self-auto transition-colors"
                    onclick="openTestimonialModal(null)">
                    <i class="fas fa-plus"></i> Add Testimonial
                </button>
            </div>

            <!-- Search & Filters -->
            <form method="GET" class="mb-6 flex flex-wrap gap-3 items-center">
                <div class="relative flex-1 min-w-[220px] max-w-md">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name, role, content..."
                           class="w-full pl-9 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-emerald-500">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                </div>
                <select name="status" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-700 dark:text-gray-200">
                    <option value="">All Statuses</option>
                    <option value="Active" <?php echo $statusFilter === 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo $statusFilter === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition">
                    Filter
                </button>
                <?php if ($search !== '' || $statusFilter !== ''): ?>
                    <a href="testimonials.php" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition">
                        Reset
                    </a>
                <?php endif; ?>
            </form>

            <!-- Table card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border dark:border-gray-700 overflow-hidden">
                <div class="px-4 sm:px-6 py-4 border-b dark:border-gray-700 flex items-center justify-between">
                    <h4 class="text-base sm:text-lg font-semibold dark:text-white">
                        Testimonials Directory
                        <span class="ml-1 text-sm font-normal text-gray-500">(Total <?php echo $totalTestimonials; ?>)</span>
                    </h4>
                </div>

                <!-- Mobile card list (hidden on md+) -->
                <div class="md:hidden divide-y dark:divide-gray-700">
                    <?php foreach ($testimonials as $t): ?>
                    <div class="p-4 space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-bold text-sm shrink-0 overflow-hidden">
                                <?php if (!empty($t['avatar_path'])): ?>
                                    <img src="../<?php echo htmlspecialchars($t['avatar_path']); ?>" alt="<?php echo htmlspecialchars($t['name']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <?php echo htmlspecialchars($t['initial']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0">
                                <div class="font-semibold dark:text-white truncate"><?php echo htmlspecialchars($t['name']); ?></div>
                                <div class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($t['role']); ?></div>
                            </div>
                            <div class="ml-auto flex items-center gap-2 shrink-0">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium <?php echo $t['status'] === 'Active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'; ?>">
                                    <?php echo htmlspecialchars($t['status']); ?>
                                </span>
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded-full dark:bg-blue-900/30 dark:text-blue-300">
                                    #<?php echo (int)$t['priority_order']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="text-xs text-yellow-500"><?php echo htmlspecialchars($t['stars']); ?></div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2"><?php echo htmlspecialchars(mb_substr($t['content'], 0, 120)); ?>...</p>
                        <!-- Mobile actions -->
                        <div class="flex flex-wrap gap-2">
                            <button type="button"
                                class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg transition-colors"
                                onclick="openTestimonialModal(<?php echo (int)$t['id']; ?>)">
                                Edit
                            </button>
                            <?php if ($t['status'] === 'Active'): ?>
                            <form action="actions/testimonial_logic.php" method="POST" class="inline">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                                <input type="hidden" name="status" value="Inactive">
                                <button type="submit" class="text-xs bg-gray-500 hover:bg-gray-600 text-white px-3 py-1.5 rounded-lg transition-colors">Deactivate</button>
                            </form>
                            <?php else: ?>
                            <form action="actions/testimonial_logic.php" method="POST" class="inline">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                                <input type="hidden" name="status" value="Active">
                                <button type="submit" class="text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg transition-colors">Activate</button>
                            </form>
                            <?php endif; ?>
                            <button type="button" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg transition-colors"
                                onclick="openDeleteModal(<?php echo (int)$t['id']; ?>)">Delete</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($testimonials)): ?>
                        <div class="p-8 text-center text-gray-500">No testimonials found.</div>
                    <?php endif; ?>
                    <?php echo render_admin_pagination($totalTestimonials, $page, $perPage, ['search' => $search, 'status' => $statusFilter]); ?>
                </div>

                <!-- Desktop table (hidden on <md) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300" style="min-width: 680px;">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Avatar</th>
                                <th class="px-4 py-3 font-semibold">Person</th>
                                <th class="px-4 py-3 font-semibold">Message</th>
                                <th class="px-4 py-3 font-semibold">Rating</th>
                                <th class="px-4 py-3 font-semibold">Order</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <?php foreach ($testimonials as $t): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                                <td class="px-4 py-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-bold text-sm shadow-sm overflow-hidden shrink-0">
                                        <?php if (!empty($t['avatar_path'])): ?>
                                            <img src="../<?php echo htmlspecialchars($t['avatar_path']); ?>" alt="<?php echo htmlspecialchars($t['name']); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($t['initial']); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 max-w-[160px]">
                                    <div class="font-semibold dark:text-white group-hover:text-black dark:group-hover:text-black truncate"><?php echo htmlspecialchars($t['name']); ?></div>
                                    <div class="text-xs text-gray-500 group-hover:text-black dark:group-hover:text-black truncate"><?php echo htmlspecialchars($t['role']); ?></div>
                                </td>
                                <td class="px-4 py-3 max-w-xs">
                                    <div class="line-clamp-2 text-sm text-gray-600 dark:text-gray-400 group-hover:text-black dark:group-hover:text-black"><?php echo htmlspecialchars(mb_substr($t['content'], 0, 100)); ?>...</div>
                                </td>
                                <td class="px-4 py-3 text-yellow-500 whitespace-nowrap text-sm"><?php echo htmlspecialchars($t['stars']); ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full dark:bg-blue-900/30 dark:text-blue-300">#<?php echo (int)$t['priority_order']; ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium whitespace-nowrap <?php echo $t['status'] === 'Active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'; ?>">
                                        <?php echo htmlspecialchars($t['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1 flex-wrap">
                                        <button type="button"
                                            class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-2.5 py-1.5 rounded-lg transition-colors whitespace-nowrap"
                                            onclick="openTestimonialModal(<?php echo (int)$t['id']; ?>)">Edit</button>
                                        <?php if ($t['status'] === 'Active'): ?>
                                        <form action="actions/testimonial_logic.php" method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                                            <input type="hidden" name="status" value="Inactive">
                                            <button type="submit" class="text-xs bg-gray-500 hover:bg-gray-600 text-white px-2.5 py-1.5 rounded-lg transition-colors whitespace-nowrap">Deactivate</button>
                                        </form>
                                        <?php else: ?>
                                        <form action="actions/testimonial_logic.php" method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                                            <input type="hidden" name="status" value="Active">
                                            <button type="submit" class="text-xs bg-green-600 hover:bg-green-700 text-white px-2.5 py-1.5 rounded-lg transition-colors whitespace-nowrap">Activate</button>
                                        </form>
                                        <?php endif; ?>
                                        <button type="button" class="text-xs bg-red-600 hover:bg-red-700 text-white px-2.5 py-1.5 rounded-lg transition-colors"
                                            onclick="openDeleteModal(<?php echo (int)$t['id']; ?>)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($testimonials)): ?>
                            <tr>
                                <td colspan="7" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-quote-left text-4xl mb-4 opacity-50 block"></i>
                                    <p>No testimonials found.</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php echo render_admin_pagination($totalTestimonials, $page, $perPage, ['search' => $search, 'status' => $statusFilter]); ?>
            </div>
        </main>
    </div>
</div>

<!-- Testimonial Modal -->
<div id="testimonialModal" class="fixed inset-0 hidden z-[90] items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="bg-white dark:bg-gray-800 w-full max-w-2xl rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 flex flex-col max-h-[90dvh]">

        <!-- Modal header -->
        <div class="px-5 py-4 border-b dark:border-gray-700 flex items-center justify-between shrink-0">
            <div>
                <h4 class="text-lg font-bold text-gray-900 dark:text-white" id="modalTitle">Add Testimonial</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400" id="modalSubtitle"></p>
            </div>
            <button type="button" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" onclick="closeTestimonialModal()">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Modal body — scrollable -->
        <div class="overflow-y-auto flex-1">
            <form id="testimonialForm" action="actions/testimonial_logic.php" method="POST" enctype="multipart/form-data" class="p-5 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="testimonial_id" value="">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Name *</label>
                        <input type="text" name="name" id="name" required maxlength="150"
                               class="w-full px-3 py-2.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Initials (e.g. R) *</label>
                        <input type="text" name="initial" id="initial" required maxlength="2"
                               class="w-full px-3 py-2.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 uppercase text-lg font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Role / Title *</label>
                        <input type="text" name="role" id="role" required maxlength="150"
                               class="w-full px-3 py-2.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Stars *</label>
                        <select name="stars" id="stars" required class="w-full px-3 py-2.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="★★★★★">★★★★★ (5 Stars)</option>
                            <option value="★★★★☆">★★★★☆ (4 Stars)</option>
                            <option value="★★★☆☆">★★★☆☆ (3 Stars)</option>
                            <option value="★★☆☆☆">★★☆☆☆ (2 Stars)</option>
                            <option value="★☆☆☆☆">★☆☆☆☆ (1 Star)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Testimonial Content *</label>
                    <textarea name="content" id="content" required rows="4" maxlength="1000"
                              class="w-full px-3 py-2.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                              placeholder="The transparency is remarkable..."></textarea>
                    <p class="text-xs text-gray-500 mt-1">Max 1000 characters.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Priority Order</label>
                        <input type="number" name="priority_order" id="priority_order" min="0" max="999" value="0"
                               class="w-full px-3 py-2.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Higher = displays first</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Avatar Image <span class="font-normal">(Optional)</span></label>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="w-full text-sm dark:text-gray-300">
                        <p class="text-xs text-gray-500 mt-1">Square, max 500KB</p>
                    </div>
                </div>

                <!-- Footer buttons inside scrollable body -->
                <div class="flex flex-col-reverse sm:flex-row gap-3 pt-2">
                    <button type="button"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 py-2.5 rounded-lg font-medium transition-colors"
                            onclick="closeTestimonialModal()">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-2.5 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-save mr-2"></i> Save Testimonial
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openTestimonialModal(id) {
    const modal = document.getElementById('testimonialModal');
    const form  = document.getElementById('testimonialForm');

    document.getElementById('modalTitle').textContent = id ? 'Edit Testimonial' : 'Add New Testimonial';

    if (id) {
        fetch(`actions/testimonial_get.php?id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const d = data.data;
                    document.getElementById('testimonial_id').value   = d.id            || '';
                    document.getElementById('name').value             = d.name          || '';
                    document.getElementById('initial').value          = d.initial       || '';
                    document.getElementById('role').value             = d.role          || '';
                    document.getElementById('content').value          = d.content       || '';
                    document.getElementById('stars').value            = d.stars         || '★★★★★';
                    document.getElementById('priority_order').value   = d.priority_order || 0;
                    document.getElementById('modalSubtitle').textContent = d.name || '';
                    showModal(modal);
                } else {
                    alert('Error loading: ' + (data.error || 'Unknown'));
                }
            })
            .catch(() => alert('Network error'));
    } else {
        form.reset();
        document.getElementById('testimonial_id').value = '';
        document.getElementById('priority_order').value = 0;
        document.getElementById('modalSubtitle').textContent = '';
        showModal(modal);
    }
}

function showModal(modal) {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeTestimonialModal() {
    const modal = document.getElementById('testimonialModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
    document.getElementById('testimonialForm').reset();
}

// Close on backdrop click
document.getElementById('testimonialModal').addEventListener('click', function(e) {
    if (e.target === this) closeTestimonialModal();
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeTestimonialModal();
});

// Delete Confirmation Modal functions
function openDeleteModal(id) {
    const modal = document.getElementById('deleteTestimonialModal');
    document.getElementById('delete_testimonial_id').value = id;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteTestimonialModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

document.getElementById('deleteTestimonialModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeDeleteModal();
});
</script>

<!-- Delete Testimonial Modal -->
<div id="deleteTestimonialModal" class="fixed inset-0 hidden z-[95] items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-3xl p-6 w-full max-w-sm text-center shadow-2xl animate-fade-in">
        <div class="w-12 h-12 bg-red-50 dark:bg-red-950/30 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-triangle-exclamation text-xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Delete Testimonial?</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Are you sure you want to permanently delete this testimonial? This action cannot be undone.</p>
        <div class="mt-6 flex flex-col sm:flex-row gap-2">
            <button type="button" onclick="closeDeleteModal()" class="flex-1 border dark:border-gray-600 bg-white dark:bg-gray-700 py-2 rounded-xl text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">Cancel</button>
            <form action="actions/testimonial_logic.php" method="POST" class="flex-1">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_testimonial_id" value="">
                <button type="submit" class="w-full bg-red-600 text-white py-2 rounded-xl text-sm font-bold hover:bg-red-700 transition">Confirm Delete</button>
            </form>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
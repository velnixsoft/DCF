<?php
// ============================================================
// admin/org_structure.php
// Organization Structure & Designation Hierarchy Management
// ============================================================

require_once '../config/db.php';
require_once '../includes/functions.php';

$csrfToken = generateCsrfToken();

if (!checkRole($pdo, 'manager')) {
    setFlash('error', 'Unauthorized access. Manager/Admin role required.');
    header('Location: dashboard.php');
    exit;
}

require 'includes/header.php';

// 1. Fetch all existing member_designations for dropdown link
$memberDesignations = [];
try {
    $memberDesignations = $pdo->query("SELECT id, title FROM member_designations WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $memberDesignations = [];
}

// 2. Fetch all management_body members for profile link
$managementProfiles = [];
try {
    $managementProfiles = $pdo->query("SELECT id, name, designation, department, phone, email, photo FROM management_body WHERE is_active = 1 ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $managementProfiles = [];
}

// 3. Fetch all org_structure nodes
$allNodes = [];
try {
    $stmt = $pdo->query("
        SELECT o.*, 
               p.title AS parent_title,
               m.name AS mgmt_name, m.photo AS mgmt_photo,
               d.title AS desig_name,
               (SELECT COUNT(*) FROM org_structure c WHERE c.parent_id = o.id) AS direct_children_count
        FROM org_structure o
        LEFT JOIN org_structure p ON o.parent_id = p.id
        LEFT JOIN management_body m ON o.management_body_id = m.id
        LEFT JOIN member_designations d ON o.designation_id = d.id
        ORDER BY o.level_tier ASC, o.sort_order ASC, o.id ASC
    ");
    $allNodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $allNodes = [];
}

// Build nested tree structure for visual hierarchy
function buildOrgTree(array $elements, $parentId = null) {
    $branch = [];
    foreach ($elements as $element) {
        if ($element['parent_id'] == $parentId) {
            $children = buildOrgTree($elements, $element['id']);
            if ($children) {
                $element['children'] = $children;
            } else {
                $element['children'] = [];
            }
            $branch[] = $element;
        }
    }
    return $branch;
}

$treeData = buildOrgTree($allNodes, null);

// Compute Metrics
$totalNodes = count($allNodes);
$tier1Count = 0;
$stateDistCount = 0;
$fieldCount = 0;
$activeCount = 0;

foreach ($allNodes as $n) {
    if ($n['is_active']) $activeCount++;
    if ($n['level_tier'] <= 2) $tier1Count++;
    elseif ($n['level_tier'] == 3 || $n['level_tier'] == 4) $stateDistCount++;
    else $fieldCount++;
}
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="orgStructureManager(
    <?php echo htmlspecialchars(json_encode($allNodes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>,
    <?php echo htmlspecialchars(json_encode($managementProfiles, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>
)">
    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 lg:p-8">
            <!-- Flash Message -->
            <?php displayFlash(); ?>

            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-2.5">
                        <span class="p-2.5 rounded-2xl bg-teal-500/10 text-teal-600 dark:text-teal-400">
                            <i class="fa-solid fa-sitemap text-2xl"></i>
                        </span>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight">Organization Structure & Hierarchy</h1>
                            <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                Design and manage parent-child designation levels, reporting lines, departments, and appointed office bearers.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="/organization-structure" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-800 hover:bg-gray-900 text-white text-xs font-bold rounded-xl shadow-sm transition">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        <span>View Public Org Chart</span>
                    </a>

                    <button @click="openCreateModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-xl shadow-sm transition transform hover:-translate-y-0.5 shadow-teal-600/20">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Add Designation Node</span>
                    </button>
                </div>
            </div>

            <!-- 4 KPI Metrics -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-900/40 text-teal-600 dark:text-teal-400 flex items-center justify-center text-lg flex-shrink-0">
                        <i class="fa-solid fa-sitemap"></i>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 font-bold uppercase block">Total Positions</span>
                        <span class="text-xl font-black text-gray-900 dark:text-white"><?php echo $totalNodes; ?></span>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-lg flex-shrink-0">
                        <i class="fa-solid fa-crown"></i>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 font-bold uppercase block">Apex & Leadership</span>
                        <span class="text-xl font-black text-indigo-600 dark:text-indigo-400"><?php echo $tier1Count; ?></span>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg flex-shrink-0">
                        <i class="fa-solid fa-city"></i>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 font-bold uppercase block">State & District</span>
                        <span class="text-xl font-black text-blue-600 dark:text-blue-400"><?php echo $stateDistCount; ?></span>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 shadow-xs flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 flex items-center justify-center text-lg flex-shrink-0">
                        <i class="fa-solid fa-people-roof"></i>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 font-bold uppercase block">Block & Field Units</span>
                        <span class="text-xl font-black text-rose-600 dark:text-rose-400"><?php echo $fieldCount; ?></span>
                    </div>
                </div>
            </div>

            <!-- View Switcher -->
            <div class="flex items-center justify-between gap-3 mb-6 pb-2 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <button type="button" @click="viewMode = 'table'" 
                            class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2"
                            :class="viewMode === 'table' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                        <i class="fa-solid fa-list-tree"></i>
                        <span>Hierarchy Table View</span>
                    </button>

                    <button type="button" @click="viewMode = 'tree'" 
                            class="px-4 py-2 text-xs font-bold rounded-xl transition flex items-center gap-2"
                            :class="viewMode === 'tree' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                        <i class="fa-solid fa-diagram-project"></i>
                        <span>Visual Tree Chart</span>
                    </button>
                </div>

                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Total: <strong class="text-gray-900 dark:text-white" x-text="filteredNodes.length"></strong> hierarchy designations
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- 1. HIERARCHY TABLE VIEW -->
            <!-- ============================================================ -->
            <div x-show="viewMode === 'table'" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <!-- Search & Filters -->
                <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 flex flex-wrap items-center justify-between gap-3">
                    <div class="relative flex-1 min-w-[220px] max-w-md">
                        <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" x-model="search" @input="page = 1" placeholder="Search designation, department, officer..." 
                               class="w-full pl-9 pr-4 py-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-xs text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-teal-500">
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-gray-500 dark:text-gray-400 font-medium">Per Page:</label>
                        <select x-model.number="perPage" @change="page = 1" class="bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-2.5 py-1.5 text-xs text-gray-700 dark:text-gray-200">
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 font-bold uppercase text-[10px] tracking-wider">
                            <tr>
                                <th class="py-3.5 px-4">Designation & Department</th>
                                <th class="py-3.5 px-4">Reporting Authority (Reports To)</th>
                                <th class="py-3.5 px-4">Appointed Officer / Member</th>
                                <th class="py-3.5 px-4 text-center">Tier Level</th>
                                <th class="py-3.5 px-4 text-center">Subordinates</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-if="paginatedNodes.length === 0">
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-gray-400">No hierarchy designations found matching your criteria.</td>
                                </tr>
                            </template>
                            <template x-for="node in paginatedNodes" :key="node.id">
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition">
                                    <!-- Title with indent -->
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-2">
                                            <div :style="'padding-left: ' + Math.max(0, (parseInt(node.level_tier || 1) - 1) * 16) + 'px;'" class="flex items-center gap-2">
                                                <template x-if="parseInt(node.level_tier || 1) > 1">
                                                    <i class="fa-solid fa-arrow-turn-down-right text-gray-300 text-xs"></i>
                                                </template>
                                                <div>
                                                    <div class="font-bold text-gray-900 dark:text-white text-xs flex items-center gap-1.5">
                                                        <span class="w-2 h-2 rounded-full" :class="getBadgeClass(node.badge_color)"></span>
                                                        <span x-text="node.title"></span>
                                                    </div>
                                                    <div class="text-[10px] text-gray-400" x-text="node.department"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Reports To -->
                                    <td class="py-3.5 px-4">
                                        <template x-if="node.parent_title">
                                            <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-[11px] font-medium inline-flex items-center gap-1">
                                                <i class="fa-solid fa-turn-up text-gray-400 text-[10px]"></i>
                                                <span x-text="node.parent_title"></span>
                                            </span>
                                        </template>
                                        <template x-if="!node.parent_title">
                                            <span class="text-indigo-600 font-bold text-[11px]">★ Apex / Top Node</span>
                                        </template>
                                    </td>

                                    <!-- Holder Info -->
                                    <td class="py-3.5 px-4">
                                        <template x-if="node.holder_name">
                                            <div class="flex items-center gap-2">
                                                <template x-if="node.holder_photo">
                                                    <img :src="'../' + node.holder_photo" class="w-7 h-7 rounded-full object-cover border border-gray-200">
                                                </template>
                                                <template x-if="!node.holder_photo && node.mgmt_photo">
                                                    <img :src="'../' + node.mgmt_photo" class="w-7 h-7 rounded-full object-cover border border-gray-200">
                                                </template>
                                                <template x-if="!node.holder_photo && !node.mgmt_photo">
                                                    <div class="w-7 h-7 rounded-full bg-teal-100 text-teal-800 flex items-center justify-center font-bold text-xs" x-text="node.holder_name.charAt(0).toUpperCase()"></div>
                                                </template>
                                                <div>
                                                    <div class="font-bold text-gray-800 dark:text-gray-200" x-text="node.holder_name"></div>
                                                    <div class="text-[10px] text-gray-400" x-text="node.holder_phone" x-show="node.holder_phone"></div>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="!node.holder_name">
                                            <span class="text-gray-400 italic text-[11px]">Vacant / Unassigned</span>
                                        </template>
                                    </td>

                                    <!-- Tier Level -->
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider" :class="getTierBadgeClass(node.level_tier)">
                                            Tier <span x-text="node.level_tier"></span>
                                        </span>
                                    </td>

                                    <!-- Children count -->
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="font-black text-gray-700 dark:text-gray-300" x-text="node.direct_children_count || 0"></span>
                                    </td>

                                    <!-- Status -->
                                    <td class="py-3.5 px-4 text-center">
                                        <form action="actions/org_structure_logic.php" method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" :value="node.id">
                                            <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-bold transition"
                                                    :class="parseInt(node.is_active) === 1 ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                                    x-text="parseInt(node.is_active) === 1 ? 'Active' : 'Hidden'">
                                            </button>
                                        </form>
                                    </td>

                                    <!-- Actions -->
                                    <td class="py-3.5 px-4 text-right space-x-1">
                                        <button type="button" @click="editNode(node)" class="p-1.5 text-gray-500 hover:text-teal-600 transition" title="Edit Node">
                                            <i class="fa-regular fa-pen-to-square"></i>
                                        </button>

                                        <form action="actions/org_structure_logic.php" method="POST" class="inline" :onsubmit="'return confirm(\'Delete designation node \' + JSON.stringify(node.title) + \'? Subordinate nodes will be safely re-parented.\');'">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                            <input type="hidden" name="action" value="delete_node">
                                            <input type="hidden" name="id" :value="node.id">
                                            <button type="submit" class="p-1.5 text-gray-400 hover:text-rose-600 transition" title="Delete Node">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4 bg-gray-50/50 dark:bg-gray-800/50" x-show="totalPages > 1">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Showing page <span class="font-bold text-gray-800 dark:text-gray-200" x-text="page"></span> of <span class="font-bold text-gray-800 dark:text-gray-200" x-text="totalPages"></span> (<span x-text="filteredNodes.length"></span> total nodes)
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="page--" :disabled="page <= 1" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-xs font-semibold hover:bg-white dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-chevron-left mr-1"></i> Prev
                        </button>
                        <template x-for="p in totalPages" :key="p">
                            <button type="button" @click="page = p" 
                                    x-show="p === 1 || p === totalPages || (p >= page - 1 && p <= page + 1)"
                                    :class="page === p ? 'bg-teal-600 text-white font-bold' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600'" 
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

            <!-- ============================================================ -->
            <!-- 2. VISUAL ORG TREE CHART VIEW -->
            <!-- ============================================================ -->
            <div x-show="viewMode === 'tree'" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 overflow-x-auto">
                <div class="min-w-[800px] flex flex-col items-center py-6">
                    <?php
                    function renderAdminTreeNode($node, $csrfToken) {
                        ?>
                        <div class="flex flex-col items-center">
                            <!-- Node Box -->
                            <div class="bg-white dark:bg-gray-700 border-2 rounded-2xl p-4 shadow-md w-72 text-center transition-all hover:shadow-xl relative group <?php
                                echo match($node['badge_color'] ?? 'teal') {
                                    'indigo' => 'border-indigo-400',
                                    'blue' => 'border-blue-400',
                                    'amber' => 'border-amber-400',
                                    'emerald' => 'border-emerald-400',
                                    'purple' => 'border-purple-400',
                                    'rose' => 'border-rose-400',
                                    default => 'border-teal-400'
                                };
                            ?>">
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider block mb-1 text-gray-500 dark:text-gray-400">
                                    Tier <?php echo $node['level_tier']; ?> • <?php echo htmlspecialchars($node['department']); ?>
                                </span>
                                <h4 class="font-black text-gray-900 dark:text-white text-xs md:text-sm mb-1"><?php echo htmlspecialchars($node['title']); ?></h4>

                                <?php if (!empty($node['holder_name'])): ?>
                                    <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-600 flex items-center justify-center gap-2 text-xs">
                                        <i class="fa-solid fa-user-tie text-teal-600"></i>
                                        <span class="font-bold text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($node['holder_name']); ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400 italic block mt-1">Vacant Position</span>
                                <?php endif; ?>

                                <!-- Quick Edit Float Trigger -->
                                <div class="mt-3 flex items-center justify-center gap-2">
                                    <button type="button" @click="editNode(<?php echo htmlspecialchars(json_encode($node)); ?>)" class="px-2.5 py-1 bg-gray-100 dark:bg-gray-600 hover:bg-teal-50 text-gray-700 dark:text-gray-200 rounded-lg text-[10px] font-bold">
                                        <i class="fa-solid fa-pen-to-square mr-1"></i> Edit
                                    </button>
                                </div>
                            </div>

                            <!-- Children Connector Lines -->
                            <?php if (!empty($node['children'])): ?>
                                <div class="w-0.5 h-6 bg-gray-300 dark:bg-gray-600"></div>
                                <div class="flex items-start gap-6 relative before:absolute before:top-0 before:left-1/2 before:-translate-x-1/2 before:w-full before:h-0.5 before:bg-gray-300 dark:before:bg-gray-600">
                                    <?php foreach ($node['children'] as $child): ?>
                                        <div class="flex flex-col items-center relative pt-6">
                                            <div class="w-0.5 h-6 bg-gray-300 dark:bg-gray-600 absolute top-0"></div>
                                            <?php renderAdminTreeNode($child, $csrfToken); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                    }

                    foreach ($treeData as $rootNode) {
                        renderAdminTreeNode($rootNode, $csrfToken);
                    }
                    ?>
                </div>
            </div>

        </main>
    </div>

    <!-- ============================================================ -->
    <!-- ADD / EDIT DESIGNATION NODE MODAL -->
    <!-- ============================================================ -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900/60 transition-opacity" @click="showModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100 dark:border-gray-700">
                <form action="actions/org_structure_logic.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="save_node">
                    <input type="hidden" name="id" :value="form.id">

                    <div class="p-6 bg-gradient-to-r from-teal-700 via-teal-800 to-slate-900 text-white relative">
                        <button type="button" @click="showModal = false" class="absolute right-5 top-5 text-white/80 hover:text-white">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                        <span class="px-2.5 py-0.5 rounded bg-white/20 text-white text-[10px] font-bold uppercase tracking-wider">Designation Hierarchy Node</span>
                        <h3 class="text-xl font-black mt-1" x-text="form.id ? 'Edit Designation Node' : 'Add New Hierarchy Node'"></h3>
                        <p class="text-xs text-white/80 mt-0.5">Configure position title, parent reporting line, department, and assigned leader.</p>
                    </div>

                    <div class="p-6 max-h-[65vh] overflow-y-auto space-y-4 text-xs">
                        
                        <!-- 1. Position Particulars -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Designation / Role Title <span class="text-rose-500">*</span></label>
                                <input type="text" name="title" x-model="form.title" required placeholder="e.g. State Program Coordinator (UP & Bihar)"
                                       class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-teal-500">
                            </div>

                            <!-- Parent Designation (Reporting To) -->
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Reports To (Parent Designation)</label>
                                <select name="parent_id" x-model="form.parent_id" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white">
                                    <option value="">None (Top / Apex Board Node)</option>
                                    <template x-for="p in allNodes" :key="p.id">
                                        <option :value="p.id" 
                                                :disabled="form.id && parseInt(form.id) === parseInt(p.id)"
                                                x-text="'[Tier ' + p.level_tier + '] ' + p.title + (p.holder_name ? ' (' + p.holder_name + ')' : '')"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Department -->
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Department / Wing</label>
                                <select name="department" x-model="form.department" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white">
                                    <option value="Executive Board">Executive Board</option>
                                    <option value="Secretariat & Administration">Secretariat & Administration</option>
                                    <option value="Health Directorate">Health Directorate</option>
                                    <option value="Youth & Education Wing">Youth & Education Wing</option>
                                    <option value="State Operations">State Operations</option>
                                    <option value="District Operations">District Operations</option>
                                    <option value="Block & Field Units">Block & Field Units</option>
                                    <option value="Finance & Audit">Finance & Audit</option>
                                    <option value="Legal & Compliance">Legal & Compliance</option>
                                    <option value="Village Volunteer Network">Village Volunteer Network</option>
                                </select>
                            </div>

                            <!-- Tier Level -->
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Hierarchy Tier Level</label>
                                <select name="level_tier" x-model="form.level_tier" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white">
                                    <option value="1">Tier 1: Apex Leadership / Board</option>
                                    <option value="2">Tier 2: Directors / Secretariat</option>
                                    <option value="3">Tier 3: State Level Officers</option>
                                    <option value="4">Tier 4: District Operations Heads</option>
                                    <option value="5">Tier 5: Block & Field Units</option>
                                </select>
                            </div>

                            <!-- Color Theme Badge -->
                            <div>
                                <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Badge Color Theme</label>
                                <select name="badge_color" x-model="form.badge_color" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white">
                                    <option value="teal">Teal (Standard)</option>
                                    <option value="indigo">Indigo (Executive)</option>
                                    <option value="blue">Blue (Operations)</option>
                                    <option value="amber">Amber (Finance/Legal)</option>
                                    <option value="emerald">Emerald (Health/Social)</option>
                                    <option value="purple">Purple (Training/Youth)</option>
                                    <option value="rose">Rose (Field/Volunteer)</option>
                                </select>
                            </div>
                        </div>

                        <!-- 2. Integration Link With Existing Management Body / Designation -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                            <h4 class="font-bold text-teal-700 dark:text-teal-400 mb-2 flex items-center gap-1.5">
                                <i class="fa-solid fa-link"></i> Link to Existing Management / Designation Table
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-semibold text-gray-600 dark:text-gray-400 mb-1">Link to Management Member Profile</label>
                                    <select name="management_body_id" x-model="form.management_body_id" @change="onManagementSelect()" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white">
                                        <option value="">None (Custom / Vacant)</option>
                                        <?php foreach ($managementProfiles as $mgmt): ?>
                                            <option value="<?php echo $mgmt['id']; ?>"><?php echo htmlspecialchars($mgmt['name'] . ' — ' . $mgmt['designation']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-semibold text-gray-600 dark:text-gray-400 mb-1">Link to Member Designation Tier</label>
                                    <select name="designation_id" x-model="form.designation_id" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 dark:text-white">
                                        <option value="">None (Custom Role)</option>
                                        <?php foreach ($memberDesignations as $md): ?>
                                            <option value="<?php echo $md['id']; ?>"><?php echo htmlspecialchars($md['title']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Appointed Officer Particulars -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                            <h4 class="font-bold text-gray-700 dark:text-gray-300 mb-2">Appointed Officer Particulars</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block font-medium text-gray-600 dark:text-gray-400 mb-1">Officer / Holder Name</label>
                                    <input type="text" name="holder_name" x-model="form.holder_name" placeholder="e.g. Dr. Arvind Sharma"
                                           class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2 text-xs text-gray-900 dark:text-white">
                                </div>

                                <div>
                                    <label class="block font-medium text-gray-600 dark:text-gray-400 mb-1">Officer Phone</label>
                                    <input type="text" name="holder_phone" x-model="form.holder_phone" placeholder="+91 98765 43210"
                                           class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2 text-xs text-gray-900 dark:text-white">
                                </div>

                                <div>
                                    <label class="block font-medium text-gray-600 dark:text-gray-400 mb-1">Officer Email</label>
                                    <input type="email" name="holder_email" x-model="form.holder_email" placeholder="officer@ngocare.org"
                                           class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2 text-xs text-gray-900 dark:text-white">
                                </div>

                                <div class="sm:col-span-3">
                                    <label class="block font-medium text-gray-600 dark:text-gray-400 mb-1">Photo Upload (Optional)</label>
                                    <input type="file" name="photo" accept="image/*" class="text-xs text-gray-500">
                                </div>
                            </div>
                        </div>

                        <!-- 4. Responsibilities -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">Key Responsibilities & Mandate</label>
                            <textarea name="responsibilities" x-model="form.responsibilities" rows="3" placeholder="Key responsibilities and decision-making scope for this role..."
                                      class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3.5 py-2 text-xs text-gray-900 dark:text-white"></textarea>
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center gap-2 pt-2">
                            <input type="checkbox" id="is_active_chk" name="is_active" value="1" :checked="form.is_active" class="rounded text-teal-600 focus:ring-teal-500">
                            <label for="is_active_chk" class="text-xs font-bold text-gray-700 dark:text-gray-300">Display this designation on public Org Chart</label>
                        </div>

                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 flex justify-between">
                        <button type="button" @click="showModal = false" class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold text-xs rounded-xl border">Cancel</button>
                        <button type="submit" class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl">Save Designation Node</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('orgStructureManager', (initialNodes = [], initialMgmt = []) => ({
        allNodes: initialNodes,
        mgmtProfiles: initialMgmt,
        viewMode: 'table',
        showModal: false,
        search: '',
        page: 1,
        perPage: 15,

        get filteredNodes() {
            if (!this.search.trim()) return this.allNodes;
            const q = this.search.toLowerCase();
            return this.allNodes.filter(n => 
                (n.title && n.title.toLowerCase().includes(q)) ||
                (n.department && n.department.toLowerCase().includes(q)) ||
                (n.holder_name && n.holder_name.toLowerCase().includes(q)) ||
                (n.parent_title && n.parent_title.toLowerCase().includes(q))
            );
        },

        get totalPages() {
            return Math.ceil(this.filteredNodes.length / this.perPage) || 1;
        },

        get paginatedNodes() {
            const start = (this.page - 1) * this.perPage;
            return this.filteredNodes.slice(start, start + this.perPage);
        },

        getBadgeClass(color) {
            switch(color) {
                case 'indigo': return 'bg-indigo-500';
                case 'blue': return 'bg-blue-500';
                case 'amber': return 'bg-amber-500';
                case 'emerald': return 'bg-emerald-500';
                case 'purple': return 'bg-purple-500';
                case 'rose': return 'bg-rose-500';
                default: return 'bg-teal-500';
            }
        },

        getTierBadgeClass(tier) {
            switch(parseInt(tier)) {
                case 1: return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300';
                case 2: return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300';
                case 3: return 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300';
                case 4: return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300';
                default: return 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300';
            }
        },

        form: {
            id: '',
            title: '',
            parent_id: '',
            department: 'Executive Board',
            designation_id: '',
            management_body_id: '',
            holder_name: '',
            holder_designation: '',
            holder_phone: '',
            holder_email: '',
            level_tier: 1,
            sort_order: 0,
            badge_color: 'teal',
            responsibilities: '',
            is_active: true
        },

        openCreateModal() {
            this.form = {
                id: '',
                title: '',
                parent_id: '',
                department: 'Executive Board',
                designation_id: '',
                management_body_id: '',
                holder_name: '',
                holder_designation: '',
                holder_phone: '',
                holder_email: '',
                level_tier: 1,
                sort_order: 0,
                badge_color: 'teal',
                responsibilities: '',
                is_active: true
            };
            this.showModal = true;
        },

        editNode(node) {
            this.form = {
                id: node.id,
                title: node.title || '',
                parent_id: node.parent_id || '',
                department: node.department || 'Executive Board',
                designation_id: node.designation_id || '',
                management_body_id: node.management_body_id || '',
                holder_name: node.holder_name || '',
                holder_designation: node.holder_designation || '',
                holder_phone: node.holder_phone || '',
                holder_email: node.holder_email || '',
                level_tier: node.level_tier || 1,
                sort_order: node.sort_order || 0,
                badge_color: node.badge_color || 'teal',
                responsibilities: node.responsibilities || '',
                is_active: parseInt(node.is_active) === 1
            };
            this.showModal = true;
        },

        onManagementSelect() {
            if (!this.form.management_body_id) return;
            const target = this.mgmtProfiles.find(m => parseInt(m.id) === parseInt(this.form.management_body_id));
            if (target) {
                if (!this.form.title) this.form.title = target.designation;
                if (!this.form.holder_name) this.form.holder_name = target.name;
                if (!this.form.holder_phone) this.form.holder_phone = target.phone;
                if (!this.form.holder_email) this.form.holder_email = target.email;
                if (target.department && !this.form.department) this.form.department = target.department;
            }
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>

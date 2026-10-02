<?php
// ============================================================
// organization-structure.php
// Public Organization Structure & Hierarchy Tree Diagram
// ============================================================

require_once 'config/db.php';
require_once 'includes/functions.php';

// Fetch all active org_structure nodes
$nodes = [];
if (dbTableExists($pdo, 'org_structure')) {
    try {
        $stmt = $pdo->query("
            SELECT o.*, 
                   p.title AS parent_title,
                   m.name AS mgmt_name, m.photo AS mgmt_photo, m.bio AS mgmt_bio,
                   d.title AS desig_name,
                   (SELECT COUNT(*) FROM org_structure c WHERE c.parent_id = o.id AND c.is_active = 1) AS direct_children_count
            FROM org_structure o
            LEFT JOIN org_structure p ON o.parent_id = p.id
            LEFT JOIN management_body m ON o.management_body_id = m.id
            LEFT JOIN member_designations d ON o.designation_id = d.id
            WHERE o.is_active = 1
            ORDER BY o.level_tier ASC, o.sort_order ASC, o.id ASC
        ");
        $nodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $nodes = [];
    }
}

// Build nested tree hierarchy for recursive rendering
function buildPublicOrgTree(array $elements, $parentId = null) {
    $branch = [];
    foreach ($elements as $element) {
        if ($element['parent_id'] == $parentId) {
            $children = buildPublicOrgTree($elements, $element['id']);
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

$treeData = buildPublicOrgTree($nodes, null);

// Department groupings
$departmentGroups = [];
foreach ($nodes as $n) {
    $dept = trim($n['department'] ?? '') ?: 'General Governance';
    $departmentGroups[$dept][] = $n;
}

require 'includes/header.php';
?>

<div class="bg-gradient-to-b from-[#F0FDFD] via-white to-[#F8FAFC] min-h-screen py-8 md:py-14"
     x-data="orgChartPortal(<?php echo htmlspecialchars(json_encode($nodes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>)"
     x-cloak>

    <div class="container mx-auto px-4 max-w-7xl">
        
        <!-- Top Switcher (Leadership Profiles <-> Org Structure Diagram) -->
        <div class="flex items-center justify-center mb-8">
            <div class="inline-flex p-1 bg-gray-100 rounded-2xl shadow-inner border border-gray-200">
                <a href="management.php" class="px-5 py-2 rounded-xl text-xs font-bold text-gray-600 hover:text-gray-900 transition flex items-center gap-2">
                    <i class="fa-solid fa-users text-teal-600"></i>
                    <span>Management Body</span>
                </a>
                <a href="organization-structure.php" class="px-5 py-2 rounded-xl text-xs font-bold bg-white text-teal-700 shadow-sm transition flex items-center gap-2">
                    <i class="fa-solid fa-sitemap text-teal-600"></i>
                    <span>Organization Structure</span>
                </a>
            </div>
        </div>

        <!-- Hero Header -->
        <div class="max-w-4xl mx-auto text-center mb-8 md:mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white shadow-xs border border-teal-100 text-teal-700 text-xs sm:text-sm font-bold uppercase tracking-wider">
                <i class="fa-solid fa-sitemap text-teal-600"></i>
                Governance & Institutional Hierarchy
            </span>
            <h1 class="mt-4 text-3xl sm:text-4xl md:text-5xl font-black text-gray-900 tracking-tight">
                Organization <span class="text-teal-600">Structure</span>
            </h1>
            <p class="mt-3 text-sm sm:text-base md:text-lg text-gray-600 max-w-2xl mx-auto leading-relaxed">
                Discover our constitutional leadership tree, administrative wings, state councils, district operations, and grassroots field coordinators driving grassroots transformation.
            </p>

            <!-- Tiers Pills -->
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3 md:gap-4 text-xs">
                <span class="px-3 py-1 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-800 font-bold">
                    Tier 1: Apex Board
                </span>
                <span class="px-3 py-1 rounded-xl bg-blue-50 border border-blue-100 text-blue-800 font-bold">
                    Tier 2: Directors & Secretariat
                </span>
                <span class="px-3 py-1 rounded-xl bg-teal-50 border border-teal-100 text-teal-800 font-bold">
                    Tier 3: State Leadership
                </span>
                <span class="px-3 py-1 rounded-xl bg-amber-50 border border-amber-100 text-amber-800 font-bold">
                    Tier 4: District Operations
                </span>
                <span class="px-3 py-1 rounded-xl bg-rose-50 border border-rose-100 text-rose-800 font-bold">
                    Tier 5: Block & Field Units
                </span>
            </div>
        </div>

        <!-- Control Bar (View Switcher & Search) -->
        <div class="bg-white rounded-3xl shadow-xl border border-teal-50 p-4 md:p-6 mb-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <!-- View Mode Switcher -->
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="button" @click="displayView = 'tree'" 
                        class="flex-1 sm:flex-initial px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 shadow-xs"
                        :class="displayView === 'tree' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-100'">
                    <i class="fa-solid fa-diagram-project"></i>
                    <span>Visual Org Chart (Tree)</span>
                </button>

                <button type="button" @click="displayView = 'departments'" 
                        class="flex-1 sm:flex-initial px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 shadow-xs"
                        :class="displayView === 'departments' ? 'bg-teal-600 text-white shadow-teal-200' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-100'">
                    <i class="fa-solid fa-cubes-stacked"></i>
                    <span>Department Breakdown</span>
                </button>
            </div>

            <!-- Search -->
            <div class="relative w-full sm:w-72">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-gray-400 text-xs"></i>
                <input type="text" x-model="searchQuery" placeholder="Search role, name, department..."
                       class="w-full bg-gray-50 pl-9 pr-3.5 py-2.5 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500">
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- 1. INTERACTIVE VISUAL ORG CHART TREE DIAGRAM -->
        <!-- ============================================================ -->
        <div x-show="displayView === 'tree'" class="bg-white rounded-3xl shadow-xl border border-teal-50 p-6 md:p-10 mb-14 overflow-x-auto">
            
            <div class="min-w-[900px] flex flex-col items-center py-6">
                <?php
                function renderPublicTreeNode($node) {
                    $color = $node['badge_color'] ?? 'teal';
                    $hasPhoto = !empty($node['holder_photo']) || !empty($node['mgmt_photo']);
                    $photoSrc = !empty($node['holder_photo']) ? $node['holder_photo'] : ($node['mgmt_photo'] ?? '');
                    ?>
                    <div class="flex flex-col items-center">
                        
                        <!-- Designation Card -->
                        <div class="bg-white rounded-2xl p-5 shadow-lg hover:shadow-2xl border-2 transition-all duration-300 w-72 text-center relative group transform hover:-translate-y-1 <?php
                            echo match($color) {
                                'indigo' => 'border-indigo-400 hover:border-indigo-600 shadow-indigo-100',
                                'blue' => 'border-blue-400 hover:border-blue-600 shadow-blue-100',
                                'amber' => 'border-amber-400 hover:border-amber-600 shadow-amber-100',
                                'emerald' => 'border-emerald-400 hover:border-emerald-600 shadow-emerald-100',
                                'purple' => 'border-purple-400 hover:border-purple-600 shadow-purple-100',
                                'rose' => 'border-rose-400 hover:border-rose-600 shadow-rose-100',
                                default => 'border-teal-400 hover:border-teal-600 shadow-teal-100'
                            };
                        ?>">
                            
                            <!-- Tier & Dept Pill -->
                            <div class="flex items-center justify-between gap-1 mb-3">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider <?php
                                    echo match($color) {
                                        'indigo' => 'bg-indigo-50 text-indigo-700',
                                        'blue' => 'bg-blue-50 text-blue-700',
                                        'amber' => 'bg-amber-50 text-amber-700',
                                        'emerald' => 'bg-emerald-50 text-emerald-700',
                                        'purple' => 'bg-purple-50 text-purple-700',
                                        'rose' => 'bg-rose-50 text-rose-700',
                                        default => 'bg-teal-50 text-teal-700'
                                    };
                                ?>">
                                    Tier <?php echo $node['level_tier']; ?>
                                </span>

                                <span class="text-[10px] font-semibold text-gray-400 truncate max-w-[120px]" title="<?php echo htmlspecialchars($node['department']); ?>">
                                    <?php echo htmlspecialchars($node['department']); ?>
                                </span>
                            </div>

                            <!-- Avatar -->
                            <div class="mb-2.5 flex justify-center">
                                <?php if ($hasPhoto): ?>
                                    <img src="<?php echo htmlspecialchars($photoSrc); ?>" alt="<?php echo htmlspecialchars($node['holder_name'] ?? ''); ?>"
                                         class="w-14 h-14 rounded-2xl object-cover border-2 border-white shadow-md">
                                <?php else: ?>
                                    <div class="w-14 h-14 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center text-xl font-black border border-teal-200/80 shadow-sm">
                                        <?php echo !empty($node['holder_name']) ? strtoupper(substr($node['holder_name'], 0, 1)) : '<i class="fa-solid fa-user-tie text-base"></i>'; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Title -->
                            <h3 class="text-sm font-black text-gray-900 group-hover:text-teal-600 transition-colors line-clamp-2">
                                <?php echo htmlspecialchars($node['title']); ?>
                            </h3>

                            <!-- Appointed Person -->
                            <?php if (!empty($node['holder_name'])): ?>
                                <div class="mt-1">
                                    <span class="text-xs font-bold text-gray-800 block"><?php echo htmlspecialchars($node['holder_name']); ?></span>
                                    <?php if (!empty($node['holder_designation'])): ?>
                                        <span class="text-[11px] text-gray-500 block"><?php echo htmlspecialchars($node['holder_designation']); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-[11px] text-gray-400 italic block mt-1">Open Role / Appointed Team</span>
                            <?php endif; ?>

                            <!-- Actions / Popup trigger -->
                            <div class="mt-3 pt-2.5 border-t border-gray-100 flex items-center justify-center gap-2">
                                <button type="button" @click="openNodeDetails(<?php echo htmlspecialchars(json_encode($node)); ?>)"
                                        class="px-3 py-1 rounded-lg bg-gray-50 hover:bg-teal-50 text-gray-700 hover:text-teal-700 text-[11px] font-bold transition flex items-center gap-1.5 border border-gray-100">
                                    <i class="fa-regular fa-id-badge text-xs"></i>
                                    <span>Mandate & Scope</span>
                                </button>
                            </div>

                        </div>

                        <!-- Subordinate Branch Connectors -->
                        <?php if (!empty($node['children'])): ?>
                            <div class="w-0.5 h-8 bg-teal-200"></div>
                            <div class="flex items-start gap-6 relative before:absolute before:top-0 before:left-1/2 before:-translate-x-1/2 before:w-full before:h-0.5 before:bg-teal-200">
                                <?php foreach ($node['children'] as $child): ?>
                                    <div class="flex flex-col items-center relative pt-8">
                                        <div class="w-0.5 h-8 bg-teal-200 absolute top-0"></div>
                                        <?php renderPublicTreeNode($child); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                    <?php
                }

                foreach ($treeData as $root) {
                    renderPublicTreeNode($root);
                }
                ?>
            </div>

        </div>

        <!-- ============================================================ -->
        <!-- 2. DEPARTMENT BREAKDOWN VIEW -->
        <!-- ============================================================ -->
        <div x-show="displayView === 'departments'" class="space-y-8 mb-14">
            <?php foreach ($departmentGroups as $deptName => $deptNodes): ?>
                <div class="bg-white rounded-3xl shadow-xl border border-teal-50 p-6 md:p-8">
                    <div class="flex items-center gap-3 border-b border-gray-100 pb-4 mb-6">
                        <div class="w-10 h-10 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center text-lg font-bold shadow-xs">
                            <i class="fa-solid fa-cube"></i>
                        </div>
                        <div>
                            <h3 class="text-lg md:text-xl font-black text-gray-900"><?php echo htmlspecialchars($deptName); ?></h3>
                            <span class="text-xs text-gray-500 font-semibold"><?php echo count($deptNodes); ?> Designated Positions</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($deptNodes as $dn): ?>
                            <div class="bg-gray-50/60 rounded-2xl p-5 border border-gray-200/60 flex flex-col justify-between hover:border-teal-300 transition">
                                <div>
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <span class="px-2 py-0.5 rounded bg-teal-100/80 text-teal-800 text-[10px] font-black uppercase">
                                            Tier <?php echo $dn['level_tier']; ?>
                                        </span>
                                        <?php if (!empty($dn['parent_title'])): ?>
                                            <span class="text-[10px] text-gray-400">Reports to: <?php echo htmlspecialchars($dn['parent_title']); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <h4 class="font-black text-gray-900 text-sm mb-1"><?php echo htmlspecialchars($dn['title']); ?></h4>

                                    <?php if (!empty($dn['holder_name'])): ?>
                                        <p class="text-xs text-teal-700 font-bold mb-2">
                                            <i class="fa-solid fa-user-tie mr-1 text-xs"></i><?php echo htmlspecialchars($dn['holder_name']); ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (!empty($dn['responsibilities'])): ?>
                                        <p class="text-xs text-gray-600 line-clamp-2 leading-relaxed"><?php echo htmlspecialchars($dn['responsibilities']); ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-4 pt-3 border-t border-gray-200/60 flex items-center justify-between">
                                    <button type="button" @click="openNodeDetails(<?php echo htmlspecialchars(json_encode($dn)); ?>)"
                                            class="text-xs font-bold text-teal-600 hover:text-teal-800 transition flex items-center gap-1">
                                        <span>View Details</span>
                                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- ============================================================ -->
    <!-- NODE DETAILS MODAL -->
    <!-- ============================================================ -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" @click="showModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                <div class="p-6 md:p-8 bg-gradient-to-r from-teal-700 to-teal-900 text-white relative">
                    <button type="button" @click="showModal = false" class="absolute right-5 top-5 text-white/80 hover:text-white">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                    <span class="px-2.5 py-0.5 rounded bg-white/20 text-white text-[10px] font-bold uppercase tracking-wider" x-text="'Tier ' + (activeNode?.level_tier || '1') + ' • ' + (activeNode?.department || '')"></span>
                    <h2 class="text-xl md:text-2xl font-black mt-2" x-text="activeNode?.title"></h2>
                    <p class="text-xs text-teal-200 mt-1" x-show="activeNode?.parent_title" x-text="'Reports directly to: ' + activeNode?.parent_title"></p>
                </div>

                <div class="p-6 md:p-8 space-y-5 text-xs md:text-sm text-gray-700">
                    <!-- Officer Profile -->
                    <div class="flex items-center gap-4 p-4 rounded-2xl bg-gray-50 border border-gray-100">
                        <template x-if="activeNode?.holder_photo || activeNode?.mgmt_photo">
                            <img :src="activeNode.holder_photo || activeNode.mgmt_photo" class="w-14 h-14 rounded-2xl object-cover border border-gray-200 shadow-sm">
                        </template>
                        <template x-if="!activeNode?.holder_photo && !activeNode?.mgmt_photo">
                            <div class="w-14 h-14 rounded-2xl bg-teal-100 text-teal-800 flex items-center justify-center text-xl font-black">
                                <span x-text="activeNode?.holder_name ? activeNode.holder_name.charAt(0).toUpperCase() : '★'"></span>
                            </div>
                        </template>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-gray-400 block">Appointed Officer</span>
                            <h4 class="text-sm md:text-base font-black text-gray-900" x-text="activeNode?.holder_name || 'Designation Open'"></h4>
                            <span class="text-xs text-gray-500" x-text="activeNode?.holder_designation || activeNode?.department"></span>
                        </div>
                    </div>

                    <!-- Roles & Responsibilities -->
                    <div x-show="activeNode?.responsibilities">
                        <h4 class="text-xs font-black uppercase tracking-wider text-gray-400 mb-2">Key Mandate & Responsibilities</h4>
                        <p class="text-gray-700 leading-relaxed whitespace-pre-line" x-text="activeNode?.responsibilities"></p>
                    </div>

                    <!-- Contact Details (if available) -->
                    <div x-show="activeNode?.holder_phone || activeNode?.holder_email" class="pt-3 border-t border-gray-100">
                        <h4 class="text-xs font-black uppercase tracking-wider text-gray-400 mb-2">Office Contact</h4>
                        <div class="flex flex-wrap gap-3">
                            <template x-if="activeNode?.holder_phone">
                                <a :href="'tel:' + activeNode.holder_phone" class="px-3 py-1.5 rounded-xl bg-teal-50 text-teal-800 text-xs font-bold inline-flex items-center gap-1.5 border border-teal-100">
                                    <i class="fa-solid fa-phone text-teal-600"></i>
                                    <span x-text="activeNode.holder_phone"></span>
                                </a>
                            </template>
                            <template x-if="activeNode?.holder_email">
                                <a :href="'mailto:' + activeNode.holder_email" class="px-3 py-1.5 rounded-xl bg-indigo-50 text-indigo-800 text-xs font-bold inline-flex items-center gap-1.5 border border-indigo-100">
                                    <i class="fa-solid fa-envelope text-indigo-600"></i>
                                    <span x-text="activeNode.holder_email"></span>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="p-4 md:p-6 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button type="button" @click="showModal = false" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('orgChartPortal', (initialNodes = []) => ({
        nodes: initialNodes,
        displayView: 'tree',
        searchQuery: '',
        showModal: false,
        activeNode: null,

        openNodeDetails(node) {
            this.activeNode = node;
            this.showModal = true;
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>

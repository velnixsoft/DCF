<?php
require 'includes/header.php';
require '../config/db.php';
require '../includes/functions.php';


if (!canAccessModule($pdo, 'coordinator', 'page.inquiries')) {
    setFlash('error', 'Access denied.');
    header('Location: dashboard.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(submitter_name LIKE :search OR submitter_email LIKE :search OR submitter_phone LIKE :search OR problem_description LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($statusFilter !== '') {
    $where[] = "status = :status";
    $params[':status'] = $statusFilter;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM inquiries $whereSql");
foreach ($params as $k => $v) {
    $countStmt->bindValue($k, $v);
}
$countStmt->execute();
$totalInquiries = (int)$countStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM inquiries $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$inquiries = $stmt->fetchAll();
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8" x-data="inquiryDashboard()">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
                <div>
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-700 dark:text-white">Inquiry Dashboard</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Member problems and suggestions. Respond and track resolution.</p>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" class="mb-6 flex flex-wrap gap-3 items-center">
                <div class="relative flex-1 min-w-[220px] max-w-md">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search name, email, phone, issue..."
                           class="w-full pl-9 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-emerald-500">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                </div>
                <select name="status" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-700 dark:text-gray-200">
                    <option value="">All Statuses</option>
                    <option value="New" <?php echo $statusFilter === 'New' ? 'selected' : ''; ?>>New</option>
                    <option value="In Progress" <?php echo $statusFilter === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="Resolved" <?php echo $statusFilter === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                    <option value="Closed" <?php echo $statusFilter === 'Closed' ? 'selected' : ''; ?>>Closed</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition">
                    Filter
                </button>
                <?php if ($search !== '' || $statusFilter !== ''): ?>
                    <a href="inquiries.php" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition">
                        Reset
                    </a>
                <?php endif; ?>
            </form>

            <div class="divide-y dark:divide-gray-700 lg:hidden bg-white dark:bg-gray-800 rounded-xl shadow-sm border dark:border-gray-700 overflow-hidden mb-6">
                <?php foreach ($inquiries as $inq): ?>
                <div class="p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold dark:text-white"><?php echo htmlspecialchars($inq['submitter_name']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($inq['submitter_email']); ?></p>
                        </div>
                        <span class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($inq['created_at'])); ?></span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full"><?php echo htmlspecialchars(($inq['category'] ?? '') !== '' ? $inq['category'] : 'General'); ?></span>
                        <span class="px-2 py-1 text-xs bg-orange-100 text-orange-800 rounded-full"><?php echo ucfirst((string)($inq['urgency'] ?? 'normal')); ?></span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed"><?php echo nl2br(htmlspecialchars($inq['problem_description'])); ?></p>
                    <div class="flex flex-wrap gap-3 text-sm">
                        <button type="button" class="text-emerald-600 font-semibold" @click='openInquiry(<?php echo htmlspecialchars(json_encode($inq), ENT_QUOTES, "UTF-8"); ?>)'>Manage</button>
                        <form action="actions/inquiry_delete.php" method="POST" class="inline-block" onsubmit="return confirm('Delete this inquiry permanently?');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                            <input type="hidden" name="id" value="<?php echo (int)$inq['id']; ?>">
                            <button type="submit" class="text-red-600 font-semibold">Delete</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($inquiries)): ?><div class="p-8 text-center text-gray-500">No inquiries found.</div><?php endif; ?>
                <?php echo render_admin_pagination($totalInquiries, $page, $perPage, ['search' => $search, 'status' => $statusFilter]); ?>
            </div>

            <div class="hidden lg:block bg-white dark:bg-gray-800 rounded-xl shadow-sm border dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="p-4 font-semibold">Submitter</th>
                                <th class="p-4 font-semibold">Category/Urgency</th>
                                <th class="p-4 font-semibold">Status</th>
                                <th class="p-4 font-semibold text-right">Date</th>
                                <th class="p-4 font-semibold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <?php foreach ($inquiries as $inq): ?>
                            <tr>
                                <td class="p-4">
                                    <p class="font-semibold dark:text-white"><?php echo htmlspecialchars($inq['submitter_name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($inq['submitter_email']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($inq['submitter_phone']); ?></p>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full"><?php echo htmlspecialchars(($inq['category'] ?? '') !== '' ? $inq['category'] : 'General'); ?></span>
                                    <br>
                                    <span class="px-2 py-1 text-xs bg-orange-100 text-orange-800 rounded-full mt-1 inline-block"><?php echo ucfirst((string)($inq['urgency'] ?? 'normal')); ?></span>
                                </td>
                                <td class="p-4">
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full <?php 
                                        echo $inq['status'] === 'New' ? 'bg-yellow-100 text-yellow-800' :
                                             ($inq['status'] === 'In Progress' ? 'bg-blue-100 text-blue-800' :
                                              ($inq['status'] === 'Resolved' ? 'bg-green-100 text-green-800' :
                                               ($inq['status'] === 'Closed' ? 'bg-gray-200 text-gray-800' : 'bg-gray-100 text-gray-800')));
                                    ?>">
                                        <?php echo ucfirst($inq['status']); ?>
                                    </span>
                                </td>
                                <td class="p-4 text-right text-xs text-gray-500"><?php echo date('M d, Y H:i', strtotime($inq['created_at'])); ?></td>
                                <td class="p-4 text-right">
                                    <?php if ($inq['attachment_path']): ?>
                                    <a href="<?php echo htmlspecialchars($inq['attachment_path']); ?>" target="_blank" class="text-blue-600 hover:underline text-xs mr-2">📎 View</a>
                                    <?php endif; ?>
                                    <button type="button"
                                        class="text-emerald-600 hover:underline text-xs inline-flex items-center gap-1"
                                        @click='openInquiry(<?php echo htmlspecialchars(json_encode($inq), ENT_QUOTES, "UTF-8"); ?>)'>
                                        <i class="fa-solid fa-pen-to-square"></i> Manage
                                    </button>
                                    <form action="actions/inquiry_delete.php" method="POST" class="inline-block ml-2" onsubmit="return confirm('Delete this inquiry permanently?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int)$inq['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:underline text-xs inline-flex items-center gap-1">
                                            <i class="fa-solid fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($inquiries)): ?>
                            <tr><td colspan="5" class="p-8 text-center text-gray-500">No inquiries found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php echo render_admin_pagination($totalInquiries, $page, $perPage, ['search' => $search, 'status' => $statusFilter]); ?>
            </div>

            <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="overscroll-behavior: contain;">
                <div class="bg-white dark:bg-gray-800 w-full max-w-2xl rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden" @click.away="closeModal()">
                    <div class="px-6 py-4 border-b dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 dark:text-white">Manage Inquiry</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="active ? ('ID #' + active.id) : ''"></p>
                        </div>
                        <button type="button" class="text-gray-500 hover:text-gray-800 dark:text-gray-300" @click="closeModal()">
                            <i class="fa-solid fa-xmark text-xl"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div class="bg-gray-50 dark:bg-gray-900/30 rounded-xl p-4 border dark:border-gray-700">
                                <p class="text-xs text-gray-500 mb-1">Submitter</p>
                                <p class="font-semibold text-gray-900 dark:text-white" x-text="active?.submitter_name ?? '-'"></p>
                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-1" x-text="active?.submitter_email ?? '-'"></p>
                                <p class="text-xs text-gray-600 dark:text-gray-300" x-text="active?.submitter_phone ?? '-'"></p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-900/30 rounded-xl p-4 border dark:border-gray-700">
                                <p class="text-xs text-gray-500 mb-1">Meta</p>
                                <div class="flex flex-wrap gap-2 items-center">
                                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full" x-text="active?.category || 'General'"></span>
                                    <span class="px-2 py-1 text-xs bg-orange-100 text-orange-800 rounded-full" x-text="(active?.urgency || 'normal').toString().toUpperCase()"></span>
                                    <template x-if="active?.attachment_path">
                                        <a :href="safeAttachmentHref(active.attachment_path)" target="_blank" class="px-2 py-1 text-xs bg-indigo-100 text-indigo-800 rounded-full inline-flex items-center gap-1 hover:underline">
                                            <i class="fa-solid fa-paperclip"></i> Attachment
                                        </a>
                                    </template>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-3" x-text="active?.created_at ? ('Submitted: ' + active.created_at) : ''"></p>
                            </div>
                        </div>

                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Problem / Suggestion</p>
                            <div class="rounded-xl border dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30 p-4 text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap" x-text="active?.problem_description ?? ''"></div>
                        </div>

                        <form action="actions/inquiry_update.php" method="POST" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                            <input type="hidden" name="id" :value="active?.id || ''">

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="md:col-span-1">
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                    <select name="status" class="w-full p-3 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white" x-model="active.status">
                                        <option value="New">New</option>
                                        <option value="In Progress">In Progress</option>
                                        <option value="Resolved">Resolved</option>
                                        <option value="Closed">Closed</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Admin Notes</label>
                                    <textarea name="admin_notes" rows="3" class="w-full p-3 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Internal notes / resolution details" x-model="active.admin_notes"></textarea>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row flex-wrap gap-3 pt-2">
                                <button type="submit" class="flex-1 bg-[#0F8B8D] hover:bg-[#0c7274] text-white py-3 rounded-lg font-semibold shadow-sm">
                                    Save Changes
                                </button>
                                <button type="button" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-3 rounded-lg font-semibold inline-flex items-center justify-center gap-2 shadow-sm" @click="sendWhatsAppReply()">
                                    <i class="fa-brands fa-whatsapp text-lg"></i> Reply via WhatsApp
                                </button>
                                <button type="button" class="px-4 bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-800 dark:text-gray-200 py-3 rounded-lg" @click="closeModal()">
                                    Cancel
                                </button>
                                <button type="button" class="bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-lg font-semibold inline-flex items-center justify-center gap-2" @click="if(confirm('Delete this inquiry permanently?')) document.getElementById('modalDeleteForm').submit()" title="Delete Inquiry">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </form>

                        <form id="modalDeleteForm" action="actions/inquiry_delete.php" method="POST" class="hidden">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                            <input type="hidden" name="id" :value="active?.id || ''">
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Fix legacy mojibake link label without having to rely on file encoding.
    document.querySelectorAll('a.text-blue-600.text-xs.mr-2').forEach((a) => {
        const t = (a.textContent || '').trim();
        if (t.toLowerCase().endsWith('view')) {
            a.classList.add('inline-flex', 'items-center', 'gap-1');
            a.innerHTML = '<i class="fa-solid fa-paperclip"></i> Attachment';
        }
    });
});

function inquiryDashboard() {
    return {
        modalOpen: false,
        active: null,
        openInquiry(inquiry) {
            this.active = {
                ...inquiry,
                admin_notes: inquiry.admin_notes ?? ''
            };
            this.modalOpen = true;
            document.body.style.overflow = 'hidden';
        },
        closeModal() {
            this.modalOpen = false;
            this.active = null;
            document.body.style.overflow = 'unset';
        },
        safeAttachmentHref(path) {
            const p = (path || '').toString();
            if (p.startsWith('../uploads/') || p.startsWith('uploads/')) return p;
            return '#';
        },
        cleanPhoneNumber(phone) {
            if (!phone) return '';
            let clean = phone.toString().replace(/\D/g, '');
            if (clean.length === 10) clean = '91' + clean;
            return clean;
        },
        sendWhatsAppReply() {
            if (!this.active) return;
            const phone = this.cleanPhoneNumber(this.active.submitter_phone);
            if (!phone) {
                alert('No phone number found for this inquiry.');
                return;
            }
            const name = this.active.submitter_name || 'Member';
            const cat = this.active.category || 'General';
            const status = this.active.status || 'New';
            const note = (this.active.admin_notes || '').trim() || 'We have received your inquiry and our team is actively addressing it.';
            const msg = `*Namaste ${name},*\n\n` +
                        `Regarding your inquiry (*${cat}*):\n` +
                        `📊 *Status:* ${status}\n\n` +
                        `💬 *Admin Response / Resolution:*\n${note}\n\n` +
                        `Thank you for reaching out!`;
            window.open(`https://wa.me/${phone}?text=${encodeURIComponent(msg)}`, '_blank');
        }
    };
}
</script>

<?php require 'includes/footer.php'; ?>


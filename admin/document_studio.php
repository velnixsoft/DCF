<?php
require '../config/db.php';
require '../includes/functions.php';
require '../includes/member_module.php';
require '../includes/template_builder.php';

if (!canAccessModule($pdo, 'coordinator', 'page.member_documents')) {
    setFlash('error', 'Access denied.');
    header('Location: dashboard.php');
    exit;
}

$settings = mm_load_settings($pdo);
$brandOptions = mm_document_brand_options($settings);
$templateLabels = [
    'id_card' => 'ID Card',
    'receipt' => 'Receipt',
    'membership_certificate' => 'Membership Certificate',
    'achievement_certificate' => 'Achievement Certificate',
    'appointment_letter' => 'Appointment Letter',
];
$savedTemplates = [];
$memberDocumentTemplates = [];
try {
    if (dbTableExists($pdo, 'templates')) {
        $savedTemplates = $pdo->query("SELECT id, template_name, template_type, status, updated_at FROM templates ORDER BY template_type ASC, updated_at DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
        $memberDocumentTemplates = array_values(array_filter($savedTemplates, static function ($template) {
            return in_array($template['template_type'] ?? '', ['id_card', 'membership_certificate', 'appointment_letter', 'achievement_certificate'], true);
        }));
    }
} catch (Throwable $e) {
    $savedTemplates = [];
    $memberDocumentTemplates = [];
}

$members = [];
try {
    $members = $pdo->query("SELECT id, full_name, email, member_no, phone, status, payment_status FROM members ORDER BY created_at DESC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $members = [];
}

$selectedMemberId = (int)($_GET['member_id'] ?? ($_GET['id'] ?? 0));
$selectedType = trim((string)($_GET['type'] ?? 'id_card'));
require 'includes/header.php';
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
                <div>
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-700 dark:text-white">Document Studio</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Generate member documents and visitor certificates with branded templates.</p>
                </div>

            </div>

            <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,.9fr)] gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-5">
                    <div class="mb-5">
                        <h4 class="font-semibold text-gray-800 dark:text-white">Member Documents</h4>
                        <p class="text-xs text-gray-500 mt-1">Pick a member, choose the document type, and select which NGO brand should appear on the PDF.</p>
                    </div>

                    <form method="GET" id="memberDocForm" class="space-y-4">
                        <div>
                            <label for="memberSearchInput" class="block text-sm font-medium mb-1.5 dark:text-gray-300">Search User</label>
                            <input type="text" id="memberSearchInput" placeholder="Search by name, member no, email, phone..." class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <p id="memberSearchMeta" class="text-xs text-gray-500 mt-1"><?php echo count($members); ?> users loaded.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Member</label>
                            <select name="id" id="memberSelect" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Select a member</option>
                                <?php foreach ($members as $member): ?>
                                    <option value="<?php echo (int)$member['id']; ?>" <?php echo $selectedMemberId === (int)$member['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($member['full_name'] . ' ¡¤ ' . ($member['member_no'] ?: 'No Member No')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Document Type</label>
                                <select name="type" id="memberDocType" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="id_card" <?php echo $selectedType === 'id_card' ? 'selected' : ''; ?>>ID Card</option>
                                    <option value="membership_certificate" <?php echo $selectedType === 'membership_certificate' ? 'selected' : ''; ?>>Membership Certificate</option>
                                    <option value="appointment_letter" <?php echo $selectedType === 'appointment_letter' ? 'selected' : ''; ?>>Appointment Letter</option>
                                    <option value="achievement_certificate" <?php echo $selectedType === 'achievement_certificate' ? 'selected' : ''; ?>>Achievement Certificate</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Brand Profile</label>
                                <select name="brand" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <?php foreach ($brandOptions as $id => $brand): ?>
                                        <option value="<?php echo (int)$id; ?>"><?php echo htmlspecialchars($brand['label']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Custom Template</label>
                                <select name="template_id" id="memberTemplateId" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="">Use active template for selected type, or old layout</option>
                                    <?php foreach ($memberDocumentTemplates as $template): ?>
                                        <option value="<?php echo (int)$template['id']; ?>" data-type="<?php echo htmlspecialchars($template['template_type']); ?>">
                                            <?php echo htmlspecialchars(($templateLabels[$template['template_type']] ?? $template['template_type']) . ' - ' . $template['template_name'] . (((int)$template['status'] === 1) ? ' (Active)' : ' (Inactive)')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Only templates matching the selected document type are shown.</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3 pt-2">
                            <button formaction="generate_member_document.php" formtarget="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium">Preview / Download</button>
                            <button formaction="actions/send_member_document.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium">Email to Member</button>
                        </div>
                    </form>

                    <div class="mt-6 rounded-xl bg-gray-50 dark:bg-gray-900/40 border dark:border-gray-700 p-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Tip: the preview button opens the PDF in a new tab. The email button sends the same branded PDF to the selected member.</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-5">
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div>
                                <h4 class="font-semibold text-gray-800 dark:text-white">Saved Custom Templates</h4>
                                <p class="text-xs text-gray-500 mt-1">Active templates automatically override old PDF layouts. If no active template exists, the old PDF layout is used.</p>
                            </div>
                            <a href="template-builder.php" class="shrink-0 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-xs">Manage</a>
                        </div>

                        <?php if (empty($savedTemplates)): ?>
                            <div class="rounded-xl border border-dashed dark:border-gray-700 p-4 text-sm text-gray-500 dark:text-gray-400">
                                No templates saved yet.
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($templateLabels as $type => $label): ?>
                                    <?php
                                    $items = array_values(array_filter($savedTemplates, static function ($template) use ($type) {
                                        return ($template['template_type'] ?? '') === $type;
                                    }));
                                    ?>
                                    <div class="rounded-xl border dark:border-gray-700 overflow-hidden">
                                        <div class="px-4 py-2 bg-gray-50 dark:bg-gray-900/40 flex items-center justify-between">
                                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($label); ?></span>
                                            <span class="text-xs text-gray-500"><?php echo count($items); ?> saved</span>
                                        </div>
                                        <div class="divide-y dark:divide-gray-700">
                                            <?php if (empty($items)): ?>
                                                <p class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">No template for this type.</p>
                                            <?php else: ?>
                                                <?php foreach ($items as $template): ?>
                                                    <div class="px-4 py-3 bg-white dark:bg-gray-800 flex items-center justify-between gap-3">
                                                        <div class="min-w-0">
                                                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate"><?php echo htmlspecialchars($template['template_name']); ?></p>
                                                            <p class="text-[11px] text-gray-500">Updated <?php echo htmlspecialchars((string)$template['updated_at']); ?></p>
                                                        </div>
                                                        <span class="shrink-0 text-[11px] px-2 py-1 rounded-full <?php echo ((int)$template['status'] === 1) ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'; ?>">
                                                            <?php echo ((int)$template['status'] === 1) ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-5">
                    <div class="mb-5">
                        <h4 class="font-semibold text-gray-800 dark:text-white">Visitor Certificate</h4>
                        <p class="text-xs text-gray-500 mt-1">Issue a non-member certificate with the same brand profiles and QR verification.</p>
                    </div>

                    <form action="visitor_certificates.php" method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Recipient Name *</label>
                                <input type="text" name="recipient_name" required class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Recipient Email *</label>
                                <input type="email" name="recipient_email" required class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Certificate Title *</label>
                            <input type="text" name="certificate_title" required placeholder="Certificate of Appreciation" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Issued For</label>
                            <textarea name="issued_for" rows="3" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Brand Profile</label>
                                <select name="brand_id" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <?php foreach ($brandOptions as $id => $brand): ?>
                                        <option value="<?php echo (int)$id; ?>"><?php echo htmlspecialchars($brand['label']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Template</label>
                                <select name="template_no" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="1">Template 1</option>
                                    <option value="2">Template 2</option>
                                    <option value="3">Template 3</option>
                                    <option value="4">Template 4</option>
                                    <option value="5">Template 5</option>
                                    <option value="6">Template 6</option>
                                </select>
                            </div>
                        </div>
                        <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium">Issue & Email</button>
                    </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
<script>
    (function () {
        const allMembers = [
            <?php foreach ($members as $member): ?>
            {
                id: <?php echo (int)$member['id']; ?>,
                label: <?php echo json_encode($member['full_name'] . ' - ' . ($member['member_no'] ?: 'No Member No')); ?>
            },
            <?php endforeach; ?>
        ];

        const allTemplates = [
            <?php foreach ($memberDocumentTemplates as $template): ?>
            {
                id: <?php echo (int)$template['id']; ?>,
                type: <?php echo json_encode($template['template_type']); ?>,
                label: <?php echo json_encode(($templateLabels[$template['template_type']] ?? $template['template_type']) . ' - ' . $template['template_name'] . (((int)$template['status'] === 1) ? ' (Active)' : ' (Inactive)')); ?>
            },
            <?php endforeach; ?>
        ];

        const typeSelect = document.getElementById('memberDocType');
        const templateSelect = document.getElementById('memberTemplateId');
        const memberSearchInput = document.getElementById('memberSearchInput');
        const memberSelect = document.getElementById('memberSelect');
        const memberSearchMeta = document.getElementById('memberSearchMeta');
        if (!typeSelect || !templateSelect) return;

        function rebuildTemplateSelect() {
            const selectedType = typeSelect.value;
            const currentVal = templateSelect.value;
            
            templateSelect.innerHTML = '<option value="">Use active template for selected type, or old layout</option>';
            
            allTemplates.forEach(t => {
                if (t.type === selectedType) {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = t.label;
                    if (String(t.id) === String(currentVal)) {
                        opt.selected = true;
                    }
                    templateSelect.appendChild(opt);
                }
            });
        }

        typeSelect.addEventListener('change', rebuildTemplateSelect);
        rebuildTemplateSelect();

        if (memberSearchInput && memberSelect) {
            function rebuildMemberSelect(query) {
                const q = query.trim().toLowerCase();
                const currentVal = memberSelect.value;
                
                memberSelect.innerHTML = '<option value="">Select a member</option>';
                
                let visibleCount = 0;
                allMembers.forEach(m => {
                    if (q === '' || m.label.toLowerCase().includes(q)) {
                        const opt = document.createElement('option');
                        opt.value = m.id;
                        opt.textContent = m.label;
                        if (String(m.id) === String(currentVal)) {
                            opt.selected = true;
                        }
                        memberSelect.appendChild(opt);
                        visibleCount++;
                    }
                });
                
                if (memberSearchMeta) {
                    memberSearchMeta.textContent = q === ''
                        ? visibleCount + ' users loaded.'
                        : visibleCount + ' users match "' + query + '".';
                }
            }

            // Prevent Enter key form submission on search input
            memberSearchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
            });

            memberSearchInput.addEventListener('input', function() {
                rebuildMemberSelect(memberSearchInput.value);
            });
            rebuildMemberSelect('');
        }

        const memberDocForm = document.getElementById('memberDocForm');
        if (memberDocForm) {
            memberDocForm.addEventListener('submit', function (e) {
                if (!memberSelect.value) {
                    e.preventDefault();
                    if (window.Alpine && Alpine.store('toast')) {
                        Alpine.store('toast').show('Please select a member name before proceeding.', 'error');
                    } else {
                        alert('Please select a member name before proceeding.');
                    }
                }
            });
        }
    })();
</script>

</div>

<?php require 'includes/footer.php'; ?>

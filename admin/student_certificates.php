<?php require 'includes/header.php'; ?>
<?php require '../config/db.php'; ?>
<?php require_once '../includes/student/certificates.php'; ?>

<?php
if (!checkRole($pdo, 'coordinator')) {
    setFlash('error', 'Unauthorized access.');
    header('Location: dashboard.php');
    exit;
}

$service = new StudentCertificateService($pdo);
$students = $pdo->query("SELECT id, full_name, student_no FROM sa_students WHERE status = 'Active' ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$templates = [];
if (dbTableExists($pdo, 'templates')) {
    $templates = $pdo->query("SELECT id, template_name FROM templates WHERE template_type = 'student_certificate' AND status = 1 ORDER BY template_name ASC")->fetchAll(PDO::FETCH_ASSOC);
}
$csrfToken = generateCsrfToken();
$certTypes = $service->certificateTypes();
$preselectStudentId = (int)($_GET['student_id'] ?? 0);

$search = trim($_GET['search'] ?? '');
$totalCertificates = 0;
$certificates = [];

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

if (dbTableExists($pdo, 'sa_certificates')) {
    $titleCol = sa_cert_title_column($pdo);
    $hasHist = dbColumnExists($pdo, 'sa_certificates', 'recipient_name');

    $where = [];
    $params = [];

    if ($search !== '') {
        if ($hasHist) {
            $where[] = "(c.certificate_no LIKE ? OR c.{$titleCol} LIKE ? OR c.recipient_name LIKE ? OR s.full_name LIKE ? OR s.student_no LIKE ?)";
            $params = ["%$search%", "%$search%", "%$search%", "%$search%", "%$search%"];
        } else {
            $where[] = "(c.certificate_no LIKE ? OR c.{$titleCol} LIKE ? OR s.full_name LIKE ? OR s.student_no LIKE ?)";
            $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
        }
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM sa_certificates c LEFT JOIN sa_students s ON s.id = c.student_id $whereSql");
    $countStmt->execute($params);
    $totalCertificates = (int)$countStmt->fetchColumn();

    $totalPages = max(1, ceil($totalCertificates / $perPage));
    if ($page > $totalPages && $totalCertificates > 0) $page = $totalPages;
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT c.*, s.student_no, c.{$titleCol} AS certificate_title";
    if ($hasHist) {
        $sql .= ", COALESCE(c.recipient_name, s.full_name) AS full_name";
        $sql .= ", COALESCE(c.recipient_college, s.college_name) AS college_name";
        $sql .= ", COALESCE(c.recipient_level, s.level_name) AS level_name";
    } else {
        $sql .= ", s.full_name, s.college_name, s.level_name";
    }
    $sql .= " FROM sa_certificates c LEFT JOIN sa_students s ON s.id = c.student_id $whereSql ORDER BY c.id DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $i => $val) {
        $stmt->bindValue($i + 1, $val);
    }
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $certificates = array_map('sa_cert_normalize_row', $rows);
}
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Student Certificates</h3>
                    <p class="text-xs text-gray-500 mt-1">Issue certificates from Template Builder layouts. Manage internship, event, volunteer, and leadership certificates.</p>
                </div>
                <a href="template-builder.php" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700">
                    <i class="fa-solid fa-pen-ruler"></i> Template Builder
                </a>
            </div>

            <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
                <section class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 p-6 shadow-sm h-fit">
                    <h4 class="font-bold text-gray-900 dark:text-white mb-4">Issue New Certificate</h4>
                    <form action="actions/student_logic.php" method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="issue_certificate">
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Student *</label>
                            <select name="id" required class="w-full px-3 py-2.5 rounded-xl border dark:border-gray-600 dark:bg-gray-700 text-sm">
                                <option value="">Select student</option>
                                <?php foreach ($students as $s): ?>
                                    <option value="<?php echo (int)$s['id']; ?>" <?php echo $preselectStudentId === (int)$s['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($s['full_name'] . ' (' . $s['student_no'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Certificate Type *</label>
                            <select name="certificate_type" required class="w-full px-3 py-2.5 rounded-xl border dark:border-gray-600 dark:bg-gray-700 text-sm">
                                <?php foreach ($certTypes as $key => $label): ?>
                                    <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Title *</label>
                            <input type="text" name="certificate_title" required placeholder="e.g. Internship Completion Certificate" class="w-full px-3 py-2.5 rounded-xl border dark:border-gray-600 dark:bg-gray-700 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Template (optional)</label>
                            <select name="template_id" class="w-full px-3 py-2.5 rounded-xl border dark:border-gray-600 dark:bg-gray-700 text-sm">
                                <option value="">Active student certificate template</option>
                                <?php foreach ($templates as $tpl): ?>
                                    <option value="<?php echo (int)$tpl['id']; ?>"><?php echo htmlspecialchars($tpl['template_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-1">Issued For</label>
                            <textarea name="issued_for" rows="2" class="w-full px-3 py-2.5 rounded-xl border dark:border-gray-600 dark:bg-gray-700 text-sm" placeholder="Recognition text on certificate"></textarea>
                        </div>
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl py-2.5 text-sm font-bold">Generate Certificate</button>
                    </form>
                </section>

                <section class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 overflow-hidden shadow-sm">
                    <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="text-sm font-bold text-gray-700 dark:text-gray-200">
                            Issued Certificates (<?= $totalCertificates ?>)
                        </div>
                        <form method="GET" class="flex items-center gap-2 w-full sm:w-auto">
                            <div class="relative flex-1 sm:w-56">
                                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search certs, students..."
                                       class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border border-gray-200 dark:border-gray-600 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                            </div>
                            <button type="submit" class="bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-300 text-xs px-3 py-1.5 rounded-xl font-bold transition">Search</button>
                            <?php if ($search !== ''): ?>
                                <a href="student_certificates.php" class="text-xs text-red-500 hover:underline">Reset</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase font-bold">
                                <tr>
                                    <th class="p-4">Student</th>
                                    <th class="p-4">Certificate</th>
                                    <th class="p-4">No.</th>
                                    <th class="p-4">Status</th>
                                    <th class="p-4">Issued</th>
                                    <th class="p-4 text-right">PDF</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <?php foreach ($certificates as $cert): ?>
                                    <tr>
                                        <td class="p-4">
                                            <p class="font-bold"><?php echo htmlspecialchars($cert['full_name']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($cert['student_no']); ?></p>
                                        </td>
                                        <td class="p-4"><?php echo htmlspecialchars($cert['certificate_title']); ?></td>
                                        <td class="p-4 font-mono text-xs"><?php echo htmlspecialchars($cert['certificate_no']); ?></td>
                                        <td class="p-4"><span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800"><?php echo htmlspecialchars($cert['status']); ?></span></td>
                                        <td class="p-4 text-xs"><?php echo !empty($cert['issued_at']) ? htmlspecialchars(date('d M Y', strtotime($cert['issued_at']))) : '—'; ?></td>
                                        <td class="p-4 text-right">
                                            <?php if (($cert['status'] ?? '') === 'Generated'): ?>
                                                <a href="generate_student_certificate.php?cert_id=<?php echo (int)$cert['id']; ?>" target="_blank" class="text-emerald-600 font-bold text-xs">Download</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($certificates)): ?>
                                    <tr><td colspan="6" class="p-8 text-center text-gray-400">No certificates found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($totalCertificates > 0): ?>
                        <?= render_admin_pagination($totalCertificates, $page, $perPage, ['search' => $search]); ?>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</div>

<?php require 'includes/footer.php'; ?>

<?php
require '../config/db.php';
require '../includes/functions.php';
require '../includes/member_module.php';
require '../includes/template_builder.php';
require '../libs/fpdf/fpdf.php';

if (!canAccessModule($pdo, 'coordinator', 'page.visitor_certificates')) {
    setFlash('error', 'Access denied.');
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$_POST['csrf_token'])) {
        setFlash('error', 'Security token invalid.');
        header('Location: visitor_certificates.php');
        exit;
    }

    $name = mm_clean($_POST['recipient_name'] ?? '');
    $email = filter_var($_POST['recipient_email'] ?? '', FILTER_SANITIZE_EMAIL);
    $title = mm_clean($_POST['certificate_title'] ?? '');
    $issuedFor = mm_clean($_POST['issued_for'] ?? '');
    $templateNo = (int)($_POST['template_no'] ?? 1);
    $templateId = (int)($_POST['template_id'] ?? 0);
    $brandId = (int)($_POST['brand_id'] ?? 0);

    if ($templateNo < 1 || $templateNo > 6) {
        $templateNo = 1;
    }

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $title === '') {
        setFlash('error', 'Please fill required fields.');
        header('Location: visitor_certificates.php');
        exit;
    }

    $certNo = 'VCERT-' . date('Y') . '-' . str_pad((string)random_int(1, 99999), 5, '0', STR_PAD_LEFT);
    $settings = mm_load_settings($pdo);
    $settings = mm_load_document_brand_settings($settings, $brandId);
    $verifyBase = ($settings['ngo_website'] ?? '') ? rtrim($settings['ngo_website'], '/') : '';
    $verifyPayload = $verifyBase ? ($verifyBase . '/member-verify.php?vcert=' . urlencode($certNo)) : ('VCERT:' . $certNo . ';NAME:' . $name);
    $visitorPayload = [
        'recipient_name' => $name,
        'recipient_email' => $email,
        'certificate_title' => $title,
        'issued_for' => $issuedFor,
        'event_title' => '',
        'event_date' => '',
        'event_location' => '',
        'occasion_name' => '',
        'achievement_position' => '',
    ];

    $customTemplate = $templateId > 0 ? tb_load_template_by_id($pdo, $templateId, 'visitor_certificate') : tb_load_active_template($pdo, 'visitor_certificate');
    if ($customTemplate) {
        $pdfContent = tb_render_template_pdf($pdo, $customTemplate, tb_visitor_pdf_context($visitorPayload, $settings, $certNo, $verifyPayload));
    } else {
        $pdf = new FPDF('L', 'mm', 'A4');
        $pdf->AddPage();

        $palette = [
            1 => [30, 58, 138],
            2 => [9, 102, 75],
            3 => [91, 33, 182],
            4 => [153, 27, 27],
            5 => [55, 65, 81],
            6 => [15, 118, 110],
        ];
        $color = $palette[$templateNo];

        $pdf->SetFillColor($color[0], $color[1], $color[2]);
        $pdf->Rect(8, 8, 281, 190, 'F');
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect(14, 14, 269, 178, 'F');
        $pdf->SetDrawColor($color[0], $color[1], $color[2]);
        $pdf->SetLineWidth(1.2);
        $pdf->Rect(18, 18, 261, 170, 'D');

        $pdf->SetFont('Arial', 'B', 28);
        $pdf->SetTextColor($color[0], $color[1], $color[2]);
        $pdf->SetXY(20, 32);
        $pdf->Cell(257, 12, 'CERTIFICATE', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 14);
        $pdf->SetXY(20, 47);
        $pdf->Cell(257, 8, $title, 0, 1, 'C');

        if (!empty($settings['ngo_logo'])) {
            $logoPathLocal = mm_prepare_image_for_fpdf('../' . $settings['ngo_logo']);
            if ($logoPathLocal && file_exists($logoPathLocal)) {
                $pdf->Image($logoPathLocal, 20, 18, 32, 32);
            }
        }

        $pdf->SetTextColor(20, 20, 20);
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetXY(30, 70);
        $pdf->Cell(237, 8, 'This is proudly presented to', 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 28);
        $pdf->SetXY(30, 81);
        $pdf->Cell(237, 12, strtoupper($name), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetXY(30, 102);
        $pdf->Cell(237, 8, $issuedFor !== '' ? $issuedFor : 'for participation and support.', 0, 1, 'C');

        $pdf->SetFont('Arial', '', 10);
        $pdf->SetXY(28, 150);
        $pdf->Cell(100, 6, 'Certificate No: ' . $certNo, 0, 1);
        $pdf->SetX(28);
        $pdf->Cell(100, 6, 'Issued On: ' . date('d M Y'), 0, 1);

        $qrPathLocal = mm_prepare_image_for_fpdf(mm_qr_image_url($verifyPayload));
        if ($qrPathLocal && file_exists($qrPathLocal)) {
            $pdf->Image($qrPathLocal, 236, 132, 32, 32, 'PNG');
        }
        $pdf->SetXY(230, 166);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(45, 5, 'Scan to Verify', 0, 1, 'C');

        if (!empty($settings['ngo_signature'])) {
            $sigPathLocal = mm_prepare_image_for_fpdf('../' . $settings['ngo_signature']);
            if ($sigPathLocal && file_exists($sigPathLocal)) {
                $pdf->Image($sigPathLocal, 155, 146, 40);
            }
        }
        $pdf->Line(154, 165, 206, 165);
        $pdf->SetXY(154, 166);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(52, 5, 'Authorized Signatory', 0, 1, 'C');

        $pdfContent = $pdf->Output('S');
    }
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $isWebView = strpos($userAgent, 'Android') !== false && strpos($userAgent, 'wv') !== false;

    try {
        $stmt = $pdo->prepare("INSERT INTO visitor_certificates (recipient_name, recipient_email, certificate_title, issued_for, template_no, certificate_no, qr_payload, issued_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $title, $issuedFor, $templateNo, $certNo, $verifyPayload, (int)($_SESSION['user_id'] ?? 0)]);

        mm_send_email(
            $settings,
            $email,
            $name,
            'Your Certificate - ' . ($settings['site_name'] ?? 'NGO'),
            '<p>Dear ' . htmlspecialchars($name) . ',</p><p>Please find your certificate attached.</p><p>Regards,<br>' . htmlspecialchars($settings['site_name'] ?? 'NGO') . '</p>',
            [[
                'name' => 'Visitor_Certificate_' . $certNo . '.pdf',
                'content' => $pdfContent,
            ]]
        );

        setFlash('success', 'Visitor certificate issued and emailed.');
    } catch (Exception $e) {
        setFlash('error', 'Failed to issue certificate: ' . $e->getMessage());
    }

    if (ob_get_length()) {
        ob_end_clean();
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . ($isWebView ? 'attachment' : 'inline') . '; filename="Visitor_Certificate_' . $certNo . '.pdf"');
    echo $pdfContent;
    exit;
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$totalRecords = (int)$pdo->query("SELECT COUNT(*) FROM visitor_certificates")->fetchColumn();
$totalPages = ceil($totalRecords / $limit);
if ($totalPages < 1) {
    $totalPages = 1;
}

$stmt = $pdo->prepare("SELECT * FROM visitor_certificates ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$settings = mm_load_settings($pdo);
$brandOptions = mm_document_brand_options($settings);
$visitorTemplates = [];
try {
    if (dbTableExists($pdo, 'templates')) {
        $visitorTemplates = $pdo->query("SELECT id, template_name, status FROM templates WHERE template_type = 'visitor_certificate' ORDER BY updated_at DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $visitorTemplates = [];
}
require 'includes/header.php';
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-700 dark:text-white">Visitor Certificates</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Issue branded certificates with QR verification and a professional print layout.</p>
                </div>
                <a href="document_studio.php" class="inline-flex items-center justify-center bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-medium">
                    Open Document Studio
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1 bg-white dark:bg-dark-card rounded-lg shadow border dark:border-gray-700 p-5">
                    <h4 class="font-semibold mb-4 dark:text-white">Issue Certificate</h4>
                    <form method="POST" class="space-y-3" id="issue-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Recipient Name *</label>
                            <input type="text" name="recipient_name" required class="w-full mt-1 border rounded p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Recipient Email *</label>
                            <input type="email" name="recipient_email" required class="w-full mt-1 border rounded p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Certificate Title *</label>
                            <input type="text" name="certificate_title" required placeholder="Certificate of Appreciation" class="w-full mt-1 border rounded p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Issued For</label>
                            <textarea name="issued_for" rows="3" class="w-full mt-1 border rounded p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Brand Profile</label>
                            <select name="brand_id" class="w-full mt-1 border rounded p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <?php foreach ($brandOptions as $id => $brand): ?>
                                    <option value="<?php echo (int)$id; ?>"><?php echo htmlspecialchars($brand['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Template (1-6)</label>
                            <select name="template_no" class="w-full mt-1 border rounded p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="1">Template 1</option>
                                <option value="2">Template 2</option>
                                <option value="3">Template 3</option>
                                <option value="4">Template 4</option>
                                <option value="5">Template 5</option>
                                <option value="6">Template 6</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Builder Template</label>
                            <select name="template_id" class="w-full mt-1 border rounded p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Use active visitor builder template, else old layout</option>
                                <?php foreach ($visitorTemplates as $template): ?>
                                    <option value="<?php echo (int)$template['id']; ?>">
                                        <?php echo htmlspecialchars($template['template_name'] . (((int)$template['status'] === 1) ? ' (Active)' : ' (Inactive)')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-2 rounded">Issue & Email</button>
                    </form>
                </div>

                <div class="lg:col-span-2 bg-white dark:bg-dark-card rounded-lg shadow border dark:border-gray-700 p-5">
                    <h4 class="font-semibold mb-4 dark:text-white">Issued Certificates</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 border-b dark:border-gray-700">
                                    <th class="p-2">Recipient</th>
                                    <th class="p-2">Certificate</th>
                                    <th class="p-2">Template</th>
                                    <th class="p-2">Certificate No</th>
                                    <th class="p-2">Issued On</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <?php foreach ($records as $r): ?>
                                    <tr>
                                        <td class="p-2">
                                            <p class="font-semibold dark:text-white"><?php echo htmlspecialchars($r['recipient_name']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($r['recipient_email']); ?></p>
                                        </td>
                                        <td class="p-2 dark:text-gray-200"><?php echo htmlspecialchars($r['certificate_title']); ?></td>
                                        <td class="p-2 text-xs dark:text-gray-200">Template <?php echo (int)$r['template_no']; ?></td>
                                        <td class="p-2 text-xs font-mono text-gray-600 dark:text-gray-300"><?php echo htmlspecialchars($r['certificate_no']); ?></td>
                                        <td class="p-2 text-xs text-gray-500"><?php echo date('d M Y H:i', strtotime($r['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($records)): ?>
                                    <tr>
                                        <td colspan="5" class="p-4 text-center text-gray-500">No certificates issued yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination UI Controls -->
                    <?php if ($totalPages > 1): ?>
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6 px-4 py-3 bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-2xl shadow-sm">
                        <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                            Showing <span class="font-bold text-gray-900 dark:text-white"><?php echo $offset + 1; ?></span> to 
                            <span class="font-bold text-gray-900 dark:text-white"><?php echo min($offset + $limit, $totalRecords); ?></span> of 
                            <span class="font-bold text-gray-900 dark:text-white"><?php echo $totalRecords; ?></span> entries
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="?page=<?php echo max(1, $page - 1); ?>" 
                               class="px-4 py-2 text-xs font-bold rounded-xl border dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 <?php if ($page === 1) echo 'opacity-50 pointer-events-none'; ?> transition flex items-center gap-1">
                                <i class="fa-solid fa-chevron-left"></i> Previous
                            </a>
                            <div class="flex items-center gap-1">
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Page</span>
                                <span class="text-xs font-bold text-gray-900 dark:text-white"><?php echo $page; ?></span>
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">of</span>
                                <span class="text-xs font-bold text-gray-900 dark:text-white"><?php echo $totalPages; ?></span>
                            </div>
                            <a href="?page=<?php echo min($totalPages, $page + 1); ?>" 
                               class="px-4 py-2 text-xs font-bold rounded-xl border dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 <?php if ($page === $totalPages) echo 'opacity-50 pointer-events-none'; ?> transition flex items-center gap-1">
                                Next <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Full-Screen Glassmorphic Loading Overlay -->
            <div id="submit-loader" class="hidden fixed inset-0 z-50 flex flex-col items-center justify-center bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white dark:bg-dark-card rounded-2xl shadow-2xl p-8 border dark:border-gray-700 flex flex-col items-center max-w-sm text-center mx-4">
                    <div class="relative w-16 h-16 mb-4">
                        <div class="absolute inset-0 rounded-full border-4 border-slate-100 dark:border-slate-800"></div>
                        <div class="absolute inset-0 rounded-full border-4 border-emerald-500 border-t-transparent animate-spin"></div>
                    </div>
                    <h5 class="text-lg font-bold text-gray-800 dark:text-white mb-2">Generating Certificate</h5>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Please wait while we generate the PDF certificate and email it to the recipient...</p>
                </div>
            </div>

            <script>
            document.getElementById('issue-form').addEventListener('submit', function() {
                document.getElementById('submit-loader').classList.remove('hidden');
            });
            </script>
        </main>
    </div>
</div>

<?php require 'includes/footer.php'; ?>

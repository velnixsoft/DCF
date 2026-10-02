<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/student/portal_helpers.php';
require_once __DIR__ . '/includes/student/certificates.php';

$student = student_portal_require_student($pdo, 'certificates');
$certService = new StudentCertificateService($pdo);
$certificates = $certService->listForStudent((int)$student['id']);

student_portal_render_shell_start($pdo, $student, 'Certificate Details', 'certificates');
?>

<div class="grid gap-6 lg:grid-cols-[1fr_320px]">
    <!-- Left Column: Certificate list -->
    <section class="rounded-3xl border border-slate-100 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-md">
        <div class="flex items-center gap-4 border-b border-slate-100 pb-6 mb-8">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                <i class="fa-solid fa-award text-xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Certificate Center</h2>
                <p class="text-sm text-slate-500">View and download your official internship and campaign accomplishments.</p>
            </div>
        </div>

        <div class="space-y-4">
            <?php if (empty($certificates)): ?>
                <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50/50 p-12 text-center text-slate-400">
                    <span class="text-3xl block mb-2">📜</span>
                    No certificates issued yet. Complete program milestones to unlock rewards.
                </div>
            <?php endif; ?>

            <?php foreach ($certificates as $certificate): 
                $isGenerated = ($certificate['status'] ?? '') === 'Generated';
            ?>
                <article class="rounded-3xl border border-slate-100 bg-slate-50/50 p-6 shadow-sm transition-all hover:bg-slate-50/70 hover:border-slate-200/80">
                    <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                        <div class="min-w-0">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[9px] font-bold uppercase tracking-wider <?php echo $isGenerated ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-slate-100 text-slate-500 border border-slate-200'; ?>">
                                <?php echo htmlspecialchars($certificate['status'] ?? 'Unknown'); ?>
                            </span>
                            
                            <h3 class="mt-3 text-lg font-extrabold text-slate-900 tracking-tight"><?php echo htmlspecialchars($certificate['certificate_title'] ?? 'Certificate of Achievement'); ?></h3>
                            
                            <div class="mt-3 space-y-1.5 text-xs font-semibold text-slate-600">
                                <p class="flex items-center gap-2">
                                    <span class="text-slate-400">Cert No:</span>
                                    <span class="font-mono text-slate-800 bg-slate-100/70 px-2 py-0.5 rounded-lg border border-slate-200/25"><?php echo htmlspecialchars($certificate['certificate_no'] ?? '—'); ?></span>
                                </p>
                                <p class="flex items-center gap-2">
                                    <span class="text-slate-400">Issue Date:</span>
                                    <span class="text-slate-800"><?php echo !empty($certificate['issued_at']) ? htmlspecialchars(date('d M Y', strtotime($certificate['issued_at']))) : '—'; ?></span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap gap-3 shrink-0">
                            <?php if ($isGenerated): ?>
                                <a href="process/download_student_certificate.php?id=<?php echo (int)$certificate['id']; ?>" 
                                   target="_blank" 
                                   class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 hover:bg-emerald-700 px-5 py-3 text-xs font-bold text-white shadow-sm hover:shadow transition-all">
                                    <i class="fa-solid fa-file-pdf text-sm"></i> Download PDF
                                </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($certificate['verification_url'])): ?>
                                <a href="<?php echo htmlspecialchars($certificate['verification_url']); ?>" 
                                   target="_blank" 
                                   class="inline-flex items-center gap-1.5 rounded-2xl border border-slate-200 bg-white hover:bg-slate-50 px-5 py-3 text-xs font-bold text-slate-700 transition-all">
                                    Verify Authenticity
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Sidebar: policy -->
    <aside class="space-y-6">
        <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">Security & Verification</h3>
            <p class="text-xs text-slate-500 mt-2 leading-relaxed">Every certificate features a unique QR code and numeric identifier. Employers can verify the status directly on our public verification portal without requiring database access or contact emails.</p>
        </section>
    </aside>
</div>

<?php student_portal_render_shell_end(); ?>

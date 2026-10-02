<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$memberNo = trim($_GET['member'] ?? '');
$docNo = trim($_GET['doc'] ?? '');
$vcertNo = trim($_GET['vcert'] ?? '');
$scertNo = trim($_GET['scert'] ?? '');

$searchAttempted = isset($_GET['member']) || isset($_GET['doc']) || isset($_GET['vcert']) || isset($_GET['scert']);
$errorMessage = '';

if (
    $searchAttempted &&
    $memberNo === '' &&
    $docNo === '' &&
    $vcertNo === '' &&
    $scertNo === ''
) {
    $errorMessage = 'Please enter Member No, Document No, Visitor Cert No, or Sanstha Certificate No.';
}
$result = null;
$type = '';

if ($memberNo !== '') {
    $stmt = $pdo->prepare("SELECT m.*, d.title AS designation_title FROM members m LEFT JOIN member_designations d ON d.id = m.designation_id WHERE m.member_no = ? LIMIT 1");
    $stmt->execute([$memberNo]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $type = 'member';
}

if (!$result && $scertNo !== '') {
    $stmt = $pdo->prepare("SELECT * FROM sanstha_certificates WHERE certificate_no = ? LIMIT 1");
    $stmt->execute([$scertNo]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $type = 'sanstha';
}

if (!$result && $docNo !== '') {
    // Check if docNo is a sanstha cert
    $stmt = $pdo->prepare("SELECT * FROM sanstha_certificates WHERE certificate_no = ? LIMIT 1");
    $stmt->execute([$docNo]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $type = 'sanstha';
    } else {
        $stmt = $pdo->prepare("SELECT md.*, m.full_name, m.member_no, m.status FROM member_documents md INNER JOIN members m ON m.id = md.member_id WHERE md.doc_no = ? ORDER BY md.id DESC LIMIT 1");
        $stmt->execute([$docNo]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $type = 'doc';
    }
}

if (!$result && $vcertNo !== '') {
    $stmt = $pdo->prepare("SELECT * FROM visitor_certificates WHERE certificate_no = ? LIMIT 1");
    $stmt->execute([$vcertNo]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $type = 'visitor';
}
?>

<div class="bg-gray-50 min-h-screen py-12">
    <div class="container mx-auto px-4 max-w-3xl">
        <div class="bg-white rounded-xl shadow p-8 border border-gray-100">
            <h1 class="text-3xl font-bold text-gray-800 mb-3">Verification Portal</h1>
            <p class="text-gray-600 mb-8">Verify member IDs, membership documents, and visitor certificates.</p>

            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-8">
                <input type="text" name="member" placeholder="Member No" class="border p-3 rounded-lg" value="<?php echo htmlspecialchars($memberNo); ?>">
                <input type="text" name="doc" placeholder="Document No" class="border p-3 rounded-lg" value="<?php echo htmlspecialchars($docNo); ?>">
                <input type="text" name="vcert" placeholder="Visitor Cert No" class="border p-3 rounded-lg" value="<?php echo htmlspecialchars($vcertNo); ?>">
                <button class="md:col-span-3 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-semibold">Verify</button>
            </form>
            <?php if ($errorMessage): ?>
    <div id="validationError" class="rounded-lg border border-red-200 bg-red-50 p-5 mb-6">
        <h2 class="text-lg font-bold text-red-700 mb-2">Validation Error</h2>
        <p class="text-red-600">
            <?php echo htmlspecialchars($errorMessage); ?>
        </p>
    </div>
<?php endif; ?>

            <?php if ($result): ?>
                <div class="rounded-lg border p-5 <?php echo (($type === 'member' && $result['status'] === 'Active') || ($type !== 'member')) ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200'; ?>">
                    <h2 class="text-xl font-bold mb-3"><?php echo ucfirst($type); ?> Verification: Valid</h2>
                    <?php if ($type === 'member'): ?>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($result['full_name']); ?></p>
                        <p><strong>Member No:</strong> <?php echo htmlspecialchars($result['member_no']); ?></p>
                        <p><strong>Designation:</strong> <?php echo htmlspecialchars($result['designation_title'] ?: '-'); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($result['status']); ?></p>
                        <p><strong>Valid Until:</strong> <?php echo htmlspecialchars($result['valid_until'] ?: '-'); ?></p>
                    <?php elseif ($type === 'doc'): ?>
                        <p><strong>Member:</strong> <?php echo htmlspecialchars($result['full_name']); ?></p>
                        <p><strong>Member No:</strong> <?php echo htmlspecialchars($result['member_no']); ?></p>
                        <p><strong>Document Type:</strong> <?php echo htmlspecialchars($result['doc_type']); ?></p>
                        <p><strong>Document No:</strong> <?php echo htmlspecialchars($result['doc_no']); ?></p>
                        <p><strong>Issued:</strong> <?php echo date('d M Y', strtotime($result['issued_at'])); ?></p>
                    <?php elseif ($type === 'sanstha'): ?>
                        <p><strong>Sanstha / Center:</strong> <?php echo htmlspecialchars($result['sanstha_name']); ?></p>
                        <p><strong>Certificate No:</strong> <?php echo htmlspecialchars($result['certificate_no']); ?></p>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($result['auth_type']); ?></p>
                        <p><strong>In-Charge:</strong> <?php echo htmlspecialchars($result['authorized_person']); ?> (<?php echo htmlspecialchars($result['designation'] ?: 'Director'); ?>)</p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($result['district'] ?: $result['city']); ?><?php echo !empty($result['state']) ? ', ' . htmlspecialchars($result['state']) : ''; ?></p>
                        <p><strong>Status:</strong> <span class="font-bold text-emerald-700 uppercase"><?php echo htmlspecialchars($result['status']); ?></span></p>
                        <p><strong>Valid From:</strong> <?php echo date('d M Y', strtotime($result['valid_from'])); ?> to <?php echo !empty($result['valid_until']) ? date('d M Y', strtotime($result['valid_until'])) : 'Perpetual'; ?></p>
                        <?php if (!empty($result['pdf_path'])): ?>
                            <div class="mt-3">
                                <a href="../<?php echo htmlspecialchars($result['pdf_path']); ?>" target="_blank" class="inline-flex items-center gap-1 text-xs bg-amber-600 text-white px-3 py-1.5 rounded-lg font-semibold hover:bg-amber-700">
                                    <i class="fa-solid fa-file-pdf"></i> View Official Sanstha Certificate
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p><strong>Recipient:</strong> <?php echo htmlspecialchars($result['recipient_name']); ?></p>
                        <p><strong>Certificate:</strong> <?php echo htmlspecialchars($result['certificate_title']); ?></p>
                        <p><strong>Certificate No:</strong> <?php echo htmlspecialchars($result['certificate_no']); ?></p>
                        <p><strong>Issued:</strong> <?php echo date('d M Y', strtotime($result['created_at'])); ?></p>
                    <?php endif; ?>
                </div>
<?php elseif (!$errorMessage && ($memberNo !== '' || $docNo !== '' || $vcertNo !== '')): ?>
                <div class="rounded-lg border border-red-200 bg-red-50 p-5">
                    <h2 class="text-xl font-bold text-red-700 mb-2">Record Not Found</h2>
                    <p class="text-red-600">No valid record matched the provided details.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const errorBox = document.getElementById('validationError');

    if (errorBox) {
        setTimeout(() => {
            errorBox.style.transition = 'opacity 0.5s ease';
            errorBox.style.opacity = '0';

            setTimeout(() => {
                errorBox.remove();
            }, 500);
        }, 3000);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

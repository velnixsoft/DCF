<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$docs = [];
$error = null;

try {
    $stmt = $pdo->query("SELECT id, title, category, file_path, upload_date FROM ngo_documents WHERE is_public = 1 ORDER BY upload_date DESC");
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = 'Documents module is not installed. Ask admin to run DB migration (Database/upgrade_v2.sql).';
    $docs = [];
}
?>

<div class="bg-white min-h-screen mt-6">
    <div class="bg-gray-50 py-14 border-b border-gray-100">
        <div class="container mx-auto px-6 text-center">
            <h1 class="text-4xl font-extrabold text-gray-900">Documents</h1>
            <p class="mt-3 text-gray-600 max-w-2xl mx-auto">Download certificates, annual reports, and other official documents.</p>
            <p class="mt-3 text-sm text-green-700 max-w-3xl mx-auto">
                Access official Seed Council files related to sustainable development, education, skill programs, employment activities, public transparency, and council documentation.
            </p>
        </div>
    </div>

    <div class="container mx-auto px-6 py-12 max-w-4xl">
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl border border-gray-100 shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left">
                        <tr>
                            <th class="p-4 font-semibold">Title</th>
                            <th class="p-4 font-semibold">Category</th>
                            <th class="p-4 font-semibold">Date</th>
                            <th class="p-4 font-semibold text-right">Download</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($docs as $d): ?>
                            <tr>
                                <td class="p-4 font-semibold text-gray-900"><?php echo htmlspecialchars((string)$d['title']); ?></td>
                                <td class="p-4 text-gray-600"><?php echo htmlspecialchars((string)$d['category']); ?></td>
                                <td class="p-4 text-gray-600"><?php echo !empty($d['upload_date']) ? htmlspecialchars(date('d M Y', strtotime((string)$d['upload_date']))) : '-'; ?></td>
                                <td class="p-4 text-right">
                                    <a href="../<?php echo htmlspecialchars((string)$d['file_path']); ?>" target="_blank" class="text-indigo-600 hover:underline font-semibold">Download</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($docs) && !$error): ?>
                            <tr><td colspan="4" class="p-10 text-center text-gray-500">No public documents available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

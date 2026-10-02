<?php
require 'includes/header.php';

$documents = [];
$error = null;
$editDoc = null;

try {
    $documents = $pdo->query("SELECT * FROM ngo_documents ORDER BY upload_date DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = 'Documents module database table is missing. Run DB migration: Database/upgrade_v2.sql';
}

if (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM ngo_documents WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$_GET['edit']]);
        $editDoc = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $editDoc = null;
    }
}
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-800 dark:text-white">Document Management</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Upload NGO certificates, annual reports, and other downloadable documents.</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 p-5">
                    <h4 class="font-semibold mb-4 dark:text-white"><?php echo $editDoc ? 'Edit Document' : 'Upload Document'; ?></h4>

                    <form action="actions/documents_crud.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="<?php echo $editDoc ? 'update' : 'create'; ?>">
                        <input type="hidden" name="id" value="<?php echo (int)($editDoc['id'] ?? 0); ?>">

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Title *</label>
                            <input type="text" name="title" required value="<?php echo htmlspecialchars($editDoc['title'] ?? ''); ?>" class="w-full mt-1 border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Category</label>
                            <select name="category" class="w-full mt-1 border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <?php
                                $currentCat = $editDoc['category'] ?? 'other';
                                $cats = ['certificate' => 'Certificate', 'annual_report' => 'Annual Report', 'other' => 'Other'];
                                foreach ($cats as $k => $label):
                                ?>
                                    <option value="<?php echo $k; ?>" <?php echo $currentCat === $k ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">File <?php echo $editDoc ? '(optional)' : '*'; ?></label>
                            <input type="file" name="doc_file" <?php echo $editDoc ? '' : 'required'; ?> accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm text-gray-500">
                            <p class="text-xs text-gray-500 mt-1">Allowed: PDF/JPG/PNG (max 5MB)</p>
                            <?php if (!empty($editDoc['file_path'])): ?>
                                <a class="text-xs text-indigo-600 hover:underline" href="../<?php echo htmlspecialchars($editDoc['file_path']); ?>" target="_blank">View current file</a>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center gap-2">
                            <?php $isPublic = (int)($editDoc['is_public'] ?? 0) === 1; ?>
                            <input id="is_public" type="checkbox" name="is_public" value="1" <?php echo $isPublic ? 'checked' : ''; ?> class="h-4 w-4">
                            <label for="is_public" class="text-sm text-gray-700 dark:text-gray-300">Visible on public website</label>
                        </div>

                        <div class="flex gap-2">
                            <button class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-semibold">
                                <?php echo $editDoc ? 'Update' : 'Upload'; ?>
                            </button>
                            <?php if ($editDoc): ?>
                                <a href="documents.php" class="flex-1 text-center border border-gray-300 dark:border-gray-600 py-2.5 rounded-lg dark:text-white">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-gray-700 dark:text-gray-200">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="p-4 text-left font-semibold">Document</th>
                                    <th class="p-4 text-left font-semibold">Category</th>
                                    <th class="p-4 text-left font-semibold">Public</th>
                                    <th class="p-4 text-right font-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <?php foreach ($documents as $d): ?>
                                    <tr class="align-top">
                                        <td class="p-4">
                                            <div class="font-semibold dark:text-white"><?php echo htmlspecialchars($d['title']); ?></div>
                                            <div class="text-xs text-gray-500 mt-1"><?php echo !empty($d['upload_date']) ? htmlspecialchars(date('d M Y', strtotime((string)$d['upload_date']))) : '-'; ?></div>
                                            <?php if (!empty($d['file_path'])): ?>
                                                <div class="text-xs mt-1">
                                                    <a class="text-blue-600 hover:underline" href="../<?php echo htmlspecialchars($d['file_path']); ?>" target="_blank">Download</a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4 text-xs"><?php echo htmlspecialchars((string)$d['category']); ?></td>
                                        <td class="p-4">
                                            <span class="px-2 py-1 rounded-full text-xs <?php echo (int)$d['is_public'] === 1 ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700'; ?>">
                                                <?php echo (int)$d['is_public'] === 1 ? 'Yes' : 'No'; ?>
                                            </span>
                                        </td>
                                        <td class="p-4 text-right">
                                            <a class="text-indigo-600 hover:underline text-sm mr-3" href="documents.php?edit=<?php echo (int)$d['id']; ?>">Edit</a>
                                            <form action="actions/documents_crud.php" method="POST" class="inline" onsubmit="return confirm('Delete this document?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo (int)$d['id']; ?>">
                                                <button class="text-red-600 hover:underline text-sm">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($documents) && !$error): ?>
                                    <tr><td class="p-8 text-center text-gray-500" colspan="4">No documents uploaded yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require 'includes/footer.php'; ?>


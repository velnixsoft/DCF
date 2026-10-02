<?php
require 'includes/header.php';

$posts = [];
$error = null;
$editPost = null;
$oldInput = $_SESSION['news_form_old'] ?? null;
unset($_SESSION['news_form_old']);

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(title LIKE :search OR slug LIKE :search OR content LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($statusFilter !== '') {
    $where[] = "status = :status";
    $params[':status'] = $statusFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;
$totalPosts = 0;

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM posts $whereSql");
    foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $totalPosts = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT id, title, slug, status, published_at, created_at FROM posts $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $error = 'News module database table is missing. Run DB migration: Database/upgrade_v3.sql';
}

if (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$_GET['edit']]);
        $editPost = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $editPost = null;
    }
}
$formPost = $oldInput ?: $editPost ?: [
    'id' => 0,
    'title' => '',
    'slug' => '',
    'status' => 'Draft',
    'content' => '',
    'cover_path' => '',
];
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-800 dark:text-white">News / Updates</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Publish website updates, announcements, and event news.</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1 bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 p-5">
                    <h4 class="font-semibold mb-4 dark:text-white"><?php echo $editPost ? 'Edit Post' : 'Create Post'; ?></h4>

                    <form action="actions/news_crud.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="<?php echo $editPost ? 'update' : 'create'; ?>">
                        <input type="hidden" name="id" value="<?php echo (int)($formPost['id'] ?? 0); ?>">

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Title *</label>
                            <input type="text" name="title" required value="<?php echo htmlspecialchars($formPost['title'] ?? ''); ?>" class="w-full mt-1 border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Slug (optional)</label>
                            <input type="text" name="slug" value="<?php echo htmlspecialchars($formPost['slug'] ?? ''); ?>" class="w-full mt-1 border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="auto-generated-from-title">
                            <p class="text-xs text-gray-500 mt-1">Used in URL: <span class="font-mono">news-details.php?slug=...</span></p>
                        </div>

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Status</label>
                            <select name="status" class="w-full mt-1 border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <?php
                                $currentStatus = $formPost['status'] ?? 'Draft';
                                foreach (['Draft', 'Published', 'Archived'] as $st):
                                ?>
                                    <option value="<?php echo $st; ?>" <?php echo $currentStatus === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Cover Image (optional, max 2MB)</label>
                            <input type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="w-full text-sm text-gray-500" data-max-size="2097152">
                            <?php if (!empty($editPost['cover_path'])): ?>
                                <a class="text-xs text-indigo-600 hover:underline" href="../<?php echo htmlspecialchars($editPost['cover_path']); ?>" target="_blank">View current cover</a>
                            <?php endif; ?>
                            <p class="text-xs text-gray-500 mt-1">Maximum file size: 2MB.</p>
                        </div>

                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Content</label>
                            <textarea name="content" rows="8" class="w-full mt-1 border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"><?php echo htmlspecialchars($formPost['content'] ?? ''); ?></textarea>
                        </div>

                        <div class="flex gap-2">
                            <button class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-semibold">
                                <?php echo $editPost ? 'Update' : 'Create'; ?>
                            </button>
                            <?php if ($editPost): ?>
                                <a href="news.php" class="flex-1 text-center border border-gray-300 dark:border-gray-600 py-2.5 rounded-lg dark:text-white">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="lg:col-span-2 space-y-4">
                    <!-- Search & Filter Bar -->
                    <form method="GET" class="flex flex-wrap gap-3 items-center">
                        <div class="relative flex-1 min-w-[200px]">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search news title, slug..."
                                   class="w-full pl-9 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-500">
                            <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        </div>
                        <select name="status" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-700 dark:text-gray-200">
                            <option value="">All Statuses</option>
                            <option value="Draft" <?php echo $statusFilter === 'Draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="Published" <?php echo $statusFilter === 'Published' ? 'selected' : ''; ?>>Published</option>
                            <option value="Archived" <?php echo $statusFilter === 'Archived' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition">
                            Filter
                        </button>
                        <?php if ($search !== '' || $statusFilter !== ''): ?>
                            <a href="news.php" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition">
                                Reset
                            </a>
                        <?php endif; ?>
                    </form>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-gray-700 dark:text-gray-200">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="p-4 text-left font-semibold">Title</th>
                                        <th class="p-4 text-left font-semibold">Status</th>
                                        <th class="p-4 text-left font-semibold">Published</th>
                                        <th class="p-4 text-right font-semibold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y dark:divide-gray-700">
                                    <?php foreach ($posts as $p): ?>
                                        <tr class="align-top">
                                            <td class="p-4">
                                                <div class="font-semibold dark:text-white"><?php echo htmlspecialchars($p['title']); ?></div>
                                                <div class="text-xs text-gray-500 font-mono mt-1"><?php echo htmlspecialchars($p['slug']); ?></div>
                                            </td>
                                            <td class="p-4">
                                                <span class="px-2 py-1 rounded-full text-xs <?php echo $p['status'] === 'Published' ? 'bg-green-100 text-green-700' : ($p['status'] === 'Draft' ? 'bg-amber-100 text-amber-700' : 'bg-gray-200 text-gray-700'); ?>">
                                                    <?php echo htmlspecialchars($p['status']); ?>
                                                </span>
                                            </td>
                                            <td class="p-4 text-xs text-gray-500">
                                                <?php echo !empty($p['published_at']) ? htmlspecialchars(date('d M Y', strtotime((string)$p['published_at']))) : '-'; ?>
                                            </td>
                                            <td class="p-4 text-right">
                                                <a class="text-indigo-600 hover:underline text-sm mr-3" href="news.php?edit=<?php echo (int)$p['id']; ?>">Edit</a>
                                                <form action="actions/news_crud.php" method="POST" class="inline" onsubmit="return confirm('Delete this post?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                                    <button class="text-red-600 hover:underline text-sm">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($posts) && !$error): ?>
                                        <tr><td class="p-8 text-center text-gray-500" colspan="4">No posts found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php echo render_admin_pagination($totalPosts, $page, $perPage, ['search' => $search, 'status' => $statusFilter]); ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const coverInput = document.querySelector('input[name="cover"][data-max-size]');
    if (!coverInput) return;

    const maxSize = Number(coverInput.dataset.maxSize || 0);
    const validateFile = () => {
        const file = coverInput.files && coverInput.files[0] ? coverInput.files[0] : null;
        if (file && maxSize > 0 && file.size > maxSize) {
            coverInput.setCustomValidity('Cover image must be under 2MB.');
        } else {
            coverInput.setCustomValidity('');
        }
        coverInput.reportValidity();
    };

    coverInput.addEventListener('change', validateFile);
});
</script>

<?php require 'includes/footer.php'; ?>

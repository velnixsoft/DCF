<?php require 'includes/header.php'; ?>
<?php require '../config/db.php'; ?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="{ isModalOpen: <?php echo (isset($_GET['action']) && $_GET['action'] === 'create') ? 'true' : 'false'; ?> }">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">

            <div class="flex justify-between items-center mb-8">
                <h3 class="text-3xl font-bold text-gray-800 dark:text-white">Projects</h3>
                <button @click="isModalOpen = true; if(window.$refs && $refs.createProjectForm) $refs.createProjectForm.reset(); else { const f = document.getElementById('createProjectForm'); if(f) f.reset(); }" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg shadow-lg flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Project
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php 
                $projects = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll(); 
                
                // Pre-fetch expenses per project
                $spentMap = [];
                try {
                    $spentRows = $pdo->query("SELECT project_id, COALESCE(SUM(amount), 0) as total_spent FROM expenses WHERE approved_status IN ('Approved', 'Paid') AND project_id IS NOT NULL GROUP BY project_id")->fetchAll(PDO::FETCH_KEY_PAIR);
                    $spentMap = $spentRows ?: [];
                } catch (Throwable $e) {
                    $spentMap = [];
                }
                ?>
                <?php if (count($projects) > 0): foreach ($projects as $p):

                        $raised = (float)$p['raised_amount'];
                        $target = (float)$p['target_amount'];
                        $spent = (float)($spentMap[$p['id']] ?? 0.0);
                        $remaining = $raised - $spent;
                        $percent_raw = ($target > 0) ? round(($raised / $target) * 100) : 0;
                        $percent_display = min($percent_raw, 100);
                ?>
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border dark:border-gray-700 overflow-hidden flex flex-col group transition-all duration-300 hover:shadow-lg hover:-translate-y-1">

                            <div class="aspect-video bg-gray-100 dark:bg-gray-700 overflow-hidden relative">
                                <img src="../<?php echo $p['thumbnail_image'] ?: 'https://placehold.co/800x600/e2e8f0/64748b?text=No+Thumb'; ?>" class="w-full h-full object-cover transition duration-300 group-hover:scale-105">
                                <span class="absolute top-3 left-3 px-2 py-1 text-[10px] font-bold rounded-full text-white <?php echo $p['status'] == 'Active' ? 'bg-emerald-600' : 'bg-gray-500'; ?>"><?php echo $p['status']; ?></span>
                                <?php if ($spent > 0): ?>
                                    <span class="absolute top-3 right-3 px-2 py-1 text-[10px] font-bold rounded-full bg-black/60 backdrop-blur-sm text-white">
                                        Spent: ₹<?php echo number_format($spent); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="p-5 flex-1 flex flex-col justify-between">
                                <div>
                                    <h4 class="font-bold text-base text-gray-800 dark:text-white line-clamp-1" title="<?php echo htmlspecialchars($p['title']); ?>"><?php echo htmlspecialchars($p['title']); ?></h4>

                                    <div class="mt-3 pt-3 border-t dark:border-gray-700">
                                        <div class="flex justify-between text-[11px] text-gray-500 dark:text-gray-400">
                                            <span>Raised: <strong class="text-emerald-600">₹<?php echo number_format($raised); ?></strong></span>
                                            <span>Target: <strong>₹<?php echo number_format($target); ?></strong></span>
                                        </div>

                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1.5 overflow-hidden relative">
                                            <div class="bg-gradient-to-r from-blue-500 to-teal-500 h-2 rounded-full" style="width: <?php echo $percent_display; ?>%"></div>
                                        </div>

                                        <div class="mt-2.5 pt-2 border-t border-dashed border-gray-100 dark:border-gray-700 flex justify-between text-[11px]">
                                            <span class="text-gray-400">Remaining Fund:</span>
                                            <span class="font-black <?php echo $remaining >= 0 ? 'text-teal-600 dark:text-teal-400' : 'text-rose-600'; ?>">
                                                ₹<?php echo number_format($remaining); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-700 grid grid-cols-2 gap-2">
                                    <a href="project_edit.php?id=<?php echo $p['id']; ?>" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-center font-bold py-2 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition text-xs">
                                        Edit Details
                                    </a>
                                    <a href="project_edit.php?id=<?php echo $p['id']; ?>&tab=expenses" class="bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] dark:text-teal-300 text-center font-bold py-2 rounded-xl hover:bg-teal-100 dark:hover:bg-teal-900/50 transition text-xs flex items-center justify-center gap-1">
                                        <i class="fa-solid fa-scale-balanced"></i> Ledger
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach;
                else: ?>
                    <p class="col-span-full text-center text-gray-500 py-10">No projects found. Create one to get started!</p>
                <?php endif; ?>
            </div>

            <div x-show="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak>
                <form action="actions/project_crud.php" id="createProjectForm" x-ref="createProjectForm" method="POST" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl" @click.away="isModalOpen = false">
                    <div class="p-6 border-b dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-xl font-bold dark:text-white">Create New Project</h3>
                        <button type="button" @click="isModalOpen=false" class="text-gray-400">&times;</button>
                    </div>
                    <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div><label class="text-sm font-medium dark:text-gray-300">Title*</label><input type="text" name="title" required class="w-full border p-2 rounded mt-1 dark:bg-gray-700 dark:border-gray-600"></div>
                        <div><label class="text-sm font-medium dark:text-gray-300">Description *</label><textarea name="description" required rows="3" class="w-full border p-2 rounded mt-1 dark:bg-gray-700 dark:border-gray-600"></textarea></div>
                        <div class="grid grid-cols-3 gap-4">
                            <div><label class="text-sm font-medium dark:text-gray-300">Target Amount *</label><input type="number" name="target_amount" min="1" required class="w-full border p-2 rounded mt-1 dark:bg-gray-700 dark:border-gray-600"></div>
                            <div><label class="text-sm font-medium dark:text-gray-300">Raised Amount</label><input type="number" name="raised_amount" min="0" value="0" class="w-full border p-2 rounded mt-1 dark:bg-gray-700 dark:border-gray-600"></div>
                            <div><label class="text-sm font-medium dark:text-gray-300">Status</label><select name="status" class="w-full border p-2 rounded mt-1 dark:bg-gray-700 dark:border-gray-600">
                                    <option>Active</option>
                                    <option>Completed</option>
                                </select></div>
                        </div>
                        <div>
                            <label class="text-sm font-medium dark:text-gray-300">YouTube Video URL</label>
                            <input type="url" name="video_url" pattern="^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be|youtube-nocookie\.com)\/.*$" placeholder="https://www.youtube.com/watch?v=..." class="w-full border p-2 rounded mt-1 dark:bg-gray-700 dark:border-gray-600">
                            <span class="text-xs text-gray-400 mt-1 block">Must be a valid YouTube URL (e.g., https://www.youtube.com/watch?v=...)</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="text-sm font-medium dark:text-gray-300">Thumbnail (JPG, PNG, WEBP)*</label><input type="file" name="thumbnail" required accept="image/jpeg,image/png,image/webp" class="w-full text-sm mt-1 dark:text-gray-400"></div>
                            <div><label class="text-sm font-medium dark:text-gray-300">UPI QR (JPG, PNG, WEBP)</label><input type="file" name="qr" accept="image/jpeg,image/png,image/webp" class="w-full text-sm mt-1 dark:text-gray-400"></div>
                        </div>
                        <div><label class="text-sm font-medium dark:text-gray-300">Gallery (Multiple, JPG, PNG, WEBP)</label><input type="file" name="gallery[]" accept="image/jpeg,image/png,image/webp" multiple class="w-full text-sm mt-1 dark:text-gray-400"></div>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/50 flex justify-end gap-3 rounded-b-xl"><button type="button" @click="isModalOpen = false" class="px-4 py-2 border rounded dark:border-gray-600">Cancel</button><button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Create Project</button></div>
                </form>
            </div>
        </main>
    </div>
</div>
<?php require 'includes/footer.php'; ?>
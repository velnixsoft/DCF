<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/student/portal_helpers.php';
require_once __DIR__ . '/includes/student/notification_manager.php';

$student = student_portal_require_student($pdo, 'notifications');
$manager = new NotificationManager($pdo);
$notifications = $manager->getNotifications((int)$student['id'], 50, 0, 'all');
$unreadSummary = $manager->getUnreadSummary((int)$student['id']);
$_SESSION['student_notification_csrf'] = $_SESSION['student_notification_csrf'] ?? bin2hex(random_bytes(16));

student_portal_render_shell_start($pdo, $student, 'My Notifications', 'notifications');
?>

<div class="grid gap-6 lg:grid-cols-[1fr_320px]">
    <!-- Left Column: Notifications center -->
    <section class="rounded-3xl border border-slate-100 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-md">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-6 mb-8">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                    <i class="fa-solid fa-bell text-xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Notification Center</h2>
                    <p class="text-sm text-slate-500">Read updates on campaign approvals, new points, level upgrades, and batch events.</p>
                </div>
            </div>
            
            <?php if ((int)$unreadSummary['unread_count'] > 0): ?>
                <form action="process/student_notification_action.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['student_notification_csrf']); ?>">
                    <input type="hidden" name="action" value="mark_all_read">
                    <button type="submit" class="rounded-2xl border border-slate-200 bg-white hover:bg-slate-50 px-5 py-3 text-xs font-bold text-slate-700 transition-all">
                        Mark All as Read
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="space-y-4">
            <?php if (empty($notifications)): ?>
                <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50/50 p-12 text-center text-slate-400">
                    <span class="text-3xl block mb-2">🔔</span>
                    No notifications yet.
                </div>
            <?php endif; ?>

            <?php foreach ($notifications as $notification): 
                $isRead = !empty($notification['is_read']);
            ?>
                <article class="rounded-3xl border transition-all p-6 shadow-sm <?php echo $isRead ? 'border-slate-100 bg-white' : 'border-emerald-150 bg-emerald-50/20'; ?>">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex items-start gap-4">
                            <!-- Unread glowing point -->
                            <?php if (!$isRead): ?>
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 shrink-0 mt-1.5 animate-pulse"></span>
                            <?php else: ?>
                                <span class="h-2.5 w-2.5 rounded-full bg-slate-200 shrink-0 mt-1.5"></span>
                            <?php endif; ?>
                            
                            <div>
                                <span class="inline-flex items-center rounded-full bg-slate-100/80 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-slate-500">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $notification['notification_type'])); ?>
                                </span>
                                
                                <h3 class="mt-2.5 text-base font-extrabold text-slate-900 tracking-tight leading-snug"><?php echo htmlspecialchars($notification['title']); ?></h3>
                                <p class="mt-1 text-xs text-slate-600 leading-relaxed max-w-xl"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <p class="mt-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider"><?php echo htmlspecialchars(date('d M Y, h:i A', strtotime($notification['created_at']))); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex flex-row sm:flex-col items-center sm:items-end gap-3 shrink-0 pt-2 sm:pt-0 pl-6 sm:pl-0 border-t border-slate-100 sm:border-t-0">
                            <?php if (!$isRead): ?>
                                <form action="process/student_notification_action.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['student_notification_csrf']); ?>">
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="notification_id" value="<?php echo (int)$notification['id']; ?>">
                                    <button type="submit" class="rounded-xl border border-emerald-250 bg-white hover:bg-emerald-50 px-4 py-2 text-xs font-bold text-emerald-700 transition-all">
                                        Mark read
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Read</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Sidebar summary -->
    <aside class="space-y-6">
        <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-extrabold text-slate-900 tracking-tight mb-4">Summary</h3>
            <div class="grid gap-3">
                <div class="rounded-2xl bg-emerald-50/50 border border-emerald-100/50 px-5 py-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Unread Alerts</p>
                    <p class="text-3xl font-black text-emerald-950 mt-1"><?php echo (int)$unreadSummary['unread_count']; ?></p>
                </div>
                <div class="rounded-2xl bg-slate-50 border border-slate-100/50 px-5 py-4">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Types</p>
                    <p class="text-3xl font-black text-slate-800 mt-1"><?php echo (int)$unreadSummary['notification_types']; ?></p>
                </div>
            </div>
        </section>
    </aside>
</div>

<?php student_portal_render_shell_end(); ?>

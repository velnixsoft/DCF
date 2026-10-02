<?php
require 'includes/header.php';
require '../config/db.php';

$members = $pdo->query("SELECT id, full_name, email, member_no FROM members WHERE status='Active' ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$logs = $pdo->query("
    SELECT md.*, mm.subject, mm.message_type, mm.created_at AS message_created_at, m.full_name, m.member_no
    FROM member_message_deliveries md
    INNER JOIN member_messages mm ON mm.id = md.message_id
    INNER JOIN members m ON m.id = md.member_id
    ORDER BY md.created_at DESC
    LIMIT 80
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-700 dark:text-white">Member Messaging</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Send messages to one member or all active members.</p>
                </div>
                <a href="actions/send_birthday_wishes.php" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm">Run Birthday Wishes</a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1 bg-white dark:bg-dark-card rounded-lg shadow border dark:border-gray-700 p-5">
                    <h4 class="font-semibold mb-4 dark:text-white">Compose Message</h4>
                    <form action="actions/member_message_logic.php" method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="send_message">
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Target</label>
                            <select name="target" id="targetSelect" class="w-full mt-1 border rounded p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white" onchange="document.getElementById('memberSelectWrap').style.display = this.value === 'single' ? 'block' : 'none';">
                                <option value="all">All Active Members</option>
                                <option value="single">Single Member</option>
                            </select>
                        </div>
                        <div id="memberSelectWrap" style="display:none;">
                            <label class="text-sm text-gray-600 dark:text-gray-300">Member</label>
                            <select name="member_id" class="w-full mt-1 border rounded p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <?php foreach ($members as $m): ?>
                                    <option value="<?php echo (int)$m['id']; ?>">
                                        <?php echo htmlspecialchars($m['full_name'] . ' (' . ($m['member_no'] ?: 'No ID') . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Subject</label>
                            <input type="text" name="subject" required class="w-full mt-1 border rounded p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Message</label>
                            <textarea name="message_body" rows="6" required class="w-full mt-1 border rounded p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>
                        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded">Send Message</button>
                    </form>
                </div>

                <div class="lg:col-span-2 bg-white dark:bg-dark-card rounded-lg shadow border dark:border-gray-700 p-5">
                    <h4 class="font-semibold mb-4 dark:text-white">Delivery Logs</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 border-b dark:border-gray-700">
                                    <th class="p-2">Member</th>
                                    <th class="p-2">Subject</th>
                                    <th class="p-2">Type</th>
                                    <th class="p-2">Email</th>
                                    <th class="p-2">Dashboard</th>
                                    <th class="p-2">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <?php foreach ($logs as $row): ?>
                                    <tr>
                                        <td class="p-2">
                                            <p class="font-semibold dark:text-white"><?php echo htmlspecialchars($row['full_name']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($row['member_no'] ?: '-'); ?></p>
                                        </td>
                                        <td class="p-2 dark:text-gray-200"><?php echo htmlspecialchars($row['subject']); ?></td>
                                        <td class="p-2 text-xs dark:text-gray-200"><?php echo htmlspecialchars($row['message_type']); ?></td>
                                        <td class="p-2 text-xs <?php echo $row['email_status'] === 'Sent' ? 'text-green-600' : ($row['email_status'] === 'Failed' ? 'text-red-600' : 'text-orange-600'); ?>">
                                            <?php echo htmlspecialchars($row['email_status']); ?>
                                        </td>
                                        <td class="p-2 text-xs dark:text-gray-200"><?php echo htmlspecialchars($row['dashboard_status']); ?></td>
                                        <td class="p-2 text-xs text-gray-500"><?php echo date('d M Y H:i', strtotime($row['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="6" class="p-4 text-center text-gray-500">No message logs found.</td>
                                    </tr>
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

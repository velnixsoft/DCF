<?php require 'includes/header.php'; ?>
<?php require '../config/db.php';

$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) $settings[$row['setting_key']] = $row['setting_value'];

$banks = $pdo->query("SELECT * FROM bank_accounts")->fetchAll();
$qrs   = $pdo->query("SELECT * FROM payment_qrs")->fetchAll();

$activeTab = $_GET['tab'] ?? 'general';
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="{ tab: '<?php echo $activeTab; ?>' }">
    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300 min-w-0">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-3 sm:p-4 md:p-8">

            <!-- Page heading -->
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Settings</h3>
            </div>

            <!-- Settings card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col md:flex-row">

                <!-- Mobile tab selector -->
                <div class="md:hidden px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 shrink-0">
                    <label class="text-xs font-bold text-gray-500 uppercase mb-1 block">Section</label>
                    <div class="relative">
                        <select x-model="tab" class="w-full appearance-none bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-white py-2.5 px-4 pr-8 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                            <option value="general">General</option>
                            <option value="payments">Payments</option>
                            <option value="smtp">Email</option>
                            <option value="receipt">Branding</option>
                            <option value="brands">Document Brands</option>
                            <option value="footer">Footer</option>
                            <option value="security">Security</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 dark:text-gray-300">
                            <svg class="fill-current h-4 w-4" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                        </div>
                    </div>
                </div>

                <!-- Desktop sidebar nav -->
                <div class="hidden md:flex flex-col w-56 lg:w-64 shrink-0 bg-gray-50 dark:bg-gray-800/50 border-r border-gray-100 dark:border-gray-700 overflow-y-auto">
                    <nav class="flex-1 py-4 space-y-0.5">
                        <?php
                        $tabs = [
                            'general'  => 'General',
                            'payments' => 'Payments',
                            'smtp'     => 'Email Server',
                            'receipt'  => 'Branding',
                            'brands'   => 'Document Brands',
                            'footer'   => 'Footer',
                            'security' => 'Security',
                        ];
                        foreach ($tabs as $key => $label): ?>
                        <button
                            @click="tab = '<?php echo $key; ?>'"
                            :class="tab==='<?php echo $key; ?>' ? 'bg-white dark:bg-gray-700 text-blue-600 border-l-4 border-blue-600 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-gray-700/40 border-l-4 border-transparent'"
                            class="w-full text-left px-5 py-3.5 text-sm transition-colors">
                            <?php echo $label; ?>
                        </button>
                        <?php endforeach; ?>
                    </nav>
                </div>

                <!-- Content panel -->
                <div class="flex-1 min-w-0 p-4 sm:p-6 md:p-8 overflow-y-auto" style="max-height: calc(100vh - 130px);">

                    <!-- ── General ── -->
                    <div x-show="tab === 'general'" x-transition.opacity class="space-y-6">
                        <form action="actions/settings_logic.php" method="POST" class="space-y-5">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="tab" value="general">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">NGO Name</label>
                                    <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Reg No.</label>
                                    <input type="text" name="reg_no" value="<?php echo htmlspecialchars($settings['reg_no'] ?? ''); ?>" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Contact Email</label>
                                    <input type="email" name="ngo_email" value="<?php echo htmlspecialchars($settings['ngo_email'] ?? ''); ?>" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Phone</label>
                                    <input type="text" name="ngo_phone" value="<?php echo htmlspecialchars($settings['ngo_phone'] ?? ''); ?>" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">NGO Website</label>
                                    <input type="text" name="ngo_website" value="<?php echo htmlspecialchars($settings['ngo_website'] ?? ''); ?>" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Homepage Theme</label>
                                    <?php $ht = strtolower((string)($settings['home_theme'] ?? 'classic')); ?>
                                    <select name="home_theme" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="classic"  <?php echo $ht === 'classic'  ? 'selected' : ''; ?>>Classic</option>
                                        <option value="modern"   <?php echo $ht === 'modern'   ? 'selected' : ''; ?>>Modern</option>
                                        <option value="modern1"  <?php echo $ht === 'modern1'  ? 'selected' : ''; ?>>Modern 1</option>
                                        <option value="modern2"  <?php echo $ht === 'modern2'  ? 'selected' : ''; ?>>Modern 2</option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Controls homepage layout for all visitors.</p>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Office Address</label>
                                    <textarea name="ngo_address" rows="3" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($settings['ngo_address'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div class="pt-4 flex justify-end">
                                <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg transition-colors font-medium">Save</button>
                            </div>
                        </form>
                    </div>

                    <!-- ── Footer ── -->
                    <div x-show="tab === 'footer'" x-transition.opacity class="space-y-6">
                        <form action="actions/settings_logic.php" method="POST" class="space-y-5">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="tab" value="footer">

                            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                                <label class="block text-sm font-bold mb-1.5 text-green-800 dark:text-green-400">
                                    <i class="fab fa-whatsapp mr-1"></i> WhatsApp Number
                                </label>
                                <input type="text" name="whatsapp_number" value="<?php echo htmlspecialchars($settings['whatsapp_number'] ?? ''); ?>"
                                    class="w-full px-4 py-2.5 rounded-lg border border-green-300 dark:border-green-700 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 outline-none transition"
                                    placeholder="e.g. 919000000000">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Enter with country code, no spaces or symbols.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Footer About</label>
                                <textarea name="footer_about" rows="3" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($settings['footer_about'] ?? ''); ?></textarea>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Facebook</label>
                                    <input type="url" name="social_facebook" value="<?php echo htmlspecialchars($settings['social_facebook'] ?? ''); ?>" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Instagram</label>
                                    <input type="url" name="social_instagram" value="<?php echo htmlspecialchars($settings['social_instagram'] ?? ''); ?>" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">YouTube</label>
                                    <input type="url" name="social_youtube" value="<?php echo htmlspecialchars($settings['social_youtube'] ?? ''); ?>" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            <div class="pt-4 flex justify-end">
                                <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg transition-colors font-medium">Save</button>
                            </div>
                        </form>
                    </div>

                    <!-- ── Security ── -->
                    <div x-show="tab === 'security'" x-transition.opacity class="space-y-6">
                        <form action="actions/settings_logic.php" method="POST" class="space-y-5 max-w-md">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="tab" value="security">
                            <div>
                                <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Current Password</label>
                                <input type="password" name="current_password" required class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">New Password</label>
                                <input type="password" name="new_password" required class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Confirm Password</label>
                                <input type="password" name="confirm_password" required class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="pt-4">
                                <button class="bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-lg transition-colors font-medium">Update Password</button>
                            </div>
                        </form>
                    </div>

                    <!-- ── Payments ── -->
                    <div x-show="tab === 'payments'" x-transition.opacity class="space-y-8">

                        <!-- Bank accounts + QR codes -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">

                            <!-- Bank accounts -->
                            <div class="space-y-4">
                                <h5 class="font-bold dark:text-white">Bank Accounts</h5>
                                <form action="actions/settings_logic.php" method="POST" class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-lg border dark:border-gray-700 space-y-3">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="action" value="add_bank">
                                    <input type="text" name="bank_name" placeholder="Bank Name" required class="w-full border p-2.5 rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <input type="text" name="account_holder" placeholder="Account Holder" required class="w-full border p-2.5 rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="text" name="account_number" placeholder="Account No." required class="border p-2.5 rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <input type="text" name="ifsc_code" placeholder="IFSC" class="border p-2.5 rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <button class="bg-blue-600 hover:bg-blue-700 text-white w-full py-2.5 rounded-lg text-sm font-medium transition-colors">Add Account</button>
                                </form>
                                <div class="space-y-2">
                                    <?php foreach ($banks as $b): ?>
                                    <div class="bg-white dark:bg-gray-700 p-3 rounded-lg border dark:border-gray-600 flex justify-between items-center gap-3 text-sm">
                                        <div class="min-w-0">
                                            <p class="font-bold dark:text-white truncate"><?php echo htmlspecialchars($b['bank_name']); ?></p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($b['account_number']); ?></p>
                                        </div>
                                        <form action="actions/settings_logic.php" method="POST" onsubmit="return confirm('Delete this bank account?');" class="shrink-0">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="action" value="delete_bank">
                                            <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                            <button class="text-red-500 hover:text-red-700 text-xl leading-none p-1">&times;</button>
                                        </form>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- UPI QR Codes -->
                            <div class="space-y-4">
                                <h5 class="font-bold dark:text-white">UPI QR Codes</h5>
                                <form action="actions/settings_logic.php" method="POST" enctype="multipart/form-data" class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-lg border dark:border-gray-700 space-y-3">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="action" value="add_qr">
                                    <input type="text" name="title" placeholder="e.g., Google Pay" required class="w-full border p-2.5 rounded-lg text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <input type="file" name="qr_image" required accept="image/*" class="w-full text-sm dark:text-gray-400">
                                    <button class="bg-green-600 hover:bg-green-700 text-white w-full py-2.5 rounded-lg text-sm font-medium transition-colors">Add QR Code</button>
                                </form>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    <?php foreach ($qrs as $q): ?>
                                    <div class="bg-white dark:bg-gray-700 p-2 rounded-lg border dark:border-gray-600 text-center relative group">
                                        <img src="../<?php echo $q['qr_image_path']; ?>" class="w-full aspect-square object-contain">
                                        <p class="text-xs font-medium mt-1 dark:text-white truncate"><?php echo htmlspecialchars($q['title']); ?></p>
                                        <form action="actions/settings_logic.php" method="POST" onsubmit="return confirm('Delete?');" class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="action" value="delete_qr">
                                            <input type="hidden" name="id" value="<?php echo $q['id']; ?>">
                                            <button class="bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">&times;</button>
                                        </form>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Razorpay — Membership -->
                        <div class="bg-gray-50 dark:bg-gray-900/50 p-4 sm:p-5 rounded-lg border dark:border-gray-700">
                            <h5 class="font-bold mb-3 dark:text-white">Razorpay (Membership Payments)</h5>
                            <form action="actions/settings_logic.php" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="tab" value="payments">
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Key ID</label>
                                    <input type="text" name="razorpay_key_id" value="<?php echo htmlspecialchars($settings['razorpay_key_id'] ?? ''); ?>" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Key Secret</label>
                                    <input type="password" name="razorpay_key_secret" value="<?php echo htmlspecialchars($settings['razorpay_key_secret'] ?? ''); ?>" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div class="sm:col-span-2 flex justify-end">
                                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg transition-colors font-medium">Save</button>
                                </div>
                            </form>
                        </div>

                        <!-- Razorpay — Donation -->
                        <div class="bg-gray-50 dark:bg-gray-900/50 p-4 sm:p-5 rounded-lg border dark:border-gray-700">
                            <h5 class="font-bold mb-1 dark:text-white">Razorpay (Donation Payments)</h5>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">If empty, donation checkout will fall back to the membership Razorpay keys.</p>
                            <form action="actions/settings_logic.php" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="tab" value="payments">
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Donation Key ID</label>
                                    <input type="text" name="razorpay_donation_key_id" value="<?php echo htmlspecialchars($settings['razorpay_donation_key_id'] ?? ''); ?>" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Donation Key Secret</label>
                                    <input type="password" name="razorpay_donation_key_secret" value="<?php echo htmlspecialchars($settings['razorpay_donation_key_secret'] ?? ''); ?>" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div class="sm:col-span-2 flex justify-end">
                                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg transition-colors font-medium">Save Donation Razorpay</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- ── SMTP ── -->
                    <div x-show="tab === 'smtp'" x-transition.opacity class="space-y-6">
                        <div>
                            <h4 class="text-xl font-bold text-gray-800 dark:text-white">Email Server (SMTP)</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Configure mail settings for notifications.</p>
                        </div>
                        <hr class="border-gray-100 dark:border-gray-700">
                        <form action="actions/settings_logic.php" method="POST" class="space-y-5">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="tab" value="smtp">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">SMTP Host</label>
                                    <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host'] ?? ''); ?>" placeholder="smtp.gmail.com" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">SMTP Port</label>
                                    <input type="text" name="smtp_port" value="<?php echo htmlspecialchars($settings['smtp_port'] ?? '587'); ?>" placeholder="587" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">Username</label>
                                    <input type="text" name="smtp_user" value="<?php echo htmlspecialchars($settings['smtp_user'] ?? ''); ?>" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">Password</label>
                                    <input type="password" name="smtp_pass" value="<?php echo htmlspecialchars($settings['smtp_pass'] ?? ''); ?>" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">Encryption</label>
                                    <select name="smtp_secure" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                                        <option value="tls" <?php echo (($settings['smtp_secure'] ?? 'tls') == 'tls') ? 'selected' : ''; ?>>TLS</option>
                                        <option value="ssl" <?php echo (($settings['smtp_secure'] ?? '') == 'ssl') ? 'selected' : ''; ?>>SSL</option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Use TLS for port 587 and SSL for port 465.</p>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                                <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg shadow transition active:scale-95 font-medium">Save Configuration</button>
                            </div>
                        </form>
                    </div>

                    <!-- ── Branding ── -->
                    <div x-show="tab === 'receipt'" x-transition.opacity class="space-y-6">
                        <form action="actions/settings_logic.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="tab" value="receipt">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">80G Enable</label>
                                    <select name="enable_80g" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="1" <?php echo ($settings['enable_80g'] ?? 0) == 1 ? 'selected' : ''; ?>>Yes</option>
                                        <option value="0" <?php echo ($settings['enable_80g'] ?? 0) == 0 ? 'selected' : ''; ?>>No</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Receipt Prefix</label>
                                    <input type="text" name="receipt_prefix" value="<?php echo htmlspecialchars($settings['receipt_prefix'] ?? 'RCP-'); ?>" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">PDF Color Template</label>
                                    <select name="pdf_color_template" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="navy"    <?php echo (($settings['pdf_color_template'] ?? 'navy') === 'navy')    ? 'selected' : ''; ?>>Navy Blue</option>
                                        <option value="emerald" <?php echo (($settings['pdf_color_template'] ?? '') === 'emerald') ? 'selected' : ''; ?>>Emerald Green</option>
                                        <option value="maroon"  <?php echo (($settings['pdf_color_template'] ?? '') === 'maroon')  ? 'selected' : ''; ?>>Maroon Red</option>
                                        <option value="slate"   <?php echo (($settings['pdf_color_template'] ?? '') === 'slate')   ? 'selected' : ''; ?>>Slate Gray</option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Applies to Donation &amp; Membership PDF documents.</p>
                                </div>

                                <!-- Logo -->
                                <div class="border p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 dark:border-gray-700">
                                    <label class="block text-sm font-bold mb-3 dark:text-gray-300">Logo</label>
                                    <div class="flex flex-wrap items-center gap-4">
                                        <?php if (!empty($settings['ngo_logo'])): ?>
                                        <div class="bg-white p-2 rounded border shrink-0">
                                            <img src="../<?php echo $settings['ngo_logo']; ?>" class="h-12 w-auto object-contain">
                                        </div>
                                        <?php endif; ?>
                                        <input type="file" name="ngo_logo" class="flex-1 text-sm min-w-0">
                                    </div>
                                </div>

                                <!-- Favicon -->
                                <div class="border p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 dark:border-gray-700">
                                    <label class="block text-sm font-bold mb-3 dark:text-gray-300">Favicon</label>
                                    <div class="flex flex-wrap items-center gap-4">
                                        <?php if (!empty($settings['site_favicon'])): ?>
                                        <div class="bg-white p-2 rounded border shrink-0">
                                            <img src="../<?php echo $settings['site_favicon']; ?>" class="h-8 w-8 object-contain">
                                        </div>
                                        <?php endif; ?>
                                        <input type="file" name="site_favicon" accept=".ico,.png,.jpg" class="flex-1 text-sm min-w-0">
                                    </div>
                                </div>

                                <!-- Signature -->
                                <div class="border p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 dark:border-gray-700 sm:col-span-2">
                                    <label class="block text-sm font-bold mb-3 dark:text-gray-300">Signature</label>
                                    <div class="flex flex-wrap items-center gap-4">
                                        <?php if (!empty($settings['ngo_signature'])): ?>
                                        <div class="bg-white p-2 rounded border shrink-0">
                                            <img src="../<?php echo $settings['ngo_signature']; ?>" class="h-10 object-contain">
                                        </div>
                                        <?php endif; ?>
                                        <input type="file" name="ngo_signature" class="flex-1 text-sm min-w-0">
                                    </div>
                                </div>
                            </div>
                            <div class="pt-4 flex justify-end">
                                <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg transition-colors font-medium">Save</button>
                            </div>
                        </form>
                    </div>

                    <!-- ── Document Brands ── -->
                    <div x-show="tab === 'brands'" x-transition.opacity class="space-y-6">
                        <div>
                            <h4 class="text-xl font-bold text-gray-800 dark:text-white">Document Brand Profiles</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Create up to three NGO identities for certificates, appointment letters, and ID cards.</p>
                        </div>

                        <form action="actions/settings_logic.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="tab" value="brands">

                            <?php for ($i = 1; $i <= 3; $i++): $prefix = 'doc_brand_' . $i . '_'; ?>
                            <div class="border border-gray-200 dark:border-gray-700 rounded-2xl p-4 sm:p-5 bg-gray-50 dark:bg-gray-900/40 space-y-5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h5 class="font-bold dark:text-white">Brand <?php echo $i; ?></h5>
                                        <p class="text-xs text-gray-500 mt-0.5">Used when generating branded documents.</p>
                                    </div>
                                    <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 shrink-0">Optional</span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                                    <div>
                                        <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">NGO Name</label>
                                        <input type="text" name="<?php echo $prefix; ?>name" value="<?php echo htmlspecialchars($settings[$prefix . 'name'] ?? ''); ?>" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Website</label>
                                        <input type="text" name="<?php echo $prefix; ?>website" value="<?php echo htmlspecialchars($settings[$prefix . 'website'] ?? ''); ?>" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Address</label>
                                        <textarea name="<?php echo $prefix; ?>address" rows="2" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($settings[$prefix . 'address'] ?? ''); ?></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Phone</label>
                                        <input type="text" name="<?php echo $prefix; ?>phone" value="<?php echo htmlspecialchars($settings[$prefix . 'phone'] ?? ''); ?>" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>

                                    <!-- Logo -->
                                    <div class="border p-4 rounded-xl bg-white dark:bg-gray-800 dark:border-gray-600">
                                        <label class="block text-sm font-medium mb-2 dark:text-gray-300">Logo</label>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <?php if (!empty($settings[$prefix . 'logo'])): ?>
                                            <div class="bg-gray-50 p-2 rounded border shrink-0">
                                                <img src="../<?php echo $settings[$prefix . 'logo']; ?>" class="h-12 w-auto object-contain">
                                            </div>
                                            <?php endif; ?>
                                            <input type="file" name="<?php echo $prefix; ?>logo" accept="image/*" class="flex-1 text-sm min-w-0">
                                        </div>
                                    </div>

                                    <!-- Signature -->
                                    <div class="border p-4 rounded-xl bg-white dark:bg-gray-800 dark:border-gray-600 sm:col-span-2">
                                        <label class="block text-sm font-medium mb-2 dark:text-gray-300">Signature</label>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <?php if (!empty($settings[$prefix . 'signature'])): ?>
                                            <div class="bg-gray-50 p-2 rounded border shrink-0">
                                                <img src="../<?php echo $settings[$prefix . 'signature']; ?>" class="h-10 object-contain">
                                            </div>
                                            <?php endif; ?>
                                            <input type="file" name="<?php echo $prefix; ?>signature" accept="image/*" class="flex-1 text-sm min-w-0">
                                        </div>
                                    </div>

                                    <!-- Certificate Background -->
                                    <div class="border p-4 rounded-xl bg-white dark:bg-gray-800 dark:border-gray-600 sm:col-span-2">
                                        <label class="block text-sm font-medium mb-2 dark:text-gray-300">Certificate Background</label>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <?php if (!empty($settings[$prefix . 'certificate_bg'])): ?>
                                            <div class="bg-gray-50 p-2 rounded border shrink-0">
                                                <img src="../<?php echo $settings[$prefix . 'certificate_bg']; ?>" class="h-20 w-auto object-contain">
                                            </div>
                                            <?php endif; ?>
                                            <input type="file" name="<?php echo $prefix; ?>certificate_bg" accept="image/*" class="flex-1 text-sm min-w-0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endfor; ?>

                            <div class="pt-2 flex justify-end">
                                <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg transition-colors font-medium">Save Brand Profiles</button>
                            </div>
                        </form>
                    </div>

                </div><!-- /content panel -->
            </div><!-- /settings card -->
        </main>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
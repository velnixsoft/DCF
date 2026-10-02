<?php require 'includes/header.php'; ?>
<?php 
require '../config/db.php';
require_once '../includes/website_visitor_qr_helper.php';

$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) $settings[$row['setting_key']] = $row['setting_value'];

$banks = $pdo->query("SELECT * FROM bank_accounts")->fetchAll();
$qrs   = $pdo->query("SELECT * FROM payment_qrs")->fetchAll();

$activeTab = $_GET['tab'] ?? 'general';
$websiteHomeUrl = get_website_visitor_qr_url();
$visitorQrPreview = get_website_visitor_qr_image_url($websiteHomeUrl, 400);
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
                            <option value="visitor_qr">🌐 Website Visitor QR</option>
                            <option value="letterhead">📄 Letterhead Template</option>
                            <option value="ai">AI Assistant</option>
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
                            'general'    => 'General',
                            'visitor_qr' => '🌐 Website Visitor QR',
                            'letterhead' => '📄 Letterhead Template',
                            'ai'         => '✨ AI Assistant',
                            'payments'   => 'Payments',
                            'smtp'       => 'Email Server',
                            'receipt'    => 'Branding',
                            'brands'     => 'Document Brands',
                            'footer'     => 'Footer',
                            'security'   => 'Security',
                        ];
                        foreach ($tabs as $key => $label): ?>
                        <button
                            @click="tab = '<?php echo $key; ?>'"
                            :class="tab==='<?php echo $key; ?>' ? 'bg-white dark:bg-gray-700 text-blue-600 border-l-4 border-blue-600 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-white/60 hover:text-black dark:hover:bg-white dark:hover:text-black border-l-4 border-transparent'"
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
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Google Maps API Key</label>
                                    <input type="text" name="google_maps_api_key" value="<?php echo htmlspecialchars($settings['google_maps_api_key'] ?? ''); ?>" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. AIzaSy...">
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

                            <!-- Website Visitor QR Quick Bar -->
                            <div class="mt-6 p-4 rounded-xl bg-gradient-to-r from-teal-50 via-emerald-50 to-white dark:from-gray-700/60 dark:to-gray-800 border border-teal-200/80 dark:border-teal-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-12 h-12 rounded-xl bg-white dark:bg-gray-800 p-1 shadow-xs border border-teal-100 flex-shrink-0">
                                        <img src="<?php echo htmlspecialchars($visitorQrPreview); ?>" alt="Visitor QR" class="w-full h-full object-contain">
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                                            <i class="fa-solid fa-qrcode text-[#0F8B8D]"></i> Official Website Visitor QR Code
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            Scan to visit homepage • Download PNG or printable A4 standee poster.
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                                    <a href="download_visitor_qr.php?format=png" class="px-3 py-2 bg-white dark:bg-gray-700 hover:bg-gray-50 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-lg transition shadow-xs flex items-center gap-1.5">
                                        <i class="fa-solid fa-download text-teal-600"></i> PNG
                                    </a>
                                    <a href="download_visitor_qr.php?format=pdf" class="px-3.5 py-2 bg-[#0F8B8D] hover:bg-teal-700 text-white text-xs font-bold rounded-lg transition shadow-xs flex items-center gap-1.5">
                                        <i class="fa-solid fa-file-pdf"></i> Standee PDF
                                    </a>
                                    <button type="button" @click="tab = 'visitor_qr'" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-lg transition">
                                        More Options →
                                    </button>
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

                        <div>
                            <h4 class="text-xl font-bold text-gray-800 dark:text-white">Payment Gateways & Methods</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Configure multi-gateway switching (Razorpay, PhonePe, PayU Money), webhooks, and offline bank accounts.</p>
                        </div>
                        <hr class="border-gray-100 dark:border-gray-700">

                        <?php 
                        $activeGateway = strtolower(trim($settings['active_payment_gateway'] ?? 'razorpay'));
                        $baseWebhookUrl = rtrim(function_exists('appBaseUrl') ? appBaseUrl() : '', '/');
                        ?>

                        <!-- Master Active Gateway Selector -->
                        <div class="bg-gradient-to-r from-blue-50 via-indigo-50 to-purple-50 dark:from-gray-800 dark:via-gray-800 dark:to-gray-800 p-5 rounded-xl border border-blue-100 dark:border-gray-700 shadow-sm">
                            <form action="actions/settings_logic.php" method="POST" class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="tab" value="payments">
                                <div>
                                    <div class="flex items-center gap-2.5">
                                        <span class="p-2 bg-blue-600 text-white rounded-lg shadow-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                        </span>
                                        <div>
                                            <h5 class="font-bold text-gray-900 dark:text-white text-base">Primary Active Payment Gateway</h5>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">Select which gateway processes online payments by default across Donations, Join Applications, and Events.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <select name="active_payment_gateway" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 dark:text-white font-medium text-sm rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm">
                                        <option value="razorpay" <?php echo ($activeGateway === 'razorpay') ? 'selected' : ''; ?>>🟢 Razorpay (Active)</option>
                                        <option value="phonepe" <?php echo ($activeGateway === 'phonepe') ? 'selected' : ''; ?>>🟣 PhonePe Standard Checkout</option>
                                        <option value="payu" <?php echo ($activeGateway === 'payu') ? 'selected' : ''; ?>>🔵 PayU Money / India</option>
                                        <option value="manual" <?php echo ($activeGateway === 'manual') ? 'selected' : ''; ?>>⚪ Offline / QR Code Only</option>
                                    </select>
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors shadow-sm shrink-0">
                                        Save Active Gateway
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- 3-Column Gateway Configuration Cards -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                            <!-- 1. Razorpay Card -->
                            <div class="bg-gray-50 dark:bg-gray-900/50 p-5 rounded-xl border <?php echo ($activeGateway === 'razorpay') ? 'border-blue-500 ring-2 ring-blue-500/20' : 'dark:border-gray-700 border-gray-200'; ?> flex flex-col justify-between space-y-4">
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                                            <h5 class="font-bold text-gray-900 dark:text-white">Razorpay</h5>
                                        </div>
                                        <?php if ($activeGateway === 'razorpay'): ?>
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 font-semibold">Primary</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Supports Cards, UPI, Netbanking, & Subscriptions.</p>

                                    <form action="actions/settings_logic.php" method="POST" class="space-y-3.5">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="tab" value="payments">
                                        
                                        <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-2.5 rounded-lg border dark:border-gray-700">
                                            <span class="text-xs font-medium dark:text-gray-300">Gateway Status</span>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="hidden" name="razorpay_enabled" value="0">
                                                <input type="checkbox" name="razorpay_enabled" value="1" <?php echo (!isset($settings['razorpay_enabled']) || $settings['razorpay_enabled'] == '1' || $settings['razorpay_enabled'] == 'on') ? 'checked' : ''; ?> class="sr-only peer">
                                                <div class="w-9 h-5 bg-gray-300 peer-focus:outline-none rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                            </label>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold mb-1 dark:text-gray-300">Key ID</label>
                                            <input type="text" name="razorpay_key_id" value="<?php echo htmlspecialchars($settings['razorpay_key_id'] ?? ''); ?>" placeholder="rzp_test_..." class="w-full px-3 py-2 text-sm rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold mb-1 dark:text-gray-300">Key Secret</label>
                                            <input type="password" name="razorpay_key_secret" value="<?php echo htmlspecialchars($settings['razorpay_key_secret'] ?? ''); ?>" class="w-full px-3 py-2 text-sm rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold mb-1 dark:text-gray-300">Donation Key ID (Optional)</label>
                                            <input type="text" name="razorpay_donation_key_id" value="<?php echo htmlspecialchars($settings['razorpay_donation_key_id'] ?? ''); ?>" placeholder="Leave blank to use main Key ID" class="w-full px-3 py-2 text-sm rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold mb-1 dark:text-gray-300">Donation Key Secret (Optional)</label>
                                            <input type="password" name="razorpay_donation_key_secret" value="<?php echo htmlspecialchars($settings['razorpay_donation_key_secret'] ?? ''); ?>" class="w-full px-3 py-2 text-sm rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold mb-1 dark:text-gray-300">Webhook Secret</label>
                                            <input type="password" name="razorpay_webhook_secret" value="<?php echo htmlspecialchars($settings['razorpay_webhook_secret'] ?? ''); ?>" placeholder="Optional webhook secret" class="w-full px-3 py-2 text-sm rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>

                                        <div class="bg-blue-50 dark:bg-blue-900/20 p-2.5 rounded-lg text-xs text-blue-800 dark:text-blue-300 break-all">
                                            <strong>S2S Webhook URL:</strong><br>
                                            <code><?php echo htmlspecialchars($baseWebhookUrl . '/process/razorpay_webhook.php'); ?></code>
                                        </div>

                                        <div class="pt-2">
                                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-sm font-semibold transition-colors">
                                                Save Razorpay Settings
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- 2. PhonePe Card -->
                            <div class="bg-gray-50 dark:bg-gray-900/50 p-5 rounded-xl border <?php echo ($activeGateway === 'phonepe') ? 'border-purple-500 ring-2 ring-purple-500/20' : 'dark:border-gray-700 border-gray-200'; ?> flex flex-col justify-between space-y-4">
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="w-3 h-3 rounded-full bg-purple-600"></span>
                                            <h5 class="font-bold text-gray-900 dark:text-white">PhonePe</h5>
                                        </div>
                                        <?php if ($activeGateway === 'phonepe'): ?>
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 font-semibold">Primary</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">PG v1 Standard Checkout (UPI Intent, QR, & Netbanking).</p>

                                    <form action="actions/settings_logic.php" method="POST" class="space-y-3.5">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="tab" value="payments">

                                        <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-2.5 rounded-lg border dark:border-gray-700">
                                            <span class="text-xs font-medium dark:text-gray-300">Gateway Status</span>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="hidden" name="phonepe_enabled" value="0">
                                                <input type="checkbox" name="phonepe_enabled" value="1" <?php echo (($settings['phonepe_enabled'] ?? '') == '1' || ($settings['phonepe_enabled'] ?? '') == 'on') ? 'checked' : ''; ?> class="sr-only peer">
                                                <div class="w-9 h-5 bg-gray-300 peer-focus:outline-none rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-purple-600"></div>
                                            </label>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold mb-1 dark:text-gray-300">Environment</label>
                                            <select name="phonepe_env" class="w-full px-3 py-2 text-sm rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                                                <option value="sandbox" <?php echo (($settings['phonepe_env'] ?? 'sandbox') === 'sandbox') ? 'selected' : ''; ?>>Sandbox / UAT (Testing)</option>
                                                <option value="production" <?php echo (($settings['phonepe_env'] ?? '') === 'production') ? 'selected' : ''; ?>>Production (Live)</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold mb-1 dark:text-gray-300">Merchant ID</label>
                                            <input type="text" name="phonepe_merchant_id" value="<?php echo htmlspecialchars($settings['phonepe_merchant_id'] ?? ''); ?>" placeholder="e.g., PGTESTPAYUAT" class="w-full px-3 py-2 text-sm rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold mb-1 dark:text-gray-300">Salt Key</label>
                                            <input type="password" name="phonepe_salt_key" value="<?php echo htmlspecialchars($settings['phonepe_salt_key'] ?? ''); ?>" placeholder="Enter PhonePe Salt Key" class="w-full px-3 py-2 text-sm rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold mb-1 dark:text-gray-300">Salt Index</label>
                                            <input type="text" name="phonepe_salt_index" value="<?php echo htmlspecialchars($settings['phonepe_salt_index'] ?? '1'); ?>" placeholder="1" class="w-full px-3 py-2 text-sm rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        </div>

                                        <div class="bg-purple-50 dark:bg-purple-900/20 p-2.5 rounded-lg text-xs text-purple-800 dark:text-purple-300 break-all">
                                            <strong>S2S Webhook URL:</strong><br>
                                            <code><?php echo htmlspecialchars($baseWebhookUrl . '/process/phonepe_webhook.php'); ?></code>
                                        </div>

                                        <div class="pt-2">
                                            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 rounded-lg text-sm font-semibold transition-colors">
                                                Save PhonePe Settings
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- 3. PayU Money / India Card -->
                            <div class="bg-gray-50 dark:bg-gray-900/50 p-5 rounded-xl border <?php echo ($activeGateway === 'payu') ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'dark:border-gray-700 border-gray-200'; ?> flex flex-col justify-between space-y-4">
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <span class="w-3 h-3 rounded-full bg-emerald-600"></span>
                                            <h5 class="font-bold text-gray-900 dark:text-white">PayU Money / India</h5>
                                        </div>
                                        <?php if ($activeGateway === 'payu'): ?>
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 font-semibold">Primary</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Standard checkout with Cards, Netbanking, UPI, & Wallets.</p>

                                    <form action="actions/settings_logic.php" method="POST" class="space-y-3.5">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="tab" value="payments">

                                        <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-2.5 rounded-lg border dark:border-gray-700">
                                            <span class="text-xs font-medium dark:text-gray-300">Gateway Status</span>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="hidden" name="payu_enabled" value="0">
                                                <input type="checkbox" name="payu_enabled" value="1" <?php echo (($settings['payu_enabled'] ?? '') == '1' || ($settings['payu_enabled'] ?? '') == 'on') ? 'checked' : ''; ?> class="sr-only peer">
                                                <div class="w-9 h-5 bg-gray-300 peer-focus:outline-none rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                                            </label>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold mb-1 dark:text-gray-300">Environment</label>
                                            <select name="payu_env" class="w-full px-3 py-2 text-sm rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                                <option value="sandbox" <?php echo (($settings['payu_env'] ?? 'sandbox') === 'sandbox') ? 'selected' : ''; ?>>Test / Sandbox</option>
                                                <option value="production" <?php echo (($settings['payu_env'] ?? '') === 'production') ? 'selected' : ''; ?>>Production (Live)</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold mb-1 dark:text-gray-300">Merchant Key</label>
                                            <input type="text" name="payu_merchant_key" value="<?php echo htmlspecialchars($settings['payu_merchant_key'] ?? ''); ?>" placeholder="e.g., gtKFFx" class="w-full px-3 py-2 text-sm rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold mb-1 dark:text-gray-300">Merchant Salt</label>
                                            <input type="password" name="payu_merchant_salt" value="<?php echo htmlspecialchars($settings['payu_merchant_salt'] ?? ''); ?>" placeholder="Enter PayU Merchant Salt" class="w-full px-3 py-2 text-sm rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                        </div>

                                        <div class="bg-emerald-50 dark:bg-emerald-900/20 p-2.5 rounded-lg text-xs text-emerald-800 dark:text-emerald-300 break-all">
                                            <strong>S2S Webhook URL:</strong><br>
                                            <code><?php echo htmlspecialchars($baseWebhookUrl . '/process/payu_webhook.php'); ?></code>
                                        </div>

                                        <div class="pt-2">
                                            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-2 rounded-lg text-sm font-semibold transition-colors">
                                                Save PayU Settings
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        </div>

                        <!-- Student Dynamic QR UPI Configuration -->
                        <div class="bg-gray-50 dark:bg-gray-900/50 p-4 sm:p-5 rounded-lg border dark:border-gray-700">
                            <h5 class="font-bold mb-1 dark:text-white">Student UPI Configuration (Dynamic QR)</h5>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Set the UPI VPA and Payee Name used to generate dynamic QR codes for student fundraising.</p>
                            <form action="actions/settings_logic.php" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="tab" value="payments">
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">UPI VPA (UPI ID)</label>
                                    <input type="text" name="upi_vpa" value="<?php echo htmlspecialchars($settings['upi_vpa'] ?? ''); ?>" placeholder="e.g., example@upi" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">UPI Payee Name</label>
                                    <input type="text" name="upi_payee_name" value="<?php echo htmlspecialchars($settings['upi_payee_name'] ?? ''); ?>" placeholder="e.g., NGO Name" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div class="sm:col-span-2 flex justify-end">
                                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg transition-colors font-medium">Save UPI Settings</button>
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
                                            <div class="bg-gray-55 dark:bg-gray-700 p-2 rounded border dark:border-gray-600 shrink-0">
                                                <img src="../<?php echo $settings[$prefix . 'logo']; ?>" class="h-12 w-auto object-contain">
                                            </div>
                                            <?php endif; ?>
                                            <input type="file" name="<?php echo $prefix; ?>logo" accept="image/*" class="flex-1 text-sm min-w-0 text-gray-500 dark:text-gray-400">
                                        </div>
                                    </div>

                                    <!-- Signature -->
                                    <div class="border p-4 rounded-xl bg-white dark:bg-gray-800 dark:border-gray-600 sm:col-span-2">
                                        <label class="block text-sm font-medium mb-2 dark:text-gray-300">Signature</label>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <?php if (!empty($settings[$prefix . 'signature'])): ?>
                                            <div class="bg-gray-55 dark:bg-gray-700 p-2 rounded border dark:border-gray-600 shrink-0">
                                                <img src="../<?php echo $settings[$prefix . 'signature']; ?>" class="h-10 object-contain">
                                            </div>
                                            <?php endif; ?>
                                            <input type="file" name="<?php echo $prefix; ?>signature" accept="image/*" class="flex-1 text-sm min-w-0 text-gray-500 dark:text-gray-400">
                                        </div>
                                    </div>

                                    <!-- Certificate Background -->
                                    <div class="border p-4 rounded-xl bg-white dark:bg-gray-800 dark:border-gray-600 sm:col-span-2">
                                        <label class="block text-sm font-medium mb-2 dark:text-gray-300">Certificate Background</label>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <?php if (!empty($settings[$prefix . 'certificate_bg'])): ?>
                                            <div class="bg-gray-55 dark:bg-gray-700 p-2 rounded border dark:border-gray-600 shrink-0">
                                                <img src="../<?php echo $settings[$prefix . 'certificate_bg']; ?>" class="h-20 w-auto object-contain">
                                            </div>
                                            <?php endif; ?>
                                            <input type="file" name="<?php echo $prefix; ?>certificate_bg" accept="image/*" class="flex-1 text-sm min-w-0 text-gray-500 dark:text-gray-400">
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

                    <!-- ── Letterhead Template Settings (Linked to CMS) ── -->
                    <div x-show="tab === 'letterhead'" x-transition.opacity class="space-y-6">
                        <div class="bg-gradient-to-r from-teal-50 to-emerald-50 dark:from-teal-950/30 dark:to-emerald-950/30 p-4 sm:p-5 rounded-2xl border border-teal-200 dark:border-teal-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl bg-[#0F8B8D] text-white flex items-center justify-center text-lg flex-shrink-0 shadow-sm">
                                    <i class="fa-solid fa-file-invoice"></i>
                                </div>
                                <div>
                                    <h4 class="font-black text-gray-900 dark:text-white text-base">Letterhead Template & Branding Configuration</h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                                        Configure header layout, contact details, registration numbers, logo, and digital signature for all official letters and certificates.
                                    </p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-teal-100 dark:bg-teal-900/50 text-teal-800 dark:text-teal-200 text-xs font-bold self-start sm:self-center">
                                <i class="fa-solid fa-link"></i> Linked with CMS Settings
                            </span>
                        </div>

                        <form action="actions/settings_logic.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="tab" value="letterhead">

                            <!-- Section 1: Organization & Identity Details -->
                            <div class="bg-slate-50 dark:bg-gray-700/40 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-gray-700 space-y-4">
                                <h5 class="text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-200 flex items-center gap-2">
                                    <i class="fa-solid fa-building-ngo text-teal-600"></i> Organization Identity on Letterhead
                                </h5>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                            Letterhead Organization Name
                                            <span class="text-gray-400 font-normal text-[11px]">(Defaults to General NGO Name if blank)</span>
                                        </label>
                                        <input type="text" name="letterhead_org_name" 
                                               value="<?php echo htmlspecialchars($settings['letterhead_org_name'] ?? ''); ?>" 
                                               placeholder="<?php echo htmlspecialchars($settings['site_name'] ?? 'Jaysmrutti Foundation'); ?>" 
                                               class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border dark:border-gray-600 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                            Registration / 80G / 12A / Darpan ID
                                        </label>
                                        <input type="text" name="letterhead_reg_no" 
                                               value="<?php echo htmlspecialchars($settings['letterhead_reg_no'] ?? ''); ?>" 
                                               placeholder="<?php echo htmlspecialchars($settings['reg_no'] ?? 'Reg. No. 123455'); ?>" 
                                               class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border dark:border-gray-600 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                    </div>

                                    <div class="sm:col-span-3">
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                            Letterhead Tagline / Mission Motto
                                        </label>
                                        <input type="text" name="letterhead_tagline" 
                                               value="<?php echo htmlspecialchars($settings['letterhead_tagline'] ?? 'Empowering Communities • Transforming Lives • Sustainable Development'); ?>" 
                                               placeholder="e.g. Empowering Communities • Transforming Lives • Sustainable Development" 
                                               class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border dark:border-gray-600 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2: Contact Details on Letterhead Header -->
                            <div class="bg-slate-50 dark:bg-gray-700/40 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-gray-700 space-y-4">
                                <h5 class="text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-200 flex items-center gap-2">
                                    <i class="fa-solid fa-address-book text-teal-600"></i> Official Contact Details on Header
                                </h5>

                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                            Official Phone
                                            <span class="text-gray-400 font-normal text-[11px]">(Falls back to NGO Phone)</span>
                                        </label>
                                        <input type="text" name="letterhead_phone" 
                                               value="<?php echo htmlspecialchars($settings['letterhead_phone'] ?? ''); ?>" 
                                               placeholder="<?php echo htmlspecialchars($settings['ngo_phone'] ?? '+91 7651910331'); ?>" 
                                               class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border dark:border-gray-600 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                            Official Email
                                            <span class="text-gray-400 font-normal text-[11px]">(Falls back to NGO Email)</span>
                                        </label>
                                        <input type="email" name="letterhead_email" 
                                               value="<?php echo htmlspecialchars($settings['letterhead_email'] ?? ''); ?>" 
                                               placeholder="<?php echo htmlspecialchars($settings['ngo_email'] ?? 'info@velnixsoft.com'); ?>" 
                                               class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border dark:border-gray-600 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                            Official Website
                                        </label>
                                        <input type="text" name="letterhead_website" 
                                               value="<?php echo htmlspecialchars($settings['letterhead_website'] ?? ''); ?>" 
                                               placeholder="<?php echo htmlspecialchars($settings['ngo_website'] ?: 'www.jaysmruttifoundation.org'); ?>" 
                                               class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border dark:border-gray-600 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                    </div>

                                    <div class="sm:col-span-3">
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                            Official Postal Address on Letterhead
                                            <span class="text-gray-400 font-normal text-[11px]">(Falls back to General Office Address)</span>
                                        </label>
                                        <textarea name="letterhead_address" rows="2" 
                                                  placeholder="<?php echo htmlspecialchars($settings['ngo_address'] ?? '2nd Floor, Dharma Villa, Wazidpur Tiraha, Jaunpur, Uttar Pradesh - 222002, India'); ?>" 
                                                  class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border dark:border-gray-600 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]"><?php echo htmlspecialchars($settings['letterhead_address'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 3: Visual Branding, Colors, Logo & Signatures -->
                            <div class="bg-slate-50 dark:bg-gray-700/40 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-gray-700 space-y-4">
                                <h5 class="text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-200 flex items-center gap-2">
                                    <i class="fa-solid fa-palette text-teal-600"></i> Visual Branding, Colors & Signatures
                                </h5>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    
                                    <!-- Letterhead Logo Upload -->
                                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-slate-200 dark:border-gray-600">
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                            Letterhead Header Logo
                                        </label>
                                        <div class="flex items-center gap-3">
                                            <?php 
                                            $effectiveLogo = !empty($settings['letterhead_logo']) ? $settings['letterhead_logo'] : ($settings['ngo_logo'] ?? '');
                                            if (!empty($effectiveLogo)): 
                                            ?>
                                                <div class="w-14 h-14 p-1.5 bg-slate-50 dark:bg-gray-700 rounded-xl border border-slate-200 dark:border-gray-600 flex items-center justify-center flex-shrink-0">
                                                    <img src="../<?php echo htmlspecialchars($effectiveLogo); ?>" class="max-h-full max-w-full object-contain">
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-1">
                                                <input type="file" name="letterhead_logo" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-gray-500 dark:text-gray-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-teal-50 file:text-[#0F8B8D]">
                                                <p class="text-[10px] text-gray-400 mt-1">Leave empty to use main NGO Logo automatically.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Authorized Signatory Signature -->
                                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-slate-200 dark:border-gray-600">
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                            Authorized Signatory Digital Signature
                                        </label>
                                        <div class="flex items-center gap-3">
                                            <?php 
                                            $effectiveSig = !empty($settings['letterhead_signature_image']) ? $settings['letterhead_signature_image'] : ($settings['ngo_signature'] ?? '');
                                            if (!empty($effectiveSig)): 
                                            ?>
                                                <div class="w-14 h-14 p-1.5 bg-slate-50 dark:bg-gray-700 rounded-xl border border-slate-200 dark:border-gray-600 flex items-center justify-center flex-shrink-0">
                                                    <img src="../<?php echo htmlspecialchars($effectiveSig); ?>" class="max-h-full max-w-full object-contain">
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-1">
                                                <input type="file" name="letterhead_signature_image" accept="image/jpeg,image/png,image/webp" class="w-full text-xs text-gray-500 dark:text-gray-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-teal-50 file:text-[#0F8B8D]">
                                                <p class="text-[10px] text-gray-400 mt-1">Appears on the official closing stamp / signature block.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Signatory Name -->
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                            Signatory Officer Name
                                        </label>
                                        <input type="text" name="letterhead_signatory_name" 
                                               value="<?php echo htmlspecialchars($settings['letterhead_signatory_name'] ?? 'Authorized Signatory'); ?>" 
                                               placeholder="e.g. Authorized Signatory / Dr. R. K. Sharma" 
                                               class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border dark:border-gray-600 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                    </div>

                                    <!-- Signatory Designation -->
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                            Signatory Designation / Role
                                        </label>
                                        <input type="text" name="letterhead_signatory_designation" 
                                               value="<?php echo htmlspecialchars($settings['letterhead_signatory_designation'] ?? 'President / General Secretary'); ?>" 
                                               placeholder="e.g. President / General Secretary" 
                                               class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border dark:border-gray-600 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                    </div>

                                    <!-- Header Accent Color -->
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                            Letterhead Accent Color
                                        </label>
                                        <div class="flex items-center gap-3">
                                            <input type="color" name="letterhead_header_color" 
                                                   value="<?php echo htmlspecialchars($settings['letterhead_header_color'] ?? '#0F8B8D'); ?>" 
                                                   class="h-10 w-16 p-0.5 rounded-lg border border-slate-200 dark:border-gray-600 cursor-pointer bg-white">
                                            <input type="text" value="<?php echo htmlspecialchars($settings['letterhead_header_color'] ?? '#0F8B8D'); ?>" 
                                                   readonly class="w-32 px-3 py-2 text-xs font-mono rounded-lg border dark:border-gray-600 dark:bg-gray-800 dark:text-white text-center">
                                        </div>
                                    </div>

                                    <!-- Background Watermark Toggle -->
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                            Background Logo Watermark
                                        </label>
                                        <select name="letterhead_watermark_enabled" class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border dark:border-gray-600 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                            <option value="1" <?php echo ($settings['letterhead_watermark_enabled'] ?? '1') === '1' ? 'selected' : ''; ?>>Enabled (Faint centered logo watermark)</option>
                                            <option value="0" <?php echo ($settings['letterhead_watermark_enabled'] ?? '1') === '0' ? 'selected' : ''; ?>>Disabled (Clean white background)</option>
                                        </select>
                                    </div>

                                </div>
                            </div>

                            <!-- Section 4: Official Footer Text & Statutory Notice -->
                            <div class="bg-slate-50 dark:bg-gray-700/40 p-4 sm:p-5 rounded-2xl border border-slate-200 dark:border-gray-700 space-y-4">
                                <h5 class="text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-200 flex items-center gap-2">
                                    <i class="fa-solid fa-scroll text-teal-600"></i> Letterhead Bottom Footer Notice
                                </h5>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        Official Footer Statutory Text & Exemptions Notice
                                    </label>
                                    <input type="text" name="letterhead_footer_text" 
                                           value="<?php echo htmlspecialchars($settings['letterhead_footer_text'] ?? 'Registered under Societies Registration Act | Donations Tax Exempted u/s 80G & 12A of Income Tax Act'); ?>" 
                                           placeholder="e.g. Registered under Societies Registration Act | Donations Tax Exempted u/s 80G & 12A" 
                                           class="w-full px-3.5 py-2.5 text-xs font-medium rounded-xl border dark:border-gray-600 dark:bg-gray-800 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                    <p class="text-[11px] text-gray-400 mt-1">Printed neatly at the bottom edge of every official letter PDF.</p>
                                </div>
                            </div>

                            <!-- Interactive Live Letterhead Preview Card -->
                            <div class="bg-slate-100 dark:bg-gray-900 p-5 rounded-3xl border border-slate-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                                        <i class="fa-solid fa-eye text-[#0F8B8D]"></i> Real-time Letterhead Preview
                                    </span>
                                    <span class="text-[10px] uppercase font-bold text-gray-400">Standard A4 Format</span>
                                </div>

                                <div class="bg-white text-gray-800 p-6 rounded-2xl shadow-md border border-slate-200 max-w-2xl mx-auto space-y-5">
                                    
                                    <!-- Top Accent Bar -->
                                    <div class="h-2 rounded-full" style="background-color: <?php echo htmlspecialchars($settings['letterhead_header_color'] ?? '#0F8B8D'); ?>;"></div>

                                    <!-- Header -->
                                    <div class="flex items-center justify-between pb-3 border-b-2" style="border-color: <?php echo htmlspecialchars($settings['letterhead_header_color'] ?? '#0F8B8D'); ?>;">
                                        <div class="flex items-center gap-3">
                                            <?php if (!empty($effectiveLogo)): ?>
                                                <img src="../<?php echo htmlspecialchars($effectiveLogo); ?>" class="w-14 h-14 object-contain">
                                            <?php else: ?>
                                                <div class="w-12 h-12 bg-teal-50 text-[#0F8B8D] rounded-xl flex items-center justify-center font-black text-xl">
                                                    NGO
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h3 class="text-lg font-black text-gray-900 leading-tight">
                                                    <?php echo htmlspecialchars(!empty($settings['letterhead_org_name']) ? $settings['letterhead_org_name'] : ($settings['site_name'] ?? 'Jaysmrutti Foundation')); ?>
                                                </h3>
                                                <p class="text-[11px] font-bold text-teal-700">
                                                    <?php echo htmlspecialchars($settings['letterhead_tagline'] ?? 'Empowering Communities • Transforming Lives'); ?>
                                                </p>
                                                <p class="text-[10px] text-gray-500">
                                                    <?php echo htmlspecialchars(!empty($settings['letterhead_reg_no']) ? $settings['letterhead_reg_no'] : ($settings['reg_no'] ?? 'Reg. No. 123455')); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right text-[10px] text-gray-600 leading-tight">
                                            <p class="font-bold"><i class="fa-solid fa-phone text-[9px]"></i> <?php echo htmlspecialchars(!empty($settings['letterhead_phone']) ? $settings['letterhead_phone'] : ($settings['ngo_phone'] ?? '+91 7651910331')); ?></p>
                                            <p class="font-bold"><i class="fa-solid fa-envelope text-[9px]"></i> <?php echo htmlspecialchars(!empty($settings['letterhead_email']) ? $settings['letterhead_email'] : ($settings['ngo_email'] ?? 'info@velnixsoft.com')); ?></p>
                                            <p class="max-w-[180px] text-[9px] text-gray-500 mt-0.5 truncate"><?php echo htmlspecialchars(!empty($settings['letterhead_address']) ? $settings['letterhead_address'] : ($settings['ngo_address'] ?? 'Jaunpur, Uttar Pradesh')); ?></p>
                                        </div>
                                    </div>

                                    <!-- Sample Letter Metadata -->
                                    <div class="flex justify-between text-xs font-bold text-gray-600 pt-1">
                                        <span>Ref: <strong>JMF/LTR/<?php echo date('Y'); ?>/001</strong></span>
                                        <span>Date: <strong><?php echo date('d M, Y'); ?></strong></span>
                                    </div>

                                    <!-- Sample Body Content -->
                                    <div class="text-xs text-gray-700 space-y-2 py-2 leading-relaxed">
                                        <p class="font-bold text-gray-900">To,<br>Mr. Rahul Verma<br><span class="font-normal text-gray-500">Senior Coordinator, Rural Outreach</span></p>
                                        <p class="font-bold text-gray-800 pt-1">Subject: Official Appointment & Appreciation Letter</p>
                                        <p class="text-[11px] text-gray-600">This is an official communication generated from the NGO Management Portal on standard letterhead format.</p>
                                    </div>

                                    <!-- Signatory Block -->
                                    <div class="flex justify-end pt-3">
                                        <div class="text-center">
                                            <?php if (!empty($effectiveSig)): ?>
                                                <img src="../<?php echo htmlspecialchars($effectiveSig); ?>" class="h-10 mx-auto object-contain">
                                            <?php else: ?>
                                                <div class="h-8 flex items-center justify-center text-[10px] text-gray-400 italic">[Signature]</div>
                                            <?php endif; ?>
                                            <p class="text-xs font-bold text-gray-900 mt-1"><?php echo htmlspecialchars($settings['letterhead_signatory_name'] ?? 'Authorized Signatory'); ?></p>
                                            <p class="text-[10px] text-gray-500"><?php echo htmlspecialchars($settings['letterhead_signatory_designation'] ?? 'President / General Secretary'); ?></p>
                                        </div>
                                    </div>

                                    <!-- Footer -->
                                    <div class="pt-3 border-t text-center text-[10px] text-gray-500 font-medium">
                                        <?php echo htmlspecialchars($settings['letterhead_footer_text'] ?? 'Registered under Societies Registration Act | Donations Tax Exempted u/s 80G & 12A of Income Tax Act'); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-2 flex justify-end">
                                <button type="submit" class="bg-[#0F8B8D] hover:bg-[#0c7274] text-white px-7 py-3 rounded-xl transition-all font-bold shadow-md text-xs flex items-center gap-2">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    <span>Save Letterhead Template Settings</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- ── AI Assistant ── -->
                    <div x-show="tab === 'ai'" x-transition.opacity class="space-y-6">
                        <div class="bg-gradient-to-r from-blue-50 to-amber-50 dark:from-sky-950/30 dark:to-amber-950/30 p-4 rounded-xl border border-blue-100 dark:border-gray-700 flex items-start gap-3">
                            <div class="p-2 bg-[#1070B0] text-white rounded-lg text-lg">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white text-sm">AI Informer & Guider Configuration</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                                    Configure the AI Guide for website visitors (how to donate, become a member, or volunteer) and the Admin AI Copilot for operational queries.
                                </p>
                            </div>
                        </div>

                        <form action="actions/settings_logic.php" method="POST" class="space-y-5">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="tab" value="ai">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Public User Panel AI Guide</label>
                                    <select name="enable_user_ai" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="1" <?php echo ($settings['enable_user_ai'] ?? '1') === '1' ? 'selected' : ''; ?>>Enabled (Show Floating AI Widget on Public Pages)</option>
                                        <option value="0" <?php echo ($settings['enable_user_ai'] ?? '1') === '0' ? 'selected' : ''; ?>>Disabled</option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Allows visitors to ask how to donate, join as a member, or register as a volunteer.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Admin Panel AI Copilot</label>
                                    <select name="enable_admin_ai" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="1" <?php echo ($settings['enable_admin_ai'] ?? '1') === '1' ? 'selected' : ''; ?>>Enabled (Active on Admin Navbar & Copilot Page)</option>
                                        <option value="0" <?php echo ($settings['enable_admin_ai'] ?? '1') === '0' ? 'selected' : ''; ?>>Disabled</option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Enables instant administrative queries for donations, members, and volunteers.</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">AI Assistant Display Name</label>
                                    <input type="text" name="ai_bot_name" value="<?php echo htmlspecialchars($settings['ai_bot_name'] ?? 'NGO Smart Guide'); ?>" placeholder="e.g. NGO Smart Guide or JayBot" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Google Gemini API Key (Optional)</label>
                                    <input type="password" name="gemini_api_key" value="<?php echo htmlspecialchars($settings['gemini_api_key'] ?? ''); ?>" placeholder="AIzaSy..." class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <p class="text-xs text-gray-500 mt-1">Optional. The built-in intelligence engine works out of the box without any external API key.</p>
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Custom Welcome Greeting (Public Widget)</label>
                                    <textarea name="ai_welcome_message" rows="2" class="w-full px-4 py-2.5 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Namaste! Welcome to our NGO. How can I assist you today?"><?php echo htmlspecialchars($settings['ai_welcome_message'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="pt-2 flex justify-end">
                                <button class="bg-[#1070B0] hover:bg-[#0d598c] text-white px-6 py-2.5 rounded-lg transition-colors font-medium shadow-md flex items-center gap-2">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    <span>Save AI Settings</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- ── Website Visitor QR Studio ── -->
                    <div x-show="tab === 'visitor_qr'" x-transition.opacity class="space-y-6">
                        
                        <!-- Header Banner -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-2xl bg-gradient-to-r from-teal-50 via-emerald-50 to-white dark:from-gray-700/60 dark:to-gray-800 border border-teal-200/80 dark:border-teal-800/50">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-2xl bg-[#0F8B8D] text-white flex items-center justify-center text-2xl shadow-md shadow-teal-700/20">
                                    <i class="fa-solid fa-qrcode"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-gray-900 dark:text-white">Website Visitor QR & Counter Standee Studio</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        Scan with any phone camera to directly open the official NGO home page. Download high-res PNG or printable A4 standee poster.
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <a href="<?php echo htmlspecialchars($websiteHomeUrl); ?>" target="_blank" class="px-3.5 py-2 bg-white dark:bg-gray-700 hover:bg-gray-50 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5">
                                    <i class="fa-solid fa-arrow-up-right-from-square text-teal-600"></i> Open Website
                                </a>
                            </div>
                        </div>

                        <!-- 2-Column Grid -->
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                            
                            <!-- Left 6 Cols: Live QR Code & Download Actions -->
                            <div class="lg:col-span-6 bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm text-center">
                                
                                <span class="text-xs font-bold uppercase tracking-wider text-teal-700 dark:text-teal-400 block mb-3">
                                    Live Scannable QR Preview
                                </span>

                                <!-- QR Image Frame -->
                                <div class="inline-block p-4 rounded-3xl bg-gradient-to-b from-gray-50 to-white dark:from-gray-700 dark:to-gray-800 border-2 border-teal-500/30 shadow-md mb-4 relative group">
                                    <img src="<?php echo htmlspecialchars($visitorQrPreview); ?>" alt="Website Visitor QR" class="w-56 h-56 mx-auto object-contain rounded-xl">
                                    <div class="absolute inset-0 bg-[#0F8B8D]/10 opacity-0 group-hover:opacity-100 rounded-3xl transition-opacity flex items-center justify-center pointer-events-none">
                                        <span class="bg-black/75 text-white px-3 py-1 rounded-full text-xs font-bold"><i class="fa-solid fa-camera"></i> Test Scan</span>
                                    </div>
                                </div>

                                <!-- Target URL Box -->
                                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600 mb-5 text-left flex items-center justify-between gap-2 text-xs">
                                    <div class="truncate">
                                        <span class="text-[10px] uppercase font-bold text-gray-400 block">Target Home URL:</span>
                                        <span class="font-mono font-bold text-[#0F8B8D] dark:text-teal-400 truncate block"><?php echo htmlspecialchars($websiteHomeUrl); ?></span>
                                    </div>
                                    <button type="button" 
                                            onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($websiteHomeUrl); ?>'); alert('Website URL copied to clipboard!');" 
                                            class="p-2 rounded-lg bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 text-xs font-bold transition flex-shrink-0 shadow-xs"
                                            title="Copy URL">
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>

                                <!-- Download Action Buttons (PNG / PDF) -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                    
                                    <!-- PNG Button -->
                                    <a href="download_visitor_qr.php?format=png" 
                                       class="py-3 px-4 rounded-xl bg-gray-900 hover:bg-black text-white text-xs font-bold transition flex items-center justify-center gap-2 shadow-md">
                                        <i class="fa-solid fa-file-image text-[#F4A640] text-sm"></i>
                                        <span>Download PNG Image</span>
                                    </a>

                                    <!-- PDF Standee Button -->
                                    <a href="download_visitor_qr.php?format=pdf" 
                                       class="py-3 px-4 rounded-xl bg-[#0F8B8D] hover:bg-teal-700 text-white text-xs font-bold transition flex items-center justify-center gap-2 shadow-md">
                                        <i class="fa-solid fa-file-pdf text-amber-300 text-sm"></i>
                                        <span>Download A4 Standee (PDF)</span>
                                    </a>

                                </div>

                                <!-- Secondary Actions -->
                                <div class="flex items-center justify-center gap-4 text-xs font-semibold text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-700">
                                    <a href="download_visitor_qr.php?format=preview" target="_blank" class="hover:text-[#0F8B8D] flex items-center gap-1.5 transition">
                                        <i class="fa-solid fa-eye text-teal-600"></i>
                                        <span>Preview Printable Standee</span>
                                    </a>
                                    <span>•</span>
                                    <button type="button" onclick="const w = window.open('download_visitor_qr.php?format=preview', '_blank'); w.focus();" class="hover:text-[#0F8B8D] flex items-center gap-1.5 transition">
                                        <i class="fa-solid fa-print text-teal-600"></i>
                                        <span>Direct Print Standee</span>
                                    </button>
                                </div>

                            </div>

                            <!-- Right 6 Cols: Specifications & Deployment Guide -->
                            <div class="lg:col-span-6 space-y-4">
                                
                                <!-- Standee Features Card -->
                                <div class="bg-gradient-to-br from-teal-900 to-[#0F8B8D] rounded-2xl p-5 text-white shadow-md">
                                    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-amber-300 mb-2">
                                        <i class="fa-solid fa-award"></i> Official Printable Standee Features
                                    </div>
                                    <h4 class="text-base font-bold">What is included in the A4 Standee PDF?</h4>
                                    <ul class="mt-3 space-y-2 text-xs text-teal-50 leading-relaxed">
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-circle-check text-amber-300 mt-0.5"></i>
                                            <span><strong>Complete NGO Branding:</strong> Official name, registration number, tagline, and NGO logo.</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-circle-check text-amber-300 mt-0.5"></i>
                                            <span><strong>Prominent Scan Prompt:</strong> "Scan to Visit Official Website" with clear bilingual guidelines.</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-circle-check text-amber-300 mt-0.5"></i>
                                            <span><strong>Core Service Highlights:</strong> Swasthya Card, Social Projects, Volunteer Induction, and 80G Donations.</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-circle-check text-amber-300 mt-0.5"></i>
                                            <span><strong>Office Contact Strip:</strong> Helpline phone, official email, and physical office address.</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Recommended Deployment Locations -->
                                <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-200 dark:border-gray-700 shadow-xs">
                                    <h5 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3">Recommended Standee Placements</h5>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                        
                                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                            <span class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5 mb-1">
                                                <i class="fa-solid fa-building text-teal-600"></i> Reception Desks
                                            </span>
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400">Place in an acrylic stand on head office & branch reception counters.</p>
                                        </div>

                                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                            <span class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5 mb-1">
                                                <i class="fa-solid fa-heart-pulse text-rose-500"></i> Health & Swasthya Camps
                                            </span>
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400">Display at registration desks for beneficiary card applications.</p>
                                        </div>

                                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                            <span class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5 mb-1">
                                                <i class="fa-solid fa-users text-amber-500"></i> Volunteer Drives
                                            </span>
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400">Direct youth and students to volunteer registration & tasks.</p>
                                        </div>

                                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                            <span class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5 mb-1">
                                                <i class="fa-solid fa-hand-holding-dollar text-emerald-600"></i> Fundraiser Events
                                            </span>
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400">Print on banner flex and donation kiosks for instant 80G contributions.</p>
                                        </div>

                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>

                </div><!-- /content panel -->
            </div><!-- /settings card -->
        </main>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
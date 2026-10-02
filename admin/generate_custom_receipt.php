<?php
// ============================================================
// admin/generate_custom_receipt.php
// Custom Receipt Generation Form with Live Preview & Instant PDF + QR
// ============================================================

require 'includes/header.php';
require '../config/db.php';
require_once '../includes/member_module.php';
require_once '../includes/custom_receipt_helper.php';
require_once '../includes/functions.php';

if (!canAccessModule($pdo, 'coordinator', 'page.donations') && !canAccessModule($pdo, 'coordinator', 'page.expenses')) {
    setFlash('error', 'Access denied. You do not have permission to generate receipts.');
    header('Location: dashboard.php');
    exit;
}

$csrfToken = generateCsrfToken();
$nextReceiptNo = cr_generate_receipt_no($pdo, 'REC');
$siteName = $settings['site_name'] ?? 'NGO System';
$ngoLogo = $settings['ngo_logo'] ?? '';

// Fetch recent 5 custom receipts
$recentStmt = $pdo->query("SELECT * FROM `custom_receipts` ORDER BY `id` DESC LIMIT 5");
$recentReceipts = $recentStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
?>

<div class="flex h-screen overflow-hidden bg-slate-50 dark:bg-gray-900" x-data="customReceiptGeneratorApp()">
    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8 space-y-6">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-10 h-10 rounded-2xl bg-[#0F8B8D]/10 text-[#0F8B8D] flex items-center justify-center text-lg shadow-sm">
                            <i class="fa-solid fa-receipt"></i>
                        </span>
                        <div>
                            <h3 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">Generate Custom Receipt</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Issue official payment & donation receipts with instant PDF and QR code verification.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="custom_receipts.php" class="px-4 py-2 rounded-xl bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 hover:bg-slate-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-list"></i> Receipt History
                    </a>
                </div>
            </div>

            <!-- Main 2-Column Grid: Form (Left) & Real-time Live Preview (Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- ════════════════════════════════════════════════════════ -->
                <!-- LEFT: RECEIPT INPUT FORM                                 -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div class="lg:col-span-7 bg-white dark:bg-gray-800 rounded-3xl p-6 border border-slate-200 dark:border-gray-700 shadow-sm space-y-6">
                    
                    <form @submit.prevent="submitForm()" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <!-- 1. Payer Details Section -->
                        <div>
                            <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2 mb-3 pb-2 border-b border-slate-100 dark:border-gray-700">
                                <span class="w-6 h-6 rounded-lg bg-teal-50 dark:bg-teal-900/40 text-[#0F8B8D] flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-user"></i>
                                </span>
                                <span>1. Payer / Donor Particulars</span>
                            </h4>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Payer Full Name -->
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        Received From (Full Name) <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" x-model="form.payer_name" required placeholder="e.g. Ramesh Chandra Gupta / ABC Enterprises" 
                                           class="w-full text-xs font-semibold p-3 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                </div>

                                <!-- Contact / WhatsApp -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        <i class="fa-brands fa-whatsapp text-emerald-600 mr-1"></i> Mobile / WhatsApp No.
                                    </label>
                                    <input type="text" x-model="form.payer_phone" placeholder="e.g. +91 9876543210" 
                                           class="w-full text-xs p-3 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                </div>

                                <!-- Email Address -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        <i class="fa-solid fa-envelope text-indigo-500 mr-1"></i> Email Address
                                    </label>
                                    <input type="email" x-model="form.payer_email" placeholder="e.g. ramesh@example.com" 
                                           class="w-full text-xs p-3 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                </div>

                                <!-- PAN Number -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        PAN / Tax ID (For 80G Exemption)
                                    </label>
                                    <input type="text" x-model="form.payer_pan" placeholder="e.g. ABCDE1234F" maxlength="15" 
                                           class="w-full text-xs font-mono uppercase p-3 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                </div>

                                <!-- Address -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        Address / City
                                    </label>
                                    <input type="text" x-model="form.payer_address" placeholder="e.g. Sector 12, Lucknow, UP" 
                                           class="w-full text-xs p-3 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                </div>
                            </div>
                        </div>

                        <!-- 2. Payment & Purpose Section -->
                        <div>
                            <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2 mb-3 pb-2 border-b border-slate-100 dark:border-gray-700">
                                <span class="w-6 h-6 rounded-lg bg-teal-50 dark:bg-teal-900/40 text-[#0F8B8D] flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-indian-rupee-sign"></i>
                                </span>
                                <span>2. Contribution & Payment Particulars</span>
                            </h4>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Amount (INR) -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        Amount Received (INR) <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-sm">₹</span>
                                        <input type="number" step="0.01" min="1" x-model.number="form.amount" required placeholder="0.00" 
                                               @input="updateAmountInWords()"
                                               class="w-full text-sm font-black pl-8 p-3 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 text-teal-700 dark:text-teal-300 outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                    </div>
                                    <div class="text-[10px] font-semibold text-emerald-700 dark:text-emerald-300 mt-1 truncate" x-show="form.amount > 0" x-text="'In Words: ' + amountInWords"></div>
                                </div>

                                <!-- Receipt Date -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        Receipt Date <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="date" x-model="form.date" required 
                                           class="w-full text-xs font-semibold p-3 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                </div>

                                <!-- Payment Mode -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        Payment Mode <span class="text-rose-500">*</span>
                                    </label>
                                    <select x-model="form.payment_mode" required 
                                            class="w-full text-xs font-semibold p-3 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                        <option value="Cash">💵 Cash</option>
                                        <option value="UPI / QR Code">📱 UPI / QR Code</option>
                                        <option value="Bank Transfer (NEFT/RTGS/IMPS)">🏦 Bank Transfer (NEFT/RTGS)</option>
                                        <option value="Cheque">📜 Cheque</option>
                                        <option value="Demand Draft">🏛️ Demand Draft</option>
                                        <option value="Online / Razorpay">💳 Online Gateway</option>
                                        <option value="Other">✨ Other Mode</option>
                                    </select>
                                </div>

                                <!-- Transaction Ref / Cheque No -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        Transaction Ref. / Cheque No.
                                    </label>
                                    <input type="text" x-model="form.transaction_ref" placeholder="e.g. UPI/12345678 / CHQ-98210" 
                                           class="w-full text-xs p-3 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                </div>

                                <!-- Purpose / Head -->
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        Contribution Purpose / Project Head <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="space-y-2">
                                        <select x-model="selectedPurpose" @change="onPurposeChange()" 
                                                class="w-full text-xs font-semibold p-3 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                            <option value="General Donation / NGO Activities">General Donation / NGO Activities</option>
                                            <option value="Education Aid & Child Welfare">Education Aid & Child Welfare</option>
                                            <option value="Health & Medical Support Camp">Health & Medical Support Camp</option>
                                            <option value="Food, Ration & Nutrition Distribution">Food, Ration & Nutrition Distribution</option>
                                            <option value="Tree Plantation & Environmental Care">Tree Plantation & Environmental Care</option>
                                            <option value="Blanket & Clothes Distribution">Blanket & Clothes Distribution</option>
                                            <option value="Corpus Fund / Infrastructure">Corpus Fund / Infrastructure</option>
                                            <option value="custom">✏️ Other Custom Purpose...</option>
                                        </select>
                                        
                                        <input type="text" x-show="selectedPurpose === 'custom'" x-model="form.purpose" placeholder="Enter custom purpose description..." 
                                               class="w-full text-xs p-2.5 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                                    </div>
                                </div>

                                <!-- Remarks / Notes -->
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                        Remarks / Internal Notes (Optional)
                                    </label>
                                    <textarea x-model="form.remarks" rows="2" placeholder="e.g. Received during Sunday community relief drive" 
                                              class="w-full text-xs p-3 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons Bar -->
                        <div class="pt-4 border-t border-slate-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
                            <button type="button" @click="resetForm()" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-xs font-bold hover:bg-slate-100 dark:hover:bg-gray-700 transition">
                                <i class="fa-solid fa-rotate-left mr-1"></i> Reset
                            </button>

                            <div class="flex flex-wrap items-center gap-2">
                                <button type="submit" :disabled="loading" 
                                        class="px-6 py-3 rounded-2xl bg-[#0F8B8D] hover:bg-[#0c7274] text-white text-xs font-black shadow-lg shadow-teal-700/20 transition transform active:scale-95 flex items-center gap-2 disabled:opacity-50">
                                    <i class="fa-solid fa-file-pdf" x-show="!loading"></i>
                                    <i class="fa-solid fa-spinner fa-spin" x-show="loading"></i>
                                    <span x-text="loading ? 'Generating PDF Receipt...' : 'Generate PDF Receipt with QR'"></span>
                                </button>
                            </div>
                        </div>

                    </form>

                </div>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- RIGHT: INTERACTIVE LIVE RECEIPT PREVIEW                  -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div class="lg:col-span-5 space-y-4">
                    
                    <div class="flex items-center justify-between px-1">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-eye text-[#0F8B8D]"></i> Realtime Receipt Preview
                        </span>
                        <span class="text-[10px] font-bold text-teal-600 dark:text-teal-400 bg-teal-50 dark:bg-teal-900/30 px-2 py-0.5 rounded-full">
                            PDF + QR Ready
                        </span>
                    </div>

                    <!-- Digital PDF Replica Card -->
                    <div class="bg-white rounded-2xl border-2 border-slate-200 shadow-xl overflow-hidden text-gray-800 font-sans relative">
                        
                        <!-- Top Header Strip -->
                        <div class="bg-[#0F8B8D] text-white p-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <?php if (!empty($ngoLogo)): ?>
                                    <img src="../<?php echo htmlspecialchars(ltrim($ngoLogo, '/')); ?>" alt="Logo" class="w-10 h-10 rounded-lg bg-white p-1 object-contain">
                                <?php else: ?>
                                    <div class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center font-black text-sm">NGO</div>
                                <?php endif; ?>
                                <div>
                                    <h4 class="font-black text-sm uppercase tracking-wide leading-tight"><?php echo htmlspecialchars($siteName); ?></h4>
                                    <p class="text-[10px] text-teal-100 opacity-90">Official Payment & Donation Receipt</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="bg-white/20 text-white font-bold text-[9px] uppercase px-2 py-0.5 rounded">OFFICIAL</span>
                            </div>
                        </div>

                        <!-- Receipt Body Container -->
                        <div class="p-5 space-y-4 text-xs">
                            
                            <!-- Receipt No & Date Row -->
                            <div class="p-2.5 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between text-[11px]">
                                <div>
                                    <span class="text-gray-400 font-bold block text-[9px] uppercase">Receipt Ref:</span>
                                    <span class="font-mono font-black text-[#0F8B8D]" x-text="receiptNo"></span>
                                </div>
                                <div class="text-right">
                                    <span class="text-gray-400 font-bold block text-[9px] uppercase">Date:</span>
                                    <span class="font-bold text-gray-800" x-text="formatDate(form.date)"></span>
                                </div>
                            </div>

                            <!-- Payer Particulars Preview -->
                            <div class="space-y-1.5 pb-3 border-b border-dashed border-slate-200">
                                <div class="text-gray-400 font-bold text-[9px] uppercase">Received With Thanks From:</div>
                                <div class="text-sm font-black text-gray-900" x-text="form.payer_name || 'Donor / Payer Name'"></div>
                                
                                <div class="flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-gray-500 pt-0.5">
                                    <span x-show="form.payer_phone" class="flex items-center gap-1">
                                        <i class="fa-solid fa-phone text-[9px] text-gray-400"></i>
                                        <span x-text="form.payer_phone"></span>
                                    </span>
                                    <span x-show="form.payer_email" class="flex items-center gap-1">
                                        <i class="fa-solid fa-envelope text-[9px] text-gray-400"></i>
                                        <span x-text="form.payer_email"></span>
                                    </span>
                                    <span x-show="form.payer_pan" class="flex items-center gap-1 font-mono font-bold text-gray-700">
                                        <span>PAN:</span>
                                        <span x-text="form.payer_pan.toUpperCase()"></span>
                                    </span>
                                </div>
                            </div>

                            <!-- Amount Box Highlight -->
                            <div class="p-3.5 bg-teal-50/60 rounded-xl border border-teal-100">
                                <div class="flex items-baseline justify-between">
                                    <span class="text-[10px] font-bold text-teal-800 uppercase">Amount Received:</span>
                                    <span class="text-lg font-black text-[#0F8B8D]" x-text="'₹ ' + Number(form.amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                </div>
                                <div class="text-[10px] font-semibold text-teal-900 italic mt-1 pt-1 border-t border-teal-200/50" x-text="'Rupees: ' + amountInWords"></div>
                            </div>

                            <!-- Mode & Purpose Table -->
                            <div class="grid grid-cols-2 gap-3 text-[11px]">
                                <div>
                                    <span class="text-gray-400 font-bold block text-[9px] uppercase">Payment Mode:</span>
                                    <span class="font-bold text-gray-800" x-text="form.payment_mode"></span>
                                </div>
                                <div>
                                    <span class="text-gray-400 font-bold block text-[9px] uppercase">Transaction Ref:</span>
                                    <span class="font-mono text-gray-800" x-text="form.transaction_ref || 'N/A'"></span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-gray-400 font-bold block text-[9px] uppercase">Purpose:</span>
                                    <span class="font-bold text-gray-800" x-text="form.purpose"></span>
                                </div>
                            </div>

                            <!-- Bottom QR & Signatory Section -->
                            <div class="pt-3 border-t border-slate-200 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-14 h-14 p-1 bg-white border border-slate-200 rounded-lg flex items-center justify-center">
                                        <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=' + encodeURIComponent('Receipt:' + receiptNo + ';Amount:' + form.amount + ';Name:' + (form.payer_name || 'Donor'))" 
                                             alt="QR Code" class="w-12 h-12 object-contain">
                                    </div>
                                    <div>
                                        <span class="text-[9px] font-bold text-gray-400 block uppercase">QR Verification</span>
                                        <span class="text-[9px] text-emerald-600 font-bold flex items-center gap-0.5">
                                            <i class="fa-solid fa-circle-check text-[8px]"></i> Authenticated
                                        </span>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <div class="w-24 h-6 border-b border-gray-400 ml-auto flex items-end justify-center">
                                        <?php if (!empty($settings['ngo_signature'])): ?>
                                            <img src="../<?php echo htmlspecialchars(ltrim($settings['ngo_signature'], '/')); ?>" alt="Sign" class="h-6 object-contain">
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-[9px] font-bold text-gray-600 block mt-0.5">Authorized Signatory</span>
                                </div>
                            </div>

                        </div>

                        <!-- Bottom Strip -->
                        <div class="bg-slate-100 p-2 text-center text-[9px] text-gray-400 border-t border-slate-200">
                            Computer generated official receipt • Tax exemption applicable
                        </div>

                    </div>

                    <!-- Generation Success Action Card (Visible when receipt is generated) -->
                    <div x-show="generatedReceipt" x-cloak class="p-5 bg-emerald-50 dark:bg-emerald-950/40 rounded-3xl border border-emerald-200 dark:border-emerald-800 space-y-3 shadow-lg">
                        <div class="flex items-center gap-2.5 text-emerald-800 dark:text-emerald-300">
                            <i class="fa-solid fa-circle-check text-xl text-emerald-600"></i>
                            <div>
                                <h4 class="font-black text-sm">Receipt Successfully Generated!</h4>
                                <p class="text-[11px] text-emerald-700 dark:text-emerald-400" x-text="'Receipt Ref: #' + generatedReceipt?.receipt_no"></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1">
                            <!-- Print / View PDF -->
                            <button type="button" @click="printReceipt(generatedReceipt?.pdf_url)" 
                                    class="px-3 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-sm transition">
                                <i class="fa-solid fa-print"></i> Print
                            </button>

                            <!-- Download PDF -->
                            <a :href="generatedReceipt?.direct_download_url" 
                               class="px-3 py-2.5 rounded-xl bg-white dark:bg-gray-800 border border-emerald-300 dark:border-emerald-700 hover:bg-emerald-50 text-emerald-800 dark:text-emerald-200 font-bold text-xs flex items-center justify-center gap-1.5 transition">
                                <i class="fa-solid fa-download"></i> Download
                            </a>

                            <!-- Email PDF -->
                            <button type="button" @click="openEmailModal(generatedReceipt)" 
                                    class="px-3 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-sm transition">
                                <i class="fa-solid fa-envelope"></i> Email
                            </button>

                            <!-- WhatsApp -->
                            <button type="button" @click="shareOnWhatsApp()" 
                                    class="px-3 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-sm transition">
                                <i class="fa-brands fa-whatsapp text-sm"></i> WhatsApp
                            </button>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Recent Receipts Table at Bottom -->
            <?php if (!empty($recentReceipts)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-slate-200 dark:border-gray-700 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-[#0F8B8D]"></i> Recently Generated Custom Receipts
                    </h4>
                    <a href="custom_receipts.php" class="text-xs font-bold text-[#0F8B8D] hover:underline">View All &rarr;</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-slate-50 dark:bg-gray-700/50 text-[11px] font-bold uppercase text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="p-3">Receipt No</th>
                                <th class="p-3">Payer Name</th>
                                <th class="p-3">Amount</th>
                                <th class="p-3">Purpose</th>
                                <th class="p-3">Payment Mode</th>
                                <th class="p-3">Date</th>
                                <th class="p-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-700">
                            <?php foreach ($recentReceipts as $rc): ?>
                            <tr class="hover:bg-slate-50/50 dark:hover:bg-gray-700/30 transition">
                                <td class="p-3 font-mono font-bold text-teal-600 dark:text-teal-400"><?php echo htmlspecialchars($rc['receipt_no']); ?></td>
                                <td class="p-3 font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($rc['payer_name']); ?></td>
                                <td class="p-3 font-black text-gray-900 dark:text-white">₹ <?php echo number_format((float)$rc['amount'], 2); ?></td>
                                <td class="p-3 truncate max-w-[180px]"><?php echo htmlspecialchars($rc['purpose']); ?></td>
                                <td class="p-3"><span class="px-2 py-0.5 rounded-full bg-slate-100 dark:bg-gray-700 text-[10px] font-bold"><?php echo htmlspecialchars($rc['payment_mode']); ?></span></td>
                                <td class="p-3 whitespace-nowrap"><?php echo date('d M, Y', strtotime($rc['date'])); ?></td>
                                <td class="p-3 text-right whitespace-nowrap">
                                    <button type="button" @click="printReceipt('download_custom_receipt.php?id=<?php echo (int)$rc['id']; ?>')" class="text-teal-600 hover:underline font-bold mr-2 inline-flex items-center gap-1" title="Print PDF">
                                        <i class="fa-solid fa-print"></i> Print
                                    </button>
                                    <button type="button" @click="openEmailModal(<?php echo htmlspecialchars(json_encode($rc), ENT_QUOTES, 'UTF-8'); ?>)" class="text-blue-600 hover:underline font-bold mr-2 inline-flex items-center gap-1" title="Email PDF">
                                        <i class="fa-solid fa-envelope"></i> Email
                                    </button>
                                    <a href="download_custom_receipt.php?id=<?php echo (int)$rc['id']; ?>&download=1" class="text-indigo-600 hover:underline font-bold inline-flex items-center gap-1" title="Download PDF">
                                        <i class="fa-solid fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- ════════════════════════════════════════════════════════════ -->
    <!-- EMAIL RECEIPT MODAL                                          -->
    <!-- ════════════════════════════════════════════════════════════ -->
    <div x-show="isEmailModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" x-transition.opacity @click.self="isEmailModalOpen = false">
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-200 dark:border-gray-700">
            <div class="p-5 border-b border-slate-100 dark:border-gray-700 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-blue-500/20 text-blue-300 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-paper-plane"></i>
                    </span>
                    <div>
                        <h4 class="text-sm font-black">Email Receipt PDF</h4>
                        <p class="text-[11px] text-slate-400" x-text="'Receipt: #' + activeReceiptForEmail?.receipt_no"></p>
                    </div>
                </div>
                <button type="button" @click="isEmailModalOpen = false" class="text-white/70 hover:text-white">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <form @submit.prevent="sendEmailReceipt()" class="p-6 space-y-4 text-xs">
                <div>
                    <label class="block text-gray-400 font-bold uppercase text-[10px] mb-1">Payer Name:</label>
                    <p class="font-bold text-gray-900 dark:text-white text-sm" x-text="activeReceiptForEmail?.payer_name"></p>
                </div>

                <div>
                    <label class="block text-gray-700 dark:text-gray-300 font-bold mb-1">Recipient Email Address *</label>
                    <input type="email" x-model="emailForm.email" required placeholder="payer@example.com" 
                           class="w-full text-xs font-semibold p-3 border rounded-xl bg-slate-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none focus:ring-2 focus:ring-[#0F8B8D]">
                    <p class="text-[10px] text-gray-400 mt-1">The official PDF receipt with verification QR code will be attached.</p>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-gray-700 flex items-center justify-end gap-2">
                    <button type="button" @click="isEmailModalOpen = false" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 font-bold text-xs hover:bg-slate-100 transition">
                        Cancel
                    </button>
                    <button type="submit" :disabled="isSendingEmail" 
                            class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-md transition disabled:opacity-50">
                        <i class="fa-solid fa-spinner fa-spin" x-show="isSendingEmail"></i>
                        <i class="fa-solid fa-paper-plane" x-show="!isSendingEmail"></i>
                        <span x-text="isSendingEmail ? 'Sending Email...' : 'Send PDF Receipt'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function customReceiptGeneratorApp() {
    return {
        receiptNo: <?php echo json_encode($nextReceiptNo); ?>,
        selectedPurpose: 'General Donation / NGO Activities',
        amountInWords: 'Zero Rupees Only',
        loading: false,
        generatedReceipt: null,

        isEmailModalOpen: false,
        activeReceiptForEmail: null,
        emailForm: { email: '' },
        isSendingEmail: false,

        form: {
            payer_name: '',
            payer_phone: '',
            payer_email: '',
            payer_pan: '',
            payer_address: '',
            amount: '',
            purpose: 'General Donation / NGO Activities',
            payment_mode: 'Cash',
            date: new Date().toISOString().slice(0, 10),
            transaction_ref: '',
            remarks: ''
        },

        init() {
            this.updateAmountInWords();
        },

        onPurposeChange() {
            if (this.selectedPurpose !== 'custom') {
                this.form.purpose = this.selectedPurpose;
            } else {
                this.form.purpose = '';
            }
        },

        formatDate(d) {
            if (!d) return '-';
            const parts = d.split('-');
            if (parts.length === 3) {
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const mIdx = parseInt(parts[1], 10) - 1;
                return `${parts[2]} ${months[mIdx] || parts[1]}, ${parts[0]}`;
            }
            return d;
        },

        printReceipt(url) {
            const printWin = window.open(url, '_blank');
            if (printWin) {
                printWin.focus();
            }
        },

        openEmailModal(receiptObj) {
            this.activeReceiptForEmail = receiptObj;
            this.emailForm.email = receiptObj.payer_email || this.form.payer_email || '';
            this.isEmailModalOpen = true;
        },

        async sendEmailReceipt() {
            if (!this.emailForm.email) {
                alert('Please enter a valid recipient email address.');
                return;
            }

            const receiptId = this.activeReceiptForEmail?.receipt_id || this.activeReceiptForEmail?.id;
            if (!receiptId) {
                alert('Receipt ID not available.');
                return;
            }

            this.isSendingEmail = true;
            const fd = new FormData();
            fd.append('id', receiptId);
            fd.append('email', this.emailForm.email);
            fd.append('ajax', '1');

            try {
                const res = await fetch('actions/send_custom_receipt.php', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: fd
                });
                const data = await res.json();
                if (data.status === 'success') {
                    alert(data.message);
                    this.isEmailModalOpen = false;
                } else {
                    alert(data.message || 'Error sending email.');
                }
            } catch (err) {
                console.error(err);
                alert('Email dispatch failed. Please check network / SMTP settings.');
            } finally {
                this.isSendingEmail = false;
            }
        },

        updateAmountInWords() {
            const num = parseFloat(this.form.amount);
            if (isNaN(num) || num <= 0) {
                this.amountInWords = 'Zero Rupees Only';
                return;
            }
            this.amountInWords = this.numberToIndianWords(num);
        },

        numberToIndianWords(num) {
            const a = ['', 'One ', 'Two ', 'Three ', 'Four ', 'Five ', 'Six ', 'Seven ', 'Eight ', 'Nine ', 'Ten ', 'Eleven ', 'Twelve ', 'Thirteen ', 'Fourteen ', 'Fifteen ', 'Sixteen ', 'Seventeen ', 'Eighteen ', 'Nineteen '];
            const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

            const inWords = (n) => {
                let str = '';
                if (n > 99) {
                    str += a[Math.floor(n / 100)] + 'Hundred ';
                    n %= 100;
                }
                if (n > 19) {
                    str += b[Math.floor(n / 10)] + (n % 10 !== 0 ? ' ' + a[n % 10] : ' ');
                } else if (n > 0) {
                    str += a[n];
                }
                return str;
            };

            let n = Math.floor(num);
            let crore = Math.floor(n / 10000000);
            n %= 10000000;
            let lakh = Math.floor(n / 100000);
            n %= 100000;
            let thousand = Math.floor(n / 1000);
            n %= 1000;
            let remainder = n;

            let result = '';
            if (crore > 0) result += inWords(crore) + 'Crore ';
            if (lakh > 0) result += inWords(lakh) + 'Lakh ';
            if (thousand > 0) result += inWords(thousand) + 'Thousand ';
            if (remainder > 0) result += inWords(remainder);

            return (result.trim() ? result.trim() + ' Rupees Only' : 'Zero Rupees Only');
        },

        async submitForm() {
            if (!this.form.payer_name || !this.form.amount || Number(this.form.amount) <= 0) {
                alert('Please fill in the required fields: Payer Name and Amount.');
                return;
            }

            this.loading = true;

            const fd = new FormData();
            fd.append('action', 'create_receipt');
            fd.append('csrf_token', <?php echo json_encode($csrfToken); ?>);
            fd.append('payer_name', this.form.payer_name);
            fd.append('payer_phone', this.form.payer_phone);
            fd.append('payer_email', this.form.payer_email);
            fd.append('payer_pan', this.form.payer_pan);
            fd.append('payer_address', this.form.payer_address);
            fd.append('amount', this.form.amount);
            fd.append('purpose', this.form.purpose || 'Donation / Contribution');
            fd.append('payment_mode', this.form.payment_mode);
            fd.append('date', this.form.date);
            fd.append('transaction_ref', this.form.transaction_ref);
            fd.append('remarks', this.form.remarks);
            fd.append('ajax', '1');

            try {
                const res = await fetch('actions/custom_receipt_logic.php', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: fd
                });

                const data = await res.json();
                if (data.status === 'success') {
                    this.generatedReceipt = data;
                    // Auto open PDF in new tab
                    window.open(data.pdf_url, '_blank');
                } else {
                    alert(data.message || 'Error creating receipt.');
                }
            } catch (err) {
                console.error(err);
                alert('Failed to generate receipt. Please check your network and try again.');
            } finally {
                this.loading = false;
            }
        },

        shareOnWhatsApp() {
            if (!this.generatedReceipt) return;
            const phone = (this.form.payer_phone || '').replace(/\D/g, '');
            const cleanPhone = phone.length === 10 ? ('91' + phone) : phone;
            const msg = this.generatedReceipt.whatsapp_text || `Receipt #${this.generatedReceipt.receipt_no} generated.`;
            const waUrl = cleanPhone 
                ? `https://wa.me/${cleanPhone}?text=${encodeURIComponent(msg)}`
                : `https://wa.me/?text=${encodeURIComponent(msg)}`;
            window.open(waUrl, '_blank');
        },

        resetForm() {
            this.form.payer_name = '';
            this.form.payer_phone = '';
            this.form.payer_email = '';
            this.form.payer_pan = '';
            this.form.payer_address = '';
            this.form.amount = '';
            this.form.purpose = 'General Donation / NGO Activities';
            this.form.payment_mode = 'Cash';
            this.form.date = new Date().toISOString().slice(0, 10);
            this.form.transaction_ref = '';
            this.form.remarks = '';
            this.selectedPurpose = 'General Donation / NGO Activities';
            this.generatedReceipt = null;
            this.updateAmountInWords();
        }
    };
}
</script>

<?php require 'includes/footer.php'; ?>

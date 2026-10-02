<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Handle Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['donor_verified'], $_SESSION['donor_email'], $_SESSION['otp_code'], $_SESSION['otp_email'], $_SESSION['otp_expiry']);
    header('Location: donor-history.php');
    exit;
}

// Check if donor or member is already authenticated
$initialEmail = '';
$initialVerified = false;

if (!empty($_SESSION['donor_verified']) && !empty($_SESSION['donor_email'])) {
    $initialEmail = trim((string)$_SESSION['donor_email']);
    $initialVerified = true;
} elseif (!empty($_SESSION['member_logged_in']) && !empty($_SESSION['member_email'])) {
    $initialEmail = trim((string)$_SESSION['member_email']);
    $initialVerified = true;
    $_SESSION['donor_verified'] = true;
    $_SESSION['donor_email'] = $initialEmail;
}

require 'includes/header.php';
?>

<div class="bg-gray-50 dark:bg-gray-900 min-h-screen py-10 md:py-16 px-4"
    x-data="donorPortal({
        initialEmail: '<?php echo htmlspecialchars($initialEmail, ENT_QUOTES, 'UTF-8'); ?>',
        initialVerified: <?php echo $initialVerified ? 'true' : 'false'; ?>
    })"
    x-init="init()">

    <div class="container mx-auto max-w-5xl">

        <!-- 1. ENTER EMAIL STATE -->
        <div x-show="state === 'enter_email'" x-transition.opacity>
            <div class="max-w-xl mx-auto bg-white dark:bg-gray-800 p-8 md:p-10 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 text-center">
                <div class="w-16 h-16 bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] rounded-2xl flex items-center justify-center mx-auto mb-6 text-2xl shadow-inner">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </div>
                <h1 class="text-2xl md:text-3xl font-black text-gray-800 dark:text-white">Donor Portal & Auto Pay</h1>
                <p class="text-gray-500 dark:text-gray-400 mt-2 text-sm">
                    Manage your recurring donations, pause or cancel mandates, and download your 80G tax receipts securely.
                </p>

                <!-- Features list -->
                <div class="grid grid-cols-3 gap-3 my-6 text-left">
                    <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border dark:border-gray-600 text-center">
                        <i class="fa-solid fa-clock-rotate-left text-[#0F8B8D] mb-1"></i>
                        <p class="text-[11px] font-semibold text-gray-700 dark:text-gray-300">AutoPay History</p>
                    </div>
                    <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border dark:border-gray-600 text-center">
                        <i class="fa-solid fa-pause text-amber-500 mb-1"></i>
                        <p class="text-[11px] font-semibold text-gray-700 dark:text-gray-300">Pause / Resume</p>
                    </div>
                    <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border dark:border-gray-600 text-center">
                        <i class="fa-solid fa-file-invoice-dollar text-emerald-500 mb-1"></i>
                        <p class="text-[11px] font-semibold text-gray-700 dark:text-gray-300">80G Tax Receipts</p>
                    </div>
                </div>

                <form @submit.prevent="sendOtp" class="mt-6 max-w-sm mx-auto space-y-3">
                    <div class="relative">
                        <input type="email" x-model="email" required placeholder="your.email@example.com" 
                               class="w-full text-center px-4 py-3.5 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-[#0F8B8D] outline-none transition text-base font-medium">
                    </div>
                    <button type="submit" :disabled="loading" 
                            class="w-full bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold py-3.5 rounded-xl shadow-lg transition flex items-center justify-center gap-2 disabled:opacity-50">
                        <span x-show="!loading"><i class="fa-solid fa-key"></i> Get Verification OTP</span>
                        <span x-show="loading" class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-notch fa-spin"></i> Sending OTP...
                        </span>
                    </button>
                    <p x-text="message" x-show="message" class="text-xs mt-3 font-medium" :class="messageType === 'error' ? 'text-rose-500' : 'text-emerald-600'"></p>
                </form>

                <div class="mt-8 pt-6 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-400">
                    Want to start a new recurring donation? 
                    <a href="donate.php" class="text-[#0F8B8D] font-bold hover:underline">Donate Now</a>
                </div>
            </div>
        </div>

        <!-- 2. ENTER OTP STATE -->
        <div x-show="state === 'enter_otp'" x-transition.opacity x-cloak>
            <div class="max-w-md mx-auto bg-white dark:bg-gray-800 p-8 md:p-10 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 text-center">
                <div class="w-14 h-14 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 text-xl">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Enter 6-Digit OTP</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    A verification code was sent to <strong class="text-gray-700 dark:text-gray-200" x-text="email"></strong>
                </p>

                <form @submit.prevent="verifyOtp" class="mt-6 space-y-4">
                    <input type="text" x-model="otp" required pattern="\d{6}" maxlength="6" placeholder="______" 
                           class="w-full text-center tracking-[12px] text-2xl font-black py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-[#0F8B8D] outline-none">
                    
                    <button type="submit" :disabled="loading" 
                            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3.5 rounded-xl shadow-lg transition flex items-center justify-center gap-2 disabled:opacity-50">
                        <span x-show="!loading">Verify & Open Dashboard</span>
                        <span x-show="loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Verifying...</span>
                    </button>
                    
                    <p x-text="message" x-show="message && messageType === 'error'" class="text-xs text-rose-500 font-medium"></p>
                    
                    <div class="flex justify-between items-center text-xs pt-2">
                        <button type="button" @click="resetState()" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                            ← Change Email
                        </button>
                        <button type="button" @click="sendOtp()" :disabled="loading" class="text-[#0F8B8D] font-bold hover:underline">
                            Resend OTP
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 3. DONOR DASHBOARD & AUTOPAY HUB -->
        <div x-show="state === 'show_history'" x-transition.opacity x-cloak>
            
            <!-- Top Profile Bar -->
            <div class="bg-white dark:bg-gray-800 p-5 md:p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] flex items-center justify-center text-xl font-bold">
                        <i class="fa-solid fa-user-heart"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-gray-800 dark:text-white">Donor Dashboard</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2 mt-0.5">
                            <span>Logged in as: <strong class="text-gray-700 dark:text-gray-300" x-text="email"></strong></span>
                            <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 w-full md:w-auto">
                    <a href="donate.php" class="flex-1 md:flex-initial bg-[#0F8B8D] hover:bg-[#0c7274] text-white px-4 py-2.5 rounded-xl font-bold text-xs flex items-center justify-center gap-2 shadow-sm transition">
                        <i class="fa-solid fa-plus"></i> New Donation / AutoPay
                    </a>
                    <a href="donor-history.php?logout=1" class="px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-xs font-semibold flex items-center gap-1.5 transition">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </a>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Active AutoPay</p>
                            <h3 class="text-2xl font-black text-gray-800 dark:text-white mt-1">
                                <span x-text="activeRecurringCount"></span> Mandates
                            </h3>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-lg">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        Monthly Commitment: <strong class="text-emerald-600">₹<span x-text="monthlyCommittedAmount.toLocaleString('en-IN')"></span></strong>
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">AutoPay Cycles Paid</p>
                            <h3 class="text-2xl font-black text-[#0F8B8D] mt-1">
                                <span x-text="totalCyclesPaid"></span> Cycles
                            </h3>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] flex items-center justify-center text-lg">
                            <i class="fa-solid fa-arrows-rotate"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        Recurring Contribution: <strong class="text-gray-800 dark:text-white">₹<span x-text="totalRecurringPaidAmount.toLocaleString('en-IN')"></span></strong>
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">One-Time Donations</p>
                            <h3 class="text-2xl font-black text-gray-800 dark:text-white mt-1">
                                <span x-text="donations.length"></span> Gifts
                            </h3>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-500 flex items-center justify-center text-lg">
                            <i class="fa-solid fa-heart"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        Total One-Time: <strong class="text-gray-800 dark:text-white">₹<span x-text="totalOneTimeAmount.toLocaleString('en-IN')"></span></strong>
                    </p>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6 gap-2">
                <button type="button" @click="activeTab = 'recurring'" 
                        class="pb-3 px-4 text-sm font-bold border-b-2 transition flex items-center gap-2"
                        :class="activeTab === 'recurring' ? 'border-[#0F8B8D] text-[#0F8B8D]' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                    <i class="fa-solid fa-arrows-rotate"></i>
                    My Auto Pay Mandates
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                          :class="activeTab === 'recurring' ? 'bg-teal-100 text-[#0F8B8D] dark:bg-teal-900/40' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                          x-text="recurringList.length"></span>
                </button>

                <button type="button" @click="activeTab = 'onetime'" 
                        class="pb-3 px-4 text-sm font-bold border-b-2 transition flex items-center gap-2"
                        :class="activeTab === 'onetime' ? 'border-[#0F8B8D] text-[#0F8B8D]' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                    One-Time Donations
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                          :class="activeTab === 'onetime' ? 'bg-teal-100 text-[#0F8B8D] dark:bg-teal-900/40' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                          x-text="donations.length"></span>
                </button>

                <button type="button" @click="activeTab = 'items'" 
                        class="pb-3 px-4 text-sm font-bold border-b-2 transition flex items-center gap-2"
                        :class="activeTab === 'items' ? 'border-[#0F8B8D] text-[#0F8B8D]' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                    <i class="fa-solid fa-gift"></i>
                    Item Donations
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                          :class="activeTab === 'items' ? 'bg-teal-100 text-[#0F8B8D] dark:bg-teal-900/40' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                          x-text="itemDonations.length"></span>
                </button>

                <button type="button" @click="activeTab = 'receipts'" 
                        class="pb-3 px-4 text-sm font-bold border-b-2 transition flex items-center gap-2"
                        :class="activeTab === 'receipts' ? 'border-[#0F8B8D] text-[#0F8B8D]' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    All Receipts & Billing History
                </button>
            </div>

            <!-- TAB 1: RECURRING AUTOPAY MANDATES -->
            <div x-show="activeTab === 'recurring'" class="space-y-4">
                
                <div x-show="recurringList.length === 0" class="bg-white dark:bg-gray-800 p-12 rounded-3xl text-center border border-gray-100 dark:border-gray-700">
                    <div class="w-16 h-16 bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">No AutoPay Mandates Active</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 max-w-md mx-auto mt-1 mb-6">
                        You haven't set up any recurring donations yet. Enable monthly giving to support ongoing causes automatically!
                    </p>
                    <a href="donate.php" class="inline-flex items-center gap-2 bg-[#0F8B8D] hover:bg-[#0c7274] text-white px-6 py-3 rounded-xl font-bold text-sm shadow-md transition">
                        <i class="fa-solid fa-heart"></i> Start Monthly AutoPay
                    </a>
                </div>

                <template x-for="item in recurringList" :key="item.id">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 md:p-6 shadow-sm border border-gray-100 dark:border-gray-700 hover:border-teal-200 transition">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                            <div>
                                <div class="flex items-center gap-2.5">
                                    <h4 class="text-xl font-black text-gray-800 dark:text-white">
                                        ₹<span x-text="Number(item.amount).toLocaleString('en-IN')"></span>
                                        <span class="text-xs font-bold text-[#0F8B8D] uppercase tracking-wider">/<span x-text="item.frequency"></span></span>
                                    </h4>

                                    <!-- Status badge -->
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider"
                                          :class="{
                                              'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300': item.status === 'active',
                                              'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300': item.status === 'paused',
                                              'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400': item.status === 'stopped',
                                              'bg-orange-100 text-orange-700': item.status === 'pending',
                                              'bg-rose-100 text-rose-700': item.status === 'failed'
                                          }">
                                        <i class="fa-solid" 
                                           :class="{
                                               'fa-circle-check': item.status === 'active',
                                               'fa-pause': item.status === 'paused',
                                               'fa-ban': item.status === 'stopped'
                                           }"></i>
                                        <span x-text="item.status"></span>
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Allocated Cause: <strong class="text-gray-700 dark:text-gray-300" x-text="item.project_title || 'General Purpose NGO Fund'"></strong>
                                </p>
                            </div>

                            <!-- Action Controls for Donor -->
                            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                                <!-- Pause Button -->
                                <button type="button" x-show="item.status === 'active'" @click="openActionModal('pause', item)"
                                        class="px-3.5 py-2 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-bold flex items-center gap-1.5 transition">
                                    <i class="fa-solid fa-pause"></i> Pause AutoPay
                                </button>

                                <!-- Resume Button -->
                                <button type="button" x-show="item.status === 'paused'" @click="openActionModal('resume', item)"
                                        class="px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold flex items-center gap-1.5 transition">
                                    <i class="fa-solid fa-play"></i> Resume AutoPay
                                </button>

                                <!-- Cancel / Stop Button -->
                                <button type="button" x-show="item.status === 'active' || item.status === 'paused'" @click="openActionModal('cancel', item)"
                                        class="px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold flex items-center gap-1.5 transition">
                                    <i class="fa-solid fa-ban"></i> Cancel Mandate
                                </button>
                            </div>
                        </div>

                        <!-- Details Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 py-3 text-xs">
                            <div>
                                <span class="text-gray-400">Next Auto-Debit:</span>
                                <p class="font-bold text-gray-700 dark:text-gray-300 mt-0.5">
                                    <span x-show="item.status === 'active'" x-text="item.next_charge_date || 'Calculated on cycle'"></span>
                                    <span x-show="item.status !== 'active'" class="text-gray-400">On Hold</span>
                                </p>
                            </div>
                            <div>
                                <span class="text-gray-400">Completed Cycles:</span>
                                <p class="font-bold text-gray-700 dark:text-gray-300 mt-0.5" x-text="item.completed_cycles + ' Cycles Paid'"></p>
                            </div>
                            <div>
                                <span class="text-gray-400">Total Given:</span>
                                <p class="font-bold text-emerald-600 mt-0.5">₹<span x-text="Number(item.total_paid_amount || 0).toLocaleString('en-IN')"></span></p>
                            </div>
                            <div>
                                <span class="text-gray-400">Mandate ID:</span>
                                <p class="font-mono text-[11px] text-gray-500 truncate mt-0.5" x-text="item.razorpay_subscription_id || 'Direct'"></p>
                            </div>
                        </div>

                        <!-- Cycles & Receipts Accordion Toggle -->
                        <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center text-xs">
                            <span class="text-gray-500">Need tax receipts for this mandate?</span>
                            <button type="button" @click="toggleCyclesView(item.id)" 
                                    class="text-[#0F8B8D] font-bold hover:underline flex items-center gap-1">
                                <i class="fa-solid fa-receipt"></i>
                                <span x-text="expandedCycles[item.id] ? 'Hide Cycle Receipts' : 'View Cycle Receipts (' + getTransactionsForMandate(item.id).length + ')'"></span>
                            </button>
                        </div>

                        <!-- Expanded Cycle Receipts List -->
                        <div x-show="expandedCycles[item.id]" x-collapse class="mt-4 pt-3 border-t border-dashed border-gray-200 dark:border-gray-700">
                            <h5 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Billing Cycles for this Mandate</h5>
                            <div class="space-y-2">
                                <template x-for="c in getTransactionsForMandate(item.id)" :key="c.id">
                                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl text-xs">
                                        <div>
                                            <span class="font-bold text-gray-700 dark:text-gray-200">Cycle #<span x-text="c.cycle_number"></span></span>
                                            <span class="text-gray-400 ml-2 font-mono" x-text="c.charge_date"></span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="font-bold text-emerald-600">₹<span x-text="Number(c.amount).toLocaleString('en-IN')"></span></span>
                                            <template x-if="c.donation_id && c.status === 'success'">
                                                <a :href="'download-receipt.php?id=' + c.donation_id" 
                                                   class="px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-lg font-semibold hover:bg-emerald-100 transition inline-flex items-center gap-1 border border-emerald-200">
                                                    <i class="fa-solid fa-download"></i> Receipt
                                                </a>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="getTransactionsForMandate(item.id).length === 0" class="text-center py-3 text-xs text-gray-400">
                                    No cycle payments charged yet for this mandate.
                                </div>
                            </div>
                        </div>

                    </div>
                </template>
            </div>

            <!-- TAB 2: ONE-TIME DONATIONS -->
            <div x-show="activeTab === 'onetime'" class="space-y-4">
                <div x-show="donations.length === 0" class="bg-white dark:bg-gray-800 p-12 rounded-3xl text-center border border-gray-100 dark:border-gray-700">
                    <i class="fa-solid fa-hand-holding-dollar text-3xl text-gray-300 mb-3"></i>
                    <h3 class="text-base font-bold text-gray-700 dark:text-gray-200">No One-Time Donations Found</h3>
                    <p class="text-xs text-gray-400 mt-1">You have not made any one-time donations with this email.</p>
                </div>

                <div class="space-y-3">
                    <template x-for="item in donations" :key="item.id">
                        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <div class="flex items-center gap-2.5">
                                    <span class="px-2.5 py-0.5 text-xs font-bold rounded-full uppercase"
                                          :class="{
                                              'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300': item.payment_status === 'Success',
                                              'bg-amber-100 text-amber-700': item.payment_status === 'Pending',
                                              'bg-rose-100 text-rose-700': item.payment_status === 'Failed'
                                          }"
                                          x-text="item.payment_status"></span>
                                    <span class="text-xs text-gray-400 font-mono" x-text="new Date(item.created_at).toLocaleDateString('en-GB')"></span>
                                </div>
                                <h4 class="font-black text-xl text-gray-800 dark:text-white mt-1.5">
                                    ₹<span x-text="parseFloat(item.amount).toLocaleString('en-IN')"></span>
                                </h4>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Project: <span x-text="item.project_title || 'General Purpose'"></span> • 
                                    Receipt No: <span class="font-mono font-semibold" x-text="item.receipt_no || 'Pending'"></span>
                                </p>
                            </div>

                            <div>
                                <a :href="'download-receipt.php?id=' + item.id"
                                   x-show="item.payment_status === 'Success'"
                                   class="inline-flex items-center gap-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 px-4 py-2.5 rounded-xl text-xs font-bold transition">
                                    <i class="fa-solid fa-download"></i> Download 80G Receipt
                                </a>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- TAB 3: ITEM DONATIONS -->
            <div x-show="activeTab === 'items'" class="space-y-4">
                <div x-show="itemDonations.length === 0" class="bg-white dark:bg-gray-800 p-12 rounded-3xl text-center border border-gray-100 dark:border-gray-700">
                    <div class="w-16 h-16 bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] rounded-2xl flex items-center justify-center mx-auto mb-4 text-2xl">
                        <i class="fa-solid fa-gift"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">No Item Donations Logged</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 max-w-md mx-auto mt-1 mb-6">
                        You haven't pledged any in-kind goods yet. Contribute books, clothes, ration, or medical aids to empower communities!
                    </p>
                    <a href="donate-items.php" class="inline-flex items-center gap-2 bg-[#0F8B8D] hover:bg-[#0c7274] text-white px-6 py-3 rounded-xl font-bold text-sm shadow-md transition">
                        <i class="fa-solid fa-gift"></i> Donate Items Now
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="itemDonations.length > 0">
                    <template x-for="item in itemDonations" :key="'itm_' + item.id">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 hover:border-teal-200 transition flex flex-col justify-between">
                            <div>
                                <div class="flex items-start justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-700">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#0F8B8D] flex items-center justify-center text-lg flex-shrink-0">
                                            <i class="fa-solid" :class="item.category_icon || 'fa-gift'"></i>
                                        </div>
                                        <div>
                                            <h5 class="font-bold text-gray-800 dark:text-white text-sm" x-text="item.category_name || 'Essential Goods'"></h5>
                                            <span class="font-mono text-[11px] text-gray-400" x-text="item.donation_code"></span>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700" x-text="item.status"></span>
                                </div>

                                <div class="py-3 text-xs space-y-1.5">
                                    <p class="text-gray-700 dark:text-gray-300 font-medium line-clamp-2" x-text="item.item_description"></p>
                                    <div class="flex items-center justify-between text-gray-500 pt-1">
                                        <span>Quantity: <strong class="text-emerald-600" x-text="item.quantity + ' ' + item.unit"></strong> (<span x-text="item.condition_type"></span>)</span>
                                        <span x-show="Number(item.estimated_value) > 0">Est. ₹<strong class="text-gray-800 dark:text-white" x-text="Number(item.estimated_value).toLocaleString('en-IN')"></strong></span>
                                    </div>
                                    <div class="text-[11px] text-gray-400">
                                        <span>Date: <span x-text="new Date(item.donation_date || item.created_at).toLocaleDateString('en-GB')"></span></span>
                                        <span class="mx-1.5">•</span>
                                        <span x-text="item.pickup_city"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-2">
                                <a :href="item.receipt_download_url || ('download-item-receipt.php?id=' + item.id)"
                                   class="flex-1 inline-flex items-center justify-center gap-1.5 bg-[#0F8B8D] hover:bg-[#0c7274] text-white py-2 px-3 rounded-xl text-xs font-bold transition shadow-sm">
                                    <i class="fa-solid fa-file-pdf"></i> Download Receipt
                                </a>
                                <a :href="item.verify_url || ('verify-item.php?code=' + item.donation_code)"
                                   class="inline-flex items-center justify-center gap-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 py-2 px-3 rounded-xl text-xs font-bold transition">
                                    <i class="fa-solid fa-qrcode"></i> Verify
                                </a>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- TAB 4: ALL RECEIPTS & BILLING HISTORY -->
            <div x-show="activeTab === 'receipts'" class="space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 flex justify-between items-center">
                        <h4 class="text-sm font-bold text-gray-800 dark:text-white flex items-center gap-2">
                            <i class="fa-solid fa-file-pdf text-emerald-600"></i>
                            Consolidated 80G Tax Deductible Receipts
                        </h4>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 uppercase tracking-wider border-b dark:border-gray-700">
                                    <th class="p-3.5">Date</th>
                                    <th class="p-3.5">Type / Cause</th>
                                    <th class="p-3.5">Receipt No</th>
                                    <th class="p-3.5">Amount</th>
                                    <th class="p-3.5">Status</th>
                                    <th class="p-3.5 text-right">Download</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                <!-- Recurring Cycles -->
                                <template x-for="c in recurringTransactions" :key="'tx_' + c.id">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/20">
                                        <td class="p-3.5 font-mono" x-text="c.charge_date"></td>
                                        <td class="p-3.5">
                                            <span class="font-bold text-[#0F8B8D]">AutoPay Cycle #<span x-text="c.cycle_number"></span></span>
                                            <p class="text-[10px] text-gray-400" x-text="c.project_title || 'General'"></p>
                                        </td>
                                        <td class="p-3.5 font-mono text-gray-600 dark:text-gray-300" x-text="c.receipt_no || c.donation_receipt_no || '—'"></td>
                                        <td class="p-3.5 font-bold text-emerald-600">₹<span x-text="Number(c.amount).toLocaleString('en-IN')"></span></td>
                                        <td class="p-3.5">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700" x-text="c.status"></span>
                                        </td>
                                        <td class="p-3.5 text-right">
                                            <template x-if="c.donation_id && c.status === 'success'">
                                                <a :href="'download-receipt.php?id=' + c.donation_id" 
                                                   class="px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-lg font-semibold hover:bg-emerald-100 transition inline-flex items-center gap-1 border border-emerald-200">
                                                    <i class="fa-solid fa-file-pdf"></i> PDF
                                                </a>
                                            </template>
                                        </td>
                                    </tr>
                                </template>

                                <!-- One Time Donations -->
                                <template x-for="d in donations" :key="'don_' + d.id">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/20">
                                        <td class="p-3.5 font-mono" x-text="new Date(d.created_at).toLocaleDateString('en-GB')"></td>
                                        <td class="p-3.5">
                                            <span class="font-bold text-gray-700 dark:text-gray-200">One-Time Gift</span>
                                            <p class="text-[10px] text-gray-400" x-text="d.project_title || 'General'"></p>
                                        </td>
                                        <td class="p-3.5 font-mono text-gray-600 dark:text-gray-300" x-text="d.receipt_no || '—'"></td>
                                        <td class="p-3.5 font-bold text-emerald-600">₹<span x-text="Number(d.amount).toLocaleString('en-IN')"></span></td>
                                        <td class="p-3.5">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                                  :class="d.payment_status === 'Success' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                                                  x-text="d.payment_status"></span>
                                        </td>
                                        <td class="p-3.5 text-right">
                                            <template x-if="d.payment_status === 'Success'">
                                                <a :href="'download-receipt.php?id=' + d.id" 
                                                   class="px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-lg font-semibold hover:bg-emerald-100 transition inline-flex items-center gap-1 border border-emerald-200">
                                                    <i class="fa-solid fa-file-pdf"></i> PDF
                                                </a>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- MODAL: Donor Action (Pause/Resume/Cancel) -->
            <div x-show="isActionModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-cloak x-transition>
                <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-700 w-full max-w-md p-6">
                    <div class="text-center">
                        <div class="w-14 h-14 rounded-2xl mx-auto mb-3 flex items-center justify-center text-2xl shadow-inner"
                             :class="{
                                 'bg-amber-50 text-amber-600': actionType === 'pause',
                                 'bg-emerald-50 text-emerald-600': actionType === 'resume',
                                 'bg-rose-50 text-rose-600': actionType === 'cancel'
                             }">
                            <i :class="{
                                'fa-solid fa-pause': actionType === 'pause',
                                'fa-solid fa-play': actionType === 'resume',
                                'fa-solid fa-ban': actionType === 'cancel'
                            }"></i>
                        </div>

                        <h4 class="text-lg font-bold text-gray-800 dark:text-white capitalize" x-text="actionType + ' AutoPay Mandate'"></h4>
                        <p class="text-xs text-gray-500 mt-1">
                            Are you sure you want to <span x-text="actionType"></span> your <span x-text="selectedMandate ? selectedMandate.frequency : ''"></span> recurring donation of 
                            <strong class="text-gray-700 dark:text-gray-300">₹<span x-text="selectedMandate ? Number(selectedMandate.amount).toLocaleString('en-IN') : ''"></span></strong>?
                        </p>
                    </div>

                    <div x-show="actionType === 'pause' || actionType === 'cancel'" class="mt-4">
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1">Reason (Optional)</label>
                        <input type="text" x-model="actionReason" placeholder="e.g. Taking a short break" 
                               class="w-full border rounded-xl px-3.5 py-2.5 text-xs bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white outline-none">
                    </div>

                    <div class="flex gap-2 mt-6">
                        <button type="button" @click="isActionModalOpen = false" 
                                class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition">
                            Cancel
                        </button>
                        <button type="button" @click="confirmAction()" :disabled="actionLoading"
                                class="flex-1 py-2.5 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center justify-center gap-1.5"
                                :class="{
                                    'bg-amber-600 hover:bg-amber-700': actionType === 'pause',
                                    'bg-emerald-600 hover:bg-emerald-700': actionType === 'resume',
                                    'bg-rose-600 hover:bg-rose-700': actionType === 'cancel'
                                }">
                            <span x-show="!actionLoading" x-text="'Confirm ' + actionType"></span>
                            <span x-show="actionLoading"><i class="fa-solid fa-circle-notch fa-spin"></i> Processing...</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
function donorPortal(config) {
    return {
        state: config.initialVerified ? 'show_history' : 'enter_email',
        email: config.initialEmail || '',
        otp: '',
        loading: false,
        message: '',
        messageType: 'error',
        activeTab: 'recurring',
        donations: [],
        recurringList: [],
        recurringTransactions: [],
        itemDonations: [],
        expandedCycles: {},

        // Modals & Action
        isActionModalOpen: false,
        actionType: '',
        selectedMandate: null,
        actionReason: '',
        actionLoading: false,

        init() {
            if (config.initialVerified && this.email) {
                this.fetchData();
            }
        },

        get activeRecurringCount() {
            return this.recurringList.filter(r => r.status === 'active').length;
        },

        get monthlyCommittedAmount() {
            return this.recurringList
                .filter(r => r.status === 'active')
                .reduce((sum, r) => sum + parseFloat(r.amount || 0), 0);
        },

        get totalCyclesPaid() {
            return this.recurringTransactions.filter(t => t.status === 'success').length;
        },

        get totalRecurringPaidAmount() {
            return this.recurringTransactions
                .filter(t => t.status === 'success')
                .reduce((sum, t) => sum + parseFloat(t.amount || 0), 0);
        },

        get totalOneTimeAmount() {
            return this.donations
                .filter(d => d.payment_status === 'Success')
                .reduce((sum, d) => sum + parseFloat(d.amount || 0), 0);
        },

        getTransactionsForMandate(recurringId) {
            return this.recurringTransactions.filter(t => t.recurring_donation_id == recurringId);
        },

        toggleCyclesView(recurringId) {
            this.expandedCycles[recurringId] = !this.expandedCycles[recurringId];
        },

        // Send OTP
        sendOtp() {
            if (!this.email) {
                this.message = 'Please enter a valid email address.';
                this.messageType = 'error';
                return;
            }
            this.loading = true;
            this.message = '';

            fetch('process/send_otp.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({ email: this.email })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.state = 'enter_otp';
                    this.message = data.message;
                    this.messageType = 'success';
                } else {
                    this.message = data.message;
                    this.messageType = 'error';
                }
            })
            .catch(() => {
                this.message = 'An unexpected error occurred. Please try again.';
                this.messageType = 'error';
            })
            .finally(() => this.loading = false);
        },

        // Verify OTP
        verifyOtp() {
            if (this.otp.length !== 6) {
                this.message = 'OTP must be 6 digits.';
                this.messageType = 'error';
                return;
            }
            this.loading = true;
            this.message = '';

            fetch('process/verify_otp.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({ email: this.email, otp: this.otp })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.donations = data.donations || [];
                    this.recurringList = data.recurring || [];
                    this.recurringTransactions = data.recurring_transactions || [];
                    this.itemDonations = data.item_donations || [];
                    this.state = 'show_history';
                } else {
                    this.message = data.message;
                    this.messageType = 'error';
                }
            })
            .catch(() => {
                this.message = 'Verification failed. Please try again.';
                this.messageType = 'error';
            })
            .finally(() => this.loading = false);
        },

        // Fetch fresh data when already logged in
        async fetchData() {
            this.loading = true;
            try {
                const res = await fetch('process/donor_recurring_action.php?action=get_data');
                const data = await res.json();
                if (data.success) {
                    this.recurringList = data.recurring || [];
                    this.recurringTransactions = data.transactions || [];
                    this.donations = data.donations || [];
                    this.itemDonations = data.item_donations || [];
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        },

        openActionModal(type, mandate) {
            this.actionType = type;
            this.selectedMandate = mandate;
            this.actionReason = '';
            this.isActionModalOpen = true;
        },

        async confirmAction() {
            if (!this.selectedMandate) return;
            this.actionLoading = true;

            try {
                const res = await fetch('process/donor_recurring_action.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: this.actionType,
                        recurring_id: this.selectedMandate.id,
                        reason: this.actionReason
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.selectedMandate.status = data.new_status;
                    this.isActionModalOpen = false;
                    alert(data.message);
                } else {
                    alert(data.message || 'Action failed.');
                }
            } catch (e) {
                alert('Network error. Please try again.');
            } finally {
                this.actionLoading = false;
            }
        },

        resetState() {
            this.state = 'enter_email';
            this.message = '';
            this.otp = '';
        }
    };
}
</script>

<?php require 'includes/footer.php'; ?>
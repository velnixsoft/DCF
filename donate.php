<?php
require 'includes/header.php';

$projects = $pdo->query("SELECT id, title FROM projects WHERE status = 'Active' ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
$banks = $pdo->query("SELECT * FROM bank_accounts WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$qrs = $pdo->query("SELECT * FROM payment_qrs WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$is80gEnabled = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'enable_80g'")->fetchColumn() == '1';
$csrfToken = generateCsrfToken();
$memberReferralCode = trim((string)($_GET['mref'] ?? ''));
?>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<div class="bg-gradient-to-b from-[#FFF8F1] via-white to-[#F0FDFD] min-h-screen" x-data="donationPage()" x-cloak>
    <div class="container mx-auto px-4 py-10 md:py-16">
        <div class="max-w-5xl mx-auto text-center mb-10 md:mb-14">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white shadow-sm border border-[#CCFBF1] text-[#0F8B8D] text-sm font-semibold">
                <i class="fas fa-heart text-[#F4A640]"></i>
                Support a cause that changes lives
            </span>
            <h1 class="mt-5 text-4xl md:text-5xl font-extrabold text-[#0F8B8D] tracking-tight">Make a Donation</h1>
            <p class="mt-4 text-lg text-[#4B5563] max-w-2xl mx-auto">
                Choose fast online payment with Razorpay or scan the QR and submit manual payment details for verification.
            </p>
            <div class="mt-4">
                <a href="donate-items.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white shadow-sm border border-amber-200 text-[#d98e2b] hover:bg-amber-50 text-xs sm:text-sm font-bold transition">
                    <i class="fa-solid fa-gift text-[#F4A640]"></i>
                    Want to donate clothes, ration, books, or physical goods instead? <u>Donate Items & Essentials →</u>
                </a>
            </div>
        </div>

        <div class="grid lg:grid-cols-[1.15fr_0.85fr] gap-8 items-start">
            <div class="bg-white rounded-[18px] shadow-2xl border border-gray-100 overflow-hidden">
                <div class="p-6 md:p-8 border-b border-gray-100 bg-gradient-to-r from-[#FFF8F1] to-white">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-[#1F2937]">Donation Details</h2>
                            <p class="text-sm text-[#4B5563] mt-1">Fill in your details and choose how you want to contribute.</p>
                        </div>
                        <div class="inline-flex rounded-2xl bg-gray-100 p-1 text-sm font-semibold">
                            <button type="button"
                                @click="paymentMode = 'razorpay'"
                                :class="paymentMode === 'razorpay' ? 'bg-white text-[#0F8B8D] shadow-sm' : 'text-[#4B5563]'"
                                class="px-4 py-2 rounded-xl transition">
                                Online
                            </button>
                            <button type="button"
                                @click="paymentMode = 'manual'"
                                :class="paymentMode === 'manual' ? 'bg-white text-[#0F8B8D] shadow-sm' : 'text-[#4B5563]'"
                                class="px-4 py-2 rounded-xl transition">
                                Manual
                            </button>
                        </div>
                    </div>
                </div>

                <form action="process/submit_donation.php" method="POST" enctype="multipart/form-data"
                    class="p-6 md:p-8 space-y-6"
                    @submit="handleSubmit($event)">

                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="payment_mode" :value="paymentMode">
                    <input type="hidden" name="donation_type" :value="donationType">
                    <input type="hidden" name="frequency" :value="frequency">
                    <input type="hidden" name="razorpay_order_id" :value="razorpay.order_id">
                    <input type="hidden" name="razorpay_payment_id" :value="razorpay.payment_id">
                    <input type="hidden" name="razorpay_signature" :value="razorpay.signature">
                    <input type="hidden" name="referral_code" value="<?php echo htmlspecialchars($memberReferralCode, ENT_QUOTES, 'UTF-8'); ?>">

                    <!-- Donation Type Toggle (One-Time vs Recurring AutoPay) -->
                    <div class="space-y-3" x-show="paymentMode === 'razorpay'">
                        <div class="p-1.5 bg-gray-100 rounded-2xl flex gap-1 border border-gray-200">
                            <button type="button"
                                @click="donationType = 'one_time'"
                                :class="donationType === 'one_time' ? 'bg-white text-[#0F8B8D] shadow-md font-bold' : 'text-gray-600 hover:text-gray-900 font-medium'"
                                class="flex-1 py-3 px-3 rounded-xl text-center text-sm sm:text-base transition flex items-center justify-center gap-2">
                                <i class="fas fa-hand-holding-heart"></i>
                                <span>One-Time</span>
                            </button>
                            <button type="button"
                                @click="donationType = 'recurring'"
                                :class="donationType === 'recurring' ? 'bg-[#0F8B8D] text-white shadow-md font-bold' : 'text-gray-600 hover:text-gray-900 font-medium'"
                                class="flex-1 py-3 px-3 rounded-xl text-center text-sm sm:text-base transition flex items-center justify-center gap-2">
                                <i class="fas fa-sync-alt" :class="donationType === 'recurring' ? 'fa-spin' : ''"></i>
                                <span>Monthly AutoPay</span>
                                <span class="hidden sm:inline-block text-[10px] uppercase tracking-wider bg-[#F4A640] text-white px-2 py-0.5 rounded-full font-bold ml-1">Recurring</span>
                            </button>
                        </div>

                        <!-- Frequency Options (when Recurring is active) -->
                        <div x-show="donationType === 'recurring'" x-transition.opacity class="bg-[#F0FDFD] border border-[#CCFBF1] rounded-xl p-3 sm:p-4">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <label class="text-xs font-bold uppercase tracking-wider text-[#0F8B8D]">Frequency</label>
                                <span class="text-xs text-gray-500">Auto-debits on schedule</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <button type="button" @click="frequency = 'monthly'"
                                    :class="frequency === 'monthly' ? 'bg-[#0F8B8D] text-white font-bold shadow-sm' : 'bg-white text-gray-700 border border-gray-200'"
                                    class="py-2 px-2 rounded-lg text-xs sm:text-sm text-center transition">
                                    Monthly
                                </button>
                                <button type="button" @click="frequency = 'quarterly'"
                                    :class="frequency === 'quarterly' ? 'bg-[#0F8B8D] text-white font-bold shadow-sm' : 'bg-white text-gray-700 border border-gray-200'"
                                    class="py-2 px-2 rounded-lg text-xs sm:text-sm text-center transition">
                                    Quarterly (3M)
                                </button>
                                <button type="button" @click="frequency = 'yearly'"
                                    :class="frequency === 'yearly' ? 'bg-[#0F8B8D] text-white font-bold shadow-sm' : 'bg-white text-gray-700 border border-gray-200'"
                                    class="py-2 px-2 rounded-lg text-xs sm:text-sm text-center transition">
                                    Yearly (12M)
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-[#FFF8F1] rounded-[18px] border border-[#FEECDC] p-4 md:p-5">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h3 class="font-bold text-[#1F2937]">Select Amount</h3>
                            <span class="text-xs text-[#4B5563]">Minimum amount is ₹1</span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-5 gap-3">
                            <template x-for="preset in presets" :key="preset">
                                <button type="button"
                                    @click="setPresetAmount(preset)"
                                    :class="amount == preset && !customMode ? 'bg-[#F4A640] text-white shadow-lg shadow-[#F4A640]/25' : 'bg-white text-[#1F2937] hover:bg-[#FFF8F1] border border-gray-200'"
                                    class="py-3 rounded-xl font-semibold transition">
                                    ₹<span x-text="preset.toLocaleString('en-IN')"></span>
                                </button>
                            </template>
                            <button type="button"
                                @click="enableCustomAmount()"
                                :class="customMode ? 'bg-[#0F8B8D] text-white shadow-lg shadow-[#0F8B8D]/25' : 'bg-white text-[#1F2937] hover:bg-[#F0FDFD] border border-gray-200'"
                                class="py-3 rounded-xl font-semibold transition">
                                Custom
                            </button>
                        </div>

                        <input type="hidden" name="amount" :value="amount">

                        <div class="mt-4" x-show="customMode" x-transition.opacity>
                            <label class="block text-sm font-medium text-[#1F2937] mb-2">Custom Amount</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold">₹</span>
                                <input x-ref="customAmount" type="number" min="1" step="1" x-model="amount"
                                    class="w-full border border-gray-300 rounded-xl pl-8 pr-4 py-3.5 focus:ring-2 focus:ring-[#0F8B8D] focus:border-[#0F8B8D] outline-none"
                                    placeholder="Enter amount">
                            </div>
                        </div>

                        <div class="mt-4" x-show="!customMode" x-transition.opacity>
                            <p class="text-sm text-[#4B5563]">Current amount: <span class="font-bold text-[#1F2937]">₹<span x-text="Number(amount || 0).toLocaleString('en-IN')"></span></span></p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-[#1F2937] mb-2">Project (Optional)</label>
                        <select name="project_id" x-model="projectId"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-[#0F8B8D] focus:border-[#0F8B8D] outline-none">
                            <option value="">General Donation</option>
                            <?php foreach ($projects as $project): ?>
                                <option value="<?php echo (int) $project['id']; ?>"><?php echo htmlspecialchars($project['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-[#1F2937] mb-2">Full Name *</label>
                            <input type="text" name="name" x-model="donorName" required
                                class="w-full border border-gray-300 rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-[#0F8B8D] focus:border-[#0F8B8D] outline-none"
                                placeholder="Your full name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[#1F2937] mb-2">Email *</label>
                            <input type="email" name="email" x-model="donorEmail" required
                                class="w-full border border-gray-300 rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-[#0F8B8D] focus:border-[#0F8B8D] outline-none"
                                placeholder="you@example.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[#1F2937] mb-2">Mobile *</label>
                            <input type="tel" name="mobile" x-model="donorMobile" required
                                class="w-full border border-gray-300 rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-[#0F8B8D] focus:border-[#0F8B8D] outline-none"
                                placeholder="9876543210">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[#1F2937] mb-2">Payment Mode</label>
                            <input type="text" readonly
                                :value="paymentMode === 'razorpay' ? 'Online / Razorpay' : 'Manual / Scan & Transfer'"
                                class="w-full border border-gray-200 rounded-xl px-4 py-3.5 bg-gray-50 text-[#4B5563]">
                        </div>
                    </div>

                    <?php if ($is80gEnabled): ?>
                        <div class="bg-[#F0FDFD] border border-[#CCFBF1] rounded-[18px] p-4 md:p-5">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="wants_80g" value="1" x-model="wants80G"
                                    class="mt-1 h-5 w-5 rounded text-[#0F8B8D] focus:ring-[#0F8B8D] border-gray-300">
                                <span class="text-sm text-[#1F2937]">
                                    I want an 80G receipt.
                                    <span class="block text-xs text-[#4B5563] mt-1">PAN is required for 80G verification.</span>
                                </span>
                            </label>

                            <div class="mt-4" x-show="wants80G" x-transition.opacity>
                                <label class="block text-sm font-medium text-[#1F2937] mb-2">PAN Number *</label>
                                <input type="text" name="pan" x-model="donorPan" :required="wants80G" maxlength="10"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-3.5 uppercase focus:ring-2 focus:ring-[#0F8B8D] focus:border-[#0F8B8D] outline-none"
                                    placeholder="ABCDE1234F">
                            </div>
                        </div>
                    <?php endif; ?>

                    <div x-show="paymentMode === 'manual'" x-transition.opacity class="space-y-4">
                        <div class="bg-[#FFF8F1] border border-[#FEECDC] rounded-[18px] p-4 md:p-5">
                            <h3 class="font-bold text-[#1F2937]">Manual Payment Details</h3>
                            <p class="text-sm text-[#4B5563] mt-1">
                                If you have already paid by UPI or bank transfer, add the transaction details below and submit them for verification.
                            </p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-[#1F2937] mb-2">Transaction ID *</label>
                                    <input type="text" name="transaction_id" x-model="transactionId" :required="paymentMode === 'manual'"
                                        minlength="8" maxlength="50"
                                        class="w-full border border-gray-300 rounded-xl px-4 py-3.5 focus:ring-2 focus:ring-[#0F8B8D] focus:border-[#0F8B8D] outline-none"
                                        placeholder="Enter UPI / bank transaction ID">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-[#1F2937] mb-2">Payment Screenshot *</label>
                                    <input type="file" name="screenshot" :required="paymentMode === 'manual'" accept="image/*"
                                        class="w-full text-sm text-[#4B5563]">
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($memberReferralCode !== ''): ?>
                        <div class="text-xs text-[#0F8B8D] bg-[#F0FDFD] border border-[#CCFBF1] rounded-xl px-4 py-3">
                            Referral code applied: <span class="font-mono font-semibold"><?php echo htmlspecialchars($memberReferralCode); ?></span>
                        </div>
                    <?php endif; ?>

                    <template x-if="message">
                        <div class="rounded-xl px-4 py-3 text-sm font-medium"
                            :class="messageType === 'error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-[#F0FDFD] text-[#0F8B8D] border border-[#CCFBF1]'">
                            <span x-text="message"></span>
                        </div>
                    </template>

                    <button type="submit" :disabled="loading"
                        class="w-full rounded-full bg-[#F4A640] hover:bg-[#D98E2B] text-white font-bold py-4 shadow-lg shadow-[#F4A640]/25 transition disabled:opacity-60 disabled:cursor-not-allowed">
                        <span x-show="!loading && paymentMode === 'manual'">Submit Manual Details</span>
                        <span x-show="!loading && paymentMode === 'razorpay'">Pay with Razorpay</span>
                        <span x-show="loading">Please wait...</span>
                    </button>

                    <p class="text-center text-xs text-[#4B5563]">
                        Secure payment options supported: UPI, QR, bank transfer, cards, and net banking.
                    </p>
                </form>
            </div>

            <div class="space-y-6">

    <!-- Scan & Pay Card -->
    <div class="bg-white rounded-3xl shadow-2xl border border-gray-100 p-8 md:p-10">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-3xl font-bold text-gray-900">
                    Scan & Pay
                </h3>
                <p class="text-gray-500 mt-1">
                    Use QR Code or Bank Transfer for Manual Payments
                </p>
            </div>

            <span
                class="px-4 py-2 rounded-full bg-green-100 text-green-700 font-semibold text-sm">
                Manual
            </span>
        </div>

        <div x-data="{ tab:'qr' }">

            <!-- Tabs -->

            <div class="flex bg-gray-100 rounded-2xl p-1 mb-8">

                <button
                    @click="tab='qr'"
                    :class="tab=='qr' ? 'bg-white text-green-700 shadow font-semibold' : 'text-gray-500'"
                    class="flex-1 py-3 rounded-xl transition">

                    QR Codes

                </button>

                <button
                    @click="tab='bank'"
                    :class="tab=='bank' ? 'bg-white text-green-700 shadow font-semibold' : 'text-gray-500'"
                    class="flex-1 py-3 rounded-xl transition">

                    Bank Transfer

                </button>

            </div>


            <!-- QR Section -->

<div
    x-show="tab=='qr'"
    x-transition
    class="space-y-8">

    <?php if(!empty($qrs)): ?>
        <?php foreach($qrs as $qr): ?>

        <div class="bg-gray-50 border rounded-3xl p-8 md:p-12 text-center">

            <h4 class="text-2xl md:text-3xl font-bold text-gray-800 mb-8">
                <?php echo htmlspecialchars($qr['title'] ?? 'QR Code'); ?>
            </h4>

            <div class="flex justify-center items-center">

                <img
                    src="<?php echo htmlspecialchars($qr['qr_image_path']); ?>"
                    alt="QR Code"
                    class="block mx-auto
                           w-full
                           max-w-[900px]
                           md:max-w-[1000px]
                           lg:max-w-[1100px]
                           h-auto
                           rounded-3xl
                           shadow-2xl">

            </div>

        </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="border border-dashed rounded-2xl p-10 text-center text-gray-500">
            QR Code not available.
        </div>

    <?php endif; ?>

</div>


            <!-- Bank Transfer -->

            <div
                x-show="tab=='bank'"
                x-transition
                class="space-y-5">

                <?php if(!empty($banks)): ?>

                    <?php foreach($banks as $bank): ?>

                    <div class="bg-gray-50 rounded-2xl border p-6">

                        <h3 class="text-2xl font-bold mb-4">

                            <?php echo htmlspecialchars($bank['bank_name']); ?>

                        </h3>

                        <div class="space-y-3 text-gray-700">

                            <p>

                                <strong>Account Holder :</strong>

                                <?php echo htmlspecialchars($bank['account_holder']); ?>

                            </p>

                            <p>

                                <strong>Account Number :</strong>

                                <?php echo htmlspecialchars($bank['account_number']); ?>

                            </p>

                            <p>

                                <strong>IFSC :</strong>

                                <?php echo htmlspecialchars($bank['ifsc_code']); ?>

                            </p>

                        </div>

                    </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="border border-dashed rounded-2xl p-10 text-center text-gray-500">

                        Bank details not available.

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>
        </div>
    </div>
</div>

<script>
function donationPage() {
    return {
        presets: [100, 250, 500, 1000, 2500],
        amount: 500,
        customMode: false,
        donationType: 'one_time',
        frequency: 'monthly',
        projectId: '',
        donorName: '',
        donorEmail: '',
        donorMobile: '',
        donorPan: '',
        transactionId: '',
        referralCode: <?php echo json_encode($memberReferralCode, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
        wants80G: false,
        paymentMode: 'razorpay',
        loading: false,
        message: '',
        messageType: 'error',
        razorpay: { order_id: '', payment_id: '', signature: '', subscription_id: '' },

        setPresetAmount(value) {
            this.amount = value;
            this.customMode = false;
        },

        enableCustomAmount() {
            this.customMode = true;
            this.amount = '';
            this.$nextTick(() => {
                if (this.$refs.customAmount) {
                    this.$refs.customAmount.focus();
                }
            });
        },

        validateOnline() {
            const amount = Math.round(Number(this.amount));
            if (!amount || amount < 1) {
                this.message = 'Donation amount must be at least ₹1.';
                this.messageType = 'error';
                return false;
            }
            if (!this.donorName.trim()) {
                this.message = 'Name is required.';
                this.messageType = 'error';
                return false;
            }
            if (!this.donorEmail.trim() || !this.donorEmail.includes('@')) {
                this.message = 'A valid email is required.';
                this.messageType = 'error';
                return false;
            }
            const mobile = (this.donorMobile || '').trim();
            if (!mobile || !/^[0-9]{10}$/.test(mobile)) {
                this.message = 'A valid 10-digit mobile number is required.';
                this.messageType = 'error';
                return false;
            }
            if (this.wants80G) {
                const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
                if (!panRegex.test((this.donorPan || '').toUpperCase())) {
                    this.message = 'A valid PAN format (e.g. ABCDE1234F) is required for 80G receipt.';
                    this.messageType = 'error';
                    return false;
                }
            }
            this.message = '';
            return true;
        },

        validateManual() {
            const amount = Math.round(Number(this.amount));
            if (!amount || amount < 1) {
                this.message = 'Donation amount must be at least ₹1.';
                this.messageType = 'error';
                return false;
            }
            if (!this.donorName.trim()) {
                this.message = 'Name is required.';
                this.messageType = 'error';
                return false;
            }
            if (!this.donorEmail.trim() || !this.donorEmail.includes('@')) {
                this.message = 'A valid email is required.';
                this.messageType = 'error';
                return false;
            }
            const mobile = (this.donorMobile || '').trim();
            if (!mobile || !/^[0-9]{10}$/.test(mobile)) {
                this.message = 'A valid 10-digit mobile number is required.';
                this.messageType = 'error';
                return false;
            }
            if (this.wants80G) {
                const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
                if (!panRegex.test((this.donorPan || '').toUpperCase())) {
                    this.message = 'A valid PAN format (e.g. ABCDE1234F) is required for 80G receipt.';
                    this.messageType = 'error';
                    return false;
                }
            }
            const txnId = (this.transactionId || '').trim();
            if (!txnId) {
                this.message = 'Transaction ID is required.';
                this.messageType = 'error';
                return false;
            }
            if (!/^[a-zA-Z0-9]{8,50}$/.test(txnId)) {
                this.message = 'Transaction ID must be between 8 and 50 alphanumeric characters.';
                this.messageType = 'error';
                return false;
            }
            this.message = '';
            return true;
        },

        async postJson(url, formData) {
            const response = await fetch(url, { method: 'POST', body: formData });
            const raw = await response.text();
            try {
                return JSON.parse(raw);
            } catch (e) {
                throw new Error('BAD_JSON');
            }
        },

        async handleSubmit(event) {
            if (this.paymentMode === 'manual') {
                if (!this.validateManual()) {
                    event.preventDefault();
                    return;
                }
                return;
            }

            event.preventDefault();
            if (!this.validateOnline()) {
                return;
            }

            this.loading = true;

            try {
                const payload = new FormData();
                payload.set('csrf_token', <?php echo json_encode($csrfToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
                payload.set('amount', String(Math.round(Number(this.amount))));
                payload.set('project_id', this.projectId || '');
                payload.set('name', this.donorName.trim());
                payload.set('email', this.donorEmail.trim());
                payload.set('mobile', this.donorMobile.trim());
                payload.set('pan', (this.donorPan || '').toUpperCase());
                payload.set('wants_80g', this.wants80G ? '1' : '0');
                payload.set('referral_code', this.referralCode || '');

                const self = this;

                // ── CASE A: RECURRING DONATION (AUTOPAY MANDATE) ────────
                if (this.donationType === 'recurring') {
                    payload.set('frequency', this.frequency || 'monthly');
                    const subData = await this.postJson('process/create_recurring_subscription.php', payload);
                    if (!subData.success) {
                        this.message = subData.message || 'Unable to initialize Recurring Subscription.';
                        this.messageType = 'error';
                        this.loading = false;
                        return;
                    }

                    const options = {
                        key: subData.key,
                        subscription_id: subData.subscription_id,
                        name: subData.company_name || 'NGO Foundation',
                        description: subData.description || 'Recurring Contribution',
                        prefill: {
                            name: this.donorName.trim(),
                            email: this.donorEmail.trim(),
                            contact: this.donorMobile.trim()
                        },
                        theme: { color: '#0F8B8D' },
                        handler: async function(response) {
                            self.loading = true;
                            try {
                                const verifyPayload = new FormData();
                                verifyPayload.set('razorpay_payment_id', response.razorpay_payment_id || '');
                                verifyPayload.set('razorpay_subscription_id', response.razorpay_subscription_id || subData.subscription_id);
                                verifyPayload.set('razorpay_signature', response.razorpay_signature || '');
                                verifyPayload.set('recurring_id', subData.recurring_id || '');

                                const verifyData = await self.postJson('process/verify_recurring_subscription.php', verifyPayload);
                                if (verifyData.success) {
                                    const target = verifyData.redirect || ('thankyou.php?donation_id=' + verifyData.donation_id + '&recurring=1');
                                    window.location.href = new URL(target, window.location.href).toString();
                                    return;
                                }

                                self.message = verifyData.message || 'Recurring subscription verification failed.';
                                self.messageType = 'error';
                            } catch (err) {
                                console.error(err);
                                self.message = 'Verification error. Your subscription mandate may be active. Please check your email or contact support.';
                                self.messageType = 'error';
                            } finally {
                                self.loading = false;
                            }
                        },
                        modal: {
                            ondismiss: function() {
                                self.loading = false;
                                self.message = 'AutoPay setup was cancelled. You can try again anytime.';
                                self.messageType = 'error';
                            }
                        }
                    };

                    const rzp = new Razorpay(options);
                    rzp.on('payment.failed', function(response) {
                        self.loading = false;
                        self.message = 'AutoPay setup failed. ' + (response?.error?.description || 'Please try again.');
                        self.messageType = 'error';
                    });
                    rzp.open();
                    this.loading = false;
                    return;
                }

                // ── CASE B: ONE-TIME DONATION (STANDARD ORDER) ──────────
                const orderData = await this.postJson('process/create_razorpay_order.php', payload);
                if (!orderData.success) {
                    this.message = orderData.message || 'Unable to initialize Razorpay payment.';
                    this.messageType = 'error';
                    this.loading = false;
                    return;
                }

                const options = {
                    key: orderData.key_id,
                    amount: orderData.amount,
                    currency: orderData.currency || 'INR',
                    name: orderData.company || 'NGO',
                    description: 'One-Time Donation',
                    order_id: orderData.order_id,
                    prefill: {
                        name: this.donorName.trim(),
                        email: this.donorEmail.trim(),
                        contact: this.donorMobile.trim()
                    },
                    theme: { color: '#16a34a' },
                    handler: async function(response) {
                        self.loading = true;
                        try {
                            const verifyPayload = new FormData();
                            verifyPayload.set('razorpay_payment_id', response.razorpay_payment_id || '');
                            verifyPayload.set('razorpay_order_id', response.razorpay_order_id || orderData.order_id);
                            verifyPayload.set('razorpay_signature', response.razorpay_signature || '');
                            verifyPayload.set('donation_id', orderData.donation_id || '');

                            const verifyData = await self.postJson('process/verify_razorpay_payment.php', verifyPayload);
                            if (verifyData.success) {
                                const target = verifyData.redirect || ('thankyou.php?donation_id=' + orderData.donation_id);
                                window.location.href = new URL(target, window.location.href).toString();
                                return;
                            }

                            self.message = verifyData.message || 'Payment verification failed.';
                            self.messageType = 'error';
                        } catch (err) {
                            console.error(err);
                            self.message = String(err && err.message).includes('BAD_JSON')
                                ? 'Could not verify payment (unexpected server response).'
                                : 'Verification error. Your payment may have gone through. Please contact support.';
                            self.messageType = 'error';
                        } finally {
                            self.loading = false;
                        }
                    },
                    modal: {
                        ondismiss: function() {
                            self.loading = false;
                            self.message = 'Payment was cancelled. You can try again.';
                            self.messageType = 'error';
                        }
                    }
                };

                const rzp = new Razorpay(options);
                rzp.on('payment.failed', function(response) {
                    self.loading = false;
                    self.message = 'Razorpay payment failed. ' + (response?.error?.description || 'Please try again.');
                    self.messageType = 'error';
                });
                rzp.open();
                this.loading = false;
            } catch (err) {
                console.error(err);
                this.loading = false;
                this.message = 'Could not start Razorpay checkout.';
                this.messageType = 'error';
            }
        }
    };
}
</script>

<?php require 'includes/footer.php'; ?>
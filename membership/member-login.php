<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['member_logged_in'])) {
    header('Location: member-dashboard.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.otp-btn-primary {
    display: flex !important;
    width: 100%;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background-color: #1d4ed8 !important;
    color: #ffffff !important;
    font-weight: 700;
    font-size: 1rem;
    padding: 14px 24px;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(29,78,216,0.4);
    transition: background-color 0.2s ease;
    margin-top: 8px;
}
.otp-btn-primary:hover {
    background-color: #1e40af !important;
}
.otp-btn-primary:active {
    background-color: #1e3a8a !important;
}
.otp-btn-primary:disabled {
    background-color: #93c5fd !important;
    cursor: not-allowed;
    box-shadow: none;
}
.otp-spinner {
    display: inline-block;
    width: 18px;
    height: 18px;
    border: 3px solid rgba(255,255,255,0.3);
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
    flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<div class="min-h-screen py-14 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900"
    x-data="memberLogin()">
    <div class="max-w-lg mx-auto">
        <div class="bg-white dark:bg-gray-800 shadow-2xl rounded-3xl p-8 md:p-10 border border-gray-100 dark:border-gray-700">

            <!-- Header -->
            <div class="text-center">
                <div class="w-16 h-16 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center mx-auto mb-5 ring-8 ring-blue-50 dark:ring-blue-900/30">
                    <i class="fa-solid fa-user-shield text-2xl"></i>
                </div>
                <h1 class="text-3xl font-extrabold text-gray-900 text-black dark:text-black">Member Login</h1>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">Secure OTP login for active members.</p>
            </div>

            <div class="mt-8 space-y-6">

                <!-- Step 1: Enter Details -->
                <template x-if="state === 'enter_details'">
                    <form @submit.prevent="sendOtp" class="space-y-5">

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Member No</label>
                            <input type="text" x-model.trim="memberNo" required
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-black dark:text-black focus:ring-2 focus:ring-blue-500 outline-none transition"
                                placeholder="e.g. MEM-00123">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Registered Email</label>
                            <input type="email" x-model.trim="email" required
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-black dark:text-black focus:ring-2 focus:ring-blue-500 outline-none transition"
                                placeholder="your.email@example.com">
                        </div>

                        <button type="submit" class="otp-btn-primary" :disabled="loading">
                            <span class="otp-spinner" x-show="loading"></span>
                            <i class="fa-solid fa-paper-plane" x-show="!loading"></i>
                            <span x-text="loading ? 'Sending...' : 'Send OTP'">Send OTP</span>
                        </button>

                        <p x-show="message" x-text="message" class="text-sm font-medium text-center"
                            :class="messageType === 'error' ? 'text-red-600' : 'text-emerald-600'"></p>
                    </form>
                </template>

                <!-- Step 2: Enter OTP -->
                <template x-if="state === 'enter_otp'">
                    <form @submit.prevent="verifyOtp" class="space-y-5">

                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-4 text-center">
                            <p class="text-sm text-gray-700 dark:text-gray-200">
                                We sent a 6-digit OTP to<br>
                                <b class="text-blue-700 dark:text-blue-300" x-text="email"></b>
                            </p>
                            <p class="text-xs text-gray-400 mt-1">Valid for 5 minutes.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Enter OTP</label>
                            <input type="text" inputmode="numeric" maxlength="6" x-model.trim="otp" required
                                class="w-full px-4 py-4 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-black dark:text-black focus:ring-2 focus:ring-blue-500 outline-none transition tracking-[0.5em] text-center text-2xl font-extrabold"
                                placeholder="------">
                        </div>

                        <button type="submit" class="otp-btn-primary" :disabled="loading || otp.length !== 6">
                            <span class="otp-spinner" x-show="loading"></span>
                            <i class="fa-solid fa-circle-check" x-show="!loading"></i>
                            <span x-text="loading ? 'Verifying...' : 'Verify & Continue'">Verify &amp; Continue</span>
                        </button>

                        <div class="flex items-center justify-between pt-1">
                            <button type="button"
                                class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white hover:underline transition"
                                @click="backToDetails()">
                                <i class="fa-solid fa-arrow-left text-xs mr-1"></i> Change details
                            </button>
                            <button type="button"
                                class="text-sm text-blue-700 dark:text-blue-400 hover:underline transition"
                                @click="sendOtp(true)">
                                <i class="fa-solid fa-rotate-right text-xs mr-1"></i> Resend OTP
                            </button>
                        </div>

                        <p x-show="message" x-text="message" class="text-sm font-medium text-center"
                            :class="messageType === 'error' ? 'text-red-600' : 'text-emerald-600'"></p>
                    </form>
                </template>

            </div>
        </div>
    </div>
</div>

<script>
function memberLogin() {
    return {
        state: 'enter_details',
        loading: false,
        email: '',
        memberNo: '',
        otp: '',
        message: '',
        messageType: 'error',

        backToDetails() {
            this.state = 'enter_details';
            this.otp = '';
            this.message = '';
        },

        async sendOtp(isResend = false) {
            if (!this.email || !this.memberNo) return;
            this.loading = true;
            this.message = '';
            try {
                const res = await fetch('process/send_member_otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ email: this.email, member_no: this.memberNo })
                });
                const data = await res.json();
                this.message = data.message || (data.success ? 'OTP sent successfully.' : 'Unable to send OTP.');
                this.messageType = data.success ? 'success' : 'error';
                if (data.success && data.otp_sent) {
                    this.state = 'enter_otp';
                    if (!isResend) this.otp = '';
                }
            } catch (e) {
                this.message = 'Unexpected error. Please try again.';
                this.messageType = 'error';
            } finally {
                this.loading = false;
            }
        },

        async verifyOtp() {
            if (!this.email || !this.memberNo || this.otp.length !== 6) return;
            this.loading = true;
            this.message = '';
            try {
                const res = await fetch('process/verify_member_otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ email: this.email, member_no: this.memberNo, otp: this.otp })
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = data.redirect || 'member-dashboard.php';
                    return;
                }
                this.message = data.message || 'Invalid or expired OTP.';
                this.messageType = 'error';
            } catch (e) {
                this.message = 'Unexpected error. Please try again.';
                this.messageType = 'error';
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
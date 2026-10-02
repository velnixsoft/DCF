<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!empty($_SESSION['volunteer_logged_in'])) {
    header('Location: volunteer-dashboard.php');
    exit;
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-screen py-14 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900"
    x-data="volunteerLogin()">
    <div class="max-w-lg mx-auto">
        <div class="bg-white dark:bg-gray-800 shadow-2xl rounded-3xl p-8 md:p-10 border border-gray-100 dark:border-gray-700">
            <div class="text-center">
                <div class="w-16 h-16 bg-green-100 text-green-700 rounded-full flex items-center justify-center mx-auto mb-5 ring-8 ring-green-50 dark:ring-green-900/30">
                    <i class="fa-solid fa-hands-helping text-2xl"></i>
                </div>
                <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">Volunteer Login</h1>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">Secure OTP login for active volunteers.</p>
            </div>

            <div class="mt-8 space-y-6">
                <template x-if="state === 'enter_email'">
                    <form @submit.prevent="sendOtp" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Registered Email</label>
                            <input type="email" x-model.trim="email" required
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-black dark:text-black focus:ring-2 focus:ring-green-500 outline-none transition"
                                placeholder="your.email@example.com">
                        </div>

                        <button type="submit" :disabled="loading"
                            class="w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white py-3.5 rounded-xl font-bold shadow-xl hover:shadow-2xl transition disabled:opacity-60 flex items-center justify-center">
                            <span x-show="!loading">Send OTP</span>
                            <span x-show="loading" class="inline-flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Sending...
                            </span>
                        </button>

                        <p x-show="message" x-text="message" class="text-sm font-medium"
                            :class="messageType === 'error' ? 'text-red-600' : 'text-emerald-600'"></p>

                        <div class="pt-2 text-sm text-gray-600 dark:text-gray-300">
                            <a href="volunteer-verify.php" class="text-green-700 dark:text-green-300 hover:underline">Verify volunteer ID</a>
                        </div>
                    </form>
                </template>

                <template x-if="state === 'enter_otp'">
                    <form @submit.prevent="verifyOtp" class="space-y-4" x-cloak>
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-900 rounded-2xl p-4">
                            <p class="text-sm text-gray-700 dark:text-gray-200">
                                We sent a 6-digit OTP to <b x-text="email"></b>. It is valid for 5 minutes.
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">OTP</label>
                            <input type="text" inputmode="numeric" maxlength="6" x-model.trim="otp" required
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-black dark:text-black focus:ring-2 focus:ring-green-500 outline-none transition tracking-[0.35em] text-center text-xl font-extrabold"
                                placeholder="••••••">
                        </div>

                        <button type="submit" :disabled="loading || otp.length !== 6"
                            class="w-full bg-[#1F4D3A] hover:bg-emerald-700 text-black py-3.5 rounded-xl font-bold shadow-xl hover:shadow-2xl transition disabled:opacity-60 flex items-center justify-center">
                            <span x-show="!loading">Verify & Continue</span>
                            <span x-show="loading" class="inline-flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Verifying...
                            </span>
                        </button>

                        <div class="flex items-center justify-between text-sm">
                            <button type="button" class="text-gray-600 dark:text-gray-300 hover:underline" @click="backToEmail()">Change email</button>
                            <button type="button" class="text-green-700 dark:text-green-300 hover:underline" @click="sendOtp(true)">Resend OTP</button>
                        </div>

                        <p x-show="message" x-text="message" class="text-sm font-medium"
                            :class="messageType === 'error' ? 'text-red-600' : 'text-emerald-600'"></p>
                    </form>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function volunteerLogin() {
    return {
        state: 'enter_email',
        loading: false,
        email: '',
        otp: '',
        message: '',
        messageType: 'error',

        backToEmail() {
            this.state = 'enter_email';
            this.otp = '';
            this.message = '';
        },

        async sendOtp(isResend = false) {
            if (!this.email) return;
            this.loading = true;
            this.message = '';

            try {
                const res = await fetch('process/send_volunteer_otp.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({ email: this.email })
                });
                const data = await res.json();
                this.message = data.message || (data.success ? 'OTP sent.' : 'Unable to send OTP.');
                this.messageType = (data.success ? 'success' : 'error');

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
            if (!this.email || this.otp.length !== 6) return;
            this.loading = true;
            this.message = '';

            try {
                const res = await fetch('process/verify_volunteer_otp.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({ email: this.email, otp: this.otp })
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = data.redirect || 'volunteer-dashboard.php';
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>


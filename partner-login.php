<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

if (!empty($_SESSION['partner_logged_in'])) {
    header('Location: partner-dashboard.php');
    exit;
}
?>

<div class="min-h-screen py-14 px-4 bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900" x-data="partnerLogin()">
    <div class="max-w-lg mx-auto">
        <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-10 border border-gray-100">
            <div class="text-center">
                <div class="w-16 h-16 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center mx-auto mb-5">
                    <i class="fa-solid fa-handshake text-2xl"></i>
                </div>
                <h1 class="text-3xl font-extrabold text-gray-900">Partner Portal Login</h1>
                <p class="text-sm text-gray-600 mt-2">Access your business dashboard after admin approval.</p>
            </div>

            <form @submit.prevent="login" class="mt-8 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Owner Email</label>
                    <input type="email" x-model.trim="email" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="owner@business.com">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                    <input type="password" x-model="password" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Your password">
                </div>
                <p x-show="message" x-text="message" class="text-sm font-medium" :class="messageType === 'error' ? 'text-red-600' : 'text-emerald-600'"></p>
                <button type="submit" :disabled="loading" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3.5 rounded-xl font-bold disabled:opacity-60">
                    <span x-show="!loading">Login</span>
                    <span x-show="loading">Signing in...</span>
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-gray-600">
                <a href="partner-register.php" class="text-blue-700 font-semibold hover:underline">New partner? Register here</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('partnerLogin', () => ({
        email: '',
        password: '',
        loading: false,
        message: '',
        messageType: 'error',
        login() {
            this.loading = true;
            this.message = '';
            const body = new FormData();
            body.append('email', this.email);
            body.append('password', this.password);
            fetch('process/verify_partner_login.php', { method: 'POST', body })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect || 'partner-dashboard.php';
                    } else {
                        this.message = data.message;
                        this.messageType = 'error';
                    }
                })
                .catch(() => {
                    this.message = 'Login failed. Please try again.';
                    this.messageType = 'error';
                })
                .finally(() => { this.loading = false; });
        }
    }));
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

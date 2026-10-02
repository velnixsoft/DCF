<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
$studentLoginCsrf = generateCsrfToken();

if (!empty($_SESSION['student_logged_in'])) {
    header('Location: student-dashboard.php');
    exit;
}
?>

<div class="min-h-screen py-16 px-4 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-emerald-50 via-slate-50 to-slate-100 flex items-center justify-center font-sans" x-data="studentLogin()" x-cloak>
    <div class="max-w-md w-full">
        <!-- Main Login Card -->
        <div class="bg-white/90 backdrop-blur-xl shadow-2xl rounded-[2.5rem] p-8 md:p-12 border border-slate-200/60 relative overflow-hidden">
            <!-- Top brand border accent -->
            <div class="absolute top-0 left-0 right-0 h-[5px] bg-gradient-to-r from-emerald-400 via-emerald-600 to-green-600"></div>
            
            <div class="text-center relative">
                <!-- Outer glowing logo container -->
                <div class="w-16 h-16 bg-gradient-to-br from-emerald-50 to-green-100/50 text-emerald-700 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-md shadow-emerald-500/10 ring-4 ring-emerald-50/70 border border-emerald-200/40 relative">
                    <span class="absolute inset-0 rounded-2xl border-2 border-emerald-500/10 animate-ping opacity-75"></span>
                    <i class="fa-solid fa-user-shield text-2xl relative z-10"></i>
                </div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Ambassador Login</h1>
                <p class="text-xs font-semibold text-slate-400 mt-2.5 uppercase tracking-wider">Student & Intern Portal</p>
            </div>

            <form @submit.prevent="handleLogin" class="mt-8 space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($studentLoginCsrf); ?>">
                
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Email or Mobile Number</label>
                    <div class="relative group">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-600 transition-colors">
                            <i class="fa-solid fa-envelope text-sm"></i>
                        </span>
                        <input type="text" x-model.trim="login_id" required
                            class="w-full pl-11 pr-4 py-3.5 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition duration-150 text-sm font-semibold text-slate-800 placeholder:text-slate-400"
                            placeholder="you@example.com or 10-digit mobile">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Password</label>
                    </div>
                    <div class="relative group">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-600 transition-colors">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </span>
                        <input type="password" x-model="password" required
                            class="w-full pl-11 pr-4 py-3.5 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition duration-150 text-sm font-semibold text-slate-800 placeholder:text-slate-400"
                            placeholder="Enter password">
                    </div>
                </div>

                <!-- Error alert message container -->
                <div x-show="message" x-transition
                     :class="messageType === 'error' ? 'bg-rose-50/70 text-rose-800 border-rose-100' : 'bg-emerald-50/70 text-emerald-800 border-emerald-100'"
                     class="p-4 rounded-2xl border text-xs font-bold flex items-center gap-2">
                    <i class="fa-solid" :class="messageType === 'error' ? 'fa-circle-exclamation text-rose-600' : 'fa-circle-check text-emerald-600'"></i>
                    <span x-text="message"></span>
                </div>

                <!-- Premium button with subtle animation -->
                <button type="submit" :disabled="loading"
                    class="w-full bg-green-600 hover:from-emerald-700 hover:to-green-700 text-white py-3.5 rounded-2xl font-bold shadow-lg shadow-emerald-500/10 hover:shadow-xl hover:shadow-emerald-500/20 active:scale-[0.98] transition-all duration-150 disabled:opacity-60 flex items-center justify-center text-sm">
                    <span x-show="!loading" class="tracking-wide">Sign In</span>
                    <span x-show="loading" class="inline-flex items-center gap-2 font-semibold">
                        <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Verifying details...
                    </span>
                </button>

                <!-- Divider line -->
                <div class="flex items-center gap-3 py-1">
                    <div class="h-[1px] bg-slate-200 flex-1"></div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Join Us</span>
                    <div class="h-[1px] bg-slate-200 flex-1"></div>
                </div>

                <div class="text-center text-xs text-slate-500 font-semibold">
                    New to the program? <a href="student-register.php" class="text-emerald-600 hover:text-emerald-700 transition font-bold underline decoration-2 decoration-emerald-600/20 hover:decoration-emerald-600/50">Apply here</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function studentLogin() {
    return {
        login_id: '',
        password: '',
        loading: false,
        message: '',
        messageType: 'error',

        async handleLogin() {
            if (!this.login_id || !this.password) return;
            this.loading = true;
            this.message = '';

            try {
                const form = this.$el.querySelector('form') || document.querySelector('form');
                const csrf = form?.querySelector('input[name="csrf_token"]')?.value || '';
                const res = await fetch('process/verify_student_login.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({ 
                        login_id: this.login_id, 
                        password: this.password,
                        csrf_token: csrf
                    })
                });
                const data = await res.json();
                
                if (data.success) {
                    this.message = 'Verification successful. Redirecting...';
                    this.messageType = 'success';
                    setTimeout(() => {
                        window.location.href = data.redirect || 'student-dashboard.php';
                    }, 800);
                } else {
                    this.message = data.message || 'Invalid login details.';
                    this.messageType = 'error';
                    this.loading = false;
                }
            } catch (e) {
                this.message = 'Unexpected error. Please try again.';
                this.messageType = 'error';
                this.loading = false;
            }
        }
    };
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

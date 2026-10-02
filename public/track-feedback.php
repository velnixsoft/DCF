<?php
// ============================================================
// public/track-feedback.php
// Public Track Feedback & Suggestion Status Portal
// Enables Members, Volunteers, Employees to track reviews and replies
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

$queryTicket = cleanInput($_GET['ticket'] ?? ($_GET['feedback_no'] ?? ''));
$initialTicketData = null;
$searchError = '';

if (!empty($queryTicket)) {
    $stmt = $pdo->prepare("
        SELECT 
            f.*, 
            u.name AS replied_by_name, u.role AS replied_by_role
        FROM `feedbacks` f
        LEFT JOIN `users` u ON f.reply_by = u.id
        WHERE UPPER(TRIM(f.feedback_no)) = UPPER(?)
        LIMIT 1
    ");
    $stmt->execute([$queryTicket]);
    $initialTicketData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$initialTicketData) {
        $searchError = "No feedback record found for reference '{$queryTicket}'. Please verify your reference number.";
    } elseif ($initialTicketData['is_anonymous']) {
        $initialTicketData['name'] = 'Anonymous Contributor (' . ucfirst($initialTicketData['submitter_type']) . ')';
    }
}

$siteName = (string)($settings['site_name'] ?? 'Jaysmrutti Foundation');
$ngoPhone = (string)($settings['ngo_phone'] ?? '+91 7651910331');
$ngoEmail = (string)($settings['ngo_email'] ?? 'info@velnixsoft.com');
?>

<div class="min-h-screen py-12 md:py-16 px-4 sm:px-6 lg:px-8 bg-slate-50 dark:bg-gray-900 relative overflow-hidden" 
     x-data="trackFeedbackApp(<?php echo htmlspecialchars(json_encode($initialTicketData), ENT_QUOTES, 'UTF-8'); ?>, '<?php echo htmlspecialchars(addslashes($searchError)); ?>', '<?php echo htmlspecialchars(addslashes($queryTicket)); ?>')" 
     x-cloak>

    <!-- Background decorative blur blobs -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[5%] left-[-10%] w-[320px] sm:w-[500px] h-[320px] sm:h-[500px] rounded-full bg-sky-100/40 dark:bg-sky-950/20 blur-[90px] sm:blur-[130px] transition-all duration-1000"></div>
        <div class="absolute bottom-[15%] right-[-10%] w-[350px] sm:w-[550px] h-[350px] sm:h-[550px] rounded-full bg-emerald-100/40 dark:bg-emerald-950/20 blur-[90px] sm:blur-[140px] transition-all duration-1000"></div>
    </div>

    <div class="max-w-3xl mx-auto relative z-10 space-y-8">

        <!-- 1. HEADER & SEARCH HERO BOX -->
        <div class="text-center space-y-3">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-sky-50 dark:bg-sky-900/40 text-[#1070B0] dark:text-sky-300 border border-sky-200/60 dark:border-sky-800/40 uppercase tracking-wider shadow-sm">
                <i class="fa-solid fa-magnifying-glass-location"></i> Live Feedback Status
            </span>
            <h1 class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-white tracking-tight">
                Track Your <span class="text-[#1070B0]">Feedback & Suggestions</span>
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 max-w-lg mx-auto leading-relaxed">
                Enter your unique feedback reference ID to check the review timeline, committee comments, and action taken.
            </p>
        </div>

        <!-- Search Form Card -->
        <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-md shadow-xl border border-slate-200 dark:border-gray-700 rounded-3xl p-6 sm:p-8">
            <form @submit.prevent="searchTicket()" class="space-y-4">
                <label for="ticketInput" class="block text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                    Enter Feedback Reference Number
                </label>
                
                <div class="flex flex-col sm:flex-row items-stretch gap-3">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 text-base">
                            <i class="fa-solid fa-hashtag"></i>
                        </span>
                        <input type="text" id="ticketInput" x-model="ticketQuery" @input="ticketQuery = ticketQuery.toUpperCase()" required 
                               placeholder="e.g. FB-2026-0001" 
                               class="w-full pl-11 pr-10 py-3.5 bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 rounded-2xl focus:bg-white dark:focus:bg-gray-900 focus:ring-4 focus:ring-[#1070B0]/10 focus:border-[#1070B0] outline-none text-gray-900 dark:text-white font-mono font-bold text-sm tracking-wide uppercase transition-all placeholder:normal-case placeholder:font-sans placeholder:font-normal">
                        <button type="button" x-show="ticketQuery" @click="ticketQuery = ''; ticket = null; errorMessage = '';" class="absolute right-3.5 top-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-white text-sm">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </button>
                    </div>

                    <button type="submit" :disabled="loading" 
                            class="py-3.5 px-7 bg-[#1070B0] hover:bg-[#0c598d] text-white font-bold text-xs sm:text-sm rounded-2xl shadow-md transition duration-200 transform active:scale-95 flex items-center justify-center gap-2 flex-shrink-0 disabled:opacity-70 disabled:cursor-not-allowed">
                        <span x-show="!loading" class="flex items-center gap-1.5">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <span>Track Status</span>
                        </span>
                        <span x-show="loading" class="flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-notch fa-spin"></i>
                            <span>Checking...</span>
                        </span>
                    </button>
                </div>

                <div class="flex items-center justify-between text-[11px] text-gray-400 pt-1">
                    <span>Format: <code class="font-mono text-[#1070B0] dark:text-sky-400 font-bold">FB-YYYY-XXXX</code></span>
                    <a href="feedback.php" class="text-[#1070B0] hover:underline font-semibold flex items-center gap-1">
                        <i class="fa-solid fa-plus-circle"></i> Submit New Feedback
                    </a>
                </div>
            </form>

            <!-- Error Banner -->
            <div x-show="errorMessage" x-transition class="mt-4 p-4 rounded-2xl bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 text-xs flex items-center gap-2.5">
                <i class="fa-solid fa-circle-exclamation text-base shrink-0"></i>
                <span x-text="errorMessage"></span>
            </div>
        </div>


        <!-- 2. STATUS DETAILS DISPLAY CARD -->
        <div x-show="ticket" x-transition class="space-y-6">

            <div class="bg-white dark:bg-gray-800 shadow-xl border border-slate-200 dark:border-gray-700 rounded-3xl overflow-hidden">
                
                <!-- Ticket Header Banner -->
                <div class="p-6 sm:p-8 bg-gradient-to-r from-slate-900 to-slate-800 text-white relative">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-mono font-bold bg-white/20 text-white border border-white/20 tracking-wider uppercase" x-text="ticket ? ticket.feedback_no : ''"></span>
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-sky-500/20 text-sky-200 border border-sky-400/30">
                                    <i class="fa-solid fa-user-tag text-[10px]"></i> <span x-text="ticket ? (ticket.submitter_type ? ticket.submitter_type.toUpperCase() : 'MEMBER') : ''"></span>
                                </span>
                                <template x-if="ticket && ticket.is_anonymous == 1">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-amber-500/20 text-amber-200 border border-amber-400/30">
                                        <i class="fa-solid fa-user-secret"></i> Anonymous
                                    </span>
                                </template>
                            </div>

                            <h2 class="text-xl sm:text-2xl font-black text-white" x-text="ticket ? ticket.subject : ''"></h2>
                            <p class="text-xs text-slate-300 mt-1">
                                Submitted on <span class="font-semibold text-white" x-text="formatDate(ticket ? ticket.created_at : '')"></span>
                            </p>
                        </div>

                        <!-- Status Badge Pill -->
                        <div class="shrink-0">
                            <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-2xl text-xs font-bold uppercase tracking-wider shadow-sm"
                                  :class="getStatusBadgeClass(ticket ? ticket.status : '')">
                                <i :class="getStatusIcon(ticket ? ticket.status : '')"></i>
                                <span x-text="getStatusLabel(ticket ? ticket.status : '')"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 4-Step Visual Progress Bar -->
                <div class="p-6 sm:p-8 border-b border-slate-100 dark:border-gray-700 bg-slate-50/50 dark:bg-gray-900/40">
                    <div class="relative flex justify-between items-center max-w-xl mx-auto">
                        
                        <!-- Progress line background -->
                        <div class="absolute top-1/2 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 -translate-y-1/2 z-0"></div>
                        <!-- Progress line active -->
                        <div class="absolute top-1/2 left-0 h-1 bg-emerald-500 -translate-y-1/2 z-0 transition-all duration-500"
                             :style="'width: ' + getProgressWidth(ticket ? ticket.status : '') + '%'"></div>

                        <!-- Step 1: Submitted -->
                        <div class="relative z-10 flex flex-col items-center">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold transition-all shadow-sm"
                                 :class="isStepActive(ticket ? ticket.status : '', 1) ? 'bg-emerald-500 text-white ring-4 ring-emerald-100 dark:ring-emerald-950' : 'bg-gray-300 dark:bg-gray-700 text-gray-600'">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <span class="text-[10px] sm:text-xs font-bold mt-2 text-gray-700 dark:text-gray-300">Logged</span>
                        </div>

                        <!-- Step 2: Under Review -->
                        <div class="relative z-10 flex flex-col items-center">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold transition-all shadow-sm"
                                 :class="isStepActive(ticket ? ticket.status : '', 2) ? 'bg-emerald-500 text-white ring-4 ring-emerald-100 dark:ring-emerald-950' : 'bg-gray-300 dark:bg-gray-700 text-gray-600'">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </div>
                            <span class="text-[10px] sm:text-xs font-bold mt-2 text-gray-700 dark:text-gray-300">Under Review</span>
                        </div>

                        <!-- Step 3: Action Taken -->
                        <div class="relative z-10 flex flex-col items-center">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold transition-all shadow-sm"
                                 :class="isStepActive(ticket ? ticket.status : '', 3) ? 'bg-emerald-500 text-white ring-4 ring-emerald-100 dark:ring-emerald-950' : 'bg-gray-300 dark:bg-gray-700 text-gray-600'">
                                <i class="fa-solid fa-list-check"></i>
                            </div>
                            <span class="text-[10px] sm:text-xs font-bold mt-2 text-gray-700 dark:text-gray-300">Action Taken</span>
                        </div>

                        <!-- Step 4: Closed / Completed -->
                        <div class="relative z-10 flex flex-col items-center">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold transition-all shadow-sm"
                                 :class="isStepActive(ticket ? ticket.status : '', 4) ? 'bg-emerald-500 text-white ring-4 ring-emerald-100 dark:ring-emerald-950' : 'bg-gray-300 dark:bg-gray-700 text-gray-600'">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <span class="text-[10px] sm:text-xs font-bold mt-2 text-gray-700 dark:text-gray-300">Closed</span>
                        </div>
                    </div>
                </div>

                <!-- Ticket Details & Feedback Content -->
                <div class="p-6 sm:p-8 space-y-6">
                    
                    <!-- Metadata Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-gray-700/30 border border-slate-200 dark:border-gray-700 text-xs">
                        <div>
                            <span class="text-gray-400 block uppercase font-bold text-[10px]">Submitter</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200 mt-0.5 block" x-text="ticket ? ticket.name : ''"></span>
                        </div>

                        <div>
                            <span class="text-gray-400 block uppercase font-bold text-[10px]">Category</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200 mt-0.5 block" x-text="ticket ? formatCategory(ticket.category) : ''"></span>
                        </div>

                        <div>
                            <span class="text-gray-400 block uppercase font-bold text-[10px]">Department</span>
                            <span class="font-bold text-gray-800 dark:text-gray-200 mt-0.5 block" x-text="ticket ? (ticket.department || 'General') : ''"></span>
                        </div>

                        <div>
                            <span class="text-gray-400 block uppercase font-bold text-[10px]">Rating</span>
                            <span class="font-bold text-amber-500 mt-0.5 block">
                                <template x-if="ticket">
                                    <span>
                                        <i class="fa-solid fa-star"></i>
                                        <span x-text="ticket.rating + '/5'"></span>
                                    </span>
                                </template>
                            </span>
                        </div>
                    </div>

                    <!-- Original Message Content -->
                    <div class="space-y-2">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Submitted Feedback Content</h4>
                        <div class="p-5 rounded-2xl bg-slate-50 dark:bg-gray-900 border border-slate-200 dark:border-gray-700 text-xs sm:text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed" x-text="ticket ? ticket.message : ''"></div>
                    </div>

                    <!-- Official Admin Reply & Action Remarks Box -->
                    <div class="space-y-2" x-show="ticket && ticket.admin_reply">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <h4 class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-widest">Official Management Response & Action Taken</h4>
                        </div>
                        <div class="p-5 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs sm:text-sm text-emerald-900 dark:text-emerald-200 space-y-3">
                            <div class="whitespace-pre-line leading-relaxed font-medium" x-text="ticket ? ticket.admin_reply : ''"></div>
                            <div class="pt-2 border-t border-emerald-200/60 dark:border-emerald-800/60 flex flex-wrap items-center justify-between text-[11px] text-emerald-700 dark:text-emerald-400 font-semibold">
                                <span>Reviewed By: <strong x-text="ticket ? (ticket.replied_by_name || 'Management Review Wing') : ''"></strong></span>
                                <span x-show="ticket && ticket.replied_at" x-text="'Updated: ' + formatDate(ticket ? ticket.replied_at : '')"></span>
                            </div>
                        </div>
                    </div>

                    <div x-show="ticket && !ticket.admin_reply" class="p-4 rounded-2xl bg-amber-50/60 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 text-xs text-amber-800 dark:text-amber-300 flex items-center gap-2.5">
                        <i class="fa-solid fa-clock-rotate-left text-base"></i>
                        <span>Your feedback is currently under administrative evaluation. Institutional actions or response updates will appear right here.</span>
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>

<script>
function trackFeedbackApp(initialData, initialError, initialQuery) {
    return {
        ticketQuery: initialQuery || '',
        ticket: initialData || null,
        errorMessage: initialError || '',
        loading: false,

        async searchTicket() {
            if (!this.ticketQuery.trim()) return;

            this.loading = true;
            this.errorMessage = '';
            this.ticket = null;

            try {
                const res = await fetch('api/track_feedback.php?ticket=' + encodeURIComponent(this.ticketQuery.trim()));
                const json = await res.json();

                if (json.success && json.data) {
                    this.ticket = json.data;
                } else {
                    this.errorMessage = json.message || 'No feedback found for this reference.';
                }
            } catch (err) {
                this.errorMessage = 'Network error while checking feedback status. Please try again.';
            } finally {
                this.loading = false;
            }
        },

        formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            const d = new Date(dateStr);
            return d.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        },

        formatCategory(cat) {
            if (!cat) return 'General';
            return cat.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
        },

        getStatusBadgeClass(s) {
            const map = {
                'pending': 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                'under_review': 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300',
                'action_taken': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300',
                'closed': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
            };
            return map[s] || 'bg-gray-100 text-gray-800';
        },

        getStatusIcon(s) {
            const map = {
                'pending': 'fa-solid fa-clock',
                'under_review': 'fa-solid fa-magnifying-glass',
                'action_taken': 'fa-solid fa-list-check',
                'closed': 'fa-solid fa-circle-check'
            };
            return map[s] || 'fa-solid fa-circle-info';
        },

        getStatusLabel(s) {
            const map = {
                'pending': 'Pending Review',
                'under_review': 'Under Review',
                'action_taken': 'Action Taken',
                'closed': 'Closed / Implemented'
            };
            return map[s] || s;
        },

        getProgressWidth(s) {
            const map = {
                'pending': 10,
                'under_review': 38,
                'action_taken': 72,
                'closed': 100
            };
            return map[s] || 10;
        },

        isStepActive(s, stepNum) {
            const stepOrder = { 'pending': 1, 'under_review': 2, 'action_taken': 3, 'closed': 4 };
            const currentStep = stepOrder[s] || 1;
            return currentStep >= stepNum;
        }
    }
}
</script>

<?php require 'includes/footer.php'; ?>

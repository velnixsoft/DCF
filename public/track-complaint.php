<?php
// ============================================================
// public/track-complaint.php
// Public Track Your Complaint & Suggestion Status Page
// Allows citizens to track real-time grievance status and administrative resolutions
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

$queryTicket = cleanInput($_GET['ticket'] ?? ($_GET['ticket_no'] ?? ''));
$initialTicketData = null;
$searchError = '';

if (!empty($queryTicket)) {
    $stmt = $pdo->prepare("
        SELECT 
            c.*, 
            u.name AS replied_by_name, u.role AS replied_by_role
        FROM `complaints` c
        LEFT JOIN `users` u ON c.reply_by = u.id
        WHERE UPPER(TRIM(c.ticket_no)) = UPPER(?)
        LIMIT 1
    ");
    $stmt->execute([$queryTicket]);
    $initialTicketData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$initialTicketData) {
        $searchError = "No record found for ticket reference '{$queryTicket}'. Please verify your ticket number.";
    }
}

$siteName = (string)($settings['site_name'] ?? 'Jaysmrutti Foundation');
$ngoPhone = (string)($settings['ngo_phone'] ?? '+91 7651910331');
$ngoEmail = (string)($settings['ngo_email'] ?? 'info@velnixsoft.com');
?>

<div class="min-h-screen py-12 md:py-16 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900 relative overflow-hidden" 
     x-data="trackComplaintApp(<?php echo htmlspecialchars(json_encode($initialTicketData), ENT_QUOTES, 'UTF-8'); ?>, '<?php echo htmlspecialchars(addslashes($searchError)); ?>', '<?php echo htmlspecialchars(addslashes($queryTicket)); ?>')" 
     x-cloak>

    <!-- Background decorative blur blobs -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[5%] left-[-10%] w-[320px] sm:w-[500px] h-[320px] sm:h-[500px] rounded-full bg-teal-100/40 dark:bg-teal-950/20 blur-[90px] sm:blur-[130px] transition-all duration-1000"></div>
        <div class="absolute bottom-[15%] right-[-10%] w-[350px] sm:w-[550px] h-[350px] sm:h-[550px] rounded-full bg-indigo-100/40 dark:bg-indigo-950/20 blur-[90px] sm:blur-[140px] transition-all duration-1000"></div>
    </div>

    <div class="max-w-3xl mx-auto relative z-10 space-y-8">

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- 1. HEADER & SEARCH HERO BOX                                  -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <div class="text-center space-y-3">
            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-teal-50 dark:bg-teal-900/40 text-[#0F8B8D] dark:text-teal-300 border border-teal-200/60 dark:border-teal-800/40 uppercase tracking-wider shadow-sm">
                <i class="fa-solid fa-magnifying-glass-location"></i> Live Grievance Tracking
            </span>
            <h1 class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-white tracking-tight">
                Track Your <span class="text-[#0F8B8D]">Complaint / Suggestion</span>
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 max-w-lg mx-auto leading-relaxed">
                Enter your unique ticket reference number to check the latest investigation status, assigned officer, and official administrative resolution.
            </p>
        </div>

        <!-- Search Form Card -->
        <div class="bg-white/85 dark:bg-gray-800/85 backdrop-blur-md shadow-[0_20px_50px_rgba(0,0,0,0.04)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.3)] border border-gray-200/70 dark:border-gray-700/60 rounded-[2rem] p-6 sm:p-8">
            <form @submit.prevent="searchTicket()" class="space-y-4">
                <label for="ticketInput" class="block text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                    Enter Ticket Reference Number
                </label>
                
                <div class="flex flex-col sm:flex-row items-stretch gap-3">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 text-base">
                            <i class="fa-solid fa-ticket"></i>
                        </span>
                        <input type="text" id="ticketInput" x-model="ticketQuery" @input="ticketQuery = ticketQuery.toUpperCase()" required 
                               placeholder="e.g. TKT-2026-0001 or SUG-2026-0002" 
                               class="w-full pl-11 pr-10 py-3.5 bg-gray-50/50 dark:bg-gray-950/40 border border-gray-200 dark:border-gray-700/70 rounded-2xl focus:bg-white dark:focus:bg-gray-900 focus:ring-4 focus:ring-[#0F8B8D]/10 focus:border-[#0F8B8D] outline-none text-gray-900 dark:text-white font-mono font-bold text-sm tracking-wide uppercase transition-all placeholder:normal-case placeholder:font-sans placeholder:font-normal">
                        <button type="button" x-show="ticketQuery" @click="ticketQuery = ''; ticket = null; errorMessage = '';" class="absolute right-3.5 top-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-white text-sm">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </button>
                    </div>

                    <button type="submit" :disabled="loading" 
                            class="py-3.5 px-7 bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold text-xs sm:text-sm rounded-2xl shadow-md transition duration-200 transform active:scale-95 flex items-center justify-center gap-2 flex-shrink-0 disabled:opacity-70 disabled:cursor-not-allowed">
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
                    <span>Format: <code class="font-mono text-teal-600 dark:text-teal-400 font-bold">TKT-YYYY-XXXX</code> or <code class="font-mono text-teal-600 dark:text-teal-400 font-bold">SUG-YYYY-XXXX</code></span>
                    <a href="complaint.php" class="text-[#0F8B8D] dark:text-teal-400 font-bold hover:underline">
                        Lodge a new grievance &rarr;
                    </a>
                </div>
            </form>
        </div>

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- 2. ERROR STATE                                               -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <div x-show="errorMessage" x-collapse class="bg-rose-50 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800/50 rounded-2xl p-5 text-rose-700 dark:text-rose-300 flex items-start gap-3 text-xs sm:text-sm">
            <i class="fa-solid fa-circle-exclamation text-lg mt-0.5 text-rose-500"></i>
            <div>
                <p class="font-bold text-rose-900 dark:text-rose-200">Ticket Not Found</p>
                <p class="mt-0.5" x-text="errorMessage"></p>
                <p class="mt-2 text-[11px] text-rose-600 dark:text-rose-400">
                    Need assistance? Call our public helpline at <a href="tel:<?php echo htmlspecialchars($ngoPhone); ?>" class="underline font-bold"><?php echo htmlspecialchars($ngoPhone); ?></a>.
                </p>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- 3. TICKET RESULT DETAILS & STATUS TIMELINE                   -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <template x-if="ticket">
            <div id="printableTicketArea" class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-xl border border-slate-200 dark:border-gray-700 overflow-hidden space-y-6">
                
                <!-- Ticket Top Header Banner -->
                <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1.5">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider" 
                                      :class="ticket.type === 'complaint' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/40' : 'bg-teal-500/20 text-teal-300 border border-teal-500/40'"
                                      x-text="ticket.type === 'complaint' ? 'Official Complaint' : 'Creative Suggestion'"></span>
                                <span class="text-xs text-slate-400" x-text="'Registered on ' + formatDate(ticket.created_at)"></span>
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-mono font-black tracking-wider text-teal-300" x-text="ticket.ticket_no"></h2>
                        </div>

                        <!-- Current Status Badge -->
                        <div class="flex flex-col sm:items-end">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Current Status</span>
                            <span class="px-4 py-1.5 rounded-2xl text-xs font-black uppercase tracking-wider inline-flex items-center gap-1.5 shadow-sm"
                                  :class="{
                                      'bg-amber-400 text-amber-950': ticket.status === 'pending',
                                      'bg-blue-500 text-white': ticket.status === 'in_progress',
                                      'bg-orange-400 text-orange-950': ticket.status === 'on_hold',
                                      'bg-emerald-500 text-white': ticket.status === 'resolved'
                                  }">
                                <i class="fa-solid text-xs" :class="{
                                    'fa-clock': ticket.status === 'pending',
                                    'fa-spinner fa-spin': ticket.status === 'in_progress',
                                    'fa-pause': ticket.status === 'on_hold',
                                    'fa-circle-check': ticket.status === 'resolved'
                                }"></i>
                                <span x-text="formatStatusLabel(ticket.status)"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Status Progress Stepper -->
                <div class="px-6 sm:px-8 py-2">
                    <div class="relative flex items-center justify-between">
                        <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-slate-200 dark:bg-gray-700 w-full z-0"></div>
                        <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-[#0F8B8D] transition-all duration-500 z-0"
                             :style="'width: ' + getProgressPercent(ticket.status)"></div>

                        <!-- Step 1: Registered -->
                        <div class="relative z-10 flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full bg-[#0F8B8D] text-white flex items-center justify-center text-xs font-bold shadow-md">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <span class="text-[10px] font-bold text-gray-700 dark:text-gray-300 mt-1.5">Logged</span>
                        </div>

                        <!-- Step 2: Under Review / In Progress -->
                        <div class="relative z-10 flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-colors"
                                 :class="['in_progress', 'on_hold', 'resolved'].includes(ticket.status) ? 'bg-[#0F8B8D] text-white shadow-md' : 'bg-slate-200 dark:bg-gray-700 text-gray-400'">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </div>
                            <span class="text-[10px] font-bold mt-1.5" :class="['in_progress', 'on_hold', 'resolved'].includes(ticket.status) ? 'text-gray-700 dark:text-gray-300' : 'text-gray-400'">
                                Reviewing
                            </span>
                        </div>

                        <!-- Step 3: Resolved -->
                        <div class="relative z-10 flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-colors"
                                 :class="ticket.status === 'resolved' ? 'bg-emerald-500 text-white shadow-md' : 'bg-slate-200 dark:bg-gray-700 text-gray-400'">
                                <i class="fa-solid fa-flag-checkered"></i>
                            </div>
                            <span class="text-[10px] font-bold mt-1.5" :class="ticket.status === 'resolved' ? 'text-emerald-600 dark:text-emerald-400 font-black' : 'text-gray-400'">
                                Resolved
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- OFFICIAL RESOLUTION / ADMIN REPLY (If present)         -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div class="px-6 sm:px-8">
                    <template x-if="ticket.admin_reply">
                        <div class="p-6 rounded-3xl bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-950/30 dark:to-teal-950/20 border-2 border-emerald-300 dark:border-emerald-800 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black uppercase text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5">
                                    <i class="fa-solid fa-stamp text-emerald-600"></i> Official Administrative Resolution
                                </span>
                                <span class="text-[11px] text-emerald-700 dark:text-emerald-400 font-bold" x-show="ticket.resolved_at || ticket.replied_at" x-text="'Updated: ' + formatDate(ticket.resolved_at || ticket.replied_at)"></span>
                            </div>

                            <div class="text-xs sm:text-sm text-gray-800 dark:text-gray-100 whitespace-pre-line leading-relaxed font-sans" x-text="ticket.admin_reply"></div>

                            <div class="pt-2 border-t border-emerald-200/60 dark:border-emerald-800/60 flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
                                <div>
                                    <span class="font-bold text-gray-700 dark:text-gray-300" x-text="ticket.replied_by_name || 'Grievance Redressal Committee'"></span>
                                    <span x-show="ticket.replied_by_role" x-text="' (' + ticket.replied_by_role + ')'"></span>
                                </div>
                                <span class="text-emerald-700 dark:text-emerald-400 font-bold flex items-center gap-1">
                                    <i class="fa-solid fa-circle-check"></i> Verified Response
                                </span>
                            </div>
                        </div>
                    </template>

                    <template x-if="!ticket.admin_reply">
                        <div class="p-5 rounded-2xl bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800/40 text-xs text-amber-800 dark:text-amber-300 flex items-start gap-3">
                            <i class="fa-solid fa-hourglass-half text-amber-600 text-lg mt-0.5"></i>
                            <div>
                                <p class="font-bold text-amber-900 dark:text-amber-200">Matter Under Active Review</p>
                                <p class="mt-0.5">
                                    Our administrative team is actively assessing this submission. You will automatically receive an email update when an official reply is recorded.
                                </p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- ════════════════════════════════════════════════════════ -->
                <!-- ORIGINAL SUBMISSION PARTICULARS                        -->
                <!-- ════════════════════════════════════════════════════════ -->
                <div class="px-6 sm:px-8 space-y-4 text-xs text-gray-700 dark:text-gray-200">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pb-4 border-b border-slate-100 dark:border-gray-700">
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Submitter Particulars:</span>
                            <p class="font-bold text-sm text-gray-900 dark:text-white mt-0.5" x-text="ticket.name"></p>
                            <p class="text-gray-500" x-show="ticket.contact_masked || ticket.contact" x-text="'Contact: ' + (ticket.contact_masked || ticket.contact)"></p>
                            <p class="text-gray-500" x-show="ticket.email_masked || ticket.email" x-text="'Email: ' + (ticket.email_masked || ticket.email)"></p>
                        </div>

                        <div class="sm:text-right">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Priority & Type:</span>
                            <p class="font-bold text-gray-900 dark:text-white mt-0.5" x-text="ticket.subject"></p>
                            <div class="mt-1 flex sm:justify-end gap-1.5">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase" x-text="'Priority: ' + (ticket.priority || 'Medium')"></span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Submitted Description:</span>
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-gray-900/60 border border-slate-200 dark:border-gray-700 whitespace-pre-line leading-relaxed font-sans" x-text="ticket.description"></div>
                    </div>

                    <!-- Attachment Link if exists -->
                    <div x-show="ticket.attachment_path" class="pt-2">
                        <a :href="ticket.attachment_path" target="_blank" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white font-bold text-xs transition">
                            <i class="fa-solid fa-paperclip text-teal-600"></i>
                            <span>View Submitted Attachment</span>
                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                        </a>
                    </div>

                </div>

                <!-- Footer Action Bar -->
                <div class="p-6 bg-slate-50 dark:bg-gray-700/50 border-t border-slate-100 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
                    <div class="text-[11px] text-gray-400">
                        Official Record ID: <span class="font-mono font-bold" x-text="ticket.ticket_no"></span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" @click="window.print()" class="px-4 py-2 rounded-xl bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 font-bold text-xs hover:bg-slate-50 transition shadow-sm">
                            <i class="fa-solid fa-print mr-1"></i> Print Summary
                        </button>
                        <a href="complaint.php" class="px-4 py-2 rounded-xl bg-[#0F8B8D] hover:bg-[#0c7274] text-white font-bold text-xs transition shadow-sm">
                            Submit New Matter
                        </a>
                    </div>
                </div>

            </div>
        </template>

        <!-- ════════════════════════════════════════════════════════════ -->
        <!-- 4. HELPLINE & FAQ FOOTER ASSISTANCE                          -->
        <!-- ════════════════════════════════════════════════════════════ -->
        <div class="bg-white/70 dark:bg-gray-800/70 rounded-3xl p-6 border border-slate-200 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-teal-50 dark:bg-teal-900/40 text-[#0F8B8D] flex items-center justify-center text-base">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 dark:text-white">Need Urgent Assistance with Your Case?</h4>
                    <p class="text-gray-500 dark:text-gray-400">Our public relations and support desk is open Monday to Saturday, 9 AM - 6 PM.</p>
                </div>
            </div>

            <div class="flex items-center gap-3 flex-shrink-0">
                <a href="tel:<?php echo htmlspecialchars($ngoPhone); ?>" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white font-bold transition">
                    <i class="fa-solid fa-phone mr-1 text-teal-600"></i> Call Helpline
                </a>
                <a href="contact.php" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white font-bold transition">
                    <i class="fa-solid fa-envelope mr-1 text-teal-600"></i> Contact Us
                </a>
            </div>
        </div>

    </div>

</div>

<script>
function trackComplaintApp(initialTicket, initialError, initialQuery) {
    return {
        ticketQuery: initialQuery || '',
        ticket: initialTicket || null,
        errorMessage: initialError || '',
        loading: false,

        async searchTicket() {
            const q = this.ticketQuery.trim().toUpperCase();
            if (!q) return;

            this.loading = true;
            this.errorMessage = '';
            this.ticket = null;

            try {
                const res = await fetch(`api/track_complaint.php?ticket=${encodeURIComponent(q)}`);
                const result = await res.json();

                if (result.success && result.data) {
                    this.ticket = result.data;
                    // Update URL without reloading
                    const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?ticket=' + encodeURIComponent(q);
                    window.history.pushState({ path: newUrl }, '', newUrl);
                } else {
                    this.errorMessage = result.message || `No complaint or suggestion found for reference "${q}".`;
                }
            } catch (err) {
                this.errorMessage = 'Network connectivity issue. Please try again.';
            } finally {
                this.loading = false;
            }
        },

        formatDate(d) {
            if (!d) return '-';
            const parts = d.split(' ')[0].split('-');
            if (parts.length === 3) {
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const mIdx = parseInt(parts[1], 10) - 1;
                return `${parts[2]} ${months[mIdx] || parts[1]}, ${parts[0]}`;
            }
            return d;
        },

        formatStatusLabel(st) {
            switch (st) {
                case 'pending': return 'Pending Review';
                case 'in_progress': return 'Under Investigation';
                case 'on_hold': return 'On Hold / Awaiting Info';
                case 'resolved': return 'Resolved & Closed';
                default: return st;
            }
        },

        getProgressPercent(st) {
            switch (st) {
                case 'pending': return '0%';
                case 'in_progress':
                case 'on_hold': return '50%';
                case 'resolved': return '100%';
                default: return '0%';
            }
        }
    };
}
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printableTicketArea, #printableTicketArea * {
        visibility: visible;
    }
    #printableTicketArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 10mm;
        box-shadow: none;
        border: none;
    }
}
</style>

<?php require 'includes/footer.php'; ?>

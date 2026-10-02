<?php
require 'includes/header.php';
require '../config/db.php';

// Safe query helper
function fetchStat(PDO $pdo, string $sql, $default = 0) {
    try {
        $val = $pdo->query($sql)->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

// Fetch quick live stats for the header cards
$totalDonation = (float)fetchStat($pdo, "SELECT SUM(amount) FROM donations WHERE payment_status = 'Success'", 0);
$pendingVolunteers = (int)fetchStat($pdo, "SELECT COUNT(*) FROM volunteers WHERE status = 'Pending'", 0);
$activeMembers = (int)fetchStat($pdo, "SELECT COUNT(*) FROM members WHERE status = 'Active'", 0);
$unreadMessages = (int)fetchStat($pdo, "SELECT COUNT(*) FROM contact_messages WHERE status = 'New'", 0);
$totalBeneficiaries = (int)fetchStat($pdo, "SELECT COUNT(*) FROM beneficiaries", 0);
$totalProviders = (int)fetchStat($pdo, "SELECT COUNT(*) FROM healthcare_providers", 0);
$pendingComplaints = (int)fetchStat($pdo, "SELECT COUNT(*) FROM complaints WHERE status = 'pending'", 0);
$jobApplications = (int)fetchStat($pdo, "SELECT COUNT(*) FROM job_applications", 0);
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="adminAiCopilot()">

    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300 min-w-0">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8 flex flex-col">

            <!-- Page Title & Header Bar in English -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-2xl md:text-3xl font-extrabold text-gray-800 dark:text-white flex items-center gap-2.5">
                        <span class="p-2 bg-gradient-to-tr from-[#1070B0] to-[#F0A010] text-white rounded-xl shadow-md text-xl">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                        </span>
                        Admin AI Copilot
                    </h2>
                    <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Instant natural language queries, live database lookups, and operational guidance across all 25+ NGO management modules. *(Supports English & Hinglish queries)*
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button @click="clearChat()" class="px-3 py-2 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:text-red-600 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold shadow-xs transition flex items-center gap-1.5">
                        <i class="fa-solid fa-trash-can"></i>
                        <span>Clear Chat</span>
                    </button>
                    <a href="/admin/dashboard" class="px-3.5 py-2 bg-[#1070B0] hover:bg-[#0d598c] text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-1.5">
                        <i class="fa-solid fa-gauge-high"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>

            <!-- Top Live KPI Overview Strip (English UI) -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 md:gap-4 mb-6">
                <div @click="sendPrompt('Show me total donations and revenue summary')" class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-xs hover:border-[#1070B0] dark:hover:border-sky-500 cursor-pointer transition group">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Total Raised</span>
                        <i class="fa-solid fa-sack-dollar text-green-500 group-hover:scale-110 transition"></i>
                    </div>
                    <div class="text-sm sm:text-base font-bold text-gray-800 dark:text-white mt-1">
                        ₹<?php echo number_format($totalDonation); ?>
                    </div>
                </div>

                <div @click="sendPrompt('Show beneficiaries count and welfare assistance history')" class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-xs hover:border-emerald-500 cursor-pointer transition group">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Beneficiaries</span>
                        <i class="fa-solid fa-hands-holding-child text-emerald-500 group-hover:scale-110 transition"></i>
                    </div>
                    <div class="text-sm sm:text-base font-bold text-gray-800 dark:text-white mt-1">
                        <?php echo $totalBeneficiaries; ?>
                        <span class="text-[10px] text-gray-400 font-normal">Enrolled</span>
                    </div>
                </div>

                <div @click="sendPrompt('Tell me about healthcare directory, doctors, and health cards')" class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-xs hover:border-teal-500 cursor-pointer transition group">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Healthcare</span>
                        <i class="fa-solid fa-hospital-user text-teal-500 group-hover:scale-110 transition"></i>
                    </div>
                    <div class="text-sm sm:text-base font-bold text-gray-800 dark:text-white mt-1">
                        <?php echo $totalProviders; ?>
                        <span class="text-[10px] text-gray-400 font-normal">Providers</span>
                    </div>
                </div>

                <div @click="sendPrompt('How many volunteers are pending approval?')" class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-xs hover:border-amber-500 cursor-pointer transition group">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Volunteers</span>
                        <i class="fa-solid fa-user-clock text-amber-500 group-hover:scale-110 transition"></i>
                    </div>
                    <div class="text-sm sm:text-base font-bold text-gray-800 dark:text-white mt-1">
                        <?php echo $pendingVolunteers; ?>
                        <?php if ($pendingVolunteers > 0): ?>
                            <span class="text-[10px] text-amber-500 font-normal">Pending</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div @click="sendPrompt('Show active members count and today\'s birthdays')" class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-xs hover:border-blue-500 cursor-pointer transition group">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Active Members</span>
                        <i class="fa-solid fa-id-card text-blue-500 group-hover:scale-110 transition"></i>
                    </div>
                    <div class="text-sm sm:text-base font-bold text-gray-800 dark:text-white mt-1">
                        <?php echo $activeMembers; ?>
                    </div>
                </div>

                <div @click="sendPrompt('Show job openings and applicant screening')" class="bg-white dark:bg-gray-800 p-3.5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-xs hover:border-purple-500 cursor-pointer transition group">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Job Applicants</span>
                        <i class="fa-solid fa-users-viewfinder text-purple-500 group-hover:scale-110 transition"></i>
                    </div>
                    <div class="text-sm sm:text-base font-bold text-gray-800 dark:text-white mt-1">
                        <?php echo $jobApplications; ?>
                        <span class="text-[10px] text-gray-400 font-normal">Received</span>
                    </div>
                </div>
            </div>

            <!-- Main Interactive Workspace -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 flex-1 min-h-[520px]">

                <!-- Conversational Chat Stream (2 Columns) -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col overflow-hidden">
                    
                    <!-- Chat Header -->
                    <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-700/80 bg-gray-50/70 dark:bg-gray-800/60 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse"></span>
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-200">Admin AI Copilot Workspace</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">25+ Connected Modules</span>
                        </div>
                    </div>

                    <!-- Messages Stream Body -->
                    <div id="admin-chat-stream" class="flex-1 p-4 md:p-6 overflow-y-auto space-y-4 max-h-[550px]">
                        <template x-for="(msg, index) in messages" :key="index">
                            <div class="flex gap-3" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
                                
                                <!-- Bot Icon -->
                                <template x-if="msg.role === 'bot'">
                                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-[#1070B0] to-[#F0A010] text-white flex items-center justify-center shrink-0 shadow-sm text-xs mt-0.5">
                                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                                    </div>
                                </template>

                                <!-- Message Bubble -->
                                <div class="max-w-[90%] md:max-w-[85%]" 
                                    :class="msg.role === 'user' ? 'bg-[#1070B0] text-white rounded-2xl rounded-tr-none px-4 py-3 shadow-sm' : 'bg-gray-50 dark:bg-gray-700/50 text-gray-800 dark:text-gray-100 rounded-2xl rounded-tl-none px-4 py-3.5 shadow-sm border border-gray-100 dark:border-gray-700'">
                                    
                                    <!-- Render Message HTML with Streaming Indicator -->
                                    <div class="chat-markdown-body text-xs md:text-sm leading-relaxed inline">
                                        <span x-html="renderMarkdown(msg.text)"></span>
                                        <template x-if="msg.isTyping">
                                            <span class="inline-block w-2 h-4 bg-[#1070B0] dark:bg-[#F0A010] animate-pulse ml-0.5 align-middle rounded-xs"></span>
                                        </template>
                                    </div>

                                    <!-- Action Shortcuts (If Bot) -->
                                    <template x-if="msg.actions && msg.actions.length > 0 && !msg.isTyping">
                                        <div class="mt-3 pt-2.5 border-t border-gray-200 dark:border-gray-600 flex flex-wrap gap-2 transition-opacity duration-300">
                                            <template x-for="(act, aIdx) in msg.actions" :key="aIdx">
                                                <div>
                                                    <template x-if="act.actionPrompt">
                                                        <button @click="sendPrompt(act.actionPrompt)"
                                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-gray-800 hover:bg-[#fffcf5] text-[#1070B0] dark:text-sky-400 font-bold rounded-lg text-xs border border-gray-200 dark:border-gray-600 shadow-xs hover:shadow transition transform hover:-translate-y-0.5">
                                                            <i :class="['fa-solid', act.icon || 'fa-wand-magic-sparkles', 'text-[11px]']"></i>
                                                            <span x-text="act.label"></span>
                                                        </button>
                                                    </template>
                                                    <template x-if="!act.actionPrompt && act.url">
                                                        <a :href="act.url" 
                                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-gray-800 hover:bg-[#fffcf5] text-[#1070B0] dark:text-sky-400 font-bold rounded-lg text-xs border border-gray-200 dark:border-gray-600 shadow-xs hover:shadow transition transform hover:-translate-y-0.5">
                                                            <i :class="['fa-solid', act.icon || 'fa-arrow-up-right-from-square', 'text-[11px]']"></i>
                                                            <span x-text="act.label"></span>
                                                        </a>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Timestamp -->
                                    <div class="text-[10px] mt-1.5 text-right opacity-70" x-text="msg.time"></div>
                                </div>
                            </div>
                        </template>

                        <!-- Dynamic Animated AI Thinking Loader -->
                        <div x-show="isLoading" class="flex items-center gap-3 justify-start transition-all duration-300 animate-fadeIn">
                            <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-[#1070B0] to-[#F0A010] text-white flex items-center justify-center shrink-0 shadow-sm text-xs animate-spin" style="animation-duration: 3s;">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-2xl rounded-tl-none px-4 py-3 border border-gray-100 dark:border-gray-700 flex items-center gap-2 shadow-xs">
                                <span class="w-2 h-2 bg-[#1070B0] rounded-full animate-bounce [animation-delay:0s]"></span>
                                <span class="w-2 h-2 bg-[#F0A010] rounded-full animate-bounce [animation-delay:0.2s]"></span>
                                <span class="w-2 h-2 bg-[#1070B0] rounded-full animate-bounce [animation-delay:0.4s]"></span>
                                <span class="text-xs font-medium text-gray-600 dark:text-gray-300 ml-1">AI Copilot is generating response...</span>
                            </div>
                        </div>
                    </div>

                    <!-- Prompt Input Area in English -->
                    <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
                        <form @submit.prevent="submitPrompt()" class="flex items-center gap-2">
                            <div class="relative flex-1">
                                <input type="text" x-model="inputQuery" placeholder="Ask anything about healthcare, beneficiaries, expenses, jobs, health cards, donations, or settings..." 
                                    class="w-full pl-4 pr-10 py-3 text-xs md:text-sm bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-white rounded-xl border border-gray-200 dark:border-gray-700 focus:outline-none focus:ring-2 focus:ring-[#1070B0] dark:focus:ring-sky-500 transition placeholder-gray-400" />
                                <button type="button" x-show="inputQuery.length > 0" @click="inputQuery = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                    <i class="fa-solid fa-circle-xmark"></i>
                                </button>
                            </div>
                            <button type="submit" :disabled="!inputQuery.trim() || isLoading"
                                class="px-5 py-3 bg-gradient-to-r from-[#1070B0] to-[#F0A010] hover:from-[#0d598c] hover:to-[#d48b0a] text-white rounded-xl font-bold text-xs md:text-sm shadow-md transition flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span>Ask Copilot</span>
                                <i class="fa-solid fa-paper-plane text-xs"></i>
                            </button>
                        </form>
                    </div>

                </div>

                <!-- Right Sidebar: Suggested Prompts & Shortcuts in English (1 Column) -->
                <div class="space-y-4">
                    
                    <!-- Pre-built Categorized Prompt Presets Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-bold text-sm text-gray-800 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-lightbulb text-amber-500"></i>
                                <span>Module Prompts & Guides</span>
                            </h4>
                        </div>

                        <!-- Category Selector Pills -->
                        <div class="flex flex-wrap gap-1.5 mb-3.5 pb-2.5 border-b border-gray-100 dark:border-gray-700 text-[11px]">
                            <button @click="activeTab = 'all'" :class="activeTab === 'all' ? 'bg-[#1070B0] text-white font-bold' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" class="px-2.5 py-1 rounded-lg transition">All</button>
                            <button @click="activeTab = 'health'" :class="activeTab === 'health' ? 'bg-[#1070B0] text-white font-bold' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" class="px-2.5 py-1 rounded-lg transition">Healthcare & Aid</button>
                            <button @click="activeTab = 'finance'" :class="activeTab === 'finance' ? 'bg-[#1070B0] text-white font-bold' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" class="px-2.5 py-1 rounded-lg transition">Finance & Expenses</button>
                            <button @click="activeTab = 'careers'" :class="activeTab === 'careers' ? 'bg-[#1070B0] text-white font-bold' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" class="px-2.5 py-1 rounded-lg transition">Jobs & HR</button>
                            <button @click="activeTab = 'students'" :class="activeTab === 'students' ? 'bg-[#1070B0] text-white font-bold' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" class="px-2.5 py-1 rounded-lg transition">Students & Field</button>
                            <button @click="activeTab = 'docs'" :class="activeTab === 'docs' ? 'bg-[#1070B0] text-white font-bold' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'" class="px-2.5 py-1 rounded-lg transition">Letters & MoUs</button>
                        </div>
                        
                        <!-- Prompts List -->
                        <div class="space-y-2 max-h-[320px] overflow-y-auto pr-1">
                            
                            <!-- Tab: All / General -->
                            <template x-if="activeTab === 'all' || activeTab === 'general'">
                                <div class="space-y-2">
                                    <button @click="sendPrompt('Show all modules in this NGO system')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-compass text-indigo-500 group-hover:scale-110 transition"></i>
                                        <span>🗺️ Complete 25+ Modules Catalog</span>
                                    </button>
                                    <button @click="sendPrompt('Show me overall NGO operations summary and stats')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-chart-line text-blue-500 group-hover:scale-110 transition"></i>
                                        <span>📊 Executive Operations Summary</span>
                                    </button>
                                    <button @click="sendPrompt('Show unread complaints and feedback')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-comments text-rose-500 group-hover:scale-110 transition"></i>
                                        <span>📢 Grievances & Stakeholder Feedback</span>
                                    </button>
                                    <button @click="sendPrompt('Where can I configure Razorpay, UPI, and email settings?')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-gear text-gray-500 group-hover:scale-110 transition"></i>
                                        <span>⚙️ Payment Gateways & System Config</span>
                                    </button>
                                </div>
                            </template>

                            <!-- Tab: Health & Beneficiaries -->
                            <template x-if="activeTab === 'all' || activeTab === 'health'">
                                <div class="space-y-2">
                                    <button @click="sendPrompt('Tell me about healthcare directory, doctors, and health cards')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-hospital-user text-teal-500 group-hover:scale-110 transition"></i>
                                        <span>🏥 Healthcare Providers & Doctor MOUs</span>
                                    </button>
                                    <button @click="sendPrompt('Show beneficiaries count and welfare assistance history')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-hands-holding-child text-emerald-500 group-hover:scale-110 transition"></i>
                                        <span>🤝 Beneficiaries & Aid Distribution Log</span>
                                    </button>
                                    <button @click="sendPrompt('How to issue and verify Swasthya Health Cards?')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-id-card-clip text-cyan-500 group-hover:scale-110 transition"></i>
                                        <span>🪪 Swasthya Health Cards Issuance</span>
                                    </button>
                                </div>
                            </template>

                            <!-- Tab: Finance & Expenses -->
                            <template x-if="activeTab === 'all' || activeTab === 'finance'">
                                <div class="space-y-2">
                                    <button @click="sendPrompt('Show expense summary and financial balance')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-receipt text-red-500 group-hover:scale-110 transition"></i>
                                        <span>💸 Expense Tracker & Category Spend</span>
                                    </button>
                                    <button @click="sendPrompt('Show custom receipts and 80G offline donations summary')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-file-invoice text-amber-500 group-hover:scale-110 transition"></i>
                                        <span>🧾 Custom 80G Offline Receipts</span>
                                    </button>
                                    <button @click="sendPrompt('Show recurring donations and monthly auto pay status')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-arrows-rotate text-blue-500 group-hover:scale-110 transition"></i>
                                        <span>🔄 Auto-Pay / Recurring Donors</span>
                                    </button>
                                    <button @click="sendPrompt('How to manage item and in-kind donation categories?')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-boxes-stacked text-purple-500 group-hover:scale-110 transition"></i>
                                        <span>📦 Item Donations (Clothes, Ration, Kits)</span>
                                    </button>
                                </div>
                            </template>

                            <!-- Tab: Careers & HR -->
                            <template x-if="activeTab === 'all' || activeTab === 'careers'">
                                <div class="space-y-2">
                                    <button @click="sendPrompt('Show job openings, applications, and HR policies')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-briefcase text-sky-500 group-hover:scale-110 transition"></i>
                                        <span>💼 Job Postings & Candidate Screening</span>
                                    </button>
                                    <button @click="sendPrompt('Show HR workplace policies like Code of Conduct and POSH')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-book-open-reader text-indigo-500 group-hover:scale-110 transition"></i>
                                        <span>📜 HR Policies & Compliance Manuals</span>
                                    </button>
                                    <button @click="sendPrompt('How does Career Guidance and skill courses work?')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-graduation-cap text-amber-500 group-hover:scale-110 transition"></i>
                                        <span>🎓 Vocational Skill Courses CMS</span>
                                    </button>
                                    <button @click="sendPrompt('Show organization structure hierarchy tree')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-sitemap text-teal-500 group-hover:scale-110 transition"></i>
                                        <span>🏛️ Leadership Tree & Management Body</span>
                                    </button>
                                </div>
                            </template>

                            <!-- Tab: Students & Youth / Field Staff -->
                            <template x-if="activeTab === 'all' || activeTab === 'students'">
                                <div class="space-y-2">
                                    <button @click="sendPrompt('Show field agent attendance and payroll summary')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-person-walking text-orange-500 group-hover:scale-110 transition"></i>
                                        <span>🏃 Field Agents, GPS Punch & Payroll</span>
                                    </button>
                                    <button @click="sendPrompt('How does the Student Ambassador module work?')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-user-graduate text-blue-500 group-hover:scale-110 transition"></i>
                                        <span>🎓 Campus Ambassadors & Tasks</span>
                                    </button>
                                    <button @click="sendPrompt('Show student task submissions awaiting grading')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-clipboard-check text-green-500 group-hover:scale-110 transition"></i>
                                        <span>📋 Grade Task Submissions & Points</span>
                                    </button>
                                </div>
                            </template>

                            <!-- Tab: Letters & Docs -->
                            <template x-if="activeTab === 'all' || activeTab === 'docs'">
                                <div class="space-y-2">
                                    <button @click="sendPrompt('How to create official letters, letterheads, and MOUs?')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-file-signature text-purple-500 group-hover:scale-110 transition"></i>
                                        <span>📄 Document Studio & Letterhead Letters</span>
                                    </button>
                                    <button @click="sendPrompt('Show agreements and institutional MoUs')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-file-contract text-emerald-500 group-hover:scale-110 transition"></i>
                                        <span>📜 Bilateral Contracts & MoUs</span>
                                    </button>
                                    <button @click="sendPrompt('How to generate visitor and sanstha authorization certificates?')" 
                                        class="w-full text-left p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] border border-gray-100 dark:border-gray-600 text-xs font-semibold text-gray-700 dark:text-gray-200 transition flex items-center gap-2 group">
                                        <i class="fa-solid fa-stamp text-amber-500 group-hover:scale-110 transition"></i>
                                        <span>🏆 Sanstha Authorization & Guest Certificates</span>
                                    </button>
                                </div>
                            </template>

                        </div>
                    </div>

                    <!-- Direct Administrative Navigation Card (English UI) -->
                    <div class="bg-gradient-to-br from-[#1070B0]/10 to-[#F0A010]/10 dark:from-sky-900/20 dark:to-amber-900/20 rounded-2xl p-4 sm:p-5 border border-[#1070B0]/20 dark:border-gray-700">
                        <h4 class="font-bold text-xs uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-2.5 flex items-center gap-1.5">
                            <i class="fa-solid fa-compass text-[#1070B0]"></i>
                            <span>Quick Launch Modules</span>
                        </h4>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <a href="/admin/healthcare_directory.php" class="p-2 bg-white dark:bg-gray-800 rounded-lg text-gray-700 dark:text-gray-200 hover:text-[#1070B0] font-medium border border-gray-100 dark:border-gray-700 flex items-center gap-1.5 shadow-xs">
                                <i class="fa-solid fa-hospital-user text-teal-500"></i>
                                <span>Healthcare</span>
                            </a>
                            <a href="/admin/beneficiaries" class="p-2 bg-white dark:bg-gray-800 rounded-lg text-gray-700 dark:text-gray-200 hover:text-[#1070B0] font-medium border border-gray-100 dark:border-gray-700 flex items-center gap-1.5 shadow-xs">
                                <i class="fa-solid fa-hands-holding-child text-emerald-500"></i>
                                <span>Beneficiaries</span>
                            </a>
                            <a href="/admin/donations" class="p-2 bg-white dark:bg-gray-800 rounded-lg text-gray-700 dark:text-gray-200 hover:text-[#1070B0] font-medium border border-gray-100 dark:border-gray-700 flex items-center gap-1.5 shadow-xs">
                                <i class="fa-solid fa-sack-dollar text-green-500"></i>
                                <span>Donations</span>
                            </a>
                            <a href="/admin/expenses" class="p-2 bg-white dark:bg-gray-800 rounded-lg text-gray-700 dark:text-gray-200 hover:text-[#1070B0] font-medium border border-gray-100 dark:border-gray-700 flex items-center gap-1.5 shadow-xs">
                                <i class="fa-solid fa-receipt text-red-500"></i>
                                <span>Expenses</span>
                            </a>
                            <a href="/admin/jobs.php" class="p-2 bg-white dark:bg-gray-800 rounded-lg text-gray-700 dark:text-gray-200 hover:text-[#1070B0] font-medium border border-gray-100 dark:border-gray-700 flex items-center gap-1.5 shadow-xs">
                                <i class="fa-solid fa-briefcase text-blue-500"></i>
                                <span>Jobs & Careers</span>
                            </a>
                            <a href="/admin/student-directory" class="p-2 bg-white dark:bg-gray-800 rounded-lg text-gray-700 dark:text-gray-200 hover:text-[#1070B0] font-medium border border-gray-100 dark:border-gray-700 flex items-center gap-1.5 shadow-xs">
                                <i class="fa-solid fa-graduation-cap text-amber-500"></i>
                                <span>Ambassadors</span>
                            </a>
                            <a href="/admin/document-studio" class="p-2 bg-white dark:bg-gray-800 rounded-lg text-gray-700 dark:text-gray-200 hover:text-[#1070B0] font-medium border border-gray-100 dark:border-gray-700 flex items-center gap-1.5 shadow-xs">
                                <i class="fa-solid fa-file-signature text-purple-500"></i>
                                <span>Doc Studio</span>
                            </a>
                            <a href="/admin/settings" class="p-2 bg-white dark:bg-gray-800 rounded-lg text-gray-700 dark:text-gray-200 hover:text-[#1070B0] font-medium border border-gray-100 dark:border-gray-700 flex items-center gap-1.5 shadow-xs">
                                <i class="fa-solid fa-gear text-gray-500"></i>
                                <span>Settings</span>
                            </a>
                        </div>
                    </div>

                </div>

            </div>

        </main>

        <?php require 'includes/footer.php'; ?>
    </div>
</div>

<script>
function adminAiCopilot() {
    return {
        inputQuery: '',
        isLoading: false,
        activeTab: 'all',
        typingInterval: null,
        messages: [
            {
                role: 'bot',
                text: "Hello **<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>**! ⚡ I am your **Admin AI Copilot** with complete access to all **25+ NGO Modules**.\n\nAsk me anything regarding live donations, beneficiaries, healthcare providers, doctor MOUs, health cards, expenses, job vacancies, student tasks, official letterheads, or system settings!\n\n*(You can ask in English or Hinglish)*",
                time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                isTyping: false,
                actions: [
                    { label: '🗺️ All Modules Directory', url: '#', actionPrompt: 'Show all modules in this NGO system', icon: 'fa-compass' },
                    { label: '📊 Operations Summary', url: '#', actionPrompt: 'Show me overall NGO operations summary and stats', icon: 'fa-chart-line' },
                    { label: '🏥 Healthcare Directory', url: '/admin/healthcare_directory.php', icon: 'fa-hospital-user' },
                    { label: '🤝 Beneficiaries Log', url: '/admin/beneficiaries', icon: 'fa-hands-holding-child' }
                ]
            }
        ],

        init() {
            this.scrollToBottom();
        },

        sendPrompt(promptText) {
            this.inputQuery = promptText;
            this.submitPrompt();
        },

        submitPrompt() {
            const query = this.inputQuery.trim();
            if (!query || this.isLoading) return;

            // Stop any ongoing typing timer
            if (this.typingInterval) {
                clearInterval(this.typingInterval);
                this.typingInterval = null;
            }

            // Push User Message
            this.messages.push({
                role: 'user',
                text: query,
                time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                isTyping: false
            });

            this.inputQuery = '';
            this.isLoading = true;
            this.scrollToBottom();

            fetch('/api/ai_chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    panel: 'admin',
                    action: 'chat',
                    message: query,
                    history: this.messages.slice(-6).map(m => ({ role: m.role, text: m.text }))
                })
            })
            .then(res => res.json())
            .then(data => {
                this.isLoading = false;
                const replyText = data.reply || data.message || "An error occurred while fetching insights.";
                const replyActions = data.actions || [];
                
                // Stream response with dynamic typewriter effect
                this.typewriterEffect(replyText, replyActions);
            })
            .catch(err => {
                this.isLoading = false;
                this.messages.push({
                    role: 'bot',
                    text: "⚠️ Connection error occurred while communicating with AI service.",
                    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                    isTyping: false,
                    actions: []
                });
                this.scrollToBottom();
            });
        },

        typewriterEffect(fullText, actions = []) {
            const newIndex = this.messages.length;
            this.messages.push({
                role: 'bot',
                text: '',
                time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                isTyping: true,
                actions: []
            });
            this.scrollToBottom();

            let currentPos = 0;
            const textLen = fullText.length;
            // Adaptive speed: faster for long text, smooth for short text
            const step = textLen > 800 ? 12 : (textLen > 400 ? 7 : 4);
            const intervalMs = 15;

            this.typingInterval = setInterval(() => {
                currentPos += step;
                if (currentPos >= textLen) {
                    clearInterval(this.typingInterval);
                    this.typingInterval = null;
                    this.messages[newIndex].text = fullText;
                    this.messages[newIndex].isTyping = false;
                    this.messages[newIndex].actions = actions;
                } else {
                    this.messages[newIndex].text = fullText.substring(0, currentPos);
                }
                this.scrollToBottom();
            }, intervalMs);
        },

        clearChat() {
            if (this.typingInterval) {
                clearInterval(this.typingInterval);
                this.typingInterval = null;
            }
            this.messages = [
                {
                    role: 'bot',
                    text: "Chat cleared. What administrative module or data would you like to inspect?",
                    time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                    isTyping: false,
                    actions: [
                        { label: '🗺️ All Modules', url: '#', actionPrompt: 'Show all modules in this NGO system', icon: 'fa-compass' },
                        { label: '📊 Operations Summary', url: '#', actionPrompt: 'Show me overall NGO operations summary and stats', icon: 'fa-chart-line' }
                    ]
                }
            ];
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const stream = document.getElementById('admin-chat-stream');
                if (stream) stream.scrollTop = stream.scrollHeight;
            });
        },

        renderMarkdown(text) {
            if (!text) return '';
            let escaped = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // Headers
            escaped = escaped.replace(/^### (.*$)/gim, '<h4 class="font-bold text-gray-900 dark:text-white text-base mt-2 mb-1.5">$1</h4>');
            escaped = escaped.replace(/^#### (.*$)/gim, '<h5 class="font-semibold text-gray-800 dark:text-gray-100 text-sm mt-2 mb-1">$1</h5>');

            // Bold & Italic
            escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-gray-900 dark:text-white">$1</strong>');
            escaped = escaped.replace(/\*(.*?)\*/g, '<em class="italic">$1</em>');
            escaped = escaped.replace(/`([^`]+)`/g, '<code class="bg-gray-100 dark:bg-gray-700 text-blue-600 dark:text-sky-400 px-1.5 py-0.5 rounded text-xs font-mono font-semibold">$1</code>');

            // Blockquotes
            escaped = escaped.replace(/^> (.*$)/gim, '<div class="border-l-4 border-amber-500 bg-amber-50 dark:bg-amber-900/20 text-amber-900 dark:text-amber-200 px-3 py-1.5 my-2 rounded text-xs leading-relaxed">$1</div>');

            // Links
            escaped = escaped.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-[#1070B0] dark:text-sky-400 font-semibold underline hover:text-[#F0A010] transition-colors">$1</a>');

            // Tables
            const lines = escaped.split('\n');
            let inTable = false;
            let tableHtml = '';
            const formattedLines = [];

            for (let i = 0; i < lines.length; i++) {
                const line = lines[i].trim();
                if (line.startsWith('|') && line.endsWith('|')) {
                    if (!inTable) {
                        inTable = true;
                        tableHtml = '<div class="overflow-x-auto my-2.5 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm"><table class="w-full text-xs text-left text-gray-700 dark:text-gray-300 divide-y divide-gray-200 dark:divide-gray-700">';
                    }
                    const cells = line.split('|').filter((_, idx, arr) => idx > 0 && idx < arr.length - 1).map(c => c.trim());
                    if (cells.some(c => c.includes('---'))) {
                        continue;
                    }
                    const isHeader = i > 0 && lines[i + 1] && lines[i + 1].includes('---');
                    const rowTag = isHeader ? 'th' : 'td';
                    const rowClass = isHeader ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white font-bold px-3 py-2' : 'px-3 py-2 odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900/40 dark:even:bg-gray-800/40';
                    
                    tableHtml += '<tr>';
                    cells.forEach(c => {
                        tableHtml += `<${rowTag} class="${rowClass}">${c}</${rowTag}>`;
                    });
                    tableHtml += '</tr>';
                } else {
                    if (inTable) {
                        inTable = false;
                        tableHtml += '</table></div>';
                        formattedLines.push(tableHtml);
                        tableHtml = '';
                    }
                    formattedLines.push(line);
                }
            }

            if (inTable) {
                tableHtml += '</table></div>';
                formattedLines.push(tableHtml);
            }

            let result = formattedLines.join('\n');
            result = result.replace(/\n\n+/g, '<br/><br/>');
            result = result.replace(/\n/g, '<br/>');

            return result;
        }
    };
}
</script>

<?php require 'includes/footer_end.php'; ?>

        scrollToBottom() {
            this.$nextTick(() => {
                const stream = document.getElementById('admin-chat-stream');
                if (stream) stream.scrollTop = stream.scrollHeight;
            });
        },

        renderMarkdown(text) {
            if (!text) return '';
            let escaped = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // Headers
            escaped = escaped.replace(/^### (.*$)/gim, '<h4 class="font-bold text-gray-900 dark:text-white text-base mt-2 mb-1.5">$1</h4>');
            escaped = escaped.replace(/^#### (.*$)/gim, '<h5 class="font-semibold text-gray-800 dark:text-gray-100 text-sm mt-2 mb-1">$1</h5>');

            // Bold & Italic
            escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-gray-900 dark:text-white">$1</strong>');
            escaped = escaped.replace(/\*(.*?)\*/g, '<em class="italic">$1</em>');
            escaped = escaped.replace(/`([^`]+)`/g, '<code class="bg-gray-100 dark:bg-gray-700 text-blue-600 dark:text-sky-400 px-1.5 py-0.5 rounded text-xs font-mono font-semibold">$1</code>');

            // Blockquotes
            escaped = escaped.replace(/^> (.*$)/gim, '<div class="border-l-4 border-amber-500 bg-amber-50 dark:bg-amber-900/20 text-amber-900 dark:text-amber-200 px-3 py-1.5 my-2 rounded text-xs leading-relaxed">$1</div>');

            // Links
            escaped = escaped.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="text-[#1070B0] dark:text-sky-400 font-semibold underline hover:text-[#F0A010] transition-colors">$1</a>');

            // Tables
            const lines = escaped.split('\n');
            let inTable = false;
            let tableHtml = '';
            const formattedLines = [];

            for (let i = 0; i < lines.length; i++) {
                const line = lines[i].trim();
                if (line.startsWith('|') && line.endsWith('|')) {
                    if (!inTable) {
                        inTable = true;
                        tableHtml = '<div class="overflow-x-auto my-2.5 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm"><table class="w-full text-xs text-left text-gray-700 dark:text-gray-300 divide-y divide-gray-200 dark:divide-gray-700">';
                    }
                    const cells = line.split('|').filter((_, idx, arr) => idx > 0 && idx < arr.length - 1).map(c => c.trim());
                    if (cells.some(c => c.includes('---'))) {
                        continue;
                    }
                    const isHeader = i > 0 && lines[i + 1] && lines[i + 1].includes('---');
                    const rowTag = isHeader ? 'th' : 'td';
                    const rowClass = isHeader ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white font-bold px-3 py-2' : 'px-3 py-2 odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900/40 dark:even:bg-gray-800/40';
                    
                    tableHtml += '<tr>';
                    cells.forEach(c => {
                        tableHtml += `<${rowTag} class="${rowClass}">${c}</${rowTag}>`;
                    });
                    tableHtml += '</tr>';
                } else {
                    if (inTable) {
                        tableHtml += '</table></div>';
                        formattedLines.push(tableHtml);
                        inTable = false;
                    }
                    formattedLines.push(line);
                }
            }
            if (inTable) {
                tableHtml += '</table></div>';
                formattedLines.push(tableHtml);
            }

            escaped = formattedLines.join('\n');

            // Lists
            escaped = escaped.replace(/^\s*•\s+(.*$)/gim, '<li class="flex items-start gap-1.5 ml-1 my-0.5"><span class="text-[#F0A010] mt-0.5">•</span><span>$1</span></li>');

            // Spacing
            escaped = escaped.replace(/\n\n+/g, '<div class="h-2"></div>');
            escaped = escaped.replace(/\n/g, '<br/>');

            return escaped;
        }
    }
}
</script>

<?php require 'includes/header.php'; ?>
<?php require '../config/db.php'; ?>

<div class="flex h-screen overflow-hidden bg-gray-100 dark:bg-dark-bg" x-data="donationManager">

    <?php require 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">

            <div class="flex flex-col gap-4 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-700 dark:text-white">Donations</h3>
                    <button @click="isAddModalOpen = true" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg shadow flex items-center gap-2 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Donation
                    </button>
                </div>

                <div class="bg-white dark:bg-dark-card p-4 rounded-lg shadow-sm border dark:border-gray-700 flex flex-col md:flex-row gap-4 items-end md:items-center flex-wrap">

                    <div class="flex flex-col w-full md:w-auto">
                        <label class="text-xs text-gray-500 mb-1">Date Range</label>
                        <select x-model="filterDate" class="px-3 py-2 border rounded-lg bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none">
                            <option value="all">All Time</option>
                            <option value="current_month">Current Month</option>
                            <option value="prev_month">Previous Month</option>
                            <option value="this_year">This Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>

                    <div x-show="filterDate === 'custom'" class="flex gap-2 w-full md:w-auto" x-cloak>
                        <div class="flex flex-col w-1/2">
                            <label class="text-xs text-gray-500 mb-1">Start</label>
                            <input type="date" x-model="customStart" :max="customEnd || today" class="px-2 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        <div class="flex flex-col w-1/2">
                            <label class="text-xs text-gray-500 mb-1">End</label>
                            <input type="date" x-model="customEnd" :min="customStart" :max="today" class="px-2 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                    </div>

                    <div class="flex flex-col w-full md:w-auto">
                        <label class="text-xs text-gray-500 mb-1">Status</label>
                        <select x-model="filterStatus" class="px-3 py-2 border rounded-lg bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none">
                            <option value="All">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Success">Success</option>
                            <option value="Failed">Failed</option>
                        </select>
                    </div>

                    <div class="flex flex-col w-full md:w-auto">
                        <label class="text-xs text-gray-500 mb-1">Payment</label>
                        <select x-model="filterPaymentMode" class="px-3 py-2 border rounded-lg bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none">
                            <option value="All">All Payments</option>
                            <option value="Razorpay">Online (Razorpay)</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>

                    <div class="flex flex-col w-full md:flex-1">
                        <label class="text-xs text-gray-500 mb-1">Search</label>
                        <div class="relative">
                            <input type="text" x-model="search" placeholder="ID, Name or Mobile..." class="w-full pl-9 pr-4 py-2 border rounded-lg bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            $paymentModeSelect = dbColumnExists($pdo, 'donations', 'payment_mode')
                ? 'd.payment_mode'
                : 'd.payment_gateway AS payment_mode';
            $sql = "SELECT d.*, p.title as project_name, s.full_name AS referrer_name, s.student_no AS referrer_student_no, {$paymentModeSelect} FROM donations d 
                    LEFT JOIN projects p ON d.project_id = p.id 
                    LEFT JOIN sa_students s ON d.sa_student_id = s.id
                    ORDER BY d.created_at DESC";
            $stmt = $pdo->query($sql);
            $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $projStmt = $pdo->query("SELECT id, title FROM projects WHERE status='Active'");
            $projects = $projStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <div class="hidden md:block w-full overflow-hidden rounded-lg shadow-xs border dark:border-gray-700 bg-white dark:bg-dark-card">
                <div class="w-full overflow-x-auto">
                    <table class="w-full whitespace-no-wrap">
                        <thead>
                            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:text-gray-400 dark:bg-gray-800">
                                <th class="px-4 py-3">Date</th>
                                <th class="p-3 font-medium">Receipt No</th>
                                <th class="px-4 py-3">Donor</th>
                                <th class="px-4 py-3">Referred By</th>
                                <th class="px-4 py-3">Amount</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <template x-for="item in pagedItems" :key="item.id">
                                <tr class="text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                    <td class="px-4 py-3 text-sm" x-text="item.created_at.split(' ')[0]"></td>

                                    <td class="p-3 font-mono text-xs text-gray-500 dark:text-gray-400">
                                        <span x-text="item.receipt_no || 'N/A'"></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-sm" x-text="item.donor_name"></span>
                                            <span class="text-xs text-gray-500" x-text="item.donor_mobile"></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <template x-if="item.referrer_name">
                                            <div class="flex flex-col">
                                                <span class="font-semibold text-gray-800 dark:text-gray-200" x-text="item.referrer_name"></span>
                                                <span class="text-xs text-gray-500" x-text="item.referrer_student_no || item.referral_code"></span>
                                            </div>
                                        </template>
                                        <template x-if="!item.referrer_name">
                                            <span class="text-xs text-gray-400">—</span>
                                        </template>
                                    </td>
                                    <td class="px-4 py-3 font-bold text-green-600">₹<span x-text="item.amount"></span></td>
                                    <td class="px-4 py-3 text-xs">
                                        <span class="px-2 py-1 font-semibold rounded-full"
                                            :class="{
                                                'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': item.payment_status === 'Success',
                                                'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': item.payment_status === 'Failed',
                                                'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': item.payment_status === 'Pending'
                                            }"
                                            x-text="item.payment_status">
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm flex justify-end gap-2">

                                        <button x-show="item.payment_status === 'Pending'" @click="openVerifyModal(item)" class="bg-blue-600 text-white px-3 py-1.5 rounded text-xs hover:bg-blue-700 shadow transition">
                                            Verify
                                        </button>

                                        <button x-show="item.payment_status !== 'Pending'" @click="openVerifyModal(item)" class="bg-gray-100 text-gray-600 px-3 py-1.5 rounded text-xs hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 border dark:border-gray-600">
                                            Details
                                        </button>

                                        <a :href="'generate_receipt.php?id=' + item.id" target="_blank" x-show="item.payment_status === 'Success'" class="bg-green-50 text-green-700 border border-green-200 px-3 py-1.5 rounded text-xs hover:bg-green-100 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400 transition" title="Download PDF">
                                            Receipt
                                        </a>

                                        <button x-show="item.payment_status === 'Success'"
                                            @click="openEmailModal(item.id)"
                                            class="bg-blue-50 text-blue-700 border border-blue-200 px-3 py-1.5 rounded text-xs hover:bg-blue-100 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-400 transition flex items-center gap-1"
                                            title="Email PDF">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                            Email
                                        </button>

                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <div x-show="filteredItems.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
                        No donations found for this period.
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:hidden">
                <template x-for="item in pagedItems" :key="item.id">
                    <div class="bg-white dark:bg-dark-card p-4 rounded-lg shadow border dark:border-gray-700 relative">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h4 class="font-bold text-gray-800 dark:text-white text-lg">₹<span x-text="item.amount"></span></h4>
                                <p class="text-xs mt-1 text-gray-400 font-mono">
                                    Receipt: <span x-text="item.receipt_no || 'Not Generated'"></span>
                                </p>
                            </div>
                            <span class="px-2 py-1 text-xs font-bold rounded-full"
                                :class="{
                                    'bg-green-100 text-green-700': item.payment_status === 'Success',
                                    'bg-red-100 text-red-700': item.payment_status === 'Failed',
                                    'bg-orange-100 text-orange-700': item.payment_status === 'Pending'
                                }"
                                x-text="item.payment_status">
                            </span>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-300 mb-4 border-t border-b py-2 dark:border-gray-700">
                                <div class="flex justify-between"><span>Donor:</span><span class="font-medium" x-text="item.donor_name"></span></div>
                                <div class="flex justify-between mt-1" x-show="item.referrer_name">
                                    <span>Referred By:</span>
                                    <span class="font-medium" x-text="item.referrer_name + ' (' + (item.referrer_student_no || item.referral_code) + ')'"></span>
                                </div>
                                <div class="flex justify-between mt-1"><span>Date:</span><span x-text="item.created_at.split(' ')[0]"></span></div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <div class="flex gap-2">
                                <button @click="openVerifyModal(item)" class="flex-1 bg-blue-600 text-white py-2 rounded-lg text-sm font-bold shadow hover:bg-blue-700">
                                    <span x-text="item.payment_status === 'Pending' ? 'Verify' : 'Details'"></span>
                                </button>
                                <a :href="'generate_receipt.php?id=' + item.id" x-show="item.payment_status === 'Success'" target="_blank" class="flex-1 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-white py-2 rounded-lg text-center text-sm font-medium border dark:border-gray-600">
                                    Receipt
                                </a>
                            </div>

                            <button x-show="item.payment_status === 'Success'"
                                @click="openEmailModal(item.id)"
                                class="w-full bg-blue-50 text-blue-700 border border-blue-200 py-2 rounded-lg text-center text-sm font-medium hover:bg-blue-100 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-400 transition">
                                Send Email
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Pagination UI Controls -->
            <div x-show="filteredItems.length > pageSize" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6 px-4 py-3 bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-2xl shadow-sm">
                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                    Showing <span class="font-bold text-gray-900 dark:text-white" x-text="filteredItems.length === 0 ? 0 : (page - 1) * pageSize + 1"></span> to 
                    <span class="font-bold text-gray-900 dark:text-white" x-text="Math.min(page * pageSize, filteredItems.length)"></span> of 
                    <span class="font-bold text-gray-900 dark:text-white" x-text="filteredItems.length"></span> entries
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="if (page > 1) page--" :disabled="page === 1" 
                        class="px-4 py-2 text-xs font-bold rounded-xl border dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 disabled:opacity-50 disabled:pointer-events-none transition flex items-center gap-1">
                        <i class="fa-solid fa-chevron-left"></i> Previous
                    </button>
                    <div class="flex items-center gap-1">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Page</span>
                        <span class="text-xs font-bold text-gray-900 dark:text-white" x-text="page"></span>
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">of</span>
                        <span class="text-xs font-bold text-gray-900 dark:text-white" x-text="totalPages || 1"></span>
                    </div>
                    <button type="button" @click="if (page < totalPages) page++" :disabled="page === totalPages || totalPages === 0" 
                        class="px-4 py-2 text-xs font-bold rounded-xl border dark:border-gray-700 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600 disabled:opacity-50 disabled:pointer-events-none transition flex items-center gap-1">
                        Next <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <div x-show="isAddModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-80 backdrop-blur-sm p-4" x-cloak>
                <div class="bg-white dark:bg-dark-card rounded-xl shadow-2xl w-full max-w-lg overflow-hidden"
                    @click.away="isAddModalOpen = false"
                    x-data="{ is80G: false }">

                    <div class="p-6 border-b dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-900/50">
                        <h3 class="text-xl font-bold text-gray-800 dark:text-white">Add Manual Donation</h3>
                        <button @click="isAddModalOpen = false" class="text-gray-400 hover:text-red-500 text-2xl transition">&times;</button>
                    </div>

                    <form action="actions/donation_logic.php" method="POST" enctype="multipart/form-data" class="p-6 overflow-y-auto max-h-[80vh] space-y-5">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="create">

                        <div>
                            <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">Project (Optional)</label>
                            <select name="project_id" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                                <option value="">General Donation</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">Donor Name *</label>
                                <input type="text" name="name" required pattern="^[a-zA-Z\s'.\-]+$" title="Donor Name must contain only letters, spaces, apostrophes, periods, or hyphens" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">Amount (₹) *</label>
                                <input type="number" name="amount" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">Mobile *</label>
                                <input type="tel" name="mobile" required maxlength="10" pattern="^[6-9][0-9]{9}$" title="Mobile number must start with 6, 7, 8 or 9 and be exactly 10 digits" placeholder="10-digit number" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1 dark:text-gray-300">Email *</label>
                                <input type="email" name="email" required class="w-full border p-2 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                        </div>

                        <?php if (($settings['enable_80g'] ?? '0') == '1'): ?>
                            <div class="space-y-3 bg-blue-50 dark:bg-gray-900/50 p-4 rounded-lg border border-blue-100 dark:border-gray-700">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" name="is_80g_eligible" value="1" x-model="is80G" class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="font-medium text-gray-800 dark:text-gray-300">Make this an 80G Eligible Donation</span>
                                </label>
                                <div x-show="is80G" x-transition class="pt-2">
                                    <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">PAN Number *</label>
                                    <input type="text" name="pan" :required="is80G" pattern="^[A-Za-z]{5}[0-9]{4}[A-Za-z]{1}$" title="PAN must be in format: 5 letters, 4 digits, 1 letter (e.g. ABCDE1234F)" placeholder="e.g. ABCDE1234F" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition uppercase">
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">Status</label>
                                <select name="status" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                                    <option value="Success">Success (Paid)</option>
                                    <option value="Pending">Pending</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">Transaction ID</label>
                                <input type="text" name="transaction_id" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">Referral Code (Optional)</label>
                                <input type="text" name="referral_code" placeholder="e.g., REF12345" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition uppercase">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">Payment Proof (Optional)</label>
                            <input type="file" name="payment_screenshot" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-gray-50 file:text-gray-700 hover:file:bg-gray-100 dark:file:bg-gray-600 dark:file:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1.5 text-gray-700 dark:text-gray-300">Address</label>
                            <textarea name="address" rows="2" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 outline-none transition"></textarea>
                        </div>

                        <div class="mt-6 pt-5 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                            <button type="button" @click="isAddModalOpen = false" class="px-4 py-2.5 border rounded-lg text-gray-600 dark:text-gray-300 dark:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 transition">Cancel</button>
                            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 shadow-md transition">Save Donation</button>
                        </div>
                    </form>
                </div>
            </div>

            <div x-show="isVerifyModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-80 backdrop-blur-sm p-4" x-cloak>
                <div class="bg-white dark:bg-dark-card rounded-xl shadow-2xl w-full max-w-5xl overflow-hidden flex flex-col md:flex-row max-h-[90vh]" @click.away="closeModal()">

                    <div class="w-full md:w-5/12 bg-gray-900 flex items-center justify-center p-4 relative min-h-[300px]">
                        <template x-if="verifyData.payment_screenshot">
                            <img :src="'../' + verifyData.payment_screenshot" class="max-w-full max-h-[60vh] object-contain rounded border border-gray-600">
                        </template>
                        <template x-if="!verifyData.payment_screenshot">
                            <div class="text-gray-500 flex flex-col items-center">
                                <svg class="w-16 h-16 mb-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span>No Screenshot</span>
                            </div>
                        </template>
                        <div class="absolute bottom-2 left-2 bg-black/60 text-white text-xs px-2 py-1 rounded">Payment Proof</div>
                    </div>

                    <div class="w-full md:w-7/12 p-6 flex flex-col overflow-y-auto">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-xl font-bold text-gray-800 dark:text-white">Donation Details</h3>
                            <button @click="closeModal()" class="text-gray-400 hover:text-red-500 text-2xl font-bold">&times;</button>
                        </div>

                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded border border-blue-100 dark:border-blue-800 mb-6 flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Total Amount</p>
                                <p class="text-3xl font-bold text-blue-700 dark:text-blue-400">₹<span x-text="verifyData.amount"></span></p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-sm font-bold border"
                                :class="{
                                    'bg-green-100 text-green-700 border-green-200': verifyData.payment_status === 'Success',
                                    'bg-red-100 text-red-700 border-red-200': verifyData.payment_status === 'Failed',
                                    'bg-orange-100 text-orange-700 border-orange-200': verifyData.payment_status === 'Pending'
                                  }"
                                x-text="verifyData.payment_status"></span>
                        </div>

                        <div class="grid grid-cols-2 gap-x-4 gap-y-6 text-sm">
                            <div class="col-span-2 md:col-span-1">
                                <p class="text-xs text-gray-500 uppercase dark:text-gray-400">Donor Name</p>
                                <p class="font-medium text-gray-800 dark:text-white text-base" x-text="verifyData.donor_name"></p>
                            </div>
                            <div class="col-span-2 md:col-span-1">
                                <p class="text-xs text-gray-500 uppercase dark:text-gray-400">Transaction ID</p>
                                <p class="font-mono bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded inline-block text-gray-800 dark:text-white" x-text="verifyData.transaction_id || 'TXN-SYS-' + verifyData.id"></p>
                            </div>
                            <div class="col-span-2 md:col-span-1">
                                <p class="text-xs text-gray-500 uppercase dark:text-gray-400">Mobile</p>
                                <p class="font-medium text-gray-800 dark:text-white" x-text="verifyData.donor_mobile"></p>
                            </div>
                            <div class="col-span-2 md:col-span-1">
                                <p class="text-xs text-gray-500 uppercase dark:text-gray-400">Email</p>
                                <p class="font-medium text-gray-800 dark:text-white truncate" x-text="verifyData.donor_email || 'N/A'"></p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-xs text-gray-500 uppercase dark:text-gray-400">Project</p>
                                <p class="font-medium text-gray-800 dark:text-white" x-text="verifyData.project_name || 'General Donation'"></p>
                            </div>
                            <div class="col-span-2 md:col-span-1">
                                <p class="text-xs text-gray-500 uppercase dark:text-gray-400">PAN (For 80G)</p>
                                <p class="font-medium text-gray-800 dark:text-white" x-text="verifyData.donor_pan || 'N/A'"></p>
                            </div>
                            <div class="col-span-2 md:col-span-1">
                                <p class="text-xs text-gray-500 uppercase dark:text-gray-400">Receipt No</p>
                                <p class="font-medium text-gray-800 dark:text-white" x-text="verifyData.receipt_no || 'Not Generated'"></p>
                            </div>
                            <div class="col-span-2 md:col-span-1" x-show="verifyData.referrer_name">
                                <p class="text-xs text-gray-500 uppercase dark:text-gray-400">Referred By</p>
                                <p class="font-medium text-gray-800 dark:text-white" x-text="verifyData.referrer_name + ' (' + (verifyData.referrer_student_no || verifyData.referral_code) + ')'"></p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-xs text-gray-500 uppercase dark:text-gray-400">Address</p>
                                <p class="font-medium text-gray-800 dark:text-white" x-text="verifyData.donor_address || 'N/A'"></p>
                            </div>
                        </div>

                        <div x-show="verifyData.payment_status === 'Pending'" class="mt-auto border-t dark:border-gray-700 pt-5">
                            <div class="flex gap-3">
                                <button @click="openRejectModal()" class="flex-1 py-3 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 font-bold transition">
                                    Reject
                                </button>
                                <form action="actions/donation_logic.php" method="POST" class="flex-1">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="donation_id" :value="verifyData.id">
                                    <input type="hidden" name="status" value="Success">
                                    <button type="submit" class="w-full py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold shadow-lg">
                                        Approve
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="rejectModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm" x-cloak>
                <div class="bg-white dark:bg-dark-card rounded-lg shadow-xl w-full max-w-sm mx-4 p-6 transform transition-all" @click.away="rejectModalOpen = false">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Reject Donation?</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">This will mark the transaction as <b>Failed</b>. This cannot be undone.</p>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <button @click="rejectModalOpen = false" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50">Cancel</button>
                        <form action="actions/donation_logic.php" method="POST" class="w-full">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="donation_id" :value="verifyData.id">
                            <input type="hidden" name="status" value="Failed">
                            <button type="submit" class="w-full rounded-lg bg-red-600 py-2 text-white hover:bg-red-700">Confirm Reject</button>
                        </form>
                    </div>
                </div>
            </div>
            <div x-show="emailModalOpen" class="fixed inset-0 z-[70] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm" x-cloak>
                <div class="bg-white dark:bg-dark-card rounded-lg shadow-xl w-full max-w-sm mx-4 p-6 transform transition-all"
                    @click.away="emailModalOpen = false"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100">

                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/30 mb-4">
                            <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Send Receipt Email?</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                            The PDF receipt will be sent to the donor's registered email address.
                        </p>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <button @click="emailModalOpen = false" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 py-2 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 transition">
                            Cancel
                        </button>

                        <a :href="'actions/send_receipt.php?id=' + emailTargetId" class="w-full inline-flex justify-center items-center rounded-lg bg-blue-600 py-2 text-white hover:bg-blue-700 transition font-medium">
                            Send Now
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('donationManager', () => ({
            items: <?php echo json_encode($donations); ?>,
            search: '',
            filterStatus: 'All',
                    filterPaymentMode: 'All',
            filterDate: 'current_month',
            page: 1,
            pageSize: 15,

            get pagedItems() {
                const start = (this.page - 1) * this.pageSize;
                return this.filteredItems.slice(start, start + this.pageSize);
            },

            get totalPages() {
                return Math.ceil(this.filteredItems.length / this.pageSize);
            },
            customStart: '',
            customEnd: '',

            get today() {
                return new Date().toISOString().split('T')[0];
            },
            isValidDate(dateStr) {
                if (!dateStr) return false;
                const d = new Date(dateStr);
                if (isNaN(d.getTime())) return false;
                const parts = dateStr.split('-');
                if (parts.length !== 3) return false;
                return d.getFullYear() === parseInt(parts[0], 10) &&
                       (d.getMonth() + 1) === parseInt(parts[1], 10) &&
                       d.getDate() === parseInt(parts[2], 10);
            },
            init() {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('action') === 'add' || urlParams.get('add') === '1') {
                    this.isAddModalOpen = true;
                }

                this.$watch('search', () => this.page = 1);
                this.$watch('filterStatus', () => this.page = 1);
                this.$watch('filterPaymentMode', () => this.page = 1);
                this.$watch('filterDate', () => this.page = 1);

                this.$watch('customStart', value => {
                    if (value) {
                        if (!this.isValidDate(value)) {
                            this.customStart = '';
                            return;
                        }
                        const todayStr = this.today;
                        if (value > todayStr) {
                            this.customStart = todayStr;
                        }
                        if (this.customEnd && value > this.customEnd) {
                            this.customStart = this.customEnd;
                        }
                    }
                });

                this.$watch('customEnd', value => {
                    if (value) {
                        if (!this.isValidDate(value)) {
                            this.customEnd = '';
                            return;
                        }
                        const todayStr = this.today;
                        if (value > todayStr) {
                            this.customEnd = todayStr;
                        }
                        if (this.customStart && value < this.customStart) {
                            this.customEnd = this.customStart;
                        }
                    }
                });
            },

            isVerifyModalOpen: false,
            isAddModalOpen: false,
            rejectModalOpen: false,
            emailModalOpen: false,

            verifyData: {},
            emailTargetId: null,

            get filteredItems() {
                if (!this.items) return [];
                const s = this.search.toLowerCase();
                const now = new Date();
                const currentMonth = now.getMonth();
                const currentYear = now.getFullYear();

                return this.items.filter(item => {

                    const name = item.donor_name ? item.donor_name.toLowerCase() : '';
                    const mobile = item.donor_mobile ? item.donor_mobile : '';
                    const tid = item.transaction_id ? item.transaction_id.toLowerCase() : '';
                    const sysId = 'txn-sys-' + item.id;
                    const matchesSearch = name.includes(s) || mobile.includes(s) || tid.includes(s) || sysId.includes(s);
                    const matchesStatus = this.filterStatus === 'All' || item.payment_status === this.filterStatus;
                        const matchesPaymentMode =
                            this.filterPaymentMode === 'All' ||
                            (this.filterPaymentMode === 'Manual' && (!item.payment_mode || item.payment_mode === 'Manual')) ||
                            item.payment_mode === this.filterPaymentMode;
                    let matchesDate = true;
                    const itemDate = new Date(item.created_at);

                    if (this.filterDate === 'current_month') {
                        matchesDate = itemDate.getMonth() === currentMonth && itemDate.getFullYear() === currentYear;
                    } else if (this.filterDate === 'prev_month') {
                        const prevMonthDate = new Date();
                        prevMonthDate.setMonth(now.getMonth() - 1);
                        matchesDate = itemDate.getMonth() === prevMonthDate.getMonth() && itemDate.getFullYear() === prevMonthDate.getFullYear();
                    } else if (this.filterDate === 'this_year') {
                        matchesDate = itemDate.getFullYear() === currentYear;
                    } else if (this.filterDate === 'custom') {
                        if (this.customStart && this.customEnd) {
                            const start = new Date(this.customStart);
                            const end = new Date(this.customEnd);
                            end.setHours(23, 59, 59);
                            matchesDate = itemDate >= start && itemDate <= end;
                        }
                    }
                    return matchesSearch && matchesStatus && matchesPaymentMode && matchesDate;
                });
            },

            openVerifyModal(item) {
                this.verifyData = item;
                this.isVerifyModalOpen = true;
            },
            closeModal() {
                this.isVerifyModalOpen = false;
            },
            openRejectModal() {
                this.rejectModalOpen = true;
            },

            openEmailModal(id) {
                this.emailTargetId = id;
                this.emailModalOpen = true;
            }
        }))
    });
</script>

<?php require 'includes/footer.php'; ?>

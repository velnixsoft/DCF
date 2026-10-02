<?php
require '../config/db.php';
require '../includes/functions.php';
require '../includes/member_module.php';

$csrfToken = generateCsrfToken();
if (!canAccessModule($pdo, 'coordinator', 'page.memberships')) {
    setFlash('error', 'Access denied. Coordinator/Manager/Admin required.');
    header('Location: dashboard.php');
    exit;
}

require 'includes/header.php';

mm_ensure_member_registration_columns($pdo);
$designations = $pdo->query("SELECT * FROM member_designations ORDER BY is_active DESC, fee_amount ASC")->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT 
    m.*, 
    d.title AS designation_title,

    ref.full_name AS referred_by_name,
    ref.member_no AS referred_by_member_no,

    (SELECT COUNT(*) 
     FROM members c 
     WHERE c.referred_by_member_id = m.id) AS member_referrals,

    (SELECT COALESCE(SUM(amount),0) 
     FROM donations dn 
     WHERE dn.referral_code = m.donation_ref_code 
     AND dn.payment_status = 'Success') AS donation_ref_amount

FROM members m

LEFT JOIN member_designations d 
    ON d.id = m.designation_id

LEFT JOIN members ref 
    ON ref.id = m.referred_by_member_id   -- 🔥 IMPORTANT JOIN

ORDER BY m.created_at DESC";
$members = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="memberManager">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            <div class="flex flex-col gap-4 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div>
                        <h3 class="text-2xl md:text-3xl font-medium text-gray-700 dark:text-white">Membership Management</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Registrations, payments, referrals, receipts, and member documents.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="member_messages.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg shadow text-sm">Member Messaging</a>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <div class="bg-white dark:bg-dark-card p-5 rounded-lg shadow-sm border dark:border-gray-700">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h4 class="font-semibold dark:text-white">Designation / Member Category Fees</h4>
                            <span class="text-xs text-gray-500"><?php echo count($designations); ?> total</span>
                        </div>
                        <form action="actions/membership_logic.php" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="action" value="add_designation">
                            <input type="text" name="title" required placeholder="Designation / Category" class="w-full border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <input type="number" step="0.01" min="0" name="fee_amount" required placeholder="Fee Amount" class="w-full border rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <button class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-4 py-2.5 text-sm font-medium">Add Category</button>
                        </form>
                        <div class="space-y-2 max-h-56 overflow-auto pr-1">
                            <?php foreach ($designations as $d): ?>
                                <div class="border rounded-lg p-3 dark:border-gray-700">
                                    <form action="actions/membership_logic.php" method="POST" class="grid grid-cols-1 md:grid-cols-[minmax(0,1.6fr)_150px_auto] gap-3 items-center">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="action" value="update_designation">
                                        <input type="hidden" name="id" value="<?php echo (int)$d['id']; ?>">
                                        <input type="text" name="title" value="<?php echo htmlspecialchars((string)$d['title']); ?>" class="w-full border rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <input type="number" step="0.01" min="0" name="fee_amount" value="<?php echo htmlspecialchars((string)$d['fee_amount']); ?>" class="w-full border rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <button class="bg-slate-800 hover:bg-slate-900 text-white rounded-lg px-4 py-2.5 text-sm font-medium">Save</button>
                                    </form>
                                    <form action="actions/membership_logic.php" method="POST" class="mt-3">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                        <input type="hidden" name="action" value="toggle_designation">
                                        <input type="hidden" name="id" value="<?php echo (int)$d['id']; ?>">
                                        <input type="hidden" name="is_active" value="<?php echo $d['is_active'] ? 0 : 1; ?>">
                                        <button class="text-xs px-2.5 py-1 rounded-full <?php echo $d['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700'; ?>">
                                            <?php echo $d['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-dark-card p-4 rounded-lg shadow-sm border dark:border-gray-700 flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex flex-wrap gap-2 bg-gray-100 dark:bg-gray-700 p-1 rounded-lg">
                        <button @click="tab = 'all'" :class="tab === 'all' ? 'bg-white dark:bg-dark-card shadow text-blue-600' : 'text-gray-500 dark:text-gray-300'" class="px-4 py-2 rounded-md text-sm font-medium transition">All Members</button>
                        <button @click="tab = 'pending'" :class="tab === 'pending' ? 'bg-white dark:bg-dark-card shadow text-orange-600' : 'text-gray-500 dark:text-gray-300'" class="px-4 py-2 rounded-md text-sm font-medium transition flex items-center gap-2">
                            Requests <span x-show="pendingCount > 0" class="bg-orange-100 text-orange-600 text-xs px-1.5 rounded-full" x-text="pendingCount"></span>
                        </button>
                        <button @click="tab = 'blocked'" :class="tab === 'blocked' ? 'bg-white dark:bg-dark-card shadow text-red-600' : 'text-gray-500 dark:text-gray-300'" class="px-4 py-2 rounded-md text-sm font-medium transition">Blocked</button>
                    </div>

                    <div class="relative w-full md:w-72">
                        <input type="text" x-model="search" placeholder="Search name, member no, phone..." class="w-full pl-10 pr-4 py-2 border rounded-lg bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="hidden md:block bg-white dark:bg-dark-card rounded-lg shadow overflow-hidden border dark:border-gray-700">
                <table class="w-full whitespace-no-wrap">
                    <thead>
                        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
                            <th class="px-4 py-3">Profile</th>
                            <th class="px-4 py-3">Membership</th>
                            <th class="px-4 py-3">Payment</th>
                            <th class="px-4 py-3">Referrals</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        <template x-for="member in pagedItems" :key="member.id">
                            <tr class="text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition align-top">
                                <td class="px-4 py-3">
                                    <div class="flex items-center text-sm">
                                        <img :src="member.photo ? '../' + member.photo : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(member.full_name || 'Member')" class="w-10 h-10 rounded-full object-cover border mr-3">
                                        <div>
                                            <p class="font-semibold" x-text="member.full_name"></p>
                                            <p class="text-xs text-gray-500" x-text="member.email"></p>
                                            <p class="text-xs text-gray-500" x-text="member.phone"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <p class="font-mono font-bold text-blue-600" x-text="member.member_no || 'Not Issued'"></p>
                                    <p class="text-xs text-gray-500" x-text="member.designation_title || 'No designation'"></p>
                                    <p class="text-xs text-gray-500" x-show="member.event_title">Event: <span x-text="member.event_title"></span></p>
                                    <p class="text-xs text-gray-500" x-show="member.occasion_name">Occasion: <span x-text="member.occasion_name"></span></p>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <p class="text-xs font-semibold" :class="getPaymentClass(member.payment_status)" x-text="member.payment_status"></p>
                                    <p class="text-xs text-gray-500">INR <span x-text="formatMoney(member.membership_fee)"></span></p>
                                    <p class="text-xs text-gray-500" x-text="member.payment_txn_id || 'No TXN'"></p>
                                    <div class="mt-1 flex flex-wrap gap-2 text-xs">
                                        <a x-show="member.payment_proof" :href="'../' + member.payment_proof" target="_blank" class="text-indigo-600 hover:underline">Proof</a>
                                        <a x-show="member.member_receipt_no" :href="'generate_member_receipt.php?id=' + member.id" target="_blank" class="text-blue-600 hover:underline">Receipt</a>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <p class="text-xs text-gray-700 dark:text-gray-300">Member refs: <span x-text="member.member_referrals || 0"></span></p>
                                    <p class="text-xs text-gray-700 dark:text-gray-300 mt-1">
    Referred By: 
    <span x-text="member.referred_by_name || 'Self / Direct'"></span>
</p>
                                    <p class="text-xs text-gray-700 dark:text-gray-300">Donation refs: INR <span x-text="formatMoney(member.donation_ref_amount)"></span></p>
                                    <p class="text-[11px] text-gray-500 mt-1">M: <span x-text="member.referral_code || '-'"></span></p>
                                    <p class="text-[11px] text-gray-500">D: <span x-text="member.donation_ref_code || '-'"></span></p>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <span class="px-2 py-1 rounded-full font-semibold" :class="getStatusClass(member.status)" x-text="member.status"></span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <button type="button" @click="openEditModal(member)" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 shadow">Edit</button>
                                        <button type="button" @click="openPaymentModal(member)" class="bg-amber-500 text-white px-3 py-1 rounded text-xs hover:bg-amber-600 shadow">Payment</button>

                                        <template x-if="member.payment_status !== 'Success'">
                                            <form action="actions/membership_logic.php" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="verify_member">
                                                <input type="hidden" name="id" :value="member.id">
                                                <button class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700 shadow">Verify</button>
                                            </form>
                                        </template>

                                        <template x-if="member.status === 'Blocked'">
                                            <form action="actions/membership_logic.php" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="update_member_status">
                                                <input type="hidden" name="id" :value="member.id">
                                                <input type="hidden" name="status" value="Active">
                                                <button class="bg-green-100 text-green-700 px-3 py-1 rounded text-xs hover:bg-green-200">Unblock</button>
                                            </form>
                                        </template>

                                        <template x-if="member.status !== 'Blocked'">
                                            <form action="actions/membership_logic.php" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="update_member_status">
                                                <input type="hidden" name="id" :value="member.id">
                                                <input type="hidden" name="status" value="Blocked">
                                                <button class="bg-red-100 text-red-700 px-3 py-1 rounded text-xs hover:bg-red-200">Block</button>
                                            </form>
                                        </template>

                                        <a :href="'document_studio.php?id=' + member.id + '&type=id_card'" class="text-blue-600 hover:text-blue-800 p-1" title="ID Card">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                            </svg>
                                        </a>
                                        <a :href="'document_studio.php?id=' + member.id + '&type=membership_certificate'" class="text-emerald-600 hover:text-emerald-800 p-1" title="Certificate">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"></path>
                                            </svg>
                                        </a>
                                        <a :href="'document_studio.php?id=' + member.id" class="text-slate-600 hover:text-slate-900 p-1" title="Studio">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"></path>
                                            </svg>
                                        </a>
                                        <a :href="'document_studio.php?id=' + member.id + '&type=membership_certificate'" class="text-violet-600 hover:text-violet-800 p-1" title="Email Certificate">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                        </a>
                                        <button type="button" @click="confirmDelete(member.id)" class="text-red-600 hover:text-red-800 p-1" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22m-5-3H6a1 1 0 00-1 1v2h14V5a1 1 0 00-1-1z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div x-show="filteredItems.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">No members found.</div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:hidden pb-20">
                <template x-for="member in pagedItems" :key="member.id">
                    <div class="bg-white dark:bg-dark-card p-4 rounded-lg shadow border dark:border-gray-700 relative flex flex-col">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <img :src="member.photo ? '../' + member.photo : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(member.full_name || 'Member')" class="w-12 h-12 rounded-full object-cover border border-gray-200">
                                <div>
                                    <h4 class="font-bold text-gray-800 dark:text-white text-base truncate max-w-[160px]" x-text="member.full_name"></h4>
                                    <p class="text-xs text-blue-600 font-mono" x-text="member.member_no || 'Not Issued'"></p>
                                </div>
                            </div>
                            <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide flex-shrink-0" :class="getStatusClass(member.status)" x-text="member.status"></span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-y-2 text-sm text-gray-600 dark:text-gray-300 border-t border-b py-3 dark:border-gray-700 flex-1">
                            <div>
                                <span class="text-[10px] text-gray-400 uppercase block">Designation</span>
                                <span x-text="member.designation_title || 'N/A'"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] text-gray-400 uppercase block">Payment</span>
                                <span :class="getPaymentClass(member.payment_status)" x-text="member.payment_status"></span>
                            </div>
                            <div>
                                <span class="text-[10px] text-gray-400 uppercase block">Phone</span>
                                <span x-text="member.phone"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] text-gray-400 uppercase block">Fee</span>
                                <span>INR <span x-text="formatMoney(member.membership_fee)"></span></span>
                            </div>
                        </div>

                        <div class="mt-3 space-y-2">
                            <template x-if="member.payment_status !== 'Success'">
                                <form action="actions/membership_logic.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                    <input type="hidden" name="action" value="verify_member">
                                    <input type="hidden" name="id" :value="member.id">
                                    <button class="w-full bg-green-600 text-white py-2 rounded-lg text-sm font-medium shadow">Verify Member</button>
                                </form>
                            </template>

                            <div class="flex gap-2" x-data="{ openMenu: false }">
                                <button @click="openEditModal(member)" class="flex-1 bg-gray-100 text-gray-700 py-2 rounded-lg text-sm font-medium dark:bg-gray-700 dark:text-white border dark:border-gray-600">Edit</button>
                                <button @click="openPaymentModal(member)" class="flex-1 bg-gray-100 text-gray-700 py-2 rounded-lg text-sm font-medium dark:bg-gray-700 dark:text-white border dark:border-gray-600">Payment</button>

                                <div class="relative">
                                    <button @click="openMenu = !openMenu" class="bg-blue-600 text-white p-2 rounded-lg shadow h-full aspect-square flex items-center justify-center">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                        </svg>
                                    </button>

                                    <div x-show="openMenu" @click.away="openMenu = false" x-transition class="absolute right-0 bottom-full mb-2 w-52 bg-white dark:bg-gray-800 rounded-lg shadow-xl border dark:border-gray-700 z-10 overflow-hidden">
                                        <a :href="'generate_member_document.php?id=' + member.id + '&type=id_card'" target="_blank" class="block w-full text-left px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 border-b dark:border-gray-700">Download ID Card</a>
                                        <a :href="'generate_member_document.php?id=' + member.id + '&type=membership_certificate'" target="_blank" class="block w-full text-left px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 border-b dark:border-gray-700">Download Certificate</a>
                                        <a :href="'generate_member_receipt.php?id=' + member.id'" target="_blank" class="block w-full text-left px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 border-b dark:border-gray-700">Download Receipt</a>
                                        <a :href="'actions/send_member_document.php?id=' + member.id + '&type=membership_certificate'" class="block w-full text-left px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 border-b dark:border-gray-700">Email Certificate</a>
                                        <a :href="'document_studio.php?member_id=' + member.id" class="block w-full text-left px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 border-b dark:border-gray-700">Open Studio</a>
                                        <template x-if="member.status === 'Blocked'">
                                            <form action="actions/membership_logic.php" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="update_member_status">
                                                <input type="hidden" name="id" :value="member.id">
                                                <input type="hidden" name="status" value="Active">
                                                <button class="block w-full text-left px-4 py-3 text-sm text-green-600 hover:bg-gray-100 dark:hover:bg-gray-700 border-b dark:border-gray-700">Unblock</button>
                                            </form>
                                        </template>
                                        <template x-if="member.status !== 'Blocked'">
                                            <form action="actions/membership_logic.php" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="update_member_status">
                                                <input type="hidden" name="id" :value="member.id">
                                                <input type="hidden" name="status" value="Blocked">
                                                <button class="block w-full text-left px-4 py-3 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700 border-b dark:border-gray-700">Block</button>
                                            </form>
                                        </template>
                                        <button @click="confirmDelete(member.id); openMenu = false" class="block w-full text-left px-4 py-3 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">Delete</button>
                                    </div>
                                </div>
                            </div>
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
        </main>
    </div>
</div>

<div id="paymentModal" class="fixed inset-0 hidden z-[90] items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="bg-white dark:bg-gray-800 w-full max-w-xl rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b dark:border-gray-700 flex items-center justify-between">
            <div>
                <h4 class="text-lg font-bold text-gray-900 dark:text-white">Update Membership Payment</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400" id="paymentModalSub"></p>
            </div>
            <button type="button" class="text-gray-500 hover:text-gray-800 dark:text-gray-300" onclick="closePaymentModal()">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <form id="paymentForm" action="actions/membership_logic.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="update_payment">
            <input type="hidden" name="id" id="pay_member_id" value="">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Payment Gateway</label>
                    <select name="payment_gateway" id="pay_gateway" class="w-full p-3 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="Manual">Manual</option>
                        <option value="Razorpay">Razorpay</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Payment Status</label>
                    <select name="payment_status" id="pay_status" class="w-full p-3 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="Pending">Pending</option>
                        <option value="Success">Success</option>
                        <option value="Failed">Failed</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Transaction ID</label>
                    <input type="text" name="payment_txn_id" id="pay_txn" class="w-full p-3 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="UTR / TXN / RZP Payment ID">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Membership Fee (INR)</label>
                    <input type="number" step="0.01" min="0" name="membership_fee" id="pay_fee" class="w-full p-3 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Valid Until (optional)</label>
                <input type="date" name="valid_until" id="pay_valid_until" class="w-full p-3 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Upload Payment Proof (optional)</label>
                <input type="file" name="payment_proof" accept="image/jpeg,image/png,image/webp,application/pdf" class="w-full text-sm dark:text-gray-300">
                <p class="text-xs text-gray-500 mt-1">Max 2MB (JPG/PNG/WEBP/PDF)</p>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-3 rounded-lg font-semibold">Save</button>
                <button type="button" class="flex-1 bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-800 dark:text-gray-200 py-3 rounded-lg" onclick="closePaymentModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php include 'edit_member_modal.html'; ?>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('memberManager', () => ({
            items: <?php echo json_encode($members); ?>,
            search: '',
            tab: 'all',
            page: 1,
            pageSize: 5,

            init() {
                this.$watch('search', () => this.page = 1);
                this.$watch('tab', () => this.page = 1);
            },

            get pagedItems() {
                const start = (this.page - 1) * this.pageSize;
                return this.filteredItems.slice(start, start + this.pageSize);
            },

            get totalPages() {
                return Math.ceil(this.filteredItems.length / this.pageSize);
            },

            get filteredItems() {
                const s = this.search.toLowerCase();
                const searched = this.items.filter((item) => {
                    return (item.full_name || '').toLowerCase().includes(s)
                        || (item.member_no || '').toLowerCase().includes(s)
                        || (item.phone || '').toLowerCase().includes(s)
                        || (item.email || '').toLowerCase().includes(s);
                });

                if (this.tab === 'pending') {
                    return searched.filter(i => i.status === 'Pending' || i.payment_status === 'Pending');
                }
                if (this.tab === 'blocked') {
                    return searched.filter(i => i.status === 'Blocked');
                }
                return searched;
            },

            get pendingCount() {
                return this.items.filter(i => i.status === 'Pending' || i.payment_status === 'Pending').length;
            },

            formatMoney(value) {
                const num = Number(value || 0);
                return num.toFixed(2);
            },

            getStatusClass(status) {
                return {
                    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': status === 'Active',
                    'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': status === 'Pending',
                    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': status === 'Blocked'
                };
            },

            getPaymentClass(status) {
                return {
                    'text-green-600 dark:text-green-400': status === 'Success',
                    'text-orange-600 dark:text-orange-400': status === 'Pending',
                    'text-red-600 dark:text-red-400': status === 'Failed'
                };
            },

            openEditModal(member) {
                window.openEditModal(member);
            },

            openPaymentModal(member) {
                window.openPaymentModal(member);
            },

            confirmDelete(id) {
                window.confirmDelete(id, <?php echo json_encode($csrfToken); ?>);
            }
        }));
    });

    function openPaymentModal(member) {
        document.getElementById('pay_member_id').value = member.id || '';
        document.getElementById('pay_gateway').value = member.payment_gateway || 'Manual';
        document.getElementById('pay_status').value = member.payment_status || 'Pending';
        document.getElementById('pay_txn').value = member.payment_txn_id || '';
        document.getElementById('pay_fee').value = member.membership_fee || 0;
        document.getElementById('pay_valid_until').value = member.valid_until || '';
        const sub = document.getElementById('paymentModalSub');
        const name = (member.full_name || '').toString();
        const email = (member.email || '').toString();
        sub.textContent = name ? (name + (email ? ' ， ' + email : '')) : '';

        const m = document.getElementById('paymentModal');
        m.classList.remove('hidden');
        m.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closePaymentModal() {
        const m = document.getElementById('paymentModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
        document.body.style.overflow = 'unset';
    }

    const adminStateDistrictMap = <?php require_once __DIR__ . '/../includes/india_locations.php'; echo india_state_district_js(); ?>;

    function initEditMemberStateOptions() {
        const stateSelect = document.getElementById('edit_state');
        if (!stateSelect || stateSelect.options.length > 1) return;
        let html = '<option value="">Select State</option>';
        Object.keys(adminStateDistrictMap).forEach(s => {
            html += `<option value="${s.replace(/"/g, '&quot;')}">${s}</option>`;
        });
        stateSelect.innerHTML = html;
    }

    function updateEditMemberDistricts(selectedDistrictVal = '') {
        const stateSelect = document.getElementById('edit_state');
        const distSelect = document.getElementById('edit_district');
        if (!stateSelect || !distSelect) return;
        const state = stateSelect.value;
        const dists = adminStateDistrictMap[state] || [];
        let html = '<option value="">Select District</option>';
        dists.forEach(d => {
            const sel = (d === selectedDistrictVal || d === distSelect.value) ? 'selected' : '';
            html += `<option value="${d.replace(/"/g, '&quot;')}" ${sel}>${d}</option>`;
        });
        distSelect.innerHTML = html;
    }

    function openEditModal(member) {
        initEditMemberStateOptions();
        document.querySelector('#editForm [name="csrf_token"]').value = <?php echo json_encode($csrfToken); ?>;
        document.getElementById('edit_member_id').value = member.id || '';
        document.getElementById('edit_full_name').value = member.full_name || '';
        document.getElementById('edit_email').value = member.email || '';
        document.getElementById('edit_phone').value = member.phone || '';
        document.getElementById('edit_blood_group').value = member.blood_group || '';
        document.getElementById('edit_status').value = member.status || 'Pending';
        document.getElementById('edit_qualification').value = member.qualification || '';
        document.getElementById('edit_profession').value = member.profession || '';
        document.getElementById('edit_marital_status').value = member.marital_status || '';
        document.getElementById('edit_address').value = member.address || '';
        document.getElementById('edit_state').value = member.state || '';
        updateEditMemberDistricts(member.district || '');
        document.getElementById('edit_local_body_type').value = member.local_body_type || '';
        document.getElementById('edit_local_body_name').value = member.local_body_name || '';
        document.getElementById('edit_ward_no').value = member.ward_no || '';
        document.getElementById('edit_ward_name').value = member.ward_name || '';
        document.getElementById('edit_kudumbha_samithi').value = member.kudumbha_samithi || '';
        document.getElementById('edit_existing_photo').value = member.photo || '';

        const photoPreview = document.getElementById('edit_photo_preview');
        const currentSection = document.getElementById('currentPhotoSection');
        const currentImg = document.getElementById('current_photo');
        const designationSelect = document.getElementById('edit_designation_id');

        let desHtml = '<option value="">Select Designation</option>';
        <?php foreach ($designations as $d): ?>
        desHtml += '<option value="<?php echo (int)$d["id"]; ?>"><?php echo htmlspecialchars($d["title"]); ?> (INR <?php echo number_format((float)$d["fee_amount"], 2); ?>)</option>';
        <?php endforeach; ?>
        designationSelect.innerHTML = desHtml;
        designationSelect.value = member.designation_id || '';

        if (member.photo) {
            currentImg.src = '../' + member.photo;
            currentSection.classList.remove('hidden');
            photoPreview.classList.add('hidden');
        } else {
            currentSection.classList.add('hidden');
            photoPreview.classList.add('hidden');
        }

        const sub = document.getElementById('editModalSub');
        sub.textContent = (member.full_name || '') + ' ， ' + (member.email || '');

        const m = document.getElementById('editModal');
        m.classList.remove('hidden');
        m.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
        const m = document.getElementById('editModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
        document.body.style.overflow = 'unset';
        document.getElementById('editForm').reset();
    }

    function confirmDelete(id, csrf) {
        if (confirm('Delete this member permanently? This cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'actions/membership_logic.php';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="${csrf}">
                <input type="hidden" name="action" value="delete_member">
                <input type="hidden" name="id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    document.getElementById('editForm').addEventListener('submit', function(e) {
        const fileInput = this.querySelector('[name="photo"]');
        const existingPhoto = document.getElementById('edit_existing_photo').value;
        if (fileInput.files.length === 0 && !existingPhoto) {
            alert('Please upload a photo or keep existing one.');
            e.preventDefault();
            return false;
        }
        return true;
    });

    document.querySelector('#editForm [name="photo"]').addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const preview = document.getElementById('edit_photo_preview');
            const url = URL.createObjectURL(file);
            preview.src = url;
            preview.classList.remove('hidden');
            document.getElementById('currentPhotoSection').classList.add('hidden');
        }
    });
</script>

<?php require 'includes/footer.php'; ?>

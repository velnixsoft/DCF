<?php require 'includes/header.php'; ?>
<?php require '../config/db.php'; ?>
<?php require '../includes/template_builder.php'; ?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="volManager">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">

            <div class="flex flex-col gap-4 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-700 dark:text-white">Volunteer Management</h3>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="template-builder.php" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg shadow text-sm">
                            Template Builder
                        </a>
                        <button @click="isBulkModalOpen = true" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-lg shadow flex items-center gap-2 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                            </svg>
                            Bulk Upload
                        </button>
                        <button @click="openModal('create')" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg shadow flex items-center gap-2 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add Volunteer
                        </button>
                    </div>
                </div>

                <div class="bg-white dark:bg-dark-card p-4 rounded-lg shadow-sm border dark:border-gray-700 flex flex-col md:flex-row justify-between items-center gap-4">

                    <div class="flex space-x-2 bg-gray-100 dark:bg-gray-700 p-1 rounded-lg">
                        <button @click="tab = 'active'" :class="tab === 'active' ? 'bg-white dark:bg-dark-card shadow text-blue-600' : 'text-gray-500 dark:text-gray-300'" class="px-4 py-2 rounded-md text-sm font-medium transition">All Volunteers</button>
                        <button @click="tab = 'pending'" :class="tab === 'pending' ? 'bg-white dark:bg-dark-card shadow text-orange-600' : 'text-gray-500 dark:text-gray-300'" class="px-4 py-2 rounded-md text-sm font-medium transition flex items-center gap-2">
                            Requests <span x-show="pendingCount > 0" class="bg-orange-100 text-orange-600 text-xs px-1.5 rounded-full" x-text="pendingCount"></span>
                        </button>
                    </div>

                    <div class="relative w-full md:w-64">
                        <input type="text" x-model="search" placeholder="Search Name, ID or Phone..." class="w-full pl-10 pr-4 py-2 border rounded-lg bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" />
                        </svg>
                    </div>
                </div>
            </div>

            <?php
            $volunteers = $pdo->query("SELECT * FROM volunteers ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <div class="hidden md:block bg-white dark:bg-dark-card rounded-lg shadow overflow-hidden border dark:border-gray-700">
                <table class="w-full whitespace-no-wrap">
                    <thead>
                        <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
                            <th class="px-4 py-3">Profile</th>
                            <th class="px-4 py-3">ID Details</th>
                            <th class="px-4 py-3">Contact</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        <template x-for="vol in pagedItems" :key="vol.id">
                            <tr class="text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <td class="px-4 py-3">
                                    <div class="flex items-center text-sm">
                                        <img :src="vol.photo ? '../' + vol.photo : 'https://ui-avatars.com/api/?name=' + vol.name" class="w-10 h-10 rounded-full object-cover border mr-3">
                                        <div>
                                            <p class="font-semibold" x-text="vol.name"></p>
                                            <p class="text-xs text-gray-500 flex items-center gap-1">
                                                <span x-show="vol.blood_group" class="bg-red-100 text-red-600 px-1 rounded text-[10px]" x-text="vol.blood_group"></span>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div x-show="vol.id_card_no">
                                        <p class="font-mono font-bold text-blue-600" x-text="vol.id_card_no"></p>
                                        <p class="text-xs" :class="isExpiring(vol.valid_until) ? 'text-red-500 font-bold' : 'text-green-600'">
                                            Exp: <span x-text="formatDate(vol.valid_until)"></span>
                                        </p>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <p x-text="vol.phone"></p>
                                    <p class="text-xs text-gray-500" x-text="vol.email"></p>
                                </td>
                                <td class="px-4 py-3 text-xs"><span class="px-2 py-1 rounded-full font-semibold" :class="getStatusClass(vol.status)" x-text="vol.status"></span></td>
                                <td class="px-4 py-3 text-sm flex justify-end gap-2">
                                    <template x-if="vol.status === 'Pending'">
                                        <button @click="openVerifyModal(vol)" class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700 shadow">Verify</button>
                                    </template>
                                    <template x-if="vol.status !== 'Pending' && vol.status !== 'Rejected'">
                                        <div class="flex gap-2 items-center">

                                            <form action="actions/volunteer_logic.php" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="id" :value="vol.id">
                                                <input type="hidden" name="status" :value="vol.status === 'Active' ? 'Inactive' : 'Active'">
                                                <button class="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700" :title="vol.status === 'Active' ? 'Deactivate' : 'Activate'">
                                                    <svg x-show="vol.status === 'Active'" class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <svg x-show="vol.status !== 'Active'" class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                            <form action="actions/volunteer_logic.php" method="POST" x-show="isExpiring(vol.valid_until)"><input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"><input type="hidden" name="action" value="renew"><input type="hidden" name="id" :value="vol.id"><button class="bg-purple-100 text-purple-700 border border-purple-200 px-2 py-1 rounded text-xs hover:bg-purple-200 animate-pulse font-bold" title="Renew 1 Year">Renew</button></form>
                                            <button @click="openVerifyModal(vol)" class="text-gray-500 hover:text-blue-600 p-1" title="View Details"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg></button>
                                            <a :href="'generate_idcard.php?id='+vol.id" class="text-blue-600 hover:text-blue-800 p-1" title="Download ID"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                                </svg></a>
                                            <a :href="'generate_volunteer_certificate.php?id='+vol.id" class="text-emerald-600 hover:text-emerald-800 p-1" title="Download Certificate"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"></path>
                                                </svg></a>
                                            <button @click="openEmailModal(vol.id)" class="text-gray-600 hover:text-blue-600 p-1" title="Email ID"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg></button>
                                            <a :href="'actions/send_volunteer_certificate.php?id='+vol.id" class="text-violet-600 hover:text-violet-800 p-1" title="Email Certificate"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg></a>
                                            <button @click="openModal('edit', vol)" class="text-gray-600 hover:text-orange-600 p-1" title="Edit"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                                </svg></button>
                                        </div>
                                    </template>
                                    <button @click="openDeleteModal(vol.id)" class="text-red-600 hover:text-red-800 p-1" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div x-show="filteredItems.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">No volunteers found.</div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:hidden pb-20">
                <template x-for="vol in pagedItems" :key="vol.id">
                    <div class="bg-white dark:bg-dark-card p-4 rounded-lg shadow border dark:border-gray-700 relative flex flex-col">

                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <img :src="vol.photo ? '../' + vol.photo : 'https://ui-avatars.com/api/?name=' + vol.name" class="w-12 h-12 rounded-full object-cover border border-gray-200">
                                <div>
                                    <h4 class="font-bold text-gray-800 dark:text-white text-base truncate max-w-[150px]" x-text="vol.name"></h4>
                                    <p class="text-xs text-blue-600 font-mono" x-text="vol.id_card_no || 'No ID'"></p>
                                </div>
                            </div>
                            <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide flex-shrink-0"
                                :class="getStatusClass(vol.status)"
                                x-text="vol.status">
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-y-2 text-sm text-gray-600 dark:text-gray-300 border-t border-b py-3 dark:border-gray-700 flex-1">
                            <div>
                                <span class="text-[10px] text-gray-400 uppercase block">Phone</span>
                                <span x-text="vol.phone"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] text-gray-400 uppercase block">Expiry</span>
                                <span :class="isExpiring(vol.valid_until) ? 'text-red-500 font-bold' : ''" x-text="formatDate(vol.valid_until) || 'N/A'"></span>
                            </div>
                        </div>

                        <div class="mt-3 space-y-2">

                            <form action="actions/volunteer_logic.php" method="POST" x-show="isExpiring(vol.valid_until)" class="flex-1">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="action" value="renew">
                                <input type="hidden" name="id" :value="vol.id">
                                <button class="w-full bg-purple-100 text-purple-700 py-2 rounded-lg text-sm font-bold animate-pulse border border-purple-200">Renew Now</button>
                            </form>

                            <template x-if="vol.status === 'Pending'">
                                <div class="flex gap-2">
                                    <button @click="openVerifyModal(vol)" class="flex-1 bg-blue-600 text-white py-2 rounded-lg text-sm font-medium shadow">Verify Request</button>
                                    <button @click="openDeleteModal(vol.id)" class="bg-red-100 hover:bg-red-200 text-red-600 p-2.5 rounded-lg border border-red-200 flex items-center justify-center" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </template>

                            <template x-if="vol.status !== 'Pending'">
                                <div class="flex gap-2" x-data="{ openMenu: false }">

                                    <button @click="openVerifyModal(vol)" class="flex-1 bg-gray-100 text-gray-700 py-2 rounded-lg text-sm font-medium dark:bg-gray-700 dark:text-white border dark:border-gray-600">
                                        View
                                    </button>

                                    <button @click="openModal('edit', vol)" class="flex-1 bg-gray-100 text-gray-700 py-2 rounded-lg text-sm font-medium dark:bg-gray-700 dark:text-white border dark:border-gray-600">
                                        Edit
                                    </button>

                                    <div class="relative">
                                        <button @click="openMenu = !openMenu" class="bg-blue-600 text-white p-2 rounded-lg shadow h-full aspect-square flex items-center justify-center">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                            </svg>
                                        </button>

                                        <div x-show="openMenu" @click.away="openMenu = false"
                                            x-transition
                                            class="absolute right-0 bottom-full mb-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-xl border dark:border-gray-700 z-10 overflow-hidden">

                                            <a :href="'generate_idcard.php?id='+vol.id" class="block w-full text-left px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 border-b dark:border-gray-700">
                                                Download ID
                                            </a>
                                            <a :href="'generate_volunteer_certificate.php?id='+vol.id" class="block w-full text-left px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 border-b dark:border-gray-700">
                                                Download Certificate
                                            </a>

                                            <button @click="openEmailModal(vol.id); openMenu = false" class="block w-full text-left px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 border-b dark:border-gray-700">
                                                Email ID
                                            </button>
                                            <a :href="'actions/send_volunteer_certificate.php?id='+vol.id" class="block w-full text-left px-4 py-3 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 border-b dark:border-gray-700">
                                                Email Certificate
                                            </a>
                                            <form action="actions/volunteer_logic.php" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="id" :value="vol.id">
                                                <input type="hidden" name="status" :value="vol.status === 'Active' ? 'Inactive' : 'Active'">
                                                <button class="block w-full text-left px-4 py-3 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 border-b dark:border-gray-700"
                                                    :class="vol.status === 'Active' ? 'text-orange-600' : 'text-green-600'"
                                                    x-text="vol.status === 'Active' ? 'Deactivate' : 'Activate'">
                                                </button>
                                            </form>
                                            <button @click="openDeleteModal(vol.id); openMenu = false" class="block w-full text-left px-4 py-3 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700 font-semibold">
                                                Delete Permanent
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
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

            <div x-show="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-80 backdrop-blur-sm p-4" x-cloak>
                <div class="bg-white dark:bg-dark-card rounded-lg shadow-xl w-full max-w-lg overflow-hidden" @click.away="isModalOpen = false">
                    <div class="p-6 border-b dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-xl font-bold dark:text-white" x-text="mode==='create'?'Add Volunteer':'Edit Volunteer'"></h3><button @click="isModalOpen = false" class="text-gray-500">✕</button>
                    </div>
                    <form action="actions/volunteer_logic.php" method="POST" enctype="multipart/form-data" class="p-6 overflow-y-auto max-h-[80vh]"><input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" x-model="formData.id"><input type="hidden" name="existing_photo" x-model="formData.photo">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2"><label class="block text-sm mb-1 dark:text-gray-300">Full Name *</label><input type="text" name="name" x-model="formData.name" required class="w-full border p-2 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"></div>
                            <div><label class="block text-sm mb-1 dark:text-gray-300">Email *</label><input type="email" name="email" x-model="formData.email" required class="w-full border p-2 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"></div>
                            <div><label class="block text-sm mb-1 dark:text-gray-300">Phone *</label><input type="text" name="phone" x-model="formData.phone" required class="w-full border p-2 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"></div>
                            <div><label class="block text-sm mb-1 dark:text-gray-300">Qualification</label><input type="text" name="qualification" x-model="formData.qualification" class="w-full border p-2 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"></div>
                            <div><label class="block text-sm mb-1 dark:text-gray-300">Profession</label><input type="text" name="profession" x-model="formData.profession" class="w-full border p-2 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"></div>
                            <div><label class="block text-sm mb-1 dark:text-gray-300">Marital Status</label><select name="marital_status" x-model="formData.marital_status" class="w-full border p-2 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">Select</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Widowed">Widowed</option>
                                    <option value="Divorced">Divorced</option>
                                    <option value="Separated">Separated</option>
                                </select></div>
                            <div><label class="block text-sm mb-1 dark:text-gray-300">Blood Group</label><select name="blood_group" x-model="formData.blood_group" class="w-full border p-2 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">Select</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                </select></div>
                            <div><label class="block text-sm mb-1 dark:text-gray-300">Validity (From Today)</label><select name="validity_type" x-model="validityType" class="w-full border p-2 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="1M">1 Month</option>
                                    <option value="3M">3 Months</option>
                                    <option value="6M">6 Months</option>
                                    <option value="1Y">1 Year (Default)</option>
                                    <option value="Lifetime">Lifetime</option>
                                    <option value="Custom">Custom Date</option>
                                </select></div>
                            <div class="col-span-2" x-show="validityType === 'Custom'"><label class="block text-sm mb-1 dark:text-gray-300">Valid Until Date</label><input type="date" name="custom_date" class="w-full border p-2 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"></div>
                            <div class="col-span-2"><label class="block text-sm mb-1 dark:text-gray-300">Address (Max 250 chars)</label><textarea name="address" x-model="formData.address" rows="2" maxlength="250" class="w-full border p-2 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea></div>
                            <div><label class="block text-sm mb-1 dark:text-gray-300">District</label><input type="text" name="district" x-model="formData.district" class="w-full border p-2 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"></div>
                            <div><label class="block text-sm mb-1 dark:text-gray-300">State</label><input type="text" name="state" x-model="formData.state" class="w-full border p-2 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"></div>
                            <div><label class="block text-sm mb-1 dark:text-gray-300">Local Body Type</label><select name="local_body_type" x-model="formData.local_body_type" class="w-full border p-2 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="">Select</option>
                                    <option value="Panchayat">Panchayat</option>
                                    <option value="Municipality">Municipality</option>
                                    <option value="Corporation">Corporation</option>
                                </select></div>
                            <div><label class="block text-sm mb-1 dark:text-gray-300">Local Body Name</label><input type="text" name="local_body_name" x-model="formData.local_body_name" class="w-full border p-2 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"></div>
                            <div><label class="block text-sm mb-1 dark:text-gray-300">Ward No</label><input type="text" name="ward_no" x-model="formData.ward_no" class="w-full border p-2 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"></div>
                            <div><label class="block text-sm mb-1 dark:text-gray-300">Ward Name</label><input type="text" name="ward_name" x-model="formData.ward_name" class="w-full border p-2 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"></div>
                            <div class="col-span-2"><label class="block text-sm mb-1 dark:text-gray-300">Kudumbha Samithi</label><input type="text" name="kudumbha_samithi" x-model="formData.kudumbha_samithi" class="w-full border p-2 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"></div>
                            <div class="col-span-2"><label class="block text-sm mb-1 dark:text-gray-300">Photo (JPG or PNG)</label><input type="file" name="photo" accept=".jpg, .jpeg, .png" class="w-full text-sm dark:text-gray-400">
                                <p class="text-xs text-gray-500 mt-1">Recommended: Passport size. JPG and PNG supported.</p>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end gap-3"><button type="button" @click="isModalOpen = false" class="px-4 py-2 border rounded text-gray-600 dark:text-gray-300">Cancel</button><button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save</button></div>
                    </form>
                </div>
            </div>

            <div x-show="isVerifyModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4" x-cloak>
                <div class="bg-white dark:bg-dark-card rounded-lg shadow-xl w-full max-w-lg" @click.away="isVerifyModalOpen = false">
                    <div class="p-6 border-b dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-xl font-bold dark:text-white" x-text="verifyData.status === 'Pending' ? 'Verify Request' : 'Volunteer Details'"></h3><button @click="isVerifyModalOpen = false" class="text-gray-500">✕</button>
                    </div>
                    <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                        <div class="flex flex-col sm:flex-row items-center gap-6"><img :src="verifyData.photo ? '../' + verifyData.photo : 'https://ui-avatars.com/api/?name=' + verifyData.name" class="w-24 h-24 rounded-full object-cover border-4 border-gray-100 dark:border-gray-600 shadow-md">
                            <div>
                                <h4 class="text-2xl font-bold dark:text-white" x-text="verifyData.name"></h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="verifyData.email"></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-sm pt-4 border-t dark:border-gray-700">
                            <div><label class="text-xs text-gray-400">Phone</label>
                                <p class="font-medium dark:text-white" x-text="verifyData.phone"></p>
                            </div>
                            <div><label class="text-xs text-gray-400">Blood Group</label>
                                <p class="font-medium dark:text-white" x-text="verifyData.blood_group || 'N/A'"></p>
                            </div>
                            <div><label class="text-xs text-gray-400">Qualification</label>
                                <p class="font-medium dark:text-white" x-text="verifyData.qualification || 'N/A'"></p>
                            </div>
                            <div><label class="text-xs text-gray-400">Profession</label>
                                <p class="font-medium dark:text-white" x-text="verifyData.profession || 'N/A'"></p>
                            </div>
                            <div><label class="text-xs text-gray-400">Marital Status</label>
                                <p class="font-medium dark:text-white" x-text="verifyData.marital_status || 'N/A'"></p>
                            </div>
                            <div><label class="text-xs text-gray-400">District</label>
                                <p class="font-medium dark:text-white" x-text="verifyData.district || 'N/A'"></p>
                            </div>
                            <div><label class="text-xs text-gray-400">State</label>
                                <p class="font-medium dark:text-white" x-text="verifyData.state || 'N/A'"></p>
                            </div>
                            <div><label class="text-xs text-gray-400">Local Body Type</label>
                                <p class="font-medium dark:text-white" x-text="verifyData.local_body_type || 'N/A'"></p>
                            </div>
                            <div><label class="text-xs text-gray-400">Local Body Name</label>
                                <p class="font-medium dark:text-white" x-text="verifyData.local_body_name || 'N/A'"></p>
                            </div>
                            <div><label class="text-xs text-gray-400">Ward No</label>
                                <p class="font-medium dark:text-white" x-text="verifyData.ward_no || 'N/A'"></p>
                            </div>
                            <div><label class="text-xs text-gray-400">Ward Name</label>
                                <p class="font-medium dark:text-white" x-text="verifyData.ward_name || 'N/A'"></p>
                            </div>
                            <div class="col-span-2"><label class="text-xs text-gray-400">Kudumbha Samithi</label>
                                <p class="font-medium dark:text-white" x-text="verifyData.kudumbha_samithi || 'N/A'"></p>
                            </div>
                            <div class="col-span-2"><label class="text-xs text-gray-400">Address</label>
                                <p class="font-medium dark:text-white" x-text="verifyData.address || 'N/A'"></p>
                            </div>
                        </div>
                    </div>
                    <div x-show="verifyData.status === 'Pending'" class="p-4 bg-gray-50 dark:bg-gray-900/50 flex justify-end gap-3 rounded-b-lg"><button @click="openRejectModal(verifyData.id)" class="px-4 py-2 border rounded text-red-600 dark:border-gray-600 dark:text-red-400">Reject</button>
                        <form action="actions/volunteer_logic.php" method="POST"><input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"><input type="hidden" name="action" value="approve"><input type="hidden" name="id" :value="verifyData.id"><button class="px-4 py-2 bg-green-600 text-white rounded">Approve</button></form>
                    </div>
                </div>
            </div>

            <div x-show="rejectModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm" x-cloak>
                <div class="bg-white dark:bg-dark-card rounded-lg p-6 w-full max-w-sm text-center">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 text-red-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg></div>
                    <h3 class="text-lg font-bold dark:text-white">Reject Request?</h3>
                    <div class="mt-6 flex gap-2"><button @click="rejectModalOpen = false" class="flex-1 border py-2 rounded dark:text-white">Cancel</button>
                        <form action="actions/volunteer_logic.php" method="POST" class="flex-1"><input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"><input type="hidden" name="action" value="update_status"><input type="hidden" name="id" :value="targetId"><input type="hidden" name="status" value="Rejected"><button class="w-full bg-red-600 text-white py-2 rounded">Confirm</button></form>
                    </div>
                </div>
            </div>

            <div x-show="emailModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm" x-cloak>
                <div class="bg-white dark:bg-dark-card rounded-lg p-6 w-full max-w-sm text-center">
                    <h3 class="text-lg font-bold dark:text-white">Send ID Card?</h3>
                    <div class="mt-6 flex gap-2"><button @click="emailModalOpen = false" class="flex-1 border py-2 rounded dark:text-white">Cancel</button><a :href="'actions/send_volunteer_email.php?id='+targetId" class="flex-1 bg-blue-600 text-white py-2 rounded flex items-center justify-center">Send Now</a></div>
                </div>
            </div>

            <!-- Delete Confirmation Modal -->
            <div x-show="deleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm" x-cloak>
                <div class="bg-white dark:bg-dark-card rounded-lg p-6 w-full max-w-sm text-center">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 text-red-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold dark:text-white">Delete Volunteer</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Are you sure you want to delete this volunteer? This action cannot be undone.</p>
                    <div class="mt-6 flex gap-2">
                        <button @click="deleteModalOpen = false" class="flex-1 border py-2 rounded dark:text-white">Cancel</button>
                        <form action="actions/volunteer_logic.php" method="POST" class="flex-1">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" :value="targetId">
                            <button class="w-full bg-red-600 text-white py-2 rounded">Delete</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Bulk Upload Modal -->
            <div x-show="isBulkModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-80 backdrop-blur-sm p-4" x-cloak>
                <div class="bg-white dark:bg-dark-card rounded-lg shadow-xl w-full max-w-lg overflow-hidden" @click.away="isBulkModalOpen = false">
                    <div class="p-6 border-b dark:border-gray-700 flex justify-between items-center">
                        <h3 class="text-xl font-bold dark:text-white">Bulk Upload Volunteers</h3><button @click="isBulkModalOpen = false" class="text-gray-500">✕</button>
                    </div>
                    <form action="actions/volunteer_logic.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="bulk_upload">
                        
                        <div>
                            <label class="block text-sm mb-1.5 dark:text-gray-300">Choose Excel (.xlsx) or CSV (.csv) File *</label>
                            <input type="file" name="upload_file" accept=".xlsx, .csv" required class="w-full text-sm dark:text-gray-400 border p-2.5 rounded bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                        
                        <div class="text-xs text-gray-500 dark:text-gray-400 space-y-2.5">
                            <p class="font-bold text-gray-700 dark:text-gray-200">Instructions:</p>
                            <ul class="list-disc pl-4 space-y-1 text-left">
                                <li>Supports Excel spreadsheet files (<strong>.xlsx</strong>) and CSV files (<strong>.csv</strong>).</li>
                                <li>The first row must be the header row containing lowercase field names.</li>
                                <li>Mandatory fields: <strong>name</strong>, <strong>email</strong>, <strong>phone</strong>.</li>
                                <li>Optional fields: <em>qualification, profession, marital_status, blood_group, address, district, state, local_body_type, local_body_name, ward_no, ward_name, kudumbha_samithi</em>.</li>
                                <li>Volunteers with duplicate emails will be skipped automatically.</li>
                            </ul>
                            <div class="pt-2 text-left">
                                <a href="actions/download_volunteer_template.php" class="text-blue-600 dark:text-blue-400 hover:underline font-bold inline-flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    Download Sample CSV Template
                                </a>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" @click="isBulkModalOpen = false" class="px-4 py-2 border rounded text-gray-600 dark:text-gray-300">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700">Upload & Import</button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('volManager', () => ({
            items: <?php echo json_encode($volunteers); ?>,
            search: '',
            tab: 'active',
            page: 1,
            pageSize: 15,

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
            isModalOpen: false,
            rejectModalOpen: false,
            deleteModalOpen: false,
            emailModalOpen: false,
            isVerifyModalOpen: false,
            isBulkModalOpen: false,
            mode: 'create',
            targetId: null,
            validityType: '1Y',
            formData: {
                id: '',
                name: '',
                email: '',
                phone: '',
                qualification: '',
                profession: '',
                marital_status: '',
                address: '',
                district: '',
                state: '',
                local_body_type: '',
                local_body_name: '',
                ward_no: '',
                ward_name: '',
                kudumbha_samithi: '',
                photo: '',
                blood_group: ''
            },
            verifyData: {},

            get filteredItems() {
                const s = this.search.toLowerCase();
                const searched = this.items.filter(i => i.name.toLowerCase().includes(s) || (i.id_card_no && i.id_card_no.toLowerCase().includes(s)) || i.phone.includes(s));
                if (this.tab === 'pending') return searched.filter(i => i.status === 'Pending');
                return searched.filter(i => i.status !== 'Pending');
            },
            get pendingCount() {
                return this.items.filter(i => i.status === 'Pending').length;
            },
            openModal(mode, data = null) {
                this.mode = mode;
                if (mode === 'edit' && data) {
                    this.formData = {
                        ...data
                    };
                    this.validityType = '1Y';
                } else {
                    this.formData = {
                        id: '',
                        name: '',
                        email: '',
                        phone: '',
                        qualification: '',
                        profession: '',
                        marital_status: '',
                        address: '',
                        district: '',
                        state: '',
                        local_body_type: '',
                        local_body_name: '',
                        ward_no: '',
                        ward_name: '',
                        kudumbha_samithi: '',
                        photo: '',
                        blood_group: ''
                    };
                    this.validityType = '1Y';
                }
                this.isModalOpen = true;
            },
            openRejectModal(id) {
                this.targetId = id;
                this.rejectModalOpen = true;
                this.isVerifyModalOpen = false;
            },
            openDeleteModal(id) {
                this.targetId = id;
                this.deleteModalOpen = true;
            },
            openEmailModal(id) {
                this.targetId = id;
                this.emailModalOpen = true;
            },
            openVerifyModal(data) {
                this.verifyData = data;
                this.isVerifyModalOpen = true;
            },
            formatDate(date) {
                if (!date) return '';
                return date.split('-').reverse().join('-');
            },
            isExpiring(date) {
                if (!date) return false;
                const d = new Date(date);
                const t = new Date();
                return (d - t) / (1000 * 60 * 60 * 24) <= 7;
            },
            getStatusClass(status) {
                return {
                    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': status === 'Active',
                    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': status === 'Rejected',
                    'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': status === 'Pending',
                    'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400': status === 'Inactive'
                };
            }
        }))
    });
</script>

<?php require 'includes/footer.php'; ?>

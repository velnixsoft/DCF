<?php
require 'includes/header.php';
require '../config/db.php';
require '../includes/functions.php';

if (!canAccessModule($pdo, 'manager', 'page.events')) {
    setFlash('error', 'Access denied.');
    header('Location: dashboard.php');
    exit;
}

$eventId = (int)($_GET['event_id'] ?? $_POST['event_id'] ?? 0);
if ($eventId <= 0) {
    setFlash('error', 'Event ID is required.');
    header('Location: events.php');
    exit;
}

// Fetch event details
$eventStmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$eventStmt->execute([$eventId]);
$event = $eventStmt->fetch(PDO::FETCH_ASSOC);
if (!$event) {
    setFlash('error', 'Event not found.');
    header('Location: events.php');
    exit;
}

// Handle Status Updates and Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlash('error', 'CSRF verification failed.');
        header("Location: event_registrations.php?event_id=" . $eventId);
        exit;
    }

    $action = $_POST['action'];
    $regId = (int)($_POST['registration_id'] ?? 0);

    try {
        if ($action === 'update_status' && $regId > 0) {
            $status = cleanInput($_POST['status'] ?? '');
            if (in_array($status, ['Registered', 'Cancelled', 'Attended'], true)) {
                $stmt = $pdo->prepare("UPDATE event_registrations SET status = ? WHERE id = ? AND event_id = ?");
                $stmt->execute([$status, $regId, $eventId]);
                setFlash('success', 'Registration status updated successfully.');
            }
        } elseif ($action === 'delete' && $regId > 0) {
            $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE id = ? AND event_id = ?");
            $stmt->execute([$regId, $eventId]);
            setFlash('success', 'Registration deleted successfully.');
        }
    } catch (Exception $e) {
        setFlash('error', 'Database error: ' . $e->getMessage());
    }

    header("Location: event_registrations.php?event_id=" . $eventId);
    exit;
}

// Fetch all registrations
$regStmt = $pdo->prepare("
    SELECT r.*, m.member_no 
    FROM event_registrations r 
    LEFT JOIN members m ON r.member_id = m.id 
    WHERE r.event_id = ? 
    ORDER BY r.created_at DESC
");
$regStmt->execute([$eventId]);
$registrations = $regStmt->fetchAll(PDO::FETCH_ASSOC);

// Metrics calculations
$totalReg = count($registrations);
$attendedCount = 0;
$cancelledCount = 0;
$registeredCount = 0;

foreach ($registrations as $r) {
    if ($r['status'] === 'Attended') {
        $attendedCount++;
    } elseif ($r['status'] === 'Cancelled') {
        $cancelledCount++;
    } else {
        $registeredCount++;
    }
}

$csrfToken = generateCsrfToken();
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900" x-data="registrationManager">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300 min-w-0">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6">
            
            <!-- Breadcrumbs -->
            <div class="mb-4">
                <a href="events.php" class="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white transition-colors">
                    <i class="fa-solid fa-arrow-left"></i> Back to Events
                </a>
            </div>

            <!-- Page Title -->
            <div class="mb-6">
                <h3 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Registrations</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Event: <span class="font-semibold text-gray-700 dark:text-gray-200"><?php echo htmlspecialchars($event['title']); ?></span> 
                    (<?php echo date('M d, Y', strtotime($event['event_date'])); ?>)
                </p>
            </div>

            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Registered</p>
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-white mt-2"><?php echo $totalReg; ?></h3>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider text-amber-600">Pending Attendance</p>
                    <h3 class="text-2xl font-bold text-amber-600 mt-2"><?php echo $registeredCount; ?></h3>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider text-emerald-600">Attended</p>
                    <h3 class="text-2xl font-bold text-emerald-600 mt-2"><?php echo $attendedCount; ?></h3>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider text-red-600">Cancelled</p>
                    <h3 class="text-2xl font-bold text-red-600 mt-2"><?php echo $cancelledCount; ?></h3>
                </div>
            </div>

            <!-- Filters & Search -->
            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border dark:border-gray-700 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-4 mb-6">
                <div class="flex flex-wrap bg-gray-100 dark:bg-gray-900 p-1 rounded-xl w-full md:w-auto">
                    <button @click="tab = 'all'" :class="tab === 'all' ? 'bg-white shadow text-blue-750 dark:bg-gray-800 dark:text-blue-400 dark:shadow-md' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'" class="flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-bold transition">All</button>
                    <button @click="tab = 'Registered'" :class="tab === 'Registered' ? 'bg-white shadow text-orange-655 dark:bg-gray-800 dark:text-orange-400 dark:shadow-md' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'" class="flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-bold transition">Pending</button>
                    <button @click="tab = 'Attended'" :class="tab === 'Attended' ? 'bg-white shadow text-emerald-750 dark:bg-gray-800 dark:text-emerald-400 dark:shadow-md' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'" class="flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-bold transition">Attended</button>
                    <button @click="tab = 'Cancelled'" :class="tab === 'Cancelled' ? 'bg-white shadow text-red-650 dark:bg-gray-800 dark:text-red-400 dark:shadow-md' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white'" class="flex-1 md:flex-none px-4 py-2 rounded-lg text-sm font-bold transition">Cancelled</button>
                </div>

                <div class="relative w-full md:w-80">
                    <input type="text" x-model="search" placeholder="Search by name, email, phone..." 
                        class="w-full pl-10 pr-4 py-2.5 border rounded-xl bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 focus:ring-2 focus:ring-emerald-500 focus:bg-white dark:focus:bg-gray-700 outline-none transition text-sm">
                    <span class="absolute left-3.5 top-3 text-gray-400">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                </div>
            </div>

            <!-- Mobile View List -->
            <div class="grid grid-cols-1 gap-4 lg:hidden">
                <template x-for="reg in pagedItems" :key="'mobile-' + reg.id">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow border dark:border-gray-700 p-4 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white" x-text="reg.name"></p>
                                <p class="text-xs text-gray-400" x-text="reg.member_no ? 'Mem No: ' + reg.member_no : 'Non-Member'"></p>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="getStatusClass(reg.status)" x-text="reg.status"></span>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                            <p><i class="fa-regular fa-envelope"></i> <span x-text="reg.email"></span></p>
                            <p><i class="fa-solid fa-phone"></i> <span x-text="reg.phone || '—'"></span></p>
                            <p><i class="fa-regular fa-clock"></i> <span x-text="formatDate(reg.created_at)"></span></p>
                        </div>
                        <div class="flex flex-wrap gap-2 pt-2 border-t dark:border-gray-700">
                            <template x-if="reg.status !== 'Attended'">
                                <form action="event_registrations.php" method="POST" class="flex-1">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                                    <input type="hidden" name="registration_id" :value="reg.id">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="status" value="Attended">
                                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded-xl text-xs transition">Attended</button>
                                </form>
                            </template>
                            <template x-if="reg.status !== 'Cancelled'">
                                <form action="event_registrations.php" method="POST" class="flex-1">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                                    <input type="hidden" name="registration_id" :value="reg.id">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="status" value="Cancelled">
                                    <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 rounded-xl text-xs transition">Cancel</button>
                                </form>
                            </template>
                            <form action="event_registrations.php" method="POST" class="flex-1" onsubmit="return confirm('Delete this registration?')">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                                <input type="hidden" name="registration_id" :value="reg.id">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 rounded-xl text-xs transition">Delete</button>
                            </form>
                        </div>
                    </div>
                </template>
                <div x-show="filteredItems.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow p-8 text-center text-gray-400 font-medium">No registrations found.</div>
            </div>

            <!-- Desktop View Table -->
            <div class="hidden lg:block bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase">
                            <tr>
                                <th class="p-4">Attendee Name</th>
                                <th class="p-4">Member No</th>
                                <th class="p-4">Contact Info</th>
                                <th class="p-4">Registration Date</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <template x-for="reg in pagedItems" :key="reg.id">
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/50 transition">
                                    <td class="p-4 font-bold text-gray-900 dark:text-white" x-text="reg.name"></td>
                                    <td class="p-4 text-xs font-mono" x-text="reg.member_no || 'Non-Member'"></td>
                                    <td class="p-4 text-xs">
                                        <p class="font-semibold" x-text="reg.email"></p>
                                        <p class="text-gray-500 mt-0.5" x-text="reg.phone || '—'"></p>
                                    </td>
                                    <td class="p-4 text-xs font-mono" x-text="formatDate(reg.created_at)"></td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold" :class="getStatusClass(reg.status)" x-text="reg.status"></span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="inline-flex gap-1.5">
                                            <template x-if="reg.status !== 'Attended'">
                                                <form action="event_registrations.php" method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                                                    <input type="hidden" name="registration_id" :value="reg.id">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="status" value="Attended">
                                                    <button type="submit" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 px-2.5 py-1.5 rounded-lg text-xs font-bold transition" title="Mark as Attended">Attended</button>
                                                </form>
                                            </template>
                                            <template x-if="reg.status !== 'Cancelled'">
                                                <form action="event_registrations.php" method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                    <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                                                    <input type="hidden" name="registration_id" :value="reg.id">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="status" value="Cancelled">
                                                    <button type="submit" class="bg-amber-50 hover:bg-amber-100 text-amber-700 px-2.5 py-1.5 rounded-lg text-xs font-bold transition" title="Cancel Registration">Cancel</button>
                                                </form>
                                            </template>
                                            <form action="event_registrations.php" method="POST" class="inline" onsubmit="return confirm('Delete this registration?')">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                                                <input type="hidden" name="registration_id" :value="reg.id">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-700 p-2 rounded-lg text-xs font-bold transition" title="Delete Registration">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredItems.length === 0">
                                <td colspan="6" class="p-8 text-center text-gray-400 font-medium">No registrations found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination Control -->
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

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('registrationManager', () => ({
        items: <?php echo json_encode($registrations); ?>,
        search: '',
        tab: 'all',
        page: 1,
        pageSize: 15,

        init() {
            this.$watch('search', () => this.page = 1);
            this.$watch('tab', () => this.page = 1);
        },

        get filteredItems() {
            const query = this.search.toLowerCase();
            const searched = this.items.filter(item => 
                (item.name || '').toLowerCase().includes(query) || 
                (item.email || '').toLowerCase().includes(query) ||
                (item.phone || '').toLowerCase().includes(query) ||
                (item.member_no || '').toLowerCase().includes(query)
            );
            if (this.tab !== 'all') {
                return searched.filter(item => item.status === this.tab);
            }
            return searched;
        },

        get pagedItems() {
            const start = (this.page - 1) * this.pageSize;
            return this.filteredItems.slice(start, start + this.pageSize);
        },

        get totalPages() {
            return Math.ceil(this.filteredItems.length / this.pageSize);
        },

        getStatusClass(status) {
            return {
                'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300': status === 'Attended',
                'bg-orange-100 text-orange-850 dark:bg-orange-900/30 dark:text-orange-300': status === 'Registered',
                'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300': status === 'Cancelled'
            };
        },

        formatDate(rawDate) {
            if (!rawDate) return '';
            // rawDate format: YYYY-MM-DD HH:MM:SS
            const parts = rawDate.split(' ');
            if (parts[0]) {
                return parts[0].split('-').reverse().join('-') + (parts[1] ? ' ' + parts[1].substring(0, 5) : '');
            }
            return rawDate;
        }
    }));
});
</script>

<?php require 'includes/footer.php'; ?>
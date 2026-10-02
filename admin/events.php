<?php
require 'includes/header.php';
require '../config/db.php';
require '../includes/functions.php';
require '../includes/qr_attendance.php';

if (!canAccessModule($pdo, 'manager', 'page.events')) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Access denied. Manager/Admin required.'];
    header('Location: dashboard.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(e.title LIKE :search OR e.description LIKE :search OR e.location LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($statusFilter !== '') {
    $where[] = "e.status = :status";
    $params[':status'] = $statusFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

$totalEvents = 0;
try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM events e $whereSql");
    foreach ($params as $k => $v) $countStmt->bindValue($k, $v);
    $countStmt->execute();
    $totalEvents = (int)$countStmt->fetchColumn();
} catch (Throwable $e) {
    $totalEvents = 0;
}

$events = [];
$attendanceIndex = [];
try {
    $stmt = $pdo->prepare("
        SELECT
            e.*,
            (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) AS registrations_count
        FROM events e
        $whereSql
        ORDER BY e.event_date DESC, e.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $events = [];
}
try {
    if (dbTableExists($pdo, 'attendance_events')) {
        $attendanceRows = $pdo->query("SELECT id, event_id, status FROM attendance_events ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($attendanceRows as $row) {
            if (!empty($row['event_id']) && !isset($attendanceIndex[(int)$row['event_id']])) {
                $attendanceIndex[(int)$row['event_id']] = $row;
            }
        }
    }
} catch (Throwable $e) {
    $attendanceIndex = [];
}
$message = '';
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'created' || $_GET['action'] === 'updated') {
        $message = 'Event ' . ($_GET['action'] === 'created' ? 'created' : 'updated') . ' successfully.';
    }
}
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300 min-w-0">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-3 sm:p-4 md:p-8">
            <?php if ($message): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-6 dark:bg-green-900/30 dark:border-green-800">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <!-- Page header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                <div>
                    <h3 class="text-xl sm:text-2xl md:text-3xl font-medium text-gray-700 dark:text-white">Events Management</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create, manage events, registrations, and QR-based attendance tracking.</p>
                </div>
                <div class="flex flex-row flex-wrap gap-2 shrink-0">
                    <a href="create-event.php" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-medium text-sm whitespace-nowrap">
                        Attendance Setup
                    </a>
                    <button onclick="openCreateEventModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-sm whitespace-nowrap">
                        + New Event
                    </button>
                </div>
            </div>

            <!-- Search & Filters -->
            <form method="GET" class="mb-6 flex flex-wrap gap-3 items-center">
                <div class="relative flex-1 min-w-[220px] max-w-md">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search title, location, description..."
                           class="w-full pl-9 pr-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-blue-500">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                </div>
                <select name="status" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-700 dark:text-gray-200">
                    <option value="">All Statuses</option>
                    <option value="Upcoming" <?php echo $statusFilter === 'Upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                    <option value="Ongoing" <?php echo $statusFilter === 'Ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                    <option value="Completed" <?php echo $statusFilter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="Cancelled" <?php echo $statusFilter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition">
                    Filter
                </button>
                <?php if ($search !== '' || $statusFilter !== ''): ?>
                    <a href="events.php" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition">
                        Reset
                    </a>
                <?php endif; ?>
            </form>

            <!-- Events table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto w-full">
                    <table class="w-full text-sm" style="min-width: 600px;">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="p-3 sm:p-4 text-left font-semibold text-gray-600 dark:text-gray-300">Title</th>
                                <th class="p-3 sm:p-4 text-left font-semibold text-gray-600 dark:text-gray-300">Date</th>
                                <th class="p-3 sm:p-4 text-left font-semibold text-gray-600 dark:text-gray-300">Status</th>
                                <th class="p-3 sm:p-4 text-center font-semibold text-gray-600 dark:text-gray-300 hidden sm:table-cell">Registrations</th>
                                <th class="p-3 sm:p-4 text-right font-semibold text-gray-600 dark:text-gray-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <?php foreach ($events as $event): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="p-3 sm:p-4 font-medium dark:text-white max-w-[160px] sm:max-w-xs">
                                    <span class="block truncate"><?php echo htmlspecialchars($event['title']); ?></span>
                                </td>
                                <td class="p-3 sm:p-4 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                    <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                                </td>
                                <td class="p-3 sm:p-4">
                                    <?php
                                    $sc = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                                    if ($event['status'] === 'Upcoming') $sc = 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
                                    elseif ($event['status'] === 'Ongoing') $sc = 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
                                    elseif ($event['status'] === 'Completed') $sc = 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
                                    elseif ($event['status'] === 'Live') $sc = 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300';
                                    elseif ($event['status'] === 'Cancelled') $sc = 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300';
                                    ?>
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full <?php echo $sc; ?>">
                                        <?php echo htmlspecialchars($event['status']); ?>
                                    </span>
                                </td>
                                <td class="p-3 sm:p-4 text-center text-gray-600 dark:text-gray-400 hidden sm:table-cell">
                                    <?php echo (int)($event['registrations_count'] ?? 0); ?>
                                </td>
                                <td class="p-3 sm:p-4 text-right whitespace-nowrap">
                                    <?php $attendanceRow = $attendanceIndex[(int)$event['id']] ?? null; ?>
                                    <div class="flex items-center gap-1 justify-end">
                                        <!-- Attendance / Report -->
                                        <a href="<?php echo $attendanceRow ? ('event-report.php?id=' . (int)$attendanceRow['id']) : ('create-event.php?event_id=' . (int)$event['id']); ?>"
                                           class="p-1.5 rounded hover:bg-orange-50 dark:hover:bg-orange-900/20 text-orange-600 hover:text-orange-800 transition-colors"
                                           title="<?php echo $attendanceRow ? 'Attendance report' : 'Setup attendance'; ?>">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                        </a>
                                        <!-- Registrations -->
                                        <a href="event_registrations.php?event_id=<?php echo (int)$event['id']; ?>"
                                           class="p-1.5 rounded hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-emerald-600 hover:text-emerald-800 transition-colors"
                                           title="View registrations">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <!-- Gallery -->
                                        <a href="event_gallery.php?event_id=<?php echo (int)$event['id']; ?>"
                                           class="p-1.5 rounded hover:bg-violet-50 dark:hover:bg-violet-900/20 text-violet-600 hover:text-violet-800 transition-colors"
                                           title="Manage event gallery">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a2 2 0 012-2h3l2 2h7a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V5z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 15l2-2 2 2 4-4 2 2"/>
                                            </svg>
                                        </a>
                                        <!-- Edit -->
                                        <button onclick='editEvent(<?php echo htmlspecialchars(json_encode($event), ENT_QUOTES, "UTF-8"); ?>)'
                                                class="p-1.5 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20 text-blue-600 hover:text-blue-800 transition-colors"
                                                title="Edit event">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <!-- Delete -->
                                        <form action="actions/event_crud.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this event?')">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int)$event['id']; ?>">
                                            <button type="submit"
                                                    class="p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 hover:text-red-800 transition-colors"
                                                    title="Delete event">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($events)): ?>
                            <tr>
                                <td colspan="5" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                    No events found.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php echo render_admin_pagination($totalEvents, $page, $perPage, ['search' => $search, 'status' => $statusFilter]); ?>
            </div>
        </main>
    </div>
</div>

<!-- Event Modal -->
<div id="eventModal" class="fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl w-full max-w-lg max-h-[90dvh] flex flex-col shadow-xl">
        <!-- Modal header -->
        <div class="flex justify-between items-center px-5 py-4 border-b dark:border-gray-700 shrink-0">
            <h3 id="eventModalTitle" class="text-lg font-bold dark:text-white">New Event</h3>
            <button onclick="closeModal('eventModal')" class="p-1 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <!-- Modal body — scrollable -->
        <div class="overflow-y-auto flex-1 px-5 py-4">
            <form id="eventForm" action="actions/event_crud.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="id" id="editId">

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Event Title</label>
                        <input type="text" name="title" required
                               class="w-full px-3 py-2.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="e.g. Monthly General Meeting">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea name="description" rows="3"
                                  class="w-full px-3 py-2.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                                  placeholder="Short event details"></textarea>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Event Date</label>
                            <input type="date" name="event_date" required max="9999-12-31"
                                   class="w-full px-3 py-2.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <select name="status"
                                    class="w-full px-3 py-2.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="Upcoming">Upcoming</option>
                                <option value="Live">Live</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location</label>
                        <input type="text" name="location"
                               class="w-full px-3 py-2.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Venue or Online Link">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Registration Open</label>
                            <select name="registration_open"
                                    class="w-full px-3 py-2.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Registrations</label>
                            <input type="number" min="0" name="max_registrations"
                                   class="w-full px-3 py-2.5 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Unlimited">
                        </div>
                    </div>
                </div>

                <!-- Modal footer buttons -->
                <div class="flex flex-col-reverse sm:flex-row gap-3 mt-6">
                    <button type="button" onclick="closeModal('eventModal')"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 py-2.5 rounded-lg font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-semibold transition-colors">
                        <span id="eventSubmitLabel">Create Event</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.body.style.overflow = '';
}
function resetEventForm() {
    document.getElementById('eventForm').reset();
    document.getElementById('editId').value = '';
    document.querySelector('#eventForm [name=action]').value = 'create';
    document.getElementById('eventModalTitle').textContent = 'New Event';
    document.getElementById('eventSubmitLabel').textContent = 'Create Event';
}
function openCreateEventModal() {
    resetEventForm();
    openModal('eventModal');
}
function editEvent(event) {
    document.getElementById('editId').value = event.id;
    document.querySelector('[name=title]').value = event.title;
    document.querySelector('[name=description]').value = event.description || '';
    document.querySelector('[name=event_date]').value = event.event_date;
    document.querySelector('[name=location]').value = event.location || '';
    document.querySelector('[name=registration_open]').value = event.registration_open;
    document.querySelector('[name=max_registrations]').value = event.max_registrations || '';
    document.querySelector('[name=status]').value = event.status;
    document.querySelector('#eventForm [name=action]').value = 'update';
    document.getElementById('eventModalTitle').textContent = 'Edit Event';
    document.getElementById('eventSubmitLabel').textContent = 'Update Event';
    openModal('eventModal');
}

// Close modal on backdrop click
document.getElementById('eventModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal('eventModal');
});
</script>

<?php require 'includes/footer.php'; ?>
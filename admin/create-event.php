<?php
require 'includes/header.php';
require '../config/db.php';
require '../includes/functions.php';
require '../includes/qr_attendance.php';

if (!canAccessModule($pdo, 'manager', 'page.events')) {
    setFlash('error', 'Access denied.');
    header('Location: dashboard.php');
    exit;
}

$attendanceEventId = (int)($_GET['id'] ?? 0);
$sourceEventId = (int)($_GET['event_id'] ?? 0);
$attendanceEvent = null;
if ($attendanceEventId > 0) {
    $attendanceEvent = qa_attendance_event($pdo, $attendanceEventId);
}

$events = [];
try {
    $events = $pdo->query("SELECT id, title, event_date, location, status FROM events ORDER BY event_date DESC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $events = [];
}

$selectedEventId = (int)($attendanceEvent['event_id'] ?? $sourceEventId);
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
                <div>
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-700 dark:text-white"><?php echo $attendanceEvent ? 'Edit Attendance Event' : 'Create Attendance Event'; ?></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Attach attendance tracking to an existing event or create a standalone gate check-in profile.</p>
                </div>
                <div class="flex gap-2">
                    <a href="events.php" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg text-sm">Back to Events</a>
                    <?php if ($attendanceEvent): ?>
                        <a href="event-report.php?id=<?php echo (int)$attendanceEvent['id']; ?>" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm">Open Report</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="max-w-4xl bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-6">
                <form action="actions/attendance_event_logic.php" method="POST" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?php echo (int)($attendanceEvent['id'] ?? 0); ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Existing Event</label>
                            <select id="sourceEventSelect" name="event_id" class="w-full px-4 py-3 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Standalone attendance event</option>
                                <?php foreach ($events as $event): ?>
                                    <option
                                        value="<?php echo (int)$event['id']; ?>"
                                        data-title="<?php echo htmlspecialchars($event['title']); ?>"
                                        data-date="<?php echo htmlspecialchars($event['event_date']); ?>"
                                        data-location="<?php echo htmlspecialchars((string)($event['location'] ?? '')); ?>"
                                        <?php echo $selectedEventId === (int)$event['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($event['title'] . ' - ' . $event['event_date']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">QR Mode</label>
                            <select name="qr_mode" class="w-full px-4 py-3 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="single" <?php echo (($attendanceEvent['qr_mode'] ?? 'single') === 'single') ? 'selected' : ''; ?>>Single Check-in</option>
                                <option value="multiple" <?php echo (($attendanceEvent['qr_mode'] ?? '') === 'multiple') ? 'selected' : ''; ?>>Allow Multiple Scans</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Attendance Event Name *</label>
                            <input id="eventNameField" type="text" name="event_name" required value="<?php echo htmlspecialchars((string)($attendanceEvent['event_name'] ?? '')); ?>" class="w-full px-4 py-3 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Event Date *</label>
                            <input id="eventDateField" type="date" name="event_date" required max="9999-12-31" value="<?php echo htmlspecialchars((string)($attendanceEvent['event_date'] ?? '')); ?>" class="w-full px-4 py-3 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Event Start Time</label>
                            <input type="time" name="event_start_time" value="<?php echo htmlspecialchars((string)($attendanceEvent['event_start_time'] ?? '')); ?>" class="w-full px-4 py-3 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <p class="text-xs text-gray-500 mt-1">Used to calculate late entries on the live report.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Status</label>
                            <select name="status" class="w-full px-4 py-3 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <?php foreach (['active' => 'Active', 'inactive' => 'Inactive', 'closed' => 'Closed'] as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo (($attendanceEvent['status'] ?? 'active') === $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 dark:text-gray-300">Location</label>
                        <input id="eventLocationField" type="text" name="event_location" value="<?php echo htmlspecialchars((string)($attendanceEvent['event_location'] ?? '')); ?>" class="w-full px-4 py-3 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-3 rounded-lg text-sm font-medium">Save Attendance Event</button>
                        <?php if ($attendanceEvent): ?>
                            <button
                                type="button"
                                class="bg-red-600 hover:bg-red-700 text-white px-5 py-3 rounded-lg text-sm font-medium"
                                onclick="openDeleteAttendanceModal()">
                                Delete
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<?php if ($attendanceEvent): ?>
<div id="deleteAttendanceModal" class="fixed inset-0 hidden z-50 items-center justify-center bg-black/60 p-4">
    <div class="w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 border dark:border-gray-700 shadow-2xl p-6">
        <h4 class="text-lg font-semibold dark:text-white">Delete Attendance Event</h4>
        <p class="text-sm text-gray-500 mt-2">This removes the attendance profile and its scan logs.</p>
        <form action="actions/attendance_event_logic.php" method="POST" class="mt-5 flex gap-3">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?php echo (int)$attendanceEvent['id']; ?>">
            <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg text-sm font-medium">Delete</button>
            <button type="button" class="flex-1 bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-white py-3 rounded-lg text-sm font-medium" onclick="closeDeleteAttendanceModal()">Cancel</button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    (function () {
        const sourceEvent = document.getElementById('sourceEventSelect');
        const nameField = document.getElementById('eventNameField');
        const dateField = document.getElementById('eventDateField');
        const locationField = document.getElementById('eventLocationField');
        if (!sourceEvent) return;

        sourceEvent.addEventListener('change', function () {
            const option = this.options[this.selectedIndex];
            if (!option || !option.value) return;
            if (!nameField.value) nameField.value = option.dataset.title || '';
            if (!dateField.value) dateField.value = option.dataset.date || '';
            if (!locationField.value) locationField.value = option.dataset.location || '';
        });
    })();

    function openDeleteAttendanceModal() {
        const modal = document.getElementById('deleteAttendanceModal');
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeDeleteAttendanceModal() {
        const modal = document.getElementById('deleteAttendanceModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>

<?php require 'includes/footer.php'; ?>

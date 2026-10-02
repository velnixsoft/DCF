<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/student/portal_helpers.php';

$student = student_portal_require_student($pdo, 'attendance');
$studentId = (int)$student['id'];

// Check if attendance already marked today
$checkedInToday = false;
try {
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM sa_attendance_logs WHERE student_id = ? AND attendance_date = CURDATE()");
    $checkStmt->execute([$studentId]);
    if ($checkStmt->fetchColumn() > 0) {
        $checkedInToday = true;
    }
} catch (Throwable $e) {}

student_portal_render_shell_start($pdo, $student, 'Attendance & Consistency', 'attendance');
?>

<div class="space-y-6" x-data="attendanceHandler()">
    <!-- Top Card / Header & Quick Checkin -->
    <div class="rounded-3xl border border-slate-100 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-md">
        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                    <i class="fa-solid fa-calendar-check text-xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Attendance & Consistency</h2>
                    <p class="text-sm text-slate-500">Track and log your active involvement daily.</p>
                </div>
            </div>

            <!-- Checkin Widget -->
            <div>
                <?php if ($checkedInToday): ?>
                    <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-5 py-3.5 text-sm font-bold text-emerald-700 border border-emerald-100">
                        <i class="fa-solid fa-circle-check text-base"></i>
                        Checked In Today
                    </div>
                <?php else: ?>
                    <button @click="markAttendance" :disabled="loading" 
                            class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 hover:bg-emerald-700 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 hover:shadow-xl hover:shadow-emerald-600/30 transition-all duration-300 disabled:opacity-50">
                        <i class="fa-solid fa-clock-rotate-left" :class="loading ? 'animate-spin' : ''"></i>
                        <span x-text="loading ? 'Marking...' : 'Mark Present Today'"></span>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div x-show="message" x-cloak class="mt-4 rounded-2xl p-4 text-xs font-semibold"
             :class="success ? 'bg-emerald-50 text-emerald-800 border border-emerald-100' : 'bg-rose-50 text-rose-800 border border-rose-100'">
             <span x-text="message"></span>
        </div>
    </div>

    <!-- Attendance Metrics Widgets -->
    <div>
        <?php
        $_SESSION['student_id'] = $studentId;
        require __DIR__ . '/includes/student/attendance_dashboard.php';
        ?>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('attendanceHandler', () => ({
        loading: false,
        message: '',
        success: false,
        markAttendance() {
            this.loading = true;
            this.message = '';
            fetch('process/mark_student_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            })
            .then(r => r.json())
            .then(d => {
                this.success = d.success;
                this.message = d.message;
                if (d.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            })
            .catch(err => {
                this.success = false;
                this.message = 'An unexpected error occurred. Please try again.';
            })
            .finally(() => {
                this.loading = false;
            });
        }
    }));
});
</script>

<?php student_portal_render_shell_end(); ?>

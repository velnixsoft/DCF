<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/student/portal_helpers.php';

$student = student_portal_require_student($pdo, 'events');
$studentId = (int)$student['id'];

$saEvents = [];
$ngoEvents = [];
$myRegistrations = [];
$myRequests = [];

if (dbTableExists($pdo, 'sa_events')) {
    $saEvents = $pdo->query("
        SELECT * FROM sa_events WHERE status IN ('Approved', 'Live', 'Upcoming')
        ORDER BY start_date ASC LIMIT 30
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (dbTableExists($pdo, 'sa_event_registrations')) {
        $regStmt = $pdo->prepare("
            SELECT r.*, e.title, e.start_date, e.venue
            FROM sa_event_registrations r
            JOIN sa_events e ON e.id = r.event_id
            WHERE r.student_id = ?
            ORDER BY r.registered_at DESC
        ");
        $regStmt->execute([$studentId]);
        $myRegistrations = array_merge($myRegistrations, $regStmt->fetchAll(PDO::FETCH_ASSOC));
    }
}

try {
    $ngoEvents = $pdo->query("
        SELECT id, title, event_date, location, status, registration_open
        FROM events WHERE status IN ('Upcoming', 'Live') AND registration_open = 1
        ORDER BY event_date ASC LIMIT 20
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (dbColumnExists($pdo, 'event_registrations', 'sa_student_id')) {
        $regStmt = $pdo->prepare("
            SELECT r.*, e.title, e.event_date AS start_date, e.location AS venue
            FROM event_registrations r JOIN events e ON e.id = r.event_id
            WHERE r.sa_student_id = ? ORDER BY r.created_at DESC
        ");
        $regStmt->execute([$studentId]);
        $myRegistrations = array_merge($myRegistrations, $regStmt->fetchAll(PDO::FETCH_ASSOC));
    }
} catch (Throwable $e) {}

if (dbTableExists($pdo, 'sa_student_event_requests')) {
    $reqStmt = $pdo->prepare('SELECT * FROM sa_student_event_requests WHERE student_id = ? ORDER BY created_at DESC');
    $reqStmt->execute([$studentId]);
    $myRequests = $reqStmt->fetchAll(PDO::FETCH_ASSOC);
}

if (empty($_SESSION['csrf_token'])) {
    generateCsrfToken();
}
$csrf = $_SESSION['csrf_token'];

$registeredEventIds = array_column($myRegistrations, 'event_id');

student_portal_render_shell_start($pdo, $student, 'Events & Campaigns', 'events');
?>

<div class="space-y-6" x-data="studentEvents()">
    <!-- Top Header -->
    <section class="rounded-3xl border border-slate-100 bg-white p-8 shadow-sm transition-all duration-300 hover:shadow-md">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                <i class="fa-solid fa-calendar-days text-xl"></i>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Events & Campaigns</h2>
                <p class="text-sm text-slate-500">Register for ongoing activities or propose custom local campaign events.</p>
            </div>
        </div>
    </section>

    <!-- Main Grid -->
    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <!-- Left Column: Available Events -->
        <div class="space-y-6">
            <!-- Ambassador Events -->
            <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-extrabold text-slate-800 tracking-tight mb-4 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Ambassador Events
                </h3>
                
                <?php if (empty($saEvents)): ?>
                    <p class="text-sm text-slate-400 py-6 text-center border border-dashed border-slate-100 rounded-2xl">No upcoming ambassador events yet.</p>
                <?php endif; ?>
                
                <div class="grid gap-4 sm:grid-cols-2">
                    <?php foreach ($saEvents as $event): 
                        $isReg = in_array((int)$event['id'], $registeredEventIds);
                    ?>
                        <article class="flex flex-col justify-between rounded-2xl border border-slate-100 bg-slate-50/50 p-5 transition-all hover:bg-slate-50 hover:border-slate-200">
                            <div>
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700 border border-emerald-100 uppercase">
                                    Ambassador
                                </span>
                                <h4 class="font-bold text-slate-900 mt-2.5 leading-snug"><?php echo htmlspecialchars($event['title']); ?></h4>
                                <p class="text-xs text-slate-500 mt-2 flex items-center gap-1.5">
                                    <i class="fa-regular fa-calendar"></i>
                                    <?php echo !empty($event['start_date']) ? date('d M Y, h:i A', strtotime($event['start_date'])) : 'TBA'; ?>
                                </p>
                                <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                                    <i class="fa-solid fa-location-dot"></i>
                                    <?php echo htmlspecialchars($event['venue'] ?? $event['city_name'] ?? 'Online'); ?>
                                </p>
                            </div>
                            
                            <div class="mt-4 pt-4 border-t border-slate-100">
                                <?php if ($isReg): ?>
                                    <span class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-700 border border-emerald-100">
                                        <i class="fa-solid fa-circle-check"></i> Registered
                                    </span>
                                <?php else: ?>
                                    <button @click="registerSaEvent(<?php echo (int)$event['id']; ?>)" 
                                            class="w-full rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:shadow transition-all text-center">
                                        Register Event
                                    </button>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- NGO Public Events -->
            <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-extrabold text-slate-800 tracking-tight mb-4 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                    NGO Public Events
                </h3>
                
                <?php if (empty($ngoEvents)): ?>
                    <p class="text-sm text-slate-400 py-6 text-center border border-dashed border-slate-100 rounded-2xl">No open NGO events currently listed.</p>
                <?php endif; ?>
                
                <div class="grid gap-4 sm:grid-cols-2">
                    <?php foreach ($ngoEvents as $event): 
                        $isReg = in_array((int)$event['id'], $registeredEventIds);
                    ?>
                        <article class="flex flex-col justify-between rounded-2xl border border-slate-100 bg-slate-50/50 p-5 transition-all hover:bg-slate-50 hover:border-slate-200">
                            <div>
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 border border-slate-200 uppercase">
                                    NGO Event
                                </span>
                                <h4 class="font-bold text-slate-900 mt-2.5 leading-snug"><?php echo htmlspecialchars($event['title']); ?></h4>
                                <p class="text-xs text-slate-500 mt-2 flex items-center gap-1.5">
                                    <i class="fa-regular fa-calendar"></i>
                                    <?php echo !empty($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : 'TBA'; ?>
                                </p>
                                <p class="text-xs text-slate-500 mt-1 flex items-center gap-1.5">
                                    <i class="fa-solid fa-location-dot"></i>
                                    <?php echo htmlspecialchars($event['location'] ?? 'NGO Office'); ?>
                                </p>
                            </div>
                            
                            <div class="mt-4 pt-4 border-t border-slate-100">
                                <?php if ($isReg): ?>
                                    <span class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-emerald-50 px-4 py-2.5 text-xs font-bold text-emerald-700 border border-emerald-100">
                                        <i class="fa-solid fa-circle-check"></i> Registered
                                    </span>
                                <?php else: ?>
                                    <button @click="registerEvent(<?php echo (int)$event['id']; ?>)" 
                                            class="w-full rounded-xl bg-slate-800 hover:bg-slate-900 px-4 py-2.5 text-xs font-bold text-white transition-all text-center">
                                        Register Event
                                    </button>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>

        <!-- Right Column: Proposal & History -->
        <div class="space-y-6">
            <!-- Request Form -->
            <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">Propose New Event</h3>
                <p class="text-xs text-slate-500 mt-1">Pitch a local college, classroom, or public camp campaign.</p>
                
                <form @submit.prevent="submitRequest($event)" class="mt-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Event Title *</label>
                        <input type="text" name="event_title" required placeholder="e.g. Blood Donation Drive" 
                               class="w-full rounded-2xl border border-slate-100 bg-slate-50/50 px-4 py-3 text-sm font-semibold text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all outline-none">
                    </div>
                    
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Event Date</label>
                            <input type="date" name="event_date" 
                                   class="w-full rounded-2xl border border-slate-100 bg-slate-50/50 px-4 py-3 text-sm font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Venue / City</label>
                            <input type="text" name="event_location" placeholder="e.g. Campus Hall" 
                                   class="w-full rounded-2xl border border-slate-100 bg-slate-50/50 px-4 py-3 text-sm font-semibold text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all outline-none">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5">Brief Description</label>
                        <textarea name="description" rows="3" placeholder="Outline of target audience, expected volunteers, and agenda..." 
                                  class="w-full rounded-2xl border border-slate-100 bg-slate-50/50 px-4 py-3 text-sm font-semibold text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all outline-none resize-none"></textarea>
                    </div>
                    
                    <button type="submit" 
                            class="w-full rounded-2xl bg-slate-900 hover:bg-black py-3.5 text-sm font-bold text-white transition-all shadow-lg shadow-slate-900/10">
                        Submit Proposal
                    </button>
                </form>
                
                <div x-show="message" x-cloak class="mt-4 rounded-2xl p-4 text-xs font-semibold"
                     :class="ok ? 'bg-emerald-50 text-emerald-800 border border-emerald-100' : 'bg-rose-50 text-rose-800 border border-rose-100'">
                     <span x-text="message"></span>
                </div>
            </section>

            <!-- Activity Logs -->
            <?php if (!empty($myRegistrations) || !empty($myRequests)): ?>
                <section class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                    <h3 class="text-lg font-extrabold text-slate-900 tracking-tight mb-4">My Activity</h3>
                    <div class="space-y-3 max-h-[300px] overflow-y-auto custom-scrollbar pr-1">
                        <?php foreach ($myRegistrations as $reg): ?>
                            <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50/50 p-4 border border-slate-100/50 text-xs">
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-850 truncate"><?php echo htmlspecialchars($reg['title']); ?></p>
                                    <p class="text-[10px] text-slate-405 mt-0.5"><?php echo !empty($reg['start_date']) ? date('d M Y', strtotime($reg['start_date'])) : ''; ?></p>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 font-bold text-emerald-700 border border-emerald-100 uppercase text-[9px] whitespace-nowrap">
                                    <?php echo htmlspecialchars($reg['status'] ?? 'Registered'); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php foreach ($myRequests as $req): 
                            $status = strtolower($req['status']);
                            $statusBg = 'bg-amber-50 text-amber-700 border border-amber-100';
                            if ($status === 'approved') {
                                $statusBg = 'bg-emerald-50 text-emerald-700 border border-emerald-100';
                            } elseif ($status === 'rejected' || $status === 'cancelled') {
                                $statusBg = 'bg-rose-50 text-rose-700 border border-rose-100';
                            }
                        ?>
                            <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50/50 p-4 border border-slate-100/50 text-xs">
                                <div class="min-w-0">
                                    <p class="font-bold text-slate-850 truncate"><?php echo htmlspecialchars($req['event_title']); ?></p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">Proposal Request</p>
                                </div>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 font-bold uppercase text-[9px] whitespace-nowrap <?php echo $statusBg; ?>">
                                    <?php echo htmlspecialchars($req['status']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('studentEvents', () => ({
        message: '', 
        ok: false, 
        csrf: <?php echo json_encode($csrf); ?>,
        registerEvent(eventId) { this._post({ action: 'register', event_id: eventId }); },
        registerSaEvent(eventId) { this._post({ action: 'register_sa', event_id: eventId }); },
        submitRequest(e) {
            const body = new FormData(e.target);
            body.append('action', 'request');
            body.append('csrf_token', this.csrf);
            fetch('process/student_event_action.php', { method: 'POST', body })
                .then(r => r.json()).then(d => { 
                    this.message = d.message; 
                    this.ok = d.success; 
                    if (d.success) {
                        e.target.reset();
                        setTimeout(() => location.reload(), 1500);
                    }
                });
        },
        _post(fields) {
            const body = new FormData();
            Object.entries(fields).forEach(([k,v]) => body.append(k, v));
            body.append('csrf_token', this.csrf);
            fetch('process/student_event_action.php', { method: 'POST', body })
                .then(r => r.json()).then(d => { 
                    this.message = d.message; 
                    this.ok = d.success; 
                    if (d.success) {
                        setTimeout(() => location.reload(), 1200);
                    } 
                });
        }
    }));
});
</script>

<?php student_portal_render_shell_end(); ?>

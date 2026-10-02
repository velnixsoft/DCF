<?php
require 'includes/header.php';
require '../config/db.php';
require '../includes/functions.php';
require '../includes/member_module.php';
require '../includes/qr_attendance.php';

if (!canAccessModule($pdo, 'manager', 'page.events')) {
    setFlash('error', 'Access denied.');
    header('Location: dashboard.php');
    exit;
}

$attendanceEventId = (int)($_GET['id'] ?? 0);
$attendanceEvent = qa_attendance_event($pdo, $attendanceEventId);
if (!$attendanceEvent) {
    setFlash('error', 'Attendance event not found.');
    header('Location: events.php');
    exit;
}

$settings = mm_load_settings($pdo);
$attendanceUrl = qa_attendance_url($settings, $attendanceEventId);

$stats = [
    'total_registered' => 0,
    'total_checked_in' => 0,
    'male_count' => 0,
    'female_count' => 0,
    'volunteer_count' => 0,
    'late_entries' => 0,
];
$presentMembers = [];
$timeline = [];

try {
    if (!empty($attendanceEvent['event_id']) && dbTableExists($pdo, 'event_registrations')) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE event_id = ?");
        $stmt->execute([(int)$attendanceEvent['event_id']]);
        $stats['total_registered'] = (int)$stmt->fetchColumn();
    }
    $presentMembers = qa_attendance_report_rows($pdo, $attendanceEventId, true);
    $stats['total_checked_in'] = count($presentMembers);

    foreach ($presentMembers as $row) {
        if (($row['attendee_type'] ?? '') === 'volunteer') {
            $stats['volunteer_count']++;
        }

        $gender = strtolower(trim((string)($row['gender'] ?? '')));
        if ($gender === 'male') {
            $stats['male_count']++;
        } elseif ($gender === 'female') {
            $stats['female_count']++;
        }

        $bucket = date('H:i', strtotime((string)$row['scan_time']));
        if (!isset($timeline[$bucket])) {
            $timeline[$bucket] = 0;
        }
        $timeline[$bucket]++;

        if (!empty($attendanceEvent['event_start_time'])) {
            $lateAfter = strtotime((string)$attendanceEvent['event_date'] . ' ' . (string)$attendanceEvent['event_start_time'] . ' +15 minutes');
            if ($lateAfter !== false && strtotime((string)$row['scan_time']) > $lateAfter) {
                $stats['late_entries']++;
            }
        }
    }
} catch (Throwable $e) {
    $presentMembers = [];
}

ksort($timeline);
?>

<div class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
    <?php require 'includes/sidebar.php'; ?>
    <div class="flex-1 flex flex-col md:ml-64 transition-all duration-300">
        <?php require 'includes/navbar.php'; ?>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-8">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-8">
                <div>
                    <h3 class="text-2xl md:text-3xl font-medium text-gray-700 dark:text-white"><?php echo htmlspecialchars($attendanceEvent['event_name']); ?></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        <?php echo htmlspecialchars(date('d M Y', strtotime((string)$attendanceEvent['event_date']))); ?>
                        <?php if (!empty($attendanceEvent['event_start_time'])): ?>
                            &middot; <?php echo htmlspecialchars(date('h:i A', strtotime((string)$attendanceEvent['event_start_time']))); ?>
                        <?php endif; ?>
                        <?php if (!empty($attendanceEvent['event_location'])): ?>
                            &middot; <?php echo htmlspecialchars($attendanceEvent['event_location']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="create-event.php?id=<?php echo (int)$attendanceEventId; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">Edit Attendance</a>
                    <a href="actions/export_attendance_report.php?id=<?php echo (int)$attendanceEventId; ?>&format=csv" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm">Export CSV</a>
                    <a href="actions/export_attendance_report.php?id=<?php echo (int)$attendanceEventId; ?>&format=pdf" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg text-sm">Export PDF</a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
                <?php
                $statCards = [
                    ['label' => 'Total Registered', 'value' => $stats['total_registered'], 'class' => 'text-blue-600'],
                    ['label' => 'Checked In', 'value' => $stats['total_checked_in'], 'class' => 'text-emerald-600'],
                    ['label' => 'Male', 'value' => $stats['male_count'], 'class' => 'text-sky-600'],
                    ['label' => 'Female', 'value' => $stats['female_count'], 'class' => 'text-pink-600'],
                    ['label' => 'Volunteers', 'value' => $stats['volunteer_count'], 'class' => 'text-violet-600'],
                    ['label' => 'Late Entries', 'value' => $stats['late_entries'], 'class' => 'text-amber-600'],
                ];
                foreach ($statCards as $card):
                ?>
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-5">
                        <p class="text-xs uppercase tracking-wide text-gray-500"><?php echo htmlspecialchars($card['label']); ?></p>
                        <p class="mt-2 text-3xl font-semibold <?php echo $card['class']; ?>"><?php echo (int)$card['value']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_390px] gap-6">
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-5">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                            <div>
                                <h4 class="font-semibold text-gray-800 dark:text-white">Present Attendees</h4>
                                <p class="text-xs text-gray-500 mt-1">Live list of successful member and volunteer check-ins.</p>
                            </div>
                            <span class="text-xs px-3 py-1 rounded-full <?php echo ($attendanceEvent['status'] === 'active') ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700'; ?>">
                                <?php echo strtoupper(htmlspecialchars($attendanceEvent['status'])); ?>
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 border-b dark:border-gray-700">
                                        <th class="p-2">Attendee</th>
                                        <th class="p-2">Designation</th>
                                        <th class="p-2">Phone</th>
                                        <th class="p-2">Scan Time</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y dark:divide-gray-700">
                                    <?php foreach ($presentMembers as $row): ?>
                                        <tr>
                                            <td class="p-2">
                                                <div class="font-semibold dark:text-white"><?php echo htmlspecialchars($row['full_name']); ?></div>
                                                <div class="text-xs text-gray-500">
                                                    <?php echo htmlspecialchars($row['member_no']); ?>
                                                    <?php if (($row['attendee_type'] ?? '') === 'volunteer'): ?>
                                                        <span class="ml-1 px-1.5 py-0.5 rounded bg-violet-100 text-violet-700">Volunteer</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="p-2 dark:text-gray-200"><?php echo htmlspecialchars($row['designation_title'] ?: '-'); ?></td>
                                            <td class="p-2 dark:text-gray-200"><?php echo htmlspecialchars($row['phone'] ?: '-'); ?></td>
                                            <td class="p-2 text-xs text-gray-500"><?php echo htmlspecialchars(date('d M Y h:i A', strtotime((string)$row['scan_time']))); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($presentMembers)): ?>
                                        <tr><td colspan="4" class="p-6 text-center text-gray-500">No attendance marked yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-5">
                        <h4 class="font-semibold text-gray-800 dark:text-white mb-4">Scan Timeline</h4>
                        <canvas id="timelineChart" height="120"></canvas>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border dark:border-gray-700 shadow-sm p-5">
                        <h4 class="font-semibold text-gray-800 dark:text-white">Scanner</h4>
                        <p class="text-xs text-gray-500 mt-1">Scan the member QR from ID cards or certificates. The same secure token URL is used for verification and attendance.</p>

                        <div class="mt-4 rounded-xl border border-dashed dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-900/30">
                            <label class="block text-xs font-bold uppercase text-gray-500 mb-2">Attendance URL</label>
                            <input type="text" readonly value="<?php echo htmlspecialchars($attendanceUrl); ?>" class="w-full px-3 py-2 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <p class="text-xs text-gray-500 mt-2">Use this on the gate device. The QR below opens the same logged-in scanner page.</p>
                            <img src="<?php echo htmlspecialchars(mm_qr_image_url($attendanceUrl)); ?>" alt="Attendance QR" class="w-40 h-40 mt-3 rounded-lg border bg-white p-2">
                        </div>

                        <div class="mt-5">
                            <label class="block text-xs font-bold uppercase text-gray-500 mb-2">Manual / Camera Scan</label>
                            <div id="scanReader" class="rounded-xl overflow-hidden"></div>
                            <form id="attendanceScanForm" class="mt-4 space-y-3">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="attendance_event_id" value="<?php echo (int)$attendanceEventId; ?>">
                                <input type="text" name="token" id="attendanceToken" placeholder="Paste QR URL, token, member ID, or doc no" class="w-full px-4 py-3 rounded-lg border dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-medium">Mark Attendance</button>
                            </form>
                        </div>

                        <div id="scanResult" class="hidden mt-4 rounded-xl border p-4"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
const form = document.getElementById('attendanceScanForm');
const tokenInput = document.getElementById('attendanceToken');
const resultBox = document.getElementById('scanResult');
const scanReader = document.getElementById('scanReader');

let lastScan = '';
let lastScanAt = 0;
let processing = false;
let audioContext = null;

function escapeHtml(value) {
    return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function beep(success = true) {
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtx) {
        return;
    }

    if (!audioContext) {
        audioContext = new AudioCtx();
    }

    if (audioContext.state === 'suspended') {
        audioContext.resume().catch(() => {});
    }

    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    const startTime = audioContext.currentTime;
    const frequencies = success ? [880, 1175] : [320, 240];

    oscillator.type = success ? 'sine' : 'square';
    oscillator.frequency.setValueAtTime(frequencies[0], startTime);
    oscillator.frequency.setValueAtTime(frequencies[1], startTime + 0.09);

    gainNode.gain.setValueAtTime(0.001, startTime);
    gainNode.gain.exponentialRampToValueAtTime(0.18, startTime + 0.01);
    gainNode.gain.exponentialRampToValueAtTime(0.001, startTime + 0.18);

    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    oscillator.start(startTime);
    oscillator.stop(startTime + 0.2);
}

function attendeeBlock(payload) {
    const attendee = payload.member || payload.volunteer;
    if (!attendee) {
        return '';
    }

    return `
        <div class="mt-3 text-sm space-y-1">
            <div><strong>Name:</strong> ${escapeHtml(attendee.full_name || '-')}</div>
            <div><strong>ID:</strong> ${escapeHtml(attendee.member_no || '-')}</div>
            <div><strong>Phone:</strong> ${escapeHtml(attendee.phone || '-')}</div>
            <div><strong>Role:</strong> ${escapeHtml(attendee.designation_title || (payload.attendee_type || '-'))}</div>
        </div>
    `;
}

function renderResult(payload) {
    const success = Boolean(payload && payload.success);
    resultBox.classList.remove('hidden', 'border-emerald-200', 'bg-emerald-50', 'text-emerald-800', 'border-red-200', 'bg-red-50', 'text-red-800', 'border-amber-200', 'bg-amber-50', 'text-amber-800');

    if (success) {
        resultBox.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-800');
    } else if ((payload && payload.status) === 'duplicate') {
        resultBox.classList.add('border-amber-200', 'bg-amber-50', 'text-amber-800');
    } else {
        resultBox.classList.add('border-red-200', 'bg-red-50', 'text-red-800');
    }

    const scanTime = payload && payload.scan_time ? payload.scan_time : '';
    resultBox.innerHTML = `
        <div class="font-semibold">${escapeHtml(payload && payload.message ? payload.message : 'Unable to process attendance.')}</div>
        ${attendeeBlock(payload || {})}
        ${scanTime ? `<div class="mt-3 text-xs opacity-80">Scan Time: ${escapeHtml(scanTime)}</div>` : ''}
    `;
}

function renderScannerNotice(message, tone = 'text-amber-600') {
    scanReader.innerHTML = `<div class="rounded-xl border border-dashed p-4 text-sm ${tone}">${message}</div>`;
}

async function submitScan(value) {
    const clean = value.trim();
    if (!clean || processing) {
        return;
    }

    processing = true;

    try {
        const data = new FormData(form);
        data.set('token', clean);

        const res = await fetch('actions/attendance_scan.php', {
            method: 'POST',
            body: data
        });

        const payload = await res.json();
        renderResult(payload);

        if (payload.success) {
            beep(true);
            tokenInput.value = '';
            setTimeout(() => window.location.reload(), 900);
        } else {
            beep(false);
        }
    } catch (error) {
        console.error(error);
        renderResult({
            success: false,
            status: 'network_error',
            message: 'Scanner request failed. Please check the connection and try again.'
        });
        beep(false);
    } finally {
        setTimeout(() => {
            processing = false;
        }, 900);
    }
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();
    await submitScan(tokenInput.value);
});

tokenInput.addEventListener('focus', () => {
    if (audioContext && audioContext.state === 'suspended') {
        audioContext.resume().catch(() => {});
    }
});

if (window.Html5Qrcode) {
    const qr = new Html5Qrcode('scanReader');

    Html5Qrcode.getCameras()
        .then((devices) => {
            if (!devices.length) {
                renderScannerNotice('No camera detected. You can still paste the QR value manually.');
                return;
            }

            return qr.start(
                { facingMode: 'environment' },
                {
                    fps: 10,
                    qrbox: { width: 260, height: 260 },
                    rememberLastUsedCamera: true
                },
                async (decodedText) => {
                    const clean = decodedText.trim();
                    const now = Date.now();

                    if (!clean || processing) {
                        return;
                    }

                    if (clean === lastScan && (now - lastScanAt) < 3000) {
                        return;
                    }

                    lastScan = clean;
                    lastScanAt = now;
                    tokenInput.value = clean;
                    await submitScan(clean);
                },
                () => {}
            );
        })
        .catch((error) => {
            console.error(error);
            renderScannerNotice('Camera scanner could not start. You can still paste the QR value manually.', 'text-red-600');
        });
} else {
    renderScannerNotice('QR camera library did not load. You can still paste the QR value manually.');
}
</script>

<?php require 'includes/footer.php'; ?>

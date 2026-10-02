<?php
session_start();
require 'config/db.php';
require 'includes/functions.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: admin/');
    exit;
}

$userHierarchy = normalizeHierarchyRole($_SESSION['hierarchy_level'] ?? '');
if ($userHierarchy !== 'field_agent') {
    header('Location: admin/dashboard.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $punchType = $_POST['punch_type'] ?? '';
    if (!in_array($punchType, ['IN', 'OUT'])) {
        $message = 'Invalid punch type.';
    } else {
        // Get GPS from JS
        $lat = $_POST['lat'] ?? null;
        $lng = $_POST['lng'] ?? null;
        $address = $_POST['address'] ?? null;

        // Get agent_id
        $agentStmt = $pdo->prepare('SELECT id FROM field_agents WHERE user_id = ?');
        $agentStmt->execute([$userId]);
        $agentId = $agentStmt->fetchColumn();

        if ($agentId) {
            $stmt = $pdo->prepare('
                INSERT INTO agent_attendance (agent_id, punch_type, latitude, longitude, address) 
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$agentId, $punchType, $lat, $lng, $address]);
            $message = 'Punch ' . $punchType . ' recorded successfully!';
            $success = true;
        } else {
            $message = 'Agent profile not found.';
        }
    }
}

require 'includes/header.php';
?>
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-50 py-12">
    <div class="container mx-auto px-4 max-w-md">
        <div class="bg-white rounded-3xl shadow-2xl border border-blue-100 p-8 text-center">
            <div class="w-20 h-20 bg-blue-500 text-white rounded-full mx-auto mb-6 flex items-center justify-center">
                <i class="fas fa-clock text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Mark Attendance</h1>
            <p class="text-gray-600 mb-8">Punch In/Out with GPS location</p>

            <?php if (isset($message)): ?>
            <div class="mb-6 p-4 rounded-xl <?php echo isset($success) && $success ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <div id="location-info" class="mb-8 p-4 bg-gray-50 rounded-xl text-sm text-gray-600 hidden">
                📍 Location will be captured automatically
            </div>

            <div class="space-y-4">
                <button onclick="punch('IN')" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-6 rounded-2xl shadow-lg transition transform hover:scale-105">
                    <i class="fas fa-sign-in-alt mr-2"></i> Punch IN
                </button>
                <button onclick="punch('OUT')" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-4 px-6 rounded-2xl shadow-lg transition transform hover:scale-105">
                    <i class="fas fa-sign-out-alt mr-2"></i> Punch OUT
                </button>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-100">
                <p class="text-xs text-gray-500">
                    Your location is recorded for verification. 
                    Enable GPS/location services for accuracy.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
let watchId = null;

function getLocation() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject('Geolocation not supported');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            position => {
                const lat = position.coords.latitude.toFixed(6);
                const lng = position.coords.longitude.toFixed(6);
                const accuracy = Math.round(position.coords.accuracy || 0);
                
                // Reverse geocode
                fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`)
                    .then(r => r.json())
                    .then(data => {
                        const address = data.display_name || 'Location recorded';
                        resolve({lat, lng, address, accuracy});
                    })
                    .catch(() => resolve({lat, lng, address: 'GPS: ' + lat + ', ' + lng, accuracy}));
            },
            error => reject(error.message),
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
        );
    });
}

async function punch(type) {
    try {
        document.querySelectorAll('button').forEach(btn => btn.disabled = true);
        
        const location = await getLocation();
        document.getElementById('location-info').classList.remove('hidden');
        document.getElementById('location-info').innerHTML = `
            📍 <strong>${location.address}</strong><br>
            GPS: ${location.lat}, ${location.lng} (±${location.accuracy}m)
        `;

        const formData = new FormData();
        formData.append('punch_type', type);
        formData.append('lat', location.lat);
        formData.append('lng', location.lng);
        formData.append('address', location.address);

        const response = await fetch('process/punch_attendance.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.message || 'Unable to record attendance');
        }

        alert(data.message || 'Attendance recorded');
        window.location.reload();
    } catch (error) {
        alert('Error: ' + error);
        document.querySelectorAll('button').forEach(btn => btn.disabled = false);
    }
}

// Auto-detect location on load
getLocation().catch(() => {});
</script>

<?php require 'includes/footer.php'; ?>


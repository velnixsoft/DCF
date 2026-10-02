<?php
if (php_sapi_name() === 'cli-server') {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = urldecode($uri);
    if (strncmp($uri, '/admin/', 7) === 0 && strlen($uri) > 7) {
        $admin_target = substr($uri, 7);
        $admin_target_underscore = str_replace('-', '_', $admin_target);
        $php_file = __DIR__ . '/' . $admin_target . '.php';
        $php_file_underscore = __DIR__ . '/' . $admin_target_underscore . '.php';
        if (file_exists($php_file)) {
            $chosen = $php_file;
            $script_name = $uri . '.php';
        } elseif (file_exists($php_file_underscore)) {
            $chosen = $php_file_underscore;
            $script_name = '/admin/' . $admin_target_underscore . '.php';
        } else {
            $chosen = null;
        }
        if ($chosen) {
            $_SERVER['SCRIPT_NAME'] = $script_name;
            $_SERVER['PHP_SELF'] = $script_name;
            $_SERVER['SCRIPT_FILENAME'] = $chosen;
            chdir(dirname($chosen));
            require $chosen;
            exit;
        }
    }
}

require '../config/db.php';
require '../includes/functions.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ' . getDefaultAdminLandingPage($pdo));
    exit;
}
$remembered_email = $_COOKIE['remember_email'] ?? '';
$adminBasePath = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/');
if ($adminBasePath === '') {
    $adminBasePath = '/admin';
}
?>
<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="../assets/style.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }

        @keyframes shrink {
            from {
                width: 100%;
            }

            to {
                width: 0%;
            }
        }

        .animate-shrink {
            animation: shrink 3s linear forwards;
        }
    </style>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('toast', {
                visible: false,
                message: '',
                type: 'success',
                show(msg, type) {
                    this.message = msg;
                    this.type = type;
                    this.visible = true;
                    setTimeout(() => {
                        this.visible = false
                    }, 3000);
                }
            });
        });
    </script>
</head>

<body class="bg-gray-900 bg-[url('')] bg-cover bg-center min-h-screen flex items-center justify-center p-4 backdrop-blur-sm" x-data>

    <div x-data x-show="$store.toast.visible" x-transition class="fixed top-5 right-5 z-50 max-w-sm w-full bg-white shadow-2xl rounded-lg overflow-hidden border-l-4" :class="$store.toast.type === 'success' ? 'border-green-500' : 'border-red-500'" x-cloak>
        <div class="p-4 flex items-start">
            <div class="flex-shrink-0"><svg x-show="$store.toast.type === 'success'" class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg><svg x-show="$store.toast.type === 'error'" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg></div>
            <div class="ml-3 w-0 flex-1 pt-0.5">
                <p class="text-sm font-medium text-gray-900" x-text="$store.toast.type === 'success' ? 'Success!' : 'Error!'"></p>
                <p class="mt-1 text-sm text-gray-500" x-text="$store.toast.message"></p>
            </div>
            <div class="ml-4 flex-shrink-0 flex"><button @click="$store.toast.visible = false" class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500"><svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" />
                    </svg></button></div>
        </div>
        <div class="h-1 bg-gray-100 w-full">
            <div class="h-full animate-shrink" :class="$store.toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'"></div>
        </div>
    </div>

    <div class="bg-white/95 backdrop-blur w-full max-w-md p-8 rounded-2xl shadow-2xl border border-white/20">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-extrabold text-green-800">Admin Secure Login</h2>
            <p class="text-gray-500 text-sm mt-2">Access your NGO Dashboard</p>
        </div>

        <form action="<?php echo htmlspecialchars($adminBasePath . '/auth.php'); ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <div class="mb-5">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($remembered_email); ?>" required
                    class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 focus:border-green-500 focus:bg-white focus:ring-2 focus:ring-green-200 outline-none transition duration-200"
                    placeholder="admin@example.com">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 focus:border-green-500 focus:bg-white focus:ring-2 focus:ring-green-200 outline-none transition duration-200"
                    placeholder="••••••••">
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-green-600 to-teal-600 text-white font-bold py-3.5 rounded-lg hover:from-green-700 hover:to-teal-700 focus:ring-4 focus:ring-green-300 transition duration-300 shadow-lg transform hover:-translate-y-0.5">
                Sign In
            </button>
        </form>
    </div>

    <?php if (isset($_SESSION['flash'])): ?>
        <script>
            document.addEventListener('alpine:initialized', () => {
                const flashData = <?php echo json_encode($_SESSION['flash']); ?>;
                Alpine.store('toast').show(flashData.message, flashData.type);
            });
        </script>
    <?php unset($_SESSION['flash']);
    endif; ?>
</body>

</html>

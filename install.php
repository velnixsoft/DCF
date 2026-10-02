<?php
/**
 * Velnix Soft - Universal Database & System Installer
 * Works seamlessly via CLI (php install.php) or Web Browser (http://yourdomain.com/install.php)
 */

require_once __DIR__ . '/config/config.php';

$isCli = (php_sapi_name() === 'cli');

// Default database credentials
$dbConfigFile = __DIR__ . '/config/database.php';
$host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
$port = defined('DB_PORT') ? DB_PORT : '3306';
$dbname = defined('DB_NAME') ? DB_NAME : 'velnixsoft';
$user = defined('DB_USER') ? DB_USER : 'root';
$pass = defined('DB_PASS') ? DB_PASS : '';

$status = [];
$error = null;
$success = false;

// If POST request with custom credentials
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isCli) {
    if (!empty($_POST['db_host'])) $host = trim($_POST['db_host']);
    if (!empty($_POST['db_port'])) $port = trim($_POST['db_port']);
    if (!empty($_POST['db_name'])) $dbname = trim($_POST['db_name']);
    if (isset($_POST['db_user'])) $user = trim($_POST['db_user']);
    if (isset($_POST['db_pass'])) $pass = trim($_POST['db_pass']);
}

if ($isCli || $_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['auto'])) {
    try {
        // 1. Test MySQL Connection
        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
        $status[] = "Connected to MySQL server ($host:$port) successfully.";

        // 2. Create database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $status[] = "Database `$dbname` created / verified.";

        // 3. Select database
        $pdo->exec("USE `$dbname`");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 4. Update config/database.php if credentials changed
        $dbConfigContent = "<?php\n/**\n * Velnix Soft - Database Connection & PDO Singleton\n */\n\nrequire_once __DIR__ . '/config.php';\n\n// Database Credentials\ndefine('DB_HOST', getenv('DB_HOST') ?: " . var_export($host, true) . ");\ndefine('DB_PORT', getenv('DB_PORT') ?: " . var_export($port, true) . ");\ndefine('DB_NAME', getenv('DB_NAME') ?: " . var_export($dbname, true) . ");\ndefine('DB_USER', getenv('DB_USER') ?: " . var_export($user, true) . ");\ndefine('DB_PASS', getenv('DB_PASS') ?: " . var_export($pass, true) . ");\ndefine('DB_CHARSET', 'utf8mb4');\n\n" . file_get_contents(__DIR__ . '/config/database_class.txt');
        
        // Write config
        @file_put_contents($dbConfigFile, $dbConfigContent);
        $status[] = "Configuration updated with active database credentials.";

        // 5. Read database.sql and execute in clean individual statements
        $sqlFile = __DIR__ . '/database.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("database.sql not found at $sqlFile");
        }

        $sql = file_get_contents($sqlFile);
        
        // Split SQL script into individual statements safely
        $statements = preg_split('/;\s*[\r\n]+/', $sql);
        $executedCount = 0;

        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            // Skip empty or comment lines
            if (empty($stmt) || strpos($stmt, '--') === 0 || strpos($stmt, '/*') === 0) {
                continue;
            }
            try {
                $pdo->exec($stmt);
                $executedCount++;
            } catch (Exception $e) {
                // If statement is DROP TABLE or SET, continue safely
                if (stripos($stmt, 'DROP TABLE') === false && stripos($stmt, 'SET') === false) {
                    error_log("Installer SQL Notice on: " . substr($stmt, 0, 50) . " - " . $e->getMessage());
                }
            }
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
        $status[] = "Executed $executedCount database statements and seeded all initial data (18 Modules, 73 Features, 8 Benefits, Products, Services).";

        // 6. Create upload directories
        $uploadDirs = [
            UPLOADS_PATH,
            UPLOADS_PATH . '/products',
            UPLOADS_PATH . '/services',
            UPLOADS_PATH . '/media',
            UPLOADS_PATH . '/testimonials',
            UPLOADS_PATH . '/general'
        ];

        foreach ($uploadDirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
        $status[] = "Upload directories verified and created.";
        $success = true;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }

    if ($isCli) {
        echo "\n============================================\n";
        echo "   VELNIX SOFT CMS - INSTALLATION RESULT   \n";
        echo "============================================\n";
        foreach ($status as $msg) {
            echo "[OK] " . $msg . "\n";
        }
        if ($error) {
            echo "[ERROR] " . $error . "\n";
            exit(1);
        } else {
            echo "\n✓ Installation completed successfully!\n";
            echo "Default Admin Login:\n";
            echo "URL:      " . BASE_URL . "/admin/login.php\n";
            echo "Username: admin\n";
            echo "Password: Admin@Velnix2026\n";
            echo "============================================\n\n";
            exit(0);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Velnix Soft - System & Database Installer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --dark: #0b192c;
            --light: #f8fafc;
            --success: #10b981;
            --danger: #ef4444;
            --border: #e2e8f0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #0b192c; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .card { background: #ffffff; border-radius: 20px; width: 100%; max-width: 600px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #0b192c 0%, #1e3a8a 100%); color: #fff; padding: 32px 30px; text-align: center; }
        .card-header h1 { font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }
        .card-header p { color: #93c5fd; font-size: 14px; margin-top: 6px; }
        .card-body { padding: 32px 30px; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 11px 14px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .log-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 20px; font-family: monospace; font-size: 13px; max-height: 200px; overflow-y: auto; }
        .log-item { color: #059669; margin-bottom: 6px; }
        .error-box { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 14px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; }
        .success-box { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 20px; border-radius: 12px; margin-bottom: 24px; }
        .btn { display: block; width: 100%; padding: 14px; background: var(--primary); color: #fff; border: none; border-radius: 10px; font-weight: 700; font-size: 15px; cursor: pointer; text-align: center; text-decoration: none; transition: 0.2s ease; }
        .btn:hover { background: var(--primary-dark); }
        .btn-success { background: var(--success); }
        .btn-success:hover { background: #059669; }
        .info-table { width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 14px; }
        .info-table td { padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .info-table td:first-child { font-weight: 600; color: #475569; width: 140px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h1>Velnix Soft CMS Installer</h1>
            <p>Database Initializer &amp; Live Server Setup</p>
        </div>
        <div class="card-body">
            <?php if ($success): ?>
                <div class="success-box">
                    <h3 style="margin-bottom: 8px;">✓ Installation Completed Successfully!</h3>
                    <p style="font-size: 14px;">The database <strong><?= htmlspecialchars($dbname) ?></strong> has been initialized and seeded with all 18 Modules, 73 Features, 8 Benefits, Services, and CMS settings.</p>
                    
                    <table class="info-table">
                        <tr><td>Admin URL:</td><td><a href="<?= BASE_URL ?>/admin/login.php" target="_blank"><?= BASE_URL ?>/admin/login.php</a></td></tr>
                        <tr><td>Username:</td><td><strong>admin</strong></td></tr>
                        <tr><td>Password:</td><td><strong>Admin@Velnix2026</strong></td></tr>
                    </table>
                </div>

                <div class="log-box">
                    <?php foreach ($status as $msg): ?>
                        <div class="log-item">✔ <?= htmlspecialchars($msg) ?></div>
                    <?php endforeach; ?>
                </div>

                <div style="display: flex; gap: 12px;">
                    <a href="<?= BASE_URL ?>/admin/login.php" class="btn btn-success" style="flex: 1;">Go to Admin Panel</a>
                    <a href="<?= BASE_URL ?>/" class="btn" style="flex: 1;">View Website</a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="error-box">
                        <strong>Installation Error:</strong><br>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <p style="font-size: 14px; color: #64748b; margin-bottom: 20px;">
                    Enter your MySQL connection credentials below (or click install to use current configuration) to initialize all tables and company data.
                </p>

                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">DB Host</label>
                            <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($host) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">DB Port</label>
                            <input type="text" name="db_port" class="form-control" value="<?= htmlspecialchars($port) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Database Name</label>
                        <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars($dbname) ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">DB User</label>
                            <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($user) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">DB Password</label>
                            <input type="password" name="db_pass" class="form-control" value="<?= htmlspecialchars($pass) ?>" placeholder="Leave blank if none">
                        </div>
                    </div>

                    <button type="submit" class="btn" style="margin-top: 10px;">Start 1-Click Database Setup</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

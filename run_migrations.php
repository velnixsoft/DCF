<?php
/**
 * Database Migration Runner
 * 
 * Runs SQL migration files to set up donation achievement tables
 * Usage: php run_migrations.php
 */

require_once __DIR__ . '/config/db.php';

if (php_sapi_name() !== 'cli') {
    echo "CLI only.\n";
    exit(1);
}

$migrations = [
    'database/create_donation_achievements.sql'
];

echo "Running database migrations...\n\n";

foreach ($migrations as $migrationFile) {
    if (!file_exists($migrationFile)) {
        echo "❌ Migration file not found: $migrationFile\n";
        continue;
    }
    
    echo "📋 Running: $migrationFile\n";
    
    $sql = file_get_contents($migrationFile);
    $statements = array_filter(array_map('trim', preg_split('/;/', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
        } catch (Exception $e) {
            echo "   ⚠️  Statement error: " . $e->getMessage() . "\n";
            echo "   SQL: " . substr($statement, 0, 100) . "...\n";
            $errorCount++;
        }
    }
    
    echo "   ✅ $successCount statements executed";
    if ($errorCount > 0) {
        echo " ($errorCount warnings/expected duplicates)";
    }
    echo "\n\n";
}

// Verify tables were created
echo "📊 Verifying table creation...\n";
$tables = ['donation_milestones', 'donation_achievements'];
foreach ($tables as $table) {
    try {
        $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
        if ($result) {
            echo "   ✅ Table '$table' exists\n";
        } else {
            echo "   ❌ Table '$table' NOT found\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error checking '$table': " . $e->getMessage() . "\n";
    }
}

// Verify milestones were seeded
echo "\n📈 Verifying milestone data...\n";
try {
    $milestones = $pdo->query("SELECT COUNT(*) FROM donation_milestones")->fetchColumn();
    echo "   ✅ Found $milestones donation milestones\n";
    
    $stmt = $pdo->query("SELECT milestone_amount, reward_points, badge_code FROM donation_milestones ORDER BY milestone_amount");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $badge = $row['badge_code'] ? "Badge: {$row['badge_code']}" : "Points: +{$row['reward_points']}";
        echo "      • ₹" . number_format($row['milestone_amount'], 2) . " → $badge\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error checking milestones: " . $e->getMessage() . "\n";
}

echo "\n✨ Migration complete!\n";
exit(0);

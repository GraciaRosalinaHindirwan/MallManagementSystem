<?php
/**
 * Parking Management - Session to Database Migration Script
 * 
 * Run this script once when you want to migrate existing session data to database.
 * Usage: php migrate.php from command line or via browser
 * 
 * IMPORTANT: Ensure config/db.php is set up and database tables exist before running!
 */

// Start session to access existing data
require_once 'database.php';

// Load DB config if available
$pdo = null;
if (file_exists(__DIR__ . '/../config/db.php')) {
    try {
        require_once __DIR__ . '/../config/db.php';
    } catch (Exception $e) {
        die('Error loading DB config: ' . $e->getMessage());
    }
}

if (!$pdo || !($pdo instanceof PDO)) {
    die('Database connection not available. Please set up config/db.php first.');
}

echo "=== Parking Management - Session to Database Migration ===\n\n";

// Check if database has data already
try {
    $countStmt = $pdo->query('SELECT COUNT(*) FROM vehicles');
    $existing = $countStmt->fetchColumn();
    if ($existing > 0) {
        echo "Warning: Database already contains {$existing} vehicle records.\n";
        echo "Migration aborted to prevent duplicates.\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "Error checking existing data: " . $e->getMessage() . "\n";
    exit(1);
}

// Migrate members
try {
    echo "[1] Migrating members...\n";
    $members = $_SESSION['parking']['members'] ?? [];
    $memberStmt = $pdo->prepare(
        'INSERT INTO members (name, email, phone, type) VALUES (?, ?, ?, ?)'
    );
    foreach ($members as $m) {
        $memberStmt->execute([
            $m['name'] ?? '',
            $m['email'] ?? '',
            $m['phone'] ?? '',
            $m['type'] ?? 'regular',
        ]);
    }
    echo "    Migrated " . count($members) . " members.\n";
} catch (Exception $e) {
    echo "    Error: " . $e->getMessage() . "\n";
}

// Migrate subscriptions
try {
    echo "[2] Migrating subscriptions...\n";
    $subs = $_SESSION['parking']['subscriptions'] ?? [];
    $subStmt = $pdo->prepare(
        'INSERT INTO subscriptions (name, slots, package, discount) VALUES (?, ?, ?, ?)'
    );
    foreach ($subs as $s) {
        $subStmt->execute([
            $s['name'] ?? '',
            $s['slots'] ?? 0,
            $s['package'] ?? 'basic',
            $s['discount'] ?? 20,
        ]);
    }
    echo "    Migrated " . count($subs) . " subscriptions.\n";
} catch (Exception $e) {
    echo "    Error: " . $e->getMessage() . "\n";
}

// Migrate transactions
try {
    echo "[3] Migrating transactions...\n";
    $txns = $_SESSION['parking']['transactions'] ?? [];
    
    // Reverse to maintain chronological order (session stores newest first)
    $txns = array_reverse($txns);
    
    $txStmt = $pdo->prepare(
        'INSERT INTO transactions (plate, type, owner_name, entry_time, exit_time, duration_minutes, base_tariff, discount_percent, discount_amount, total)
         VALUES (?, ?, ?, NOW() - INTERVAL ? MINUTE, NOW(), ?, ?, ?, ?, ?)'
    );
    
    foreach ($txns as $tx) {
        $txStmt->execute([
            $tx['plate'] ?? '',
            $tx['type'] ?? 'regular',
            $tx['owner_name'] ?? '',
            ($tx['duration'] ?? 0),  // Use duration to estimate entry time
            $tx['duration'] ?? 0,
            $tx['baseTariff'] ?? 0,
            $tx['discountPercent'] ?? 0,
            $tx['discountAmount'] ?? 0,
            $tx['total'] ?? 0,
        ]);
    }
    echo "    Migrated " . count($txns) . " transactions.\n";
} catch (Exception $e) {
    echo "    Error: " . $e->getMessage() . "\n";
}

echo "\n=== Migration Complete ===\n";
echo "Note: Verify the migrated data in phpMyAdmin before continuing.\n";
echo "You can now use the parking system with database persistence.\n";

?>

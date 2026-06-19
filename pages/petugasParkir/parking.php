<?php
require_once 'database.php';

// Try to load database connection if config/db.php exists
$pdo = null;
$useDb = false;
if (file_exists(__DIR__ . '/../config/db.php')) {
    try {
        require_once __DIR__ . '/../config/db.php';
        if (isset($pdo) && $pdo instanceof PDO) {
            $useDb = true;
        }
    } catch (Exception $e) {
        error_log('Failed to load DB config: ' . $e->getMessage());
        // fallback to session
    }
}

function getParkingState() {
    global $useDb, $pdo;
    
    if ($useDb && $pdo) {
        return getParkingStateFromDb($pdo);
    }
    
    // Fallback to session
    $parking = &$_SESSION['parking'];
    $vehicles = $parking['vehicles'] ?? [];

    $members = $parking['members'] ?? [];
    // augment members with computed discountPercent for frontend convenience
    $membersAug = array_map(function($m) {
        $m['discountPercent'] = getMemberDiscount($m['type'] ?? 'regular');
        return $m;
    }, $members);

    return [
        'totalSlots' => 500,
        'occupied' => $parking['occupied'] ?? count($vehicles),
        'available' => max(0, 500 - ($parking['occupied'] ?? count($vehicles))),
        'vehicles' => $vehicles,
        'members' => $membersAug,
        'subscriptions' => $parking['subscriptions'] ?? [],
        'transactions' => array_slice(array_reverse($parking['transactions'] ?? []), 0, 20),
        'stats' => $parking['stats'] ?? [
            'entry' => 0,
            'exit' => 0,
            'revenue' => 0,
            'durations' => [],
        ],
    ];
}

function getParkingStateFromDb($pdo) {
    try {
        // Fetch vehicles
        $stmt = $pdo->prepare('SELECT id, plate, type, owner_name, ticket FROM vehicles WHERE 1');
        $stmt->execute();
        $vehicles = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $vehicles[$row['plate']] = [
                'id' => $row['id'],
                'type' => $row['type'],
                'owner_name' => $row['owner_name'],
                'ticket' => $row['ticket'],
                'time' => strtotime($row['entry_time'] ?? 'now'),
            ];
        }

        // Fetch members
        $stmt = $pdo->prepare('SELECT id, name, email, phone, type FROM members');
        $stmt->execute();
        $members = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['discountPercent'] = getMemberDiscount($row['type']);
            $members[] = $row;
        }

        // Fetch subscriptions
        $stmt = $pdo->prepare('SELECT id, name, slots, package, discount FROM subscriptions');
        $stmt->execute();
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch last 20 transactions
        $stmt = $pdo->prepare('SELECT * FROM transactions ORDER BY exit_time DESC LIMIT 20');
        $stmt->execute();
        $transactions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $transactions[] = [
                'plate' => $row['plate'],
                'type' => $row['type'],
                'owner_name' => $row['owner_name'],
                'exitTime' => date('H:i:s', strtotime($row['exit_time'] ?? 'now')),
                'duration' => $row['duration_minutes'],
                'hours' => ceil($row['duration_minutes'] / 60),
                'baseTariff' => $row['base_tariff'],
                'discountPercent' => $row['discount_percent'],
                'discountAmount' => $row['discount_amount'],
                'total' => $row['total'],
            ];
        }

        // Compute stats from transactions
        $statsStmt = $pdo->prepare(
            'SELECT COUNT(*) as exits, COALESCE(SUM(total), 0) as revenue FROM transactions'
        );
        $statsStmt->execute();
        $statsRow = $statsStmt->fetch(PDO::FETCH_ASSOC);

        $durationStmt = $pdo->prepare(
            'SELECT COALESCE(AVG(duration_minutes), 0) as avg_dur FROM transactions'
        );
        $durationStmt->execute();
        $durRow = $durationStmt->fetch(PDO::FETCH_ASSOC);

        $totalSlots = 500;
        $occupied = count($vehicles);
        $available = max(0, $totalSlots - $occupied);

        return [
            'totalSlots' => $totalSlots,
            'occupied' => $occupied,
            'available' => $available,
            'vehicles' => $vehicles,
            'members' => $members,
            'subscriptions' => $subscriptions,
            'transactions' => $transactions,
            'stats' => [
                'entry' => 0, // Not tracked in DB yet; can add column if needed
                'exit' => intval($statsRow['exits'] ?? 0),
                'revenue' => intval($statsRow['revenue'] ?? 0),
                'durations' => [],
            ],
        ];
    } catch (Exception $e) {
        error_log('getParkingStateFromDb failed: ' . $e->getMessage());
        // fallback to session
        return null;
    }
}

function normalizePlate($plate) {
    return strtoupper(trim(preg_replace('/\s+/', '', $plate)));
}

function getMemberDiscount($type) {
    if ($type === 'premium') {
        return 25;
    }
    return 0;
}

function getCorporateDiscount($package) {
    return match ($package) {
        'premium' => 35,
        'ultimate' => 50,
        default => 20,
    };
}

function processEntry($plate, $type, $owner_name = '') {
    global $useDb, $pdo;
    
    $plate = normalizePlate($plate);
    $type = in_array($type, ['regular', 'member', 'corporate'], true) ? $type : 'regular';
    $owner_name = trim($owner_name);

    if ($plate === '') {
        return ['success' => false, 'message' => 'Plat nomor dibutuhkan.'];
    }

    $ticketCode = 'PKG' . strtoupper(substr(sha1($plate . time()), 0, 8));

    // Try DB first
    if ($useDb && $pdo) {
        try {
            $pdo->beginTransaction();
            
            // Check if plate already exists
            $checkStmt = $pdo->prepare('SELECT id FROM vehicles WHERE plate = ?');
            $checkStmt->execute([$plate]);
            if ($checkStmt->fetch()) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Plat sudah ada di dalam parkir.'];
            }

            // Insert vehicle
            $insertStmt = $pdo->prepare(
                'INSERT INTO vehicles (plate, type, owner_name, ticket, entry_time) VALUES (?, ?, ?, ?, NOW())'
            );
            $insertStmt->execute([$plate, $type, $owner_name, $ticketCode]);

            $pdo->commit();
            return [
                'success' => true,
                'message' => "Sukses: Kendaraan $plate masuk.",
                'ticket' => $ticketCode,
                'state' => getParkingState(),
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('processEntry DB failed: ' . $e->getMessage());
            // fallback to session
        }
    }

    // Fallback to session
    if (isset($_SESSION['parking']['vehicles'][$plate])) {
        return ['success' => false, 'message' => 'Plat sudah ada di dalam parkir.'];
    }

    if ($_SESSION['parking']['occupied'] >= 500) {
        return ['success' => false, 'message' => 'Parkir penuh.'];
    }

    $_SESSION['parking']['vehicles'][$plate] = [
        'type' => $type,
        'time' => time(),
        'ticket' => $ticketCode,
        'owner_name' => $owner_name,
    ];
    $_SESSION['parking']['occupied']++;
    $_SESSION['parking']['stats']['entry']++;
    $_SESSION['parking']['last_ticket'] = $ticketCode;

    return [
        'success' => true,
        'message' => "Sukses: Kendaraan $plate masuk.",
        'ticket' => $ticketCode,
        'state' => getParkingState(),
    ];
}

function processExit($plate) {
    global $useDb, $pdo;
    
    $plate = normalizePlate($plate);

    // Try DB first
    if ($useDb && $pdo) {
        try {
            $pdo->beginTransaction();

            // Lock and fetch vehicle
            $vehicleStmt = $pdo->prepare('SELECT id, type, owner_name, ticket, entry_time FROM vehicles WHERE plate = ? FOR UPDATE');
            $vehicleStmt->execute([$plate]);
            $vehicle = $vehicleStmt->fetch(PDO::FETCH_ASSOC);

            if (!$vehicle) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Plat tidak ditemukan.'];
            }

            $vehicleId = $vehicle['id'];
            $durationMinutes = max(1, ceil((time() - strtotime($vehicle['entry_time'])) / 60));
            $hours = max(1, ceil($durationMinutes / 60));
            $baseTariff = $hours * 5000;

            // Determine discount
            $discountPercent = 0;
            if ($vehicle['type'] === 'member') {
                $memberStmt = $pdo->prepare('SELECT type FROM members WHERE email = ? LIMIT 1');
                $memberStmt->execute([$vehicle['owner_name']]);
                $member = $memberStmt->fetch(PDO::FETCH_ASSOC);
                if ($member) {
                    $discountPercent = getMemberDiscount($member['type']);
                }
            } elseif ($vehicle['type'] === 'corporate') {
                $subStmt = $pdo->prepare('SELECT discount FROM subscriptions WHERE name = ? LIMIT 1');
                $subStmt->execute([$vehicle['owner_name']]);
                $sub = $subStmt->fetch(PDO::FETCH_ASSOC);
                if ($sub) {
                    $discountPercent = intval($sub['discount']);
                }
            }

            $discountAmount = round($baseTariff * ($discountPercent / 100));
            $total = round($baseTariff - $discountAmount);

            // Insert transaction
            $txStmt = $pdo->prepare(
                'INSERT INTO transactions (vehicle_id, plate, type, owner_name, entry_time, exit_time, duration_minutes, base_tariff, discount_percent, discount_amount, total)
                 VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)'
            );
            $txStmt->execute([
                $vehicleId,
                $plate,
                $vehicle['type'],
                $vehicle['owner_name'],
                $vehicle['entry_time'],
                $durationMinutes,
                $baseTariff,
                $discountPercent,
                $discountAmount,
                $total,
            ]);

            // Delete vehicle from parking
            $delStmt = $pdo->prepare('DELETE FROM vehicles WHERE id = ?');
            $delStmt->execute([$vehicleId]);

            $pdo->commit();

            $receipt = [
                'plate' => $plate,
                'type' => $vehicle['type'],
                'owner_name' => $vehicle['owner_name'],
                'ticket' => $vehicle['ticket'],
                'entryTime' => date('H:i:s', strtotime($vehicle['entry_time'])),
                'exitTime' => date('H:i:s'),
                'duration' => $durationMinutes,
                'hours' => $hours,
                'baseTariff' => $baseTariff,
                'discountPercent' => $discountPercent,
                'discountAmount' => $discountAmount,
                'total' => $total,
            ];

            return [
                'success' => true,
                'message' => 'Kendaraan keluar. Total biaya: Rp ' . number_format($total, 0, ',', '.'),
                'receipt' => $receipt,
                'state' => getParkingState(),
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('processExit DB failed: ' . $e->getMessage());
            // fallback to session
        }
    }

    // Fallback to session
    if (!isset($_SESSION['parking']['vehicles'][$plate])) {
        return ['success' => false, 'message' => 'Plat tidak ditemukan.'];
    }

    $v = $_SESSION['parking']['vehicles'][$plate];
    $durationMinutes = max(1, ceil((time() - $v['time']) / 60));
    $hours = max(1, ceil($durationMinutes / 60));
    $baseTariff = $hours * 5000;

    // Determine discount percent based on member or subscription
    $discountPercent = 0;
    if ($v['type'] === 'member') {
        $ownerEmail = $v['owner_name'] ?? '';
        foreach ($_SESSION['parking']['members'] as $m) {
            if (isset($m['email']) && strtolower($m['email']) === strtolower($ownerEmail)) {
                $discountPercent = getMemberDiscount($m['type']);
                break;
            }
        }
    } elseif ($v['type'] === 'corporate') {
        $ownerName = $v['owner_name'] ?? '';
        foreach ($_SESSION['parking']['subscriptions'] as $s) {
            if (isset($s['name']) && strtolower($s['name']) === strtolower($ownerName)) {
                $discountPercent = $s['discount'] ?? getCorporateDiscount($s['package'] ?? 'basic');
                break;
            }
        }
    }

    $discountAmount = round($baseTariff * ($discountPercent / 100));
    $total = round($baseTariff - $discountAmount);

    unset($_SESSION['parking']['vehicles'][$plate]);
    $_SESSION['parking']['occupied']--;
    $_SESSION['parking']['stats']['exit']++;
    $_SESSION['parking']['stats']['revenue'] += $total;
    $_SESSION['parking']['stats']['durations'][] = $durationMinutes;

    $transaction = [
        'plate' => $plate,
        'type' => $v['type'],
        'owner_name' => $v['owner_name'] ?? '',
        'exitTime' => date('H:i:s'),
        'duration' => $durationMinutes,
        'hours' => $hours,
        'baseTariff' => $baseTariff,
        'discountPercent' => $discountPercent,
        'discountAmount' => $discountAmount,
        'total' => $total,
    ];
    array_unshift($_SESSION['parking']['transactions'], $transaction);
    $_SESSION['parking']['last_receipt'] = $transaction;

    return [
        'success' => true,
        'message' => 'Kendaraan keluar. Total biaya: Rp ' . number_format($total, 0, ',', '.'),
        'receipt' => array_merge($transaction, [
            'entryTime' => date('H:i:s', $v['time']),
            'ticket' => $v['ticket'] ?? null,
            'owner_name' => $v['owner_name'] ?? '',
        ]),
        'state' => getParkingState(),
    ];
}

function addMember($name, $email, $phone, $type) {
    $name = trim($name);
    $email = trim($email);
    $phone = trim($phone);
    $type = in_array($type, ['regular', 'premium'], true) ? $type : 'regular';

    if ($name === '' || $email === '' || $phone === '') {
        return ['success' => false, 'message' => 'Semua field member harus diisi.'];
    }

    foreach ($_SESSION['parking']['members'] as $member) {
        if (strtolower($member['email']) === strtolower($email)) {
            return ['success' => false, 'message' => 'Email sudah terdaftar.'];
        }
    }

    $_SESSION['parking']['members'][] = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'type' => $type,
    ];

    return ['success' => true, 'message' => 'Member berhasil ditambahkan.', 'state' => getParkingState()];
}

function deleteMember($email) {
    $email = trim($email);
    $members = $_SESSION['parking']['members'];
    $updated = [];

    foreach ($members as $member) {
        if (strtolower($member['email']) !== strtolower($email)) {
            $updated[] = $member;
        }
    }

    if (count($updated) === count($members)) {
        return ['success' => false, 'message' => 'Member tidak ditemukan.'];
    }

    $_SESSION['parking']['members'] = $updated;
    return ['success' => true, 'message' => 'Member dihapus.', 'state' => getParkingState()];
}

function addSubscription($name, $slots, $package) {
    $name = trim($name);
    $slots = intval($slots);
    $package = in_array($package, ['basic', 'premium', 'ultimate'], true) ? $package : 'basic';

    if ($name === '' || $slots <= 0) {
        return ['success' => false, 'message' => 'Nama perusahaan dan jumlah slot harus diisi.'];
    }

    $_SESSION['parking']['subscriptions'][] = [
        'name' => $name,
        'slots' => $slots,
        'package' => $package,
        'discount' => getCorporateDiscount($package),
    ];

    return ['success' => true, 'message' => 'Langganan korporat berhasil ditambahkan.', 'state' => getParkingState()];
}

function deleteSubscription($name) {
    $name = trim($name);
    $subscriptions = $_SESSION['parking']['subscriptions'];
    $updated = [];

    foreach ($subscriptions as $subscription) {
        if (strtolower($subscription['name']) !== strtolower($name)) {
            $updated[] = $subscription;
        }
    }

    if (count($updated) === count($subscriptions)) {
        return ['success' => false, 'message' => 'Langganan tidak ditemukan.'];
    }

    $_SESSION['parking']['subscriptions'] = $updated;
    return ['success' => true, 'message' => 'Langganan korporat dihapus.', 'state' => getParkingState()];
}

function resetStats() {
    $_SESSION['parking']['stats'] = [
        'entry' => 0,
        'exit' => 0,
        'revenue' => 0,
        'durations' => [],
    ];
    $_SESSION['parking']['transactions'] = [];
    return ['success' => true, 'message' => 'Statistik dan riwayat transaksi telah direset.', 'state' => getParkingState()];
}

function clearTransactions() {
    $_SESSION['parking']['transactions'] = [];
    return ['success' => true, 'message' => 'Riwayat transaksi dibersihkan.', 'state' => getParkingState()];
}
?>
<?php
/**
 * Parking Management — Logic Layer
 * Terhubung penuh ke database mall_erp:
 *   04_parking_zona      → slot & kapasitas per zona
 *   04_parking_member    → data member & korporat
 *   04_parking_transaksi → entry / exit kendaraan
 *   04_parking_tarif     → tarif per tipe kendaraan & user
 */

// ── Inisialisasi sesi ──────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Koneksi database ──────────────────────────────────────────────────────────
$pdo    = null;
$useDb  = false;

$dbConfigPath = __DIR__ . '/config/db.php';
if (file_exists($dbConfigPath)) {
    try {
        require_once $dbConfigPath;
        if (isset($pdo) && $pdo instanceof PDO) {
            $useDb = true;
        }
    } catch (Throwable $e) {
        error_log('[Parking] Gagal load config DB: ' . $e->getMessage());
    }
}

// ══════════════════════════════════════════════════════════════════════════════
//  HELPER — normalisasi & tarif
// ══════════════════════════════════════════════════════════════════════════════

function normalizePlate(string $plate): string
{
    return strtoupper(trim(preg_replace('/\s+/', ' ', $plate)));
}

/**
 * Ambil tarif dari tabel 04_parking_tarif.
 * Fallback ke nilai hard-coded jika DB tidak tersedia.
 *
 * @param  PDO    $pdo
 * @param  string $tipeKendaraan  'motor'|'mobil'|'truk'
 * @param  string $tipeUser       'umum'|'member'|'korporat'
 * @return array  ['tarif_jam_pertama', 'tarif_per_jam', 'tarif_harian_max']
 */
function getTarif(PDO $pdo, string $tipeKendaraan, string $tipeUser): array
{
    $today = date('Y-m-d');
    $stmt  = $pdo->prepare(
        "SELECT tarif_jam_pertama, tarif_per_jam, tarif_harian_max
           FROM 04_parking_tarif
          WHERE tipe_kendaraan = ?
            AND tipe_user      = ?
            AND berlaku_dari  <= ?
            AND (berlaku_sampai IS NULL OR berlaku_sampai >= ?)
          ORDER BY berlaku_dari DESC
          LIMIT 1"
    );
    $stmt->execute([$tipeKendaraan, $tipeUser, $today, $today]);
    $row = $stmt->fetch();
    if ($row) {
        return $row;
    }

    // Fallback default (mobil/umum)
    return [
        'tarif_jam_pertama' => 5000,
        'tarif_per_jam'     => 3000,
        'tarif_harian_max'  => 50000,
    ];
}

/**
 * Hitung biaya parkir berdasarkan tarif & durasi.
 *
 * @param  array $tarif
 * @param  int   $durasiMenit
 * @return array ['baseTariff', 'hours', 'durationMinutes']
 */
function hitungBiaya(array $tarif, int $durasiMenit): array
{
    $hours = max(1, (int) ceil($durasiMenit / 60));
    // Jam pertama: tarif_jam_pertama, selebihnya tarif_per_jam
    $biaya = $tarif['tarif_jam_pertama'] + max(0, ($hours - 1)) * $tarif['tarif_per_jam'];
    if ($tarif['tarif_harian_max']) {
        $biaya = min($biaya, (float) $tarif['tarif_harian_max']);
    }
    return [
        'baseTariff'      => (int) $biaya,
        'hours'           => $hours,
        'durationMinutes' => $durasiMenit,
    ];
}

// ══════════════════════════════════════════════════════════════════════════════
//  STATE — baca status parkir dari DB
// ══════════════════════════════════════════════════════════════════════════════

function getParkingState(): array
{
    global $useDb, $pdo;

    if ($useDb && $pdo) {
        $state = getParkingStateFromDb($pdo);
        if ($state !== null) {
            return $state;
        }
    }

    // Fallback session (tanpa DB)
    return getParkingStateFromSession();
}

function getParkingStateFromDb(PDO $pdo): ?array
{
    try {
        // ── Zona & slot ──────────────────────────────────────────────────────
        $zonaStmt = $pdo->query(
            'SELECT id_zona, nama_zona, total_slot, occupied_slot
               FROM 04_parking_zona
              ORDER BY id_zona'
        );
        $zonas       = $zonaStmt->fetchAll();
        $totalSlots  = array_sum(array_column($zonas, 'total_slot'));
        $occupied    = array_sum(array_column($zonas, 'occupied_slot'));
        $available   = max(0, $totalSlots - $occupied);

        // ── Kendaraan aktif (belum exit) ─────────────────────────────────────
        $vStmt = $pdo->query(
            "SELECT t.id_transaksi,
                    t.plat_nomor,
                    t.tipe_kendaraan,
                    t.entry_time,
                    t.parking_slot,
                    t.zona_id,
                    t.id_member,
                    COALESCE(m.membership_type, 'umum') AS membership_type,
                    COALESCE(ten.tenant_name, '') AS tenant_name
               FROM 04_parking_transaksi t
          LEFT JOIN 04_parking_member  m   ON m.id_member  = t.id_member
          LEFT JOIN 02_tenants         ten ON ten.id_tenant = m.tenant_id
              WHERE t.exit_time IS NULL
              ORDER BY t.entry_time DESC"
        );
        $vehicles = [];
        while ($row = $vStmt->fetch()) {
            $type = 'regular';
            if ($row['membership_type'] === 'Korporat') {
                $type = 'corporate';
            } elseif (in_array($row['membership_type'], ['Reguler', 'VIP'], true)) {
                $type = 'member';
            }
            $vehicles[$row['plat_nomor']] = [
                'id'           => $row['id_transaksi'],
                'type'         => $type,
                'tipeKendaraan'=> $row['tipe_kendaraan'] ?? 'mobil',
                'owner_name'   => $row['tenant_name'] ?: ($row['membership_type'] ?? ''),
                'ticket'       => 'PKG-' . strtoupper(substr(sha1($row['plat_nomor'] . $row['entry_time']), 0, 8)),
                'entry_time'   => $row['entry_time'],
                'time'         => strtotime($row['entry_time']),
                'zona_id'      => $row['zona_id'],
                'parking_slot' => $row['parking_slot'],
                'id_member'    => $row['id_member'],
            ];
        }

        // ── Member ───────────────────────────────────────────────────────────
        $mStmt = $pdo->query(
            "SELECT m.id_member,
                    COALESCE(t.tenant_name, '') AS name,
                    m.plat_nomor                AS plate,
                    m.tipe_kendaraan            AS tipeKendaraan,
                    m.membership_type           AS type,
                    CASE m.membership_type
                        WHEN 'VIP'      THEN 25
                        WHEN 'Korporat' THEN 30
                        ELSE 0
                    END AS discountPercent
               FROM 04_parking_member  m
          LEFT JOIN 02_tenants         t ON t.id_tenant = m.tenant_id
              ORDER BY m.id_member"
        );
        $members = $mStmt->fetchAll();

        // ── Zona (untuk dropdown) ─────────────────────────────────────────────
        $subscriptions = $zonas; // zona dipakai sebagai "subscriptions" di frontend

        // ── 20 transaksi terakhir (sudah exit) ───────────────────────────────
        $txStmt = $pdo->query(
            "SELECT t.plat_nomor,
                    t.tipe_kendaraan,
                    COALESCE(ten.tenant_name, '') AS owner_name,
                    t.entry_time,
                    t.exit_time,
                    t.amount,
                    COALESCE(m.membership_type, 'umum') AS membership_type
               FROM 04_parking_transaksi t
          LEFT JOIN 04_parking_member    m   ON m.id_member  = t.id_member
          LEFT JOIN 02_tenants           ten ON ten.id_tenant = m.tenant_id
              WHERE t.exit_time IS NOT NULL
              ORDER BY t.exit_time DESC
              LIMIT 20"
        );
        $transactions = [];
        while ($row = $txStmt->fetch()) {
            $entryTs       = strtotime($row['entry_time']);
            $exitTs        = strtotime($row['exit_time']);
            $durasiMenit   = max(1, (int) ceil(($exitTs - $entryTs) / 60));
            $hours         = max(1, (int) ceil($durasiMenit / 60));
            $transactions[] = [
                'plate'           => $row['plat_nomor'],
                'type'            => $row['membership_type'] === 'Korporat' ? 'corporate'
                                   : ($row['membership_type'] !== 'umum'   ? 'member' : 'regular'),
                'owner_name'      => $row['owner_name'],
                'exitTime'        => date('H:i:s', $exitTs),
                'entryTime'       => date('H:i:s', $entryTs),
                'duration'        => $durasiMenit,
                'hours'           => $hours,
                'baseTariff'      => (int) $row['amount'],
                'discountPercent' => 0,
                'discountAmount'  => 0,
                'total'           => (int) $row['amount'],
            ];
        }

        // ── Statistik ─────────────────────────────────────────────────────────
        $statStmt = $pdo->query(
            "SELECT COUNT(*)             AS exits,
                    COALESCE(SUM(amount), 0) AS revenue
               FROM 04_parking_transaksi
              WHERE exit_time IS NOT NULL"
        );
        $stat     = $statStmt->fetch();
        $entryRow = $pdo->query(
            "SELECT COUNT(*) AS entries FROM 04_parking_transaksi"
        )->fetch();

        return [
            'totalSlots'    => $totalSlots,
            'occupied'      => $occupied,
            'available'     => $available,
            'vehicles'      => $vehicles,
            'members'       => $members,
            'subscriptions' => $subscriptions,   // zona list
            'zonas'         => $zonas,
            'transactions'  => $transactions,
            'stats'         => [
                'entry'   => (int) ($entryRow['entries'] ?? 0),
                'exit'    => (int) ($stat['exits']   ?? 0),
                'revenue' => (int) ($stat['revenue'] ?? 0),
                'durations' => [],
            ],
        ];
    } catch (Throwable $e) {
        error_log('[getParkingStateFromDb] ' . $e->getMessage());
        return null;
    }
}

function getParkingStateFromSession(): array
{
    $parking  = &$_SESSION['parking'];
    $vehicles = $parking['vehicles'] ?? [];

    $members = array_map(function ($m) {
        $m['discountPercent'] = match ($m['type'] ?? 'Reguler') {
            'VIP'      => 25,
            'Korporat' => 30,
            default    => 0,
        };
        return $m;
    }, $parking['members'] ?? []);

    return [
        'totalSlots'    => 715,   // total dari data zona seeder
        'occupied'      => $parking['occupied'] ?? count($vehicles),
        'available'     => max(0, 715 - ($parking['occupied'] ?? count($vehicles))),
        'vehicles'      => $vehicles,
        'members'       => $members,
        'subscriptions' => $parking['subscriptions'] ?? [],
        'zonas'         => [],
        'transactions'  => array_slice(array_reverse($parking['transactions'] ?? []), 0, 20),
        'stats'         => $parking['stats'] ?? ['entry' => 0, 'exit' => 0, 'revenue' => 0, 'durations' => []],
    ];
}

// ══════════════════════════════════════════════════════════════════════════════
//  ENTRY — kendaraan masuk
// ══════════════════════════════════════════════════════════════════════════════

/**
 * @param string $plate         Plat nomor
 * @param string $tipeUser      'umum'|'member'|'korporat'
 * @param string $tipeKendaraan 'motor'|'mobil'|'truk'
 * @param int    $zonaId        ID zona (04_parking_zona.id_zona)
 * @param string $parkingSlot   Nomor slot fisik (opsional)
 * @param int|null $idMember    ID member jika tipeUser != 'umum'
 */
function processEntry(
    string $plate,
    string $tipeUser      = 'umum',
    string $tipeKendaraan = 'mobil',
    int    $zonaId        = 1,
    string $parkingSlot   = '',
    ?int   $idMember      = null
): array {
    global $useDb, $pdo;

    $plate         = normalizePlate($plate);
    $tipeUser      = in_array($tipeUser, ['umum', 'member', 'korporat'], true) ? $tipeUser : 'umum';
    $tipeKendaraan = in_array($tipeKendaraan, ['motor', 'mobil', 'truk'], true) ? $tipeKendaraan : 'mobil';

    if ($plate === '') {
        return ['success' => false, 'message' => 'Plat nomor dibutuhkan.'];
    }

    // ── DB path ───────────────────────────────────────────────────────────────
    if ($useDb && $pdo) {
        try {
            $pdo->beginTransaction();

            // Cek apakah plat sudah ada di parkir (belum exit)
            $cek = $pdo->prepare(
                'SELECT id_transaksi FROM 04_parking_transaksi WHERE plat_nomor = ? AND exit_time IS NULL LIMIT 1'
            );
            $cek->execute([$plate]);
            if ($cek->fetch()) {
                $pdo->rollBack();
                return ['success' => false, 'message' => "Plat $plate sudah berada di dalam parkir."];
            }

            // Cek kapasitas zona
            $zonaStmt = $pdo->prepare(
                'SELECT total_slot, occupied_slot FROM 04_parking_zona WHERE id_zona = ? FOR UPDATE'
            );
            $zonaStmt->execute([$zonaId]);
            $zona = $zonaStmt->fetch();
            if (!$zona) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Zona tidak ditemukan.'];
            }
            if ($zona['occupied_slot'] >= $zona['total_slot']) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Zona parkir penuh.'];
            }

            // Jika member/korporat dan idMember tidak diisi, cari otomatis
            if ($tipeUser !== 'umum' && $idMember === null) {
                $mCari = $pdo->prepare(
                    'SELECT id_member FROM 04_parking_member WHERE plat_nomor = ? LIMIT 1'
                );
                $mCari->execute([$plate]);
                $found = $mCari->fetchColumn();
                if ($found) {
                    $idMember = (int) $found;
                }
            }

            // Insert transaksi entry
            $ins = $pdo->prepare(
                'INSERT INTO 04_parking_transaksi
                    (plat_nomor, id_member, zona_id, tipe_kendaraan, entry_time, amount, parking_slot)
                 VALUES (?, ?, ?, ?, NOW(), 0, ?)'
            );
            $ins->execute([$plate, $idMember, $zonaId, $tipeKendaraan, $parkingSlot ?: null]);
            $newId = $pdo->lastInsertId();

            // Update occupied_slot zona
            $pdo->prepare('UPDATE 04_parking_zona SET occupied_slot = occupied_slot + 1 WHERE id_zona = ?')
                ->execute([$zonaId]);

            $pdo->commit();

            $ticketCode = 'PKG-' . strtoupper(substr(sha1($plate . $newId), 0, 8));

            return [
                'success' => true,
                'message' => "Sukses: Kendaraan $plate masuk. Tiket: $ticketCode",
                'ticket'  => $ticketCode,
                'state'   => getParkingState(),
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[processEntry] ' . $e->getMessage());
            // fall through ke session
        }
    }

    // ── Session fallback ──────────────────────────────────────────────────────
    if (isset($_SESSION['parking']['vehicles'][$plate])) {
        return ['success' => false, 'message' => "Plat $plate sudah ada di parkir."];
    }
    $ticketCode = 'PKG-' . strtoupper(substr(sha1($plate . time()), 0, 8));
    $_SESSION['parking']['vehicles'][$plate] = [
        'type'          => $tipeUser,
        'tipeKendaraan' => $tipeKendaraan,
        'time'          => time(),
        'ticket'        => $ticketCode,
        'owner_name'    => '',
    ];
    $_SESSION['parking']['occupied'] = ($_SESSION['parking']['occupied'] ?? 0) + 1;
    $_SESSION['parking']['stats']['entry'] = ($_SESSION['parking']['stats']['entry'] ?? 0) + 1;

    return [
        'success' => true,
        'message' => "Sukses: Kendaraan $plate masuk (mode sesi). Tiket: $ticketCode",
        'ticket'  => $ticketCode,
        'state'   => getParkingState(),
    ];
}

// ══════════════════════════════════════════════════════════════════════════════
//  EXIT — kendaraan keluar & hitung biaya
// ══════════════════════════════════════════════════════════════════════════════

function processExit(string $plate, string $paymentMethod = 'cash'): array
{
    global $useDb, $pdo;

    $plate = normalizePlate($plate);

    // ── DB path ───────────────────────────────────────────────────────────────
    if ($useDb && $pdo) {
        try {
            $pdo->beginTransaction();

            // Ambil transaksi aktif
            $txStmt = $pdo->prepare(
                "SELECT t.id_transaksi,
                        t.plat_nomor,
                        t.tipe_kendaraan,
                        t.entry_time,
                        t.zona_id,
                        t.parking_slot,
                        t.id_member,
                        COALESCE(m.membership_type, 'umum') AS membership_type,
                        COALESCE(ten.tenant_name, '')       AS tenant_name
                   FROM 04_parking_transaksi t
              LEFT JOIN 04_parking_member    m   ON m.id_member  = t.id_member
              LEFT JOIN 02_tenants           ten ON ten.id_tenant = m.tenant_id
                  WHERE t.plat_nomor = ? AND t.exit_time IS NULL
                  LIMIT 1 FOR UPDATE"
            );
            $txStmt->execute([$plate]);
            $tx = $txStmt->fetch();

            if (!$tx) {
                $pdo->rollBack();
                return ['success' => false, 'message' => "Plat $plate tidak ditemukan di parkir."];
            }

            // Durasi & tarif
            $entryTs     = strtotime($tx['entry_time']);
            $durasiMenit = max(1, (int) ceil((time() - $entryTs) / 60));

            $memberType = strtolower($tx['membership_type']);
            $tipeUser   = match (true) {
                $memberType === 'korporat'                  => 'korporat',
                in_array($memberType, ['reguler', 'vip'])   => 'member',
                default                                     => 'umum',
            };

            $tarif  = getTarif($pdo, $tx['tipe_kendaraan'] ?? 'mobil', $tipeUser);
            $kalkulasi = hitungBiaya($tarif, $durasiMenit);
            $amount = $kalkulasi['baseTariff'];

            // Update transaksi → exit
            $pdo->prepare(
                'UPDATE 04_parking_transaksi
                    SET exit_time = NOW(), amount = ?, payment_method = ?
                  WHERE id_transaksi = ?'
            )->execute([$amount, $paymentMethod, $tx['id_transaksi']]);

            // Kurangi occupied_slot zona
            if ($tx['zona_id']) {
                $pdo->prepare('UPDATE 04_parking_zona SET occupied_slot = GREATEST(0, occupied_slot - 1) WHERE id_zona = ?')
                    ->execute([$tx['zona_id']]);
            }

            $pdo->commit();

            $receipt = [
                'plate'           => $plate,
                'type'            => $tipeUser,
                'owner_name'      => $tx['tenant_name'],
                'ticket'          => 'PKG-' . strtoupper(substr(sha1($plate . $tx['entry_time']), 0, 8)),
                'entryTime'       => date('H:i:s', $entryTs),
                'exitTime'        => date('H:i:s'),
                'duration'        => $durasiMenit,
                'hours'           => $kalkulasi['hours'],
                'baseTariff'      => $amount,
                'discountPercent' => 0,
                'discountAmount'  => 0,
                'total'           => $amount,
            ];

            return [
                'success' => true,
                'message' => 'Kendaraan keluar. Total biaya: Rp ' . number_format($amount, 0, ',', '.'),
                'receipt' => $receipt,
                'state'   => getParkingState(),
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[processExit] ' . $e->getMessage());
            // fall through ke session
        }
    }

    // ── Session fallback ──────────────────────────────────────────────────────
    if (!isset($_SESSION['parking']['vehicles'][$plate])) {
        return ['success' => false, 'message' => "Plat $plate tidak ditemukan."];
    }

    $v           = $_SESSION['parking']['vehicles'][$plate];
    $durasiMenit = max(1, (int) ceil((time() - $v['time']) / 60));
    $hours       = max(1, (int) ceil($durasiMenit / 60));
    $amount      = $hours * 5000;

    unset($_SESSION['parking']['vehicles'][$plate]);
    $_SESSION['parking']['occupied']          = max(0, ($_SESSION['parking']['occupied'] ?? 1) - 1);
    $_SESSION['parking']['stats']['exit']     = ($_SESSION['parking']['stats']['exit'] ?? 0) + 1;
    $_SESSION['parking']['stats']['revenue']  = ($_SESSION['parking']['stats']['revenue'] ?? 0) + $amount;

    $receipt = [
        'plate'           => $plate,
        'type'            => $v['type'] ?? 'umum',
        'owner_name'      => $v['owner_name'] ?? '',
        'ticket'          => $v['ticket'] ?? '-',
        'entryTime'       => date('H:i:s', $v['time']),
        'exitTime'        => date('H:i:s'),
        'duration'        => $durasiMenit,
        'hours'           => $hours,
        'baseTariff'      => $amount,
        'discountPercent' => 0,
        'discountAmount'  => 0,
        'total'           => $amount,
    ];

    array_unshift($_SESSION['parking']['transactions'] ?? [], $receipt);

    return [
        'success' => true,
        'message' => 'Kendaraan keluar. Total biaya: Rp ' . number_format($amount, 0, ',', '.'),
        'receipt' => $receipt,
        'state'   => getParkingState(),
    ];
}

// ══════════════════════════════════════════════════════════════════════════════
//  MEMBER — CRUD 04_parking_member
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Tambah member baru ke 04_parking_member.
 *
 * @param string   $platNomor
 * @param string   $tipeKendaraan  'motor'|'mobil'|'truk'
 * @param string   $membershipType 'Reguler'|'VIP'|'Korporat'
 * @param int|null $tenantId       FK ke 02_tenants, null jika bukan korporat
 */
function addMember(
    string $platNomor,
    string $tipeKendaraan  = 'mobil',
    string $membershipType = 'Reguler',
    ?int   $tenantId       = null
): array {
    global $useDb, $pdo;

    $platNomor     = normalizePlate($platNomor);
    $tipeKendaraan = in_array($tipeKendaraan, ['motor', 'mobil', 'truk'], true) ? $tipeKendaraan : 'mobil';
    $membershipType = in_array($membershipType, ['Reguler', 'VIP', 'Korporat'], true) ? $membershipType : 'Reguler';

    if ($platNomor === '') {
        return ['success' => false, 'message' => 'Plat nomor dibutuhkan.'];
    }

    if ($useDb && $pdo) {
        try {
            // Cek duplikat plat
            $cek = $pdo->prepare('SELECT id_member FROM 04_parking_member WHERE plat_nomor = ? LIMIT 1');
            $cek->execute([$platNomor]);
            if ($cek->fetch()) {
                return ['success' => false, 'message' => "Plat $platNomor sudah terdaftar sebagai member."];
            }

            $pdo->prepare(
                'INSERT INTO 04_parking_member (tenant_id, plat_nomor, tipe_kendaraan, membership_type)
                 VALUES (?, ?, ?, ?)'
            )->execute([$tenantId, $platNomor, $tipeKendaraan, $membershipType]);

            return ['success' => true, 'message' => "Member $platNomor berhasil ditambahkan.", 'state' => getParkingState()];
        } catch (Throwable $e) {
            error_log('[addMember] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menambah member: ' . $e->getMessage()];
        }
    }

    // Session fallback
    foreach ($_SESSION['parking']['members'] ?? [] as $m) {
        if (strtoupper($m['plate'] ?? '') === $platNomor) {
            return ['success' => false, 'message' => "Plat $platNomor sudah terdaftar."];
        }
    }
    $_SESSION['parking']['members'][] = [
        'plate'          => $platNomor,
        'tipeKendaraan'  => $tipeKendaraan,
        'type'           => $membershipType,
        'discountPercent'=> $membershipType === 'VIP' ? 25 : ($membershipType === 'Korporat' ? 30 : 0),
    ];
    return ['success' => true, 'message' => "Member $platNomor ditambahkan (sesi).", 'state' => getParkingState()];
}

/**
 * Hapus member berdasarkan plat nomor.
 */
function deleteMember(string $platNomor): array
{
    global $useDb, $pdo;

    $platNomor = normalizePlate($platNomor);

    if ($useDb && $pdo) {
        try {
            $stmt = $pdo->prepare('DELETE FROM 04_parking_member WHERE plat_nomor = ?');
            $stmt->execute([$platNomor]);
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => "Member $platNomor tidak ditemukan."];
            }
            return ['success' => true, 'message' => "Member $platNomor dihapus.", 'state' => getParkingState()];
        } catch (Throwable $e) {
            error_log('[deleteMember] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal hapus member: ' . $e->getMessage()];
        }
    }

    // Session fallback
    $before = count($_SESSION['parking']['members'] ?? []);
    $_SESSION['parking']['members'] = array_values(
        array_filter($_SESSION['parking']['members'] ?? [], fn($m) => strtoupper($m['plate'] ?? '') !== $platNomor)
    );
    if (count($_SESSION['parking']['members']) === $before) {
        return ['success' => false, 'message' => "Member $platNomor tidak ditemukan."];
    }
    return ['success' => true, 'message' => "Member $platNomor dihapus.", 'state' => getParkingState()];
}

// ══════════════════════════════════════════════════════════════════════════════
//  ZONA — update kapasitas 04_parking_zona
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Tambah zona parkir baru.
 */
function addZona(string $namaZona, int $totalSlot, ?int $floorId = null): array
{
    global $useDb, $pdo;

    if (trim($namaZona) === '' || $totalSlot <= 0) {
        return ['success' => false, 'message' => 'Nama zona dan total slot harus diisi.'];
    }

    if ($useDb && $pdo) {
        try {
            $pdo->prepare(
                'INSERT INTO 04_parking_zona (nama_zona, total_slot, occupied_slot, floor_id)
                 VALUES (?, ?, 0, ?)'
            )->execute([trim($namaZona), $totalSlot, $floorId]);
            return ['success' => true, 'message' => "Zona '$namaZona' berhasil ditambahkan.", 'state' => getParkingState()];
        } catch (Throwable $e) {
            error_log('[addZona] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal tambah zona: ' . $e->getMessage()];
        }
    }

    return ['success' => false, 'message' => 'Database tidak tersedia.'];
}

/**
 * Hapus zona parkir (hanya jika tidak ada kendaraan aktif).
 */
function deleteZona(int $zonaId): array
{
    global $useDb, $pdo;

    if ($useDb && $pdo) {
        try {
            // Cek kendaraan aktif
            $cek = $pdo->prepare(
                'SELECT COUNT(*) FROM 04_parking_transaksi WHERE zona_id = ? AND exit_time IS NULL'
            );
            $cek->execute([$zonaId]);
            if ((int) $cek->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Zona masih memiliki kendaraan di dalamnya.'];
            }

            $stmt = $pdo->prepare('DELETE FROM 04_parking_zona WHERE id_zona = ?');
            $stmt->execute([$zonaId]);
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Zona tidak ditemukan.'];
            }
            return ['success' => true, 'message' => 'Zona berhasil dihapus.', 'state' => getParkingState()];
        } catch (Throwable $e) {
            error_log('[deleteZona] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal hapus zona: ' . $e->getMessage()];
        }
    }

    return ['success' => false, 'message' => 'Database tidak tersedia.'];
}

// ══════════════════════════════════════════════════════════════════════════════
//  STATISTIK — reset / bersihkan
// ══════════════════════════════════════════════════════════════════════════════

/**
 * Reset statistik: hapus transaksi SELESAI (yang sudah exit) hari ini.
 * Data kendaraan aktif tidak tersentuh.
 */
function resetStats(): array
{
    global $useDb, $pdo;

    if ($useDb && $pdo) {
        try {
            $pdo->exec(
                "DELETE FROM 04_parking_transaksi
                  WHERE exit_time IS NOT NULL
                    AND DATE(exit_time) = CURDATE()"
            );
            return ['success' => true, 'message' => 'Statistik & transaksi hari ini berhasil direset.', 'state' => getParkingState()];
        } catch (Throwable $e) {
            error_log('[resetStats] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal reset: ' . $e->getMessage()];
        }
    }

    // Session fallback
    $_SESSION['parking']['stats']        = ['entry' => 0, 'exit' => 0, 'revenue' => 0, 'durations' => []];
    $_SESSION['parking']['transactions'] = [];
    return ['success' => true, 'message' => 'Statistik sesi direset.', 'state' => getParkingState()];
}

/**
 * Bersihkan riwayat transaksi dari tampilan (hanya LIMIT, tidak hapus DB).
 */
function clearTransactions(): array
{
    global $useDb, $pdo;

    if ($useDb && $pdo) {
        // Di DB kita tidak hapus data; cukup kembalikan state (view akan menampilkan 20 terakhir)
        return ['success' => true, 'message' => 'Riwayat tampilan dibersihkan.', 'state' => getParkingState()];
    }

    $_SESSION['parking']['transactions'] = [];
    return ['success' => true, 'message' => 'Riwayat transaksi dibersihkan.', 'state' => getParkingState()];
}

// ── Alias agar index.php lama (subscription_add / subscription_delete) tetap jalan ──
function addSubscription(string $namaZona, int $totalSlot, string $package = 'basic'): array
{
    return addZona($namaZona, $totalSlot);
}
function deleteSubscription(string $namaZona): array
{
    global $useDb, $pdo;
    if ($useDb && $pdo) {
        $row = $pdo->prepare('SELECT id_zona FROM 04_parking_zona WHERE nama_zona = ? LIMIT 1');
        $row->execute([$namaZona]);
        $zona = $row->fetch();
        if (!$zona) {
            return ['success' => false, 'message' => 'Zona tidak ditemukan.'];
        }
        return deleteZona((int) $zona['id_zona']);
    }
    return ['success' => false, 'message' => 'Database tidak tersedia.'];
}

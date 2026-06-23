<?php
/**
 * Parking Management — Migration / Verifikasi Koneksi
 *
 * Jalankan via browser: http://localhost/parking/migrate.php
 * atau CLI: php migrate.php
 *
 * Script ini TIDAK mengubah data; hanya memverifikasi koneksi &
 * menampilkan ringkasan isi tabel parkir di database mall_erp.
 */

$dbPath = __DIR__ . '/config/db.php';
if (!file_exists($dbPath)) {
    die("ERROR: config/db.php tidak ditemukan.\nSalin db.php ke config/db.php lalu sesuaikan kredensial.\n");
}

require_once $dbPath;

if (!isset($pdo) || !$pdo instanceof PDO) {
    die("ERROR: Koneksi database gagal. Periksa host/user/pass di config/db.php\n");
}

$isCli = PHP_SAPI === 'cli';
$nl    = $isCli ? "\n" : "<br>\n";
$bold  = fn($s) => $isCli ? "\033[1m$s\033[0m" : "<strong>$s</strong>";

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">
          <title>Parking — Verifikasi DB</title>
          <style>body{font-family:monospace;padding:20px;background:#0f172a;color:#e2e8f0}
          strong{color:#22c55e} .err{color:#ef4444} .warn{color:#f59e0b}
          pre{background:#1e293b;padding:12px;border-radius:8px}</style>
          </head><body><pre>';
}

echo $bold('=== Mall ERP — Parking: Verifikasi Koneksi Database ===') . $nl . $nl;

// ── Cek database aktif ────────────────────────────────────────────────────────
$dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
echo "✔ Terhubung ke database: {$bold($dbName)}" . $nl . $nl;

// ── Tabel yang wajib ada ──────────────────────────────────────────────────────
$wajib = [
    '04_parking_zona'      => 'Zona & kapasitas slot',
    '04_parking_member'    => 'Data member & korporat',
    '04_parking_transaksi' => 'Entry / exit kendaraan',
    '04_parking_tarif'     => 'Tarif per tipe & user',
    '02_tenants'           => 'Data tenant (FK member korporat)',
];

echo $bold('[1] Cek Tabel Wajib') . $nl;
$allOk = true;
foreach ($wajib as $tbl => $desc) {
    $exists = $pdo->query("SHOW TABLES LIKE '$tbl'")->fetchColumn();
    if ($exists) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn();
        echo "    ✔ $tbl ($desc) — {$count} baris" . $nl;
    } else {
        echo ($isCli ? '' : '<span class="err">') .
             "    ✘ $tbl TIDAK DITEMUKAN! Import mall_erp__22-07_.sql terlebih dahulu." .
             ($isCli ? '' : '</span>') . $nl;
        $allOk = false;
    }
}
echo $nl;

if (!$allOk) {
    echo ($isCli ? '' : '<span class="err">') .
         "GAGAL: Beberapa tabel tidak ada. Import SQL dump ke database '$dbName' dulu." .
         ($isCli ? '' : '</span>') . $nl;
    if (!$isCli) { echo '</pre></body></html>'; }
    exit(1);
}

// ── Ringkasan data ────────────────────────────────────────────────────────────
echo $bold('[2] Ringkasan Data Parkir') . $nl;

// Zona
$zonas = $pdo->query(
    'SELECT nama_zona, total_slot, occupied_slot FROM 04_parking_zona ORDER BY id_zona'
)->fetchAll(PDO::FETCH_ASSOC);
echo $nl . "  Zona Parkir ({$bold(count($zonas))} zona):" . $nl;
foreach ($zonas as $z) {
    $util = $z['total_slot'] > 0 ? round($z['occupied_slot'] / $z['total_slot'] * 100) : 0;
    echo "    • {$z['nama_zona']} — {$z['occupied_slot']}/{$z['total_slot']} slot ($util%)" . $nl;
}

// Member
$totalMember = $pdo->query('SELECT COUNT(*) FROM 04_parking_member')->fetchColumn();
$byType = $pdo->query(
    "SELECT membership_type, COUNT(*) as jml FROM 04_parking_member GROUP BY membership_type"
)->fetchAll(PDO::FETCH_ASSOC);
echo $nl . "  Member ({$bold($totalMember)} total):" . $nl;
foreach ($byType as $r) {
    echo "    • {$r['membership_type']}: {$r['jml']}" . $nl;
}

// Transaksi
$aktif = $pdo->query(
    'SELECT COUNT(*) FROM 04_parking_transaksi WHERE exit_time IS NULL'
)->fetchColumn();
$selesai = $pdo->query(
    'SELECT COUNT(*) FROM 04_parking_transaksi WHERE exit_time IS NOT NULL'
)->fetchColumn();
$revenue = $pdo->query(
    'SELECT COALESCE(SUM(amount), 0) FROM 04_parking_transaksi WHERE exit_time IS NOT NULL'
)->fetchColumn();
echo $nl . "  Transaksi:" . $nl;
echo "    • Kendaraan aktif  : {$bold($aktif)}" . $nl;
echo "    • Transaksi selesai: {$bold($selesai)}" . $nl;
echo "    • Total pendapatan : Rp " . $bold(number_format($revenue, 0, ',', '.')) . $nl;

// Tarif
$tarif = $pdo->query(
    'SELECT tipe_kendaraan, tipe_user, tarif_jam_pertama, tarif_per_jam
       FROM 04_parking_tarif
      WHERE (berlaku_sampai IS NULL OR berlaku_sampai >= CURDATE())
        AND berlaku_dari <= CURDATE()
      ORDER BY tipe_kendaraan, tipe_user'
)->fetchAll(PDO::FETCH_ASSOC);
echo $nl . "  Tarif Aktif ({$bold(count($tarif))} entri):" . $nl;
foreach ($tarif as $t) {
    $j1  = number_format($t['tarif_jam_pertama'], 0, ',', '.');
    $pjm = number_format($t['tarif_per_jam'], 0, ',', '.');
    echo "    • {$t['tipe_kendaraan']} / {$t['tipe_user']}: Rp {$j1} (jam 1), Rp {$pjm}/jam berikutnya" . $nl;
}

echo $nl . $bold('=== Verifikasi Selesai — Semua tabel OK! ===') . $nl;
echo "Sistem parking siap digunakan dengan database {$bold($dbName)}." . $nl;

if (!$isCli) { echo '</pre></body></html>'; }

<?php
require_once __DIR__ . '/../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$pegawai_id = (int)$_POST['pegawai_id'];
$bulan      = $_POST['bulan'] ?? '';
$tahun      = $_POST['tahun'] ?? '';
$catatan    = trim($_POST['catatan'] ?? '');

if (!$pegawai_id || !$bulan || !$tahun) {
    header("Location: index.php");
    exit;
}

$periode = date('F Y', mktime(0, 0, 0, (int)$bulan, 1, (int)$tahun));
// contoh hasil: "Juni 2025"

// ─── CEK DUPLIKAT ────────────────────────────────────────────────
$cek = $pdo->prepare("SELECT id FROM kpi WHERE pegawai_id = ? AND periode = ?");
$cek->execute([$pegawai_id, $periode]);
if ($cek->fetch()) {
    header("Location: index.php?msg=exists");
    exit;
}

// ─── RENTANG TANGGAL ─────────────────────────────────────────────
$tgl_awal  = "$tahun-$bulan-01";
$tgl_akhir = date('Y-m-t', strtotime($tgl_awal)); // akhir bulan

// ─── AMBIL DATA ABSENSI ──────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) as total
    FROM absensi
    WHERE pegawai_id = ? AND tanggal BETWEEN ? AND ?
    GROUP BY status
");
$stmt->execute([$pegawai_id, $tgl_awal, $tgl_akhir]);
$rows = $stmt->fetchAll();

if (!$rows) {
    header("Location: index.php?msg=nodata");
    exit;
}

$rekap = ['hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0];
foreach ($rows as $r) {
    if (isset($rekap[$r['status']])) {
        $rekap[$r['status']] = (int)$r['total'];
    }
}

$total_absensi = array_sum($rekap);
$hadir_total   = $rekap['hadir'] + $rekap['terlambat']; // masuk kerja

// ─── AMBIL DATA CUTI ─────────────────────────────────────────────
$stmt2 = $pdo->prepare("
    SELECT COUNT(*) as total_cuti
    FROM cuti
    WHERE pegawai_id = ? AND status = 'approved'
      AND (
          (tgl_mulai BETWEEN ? AND ?) OR
          (tgl_selesai BETWEEN ? AND ?)
      )
");
$stmt2->execute([$pegawai_id, $tgl_awal, $tgl_akhir, $tgl_awal, $tgl_akhir]);
$total_cuti = (int)$stmt2->fetchColumn();

// ─── HITUNG NILAI KPI ────────────────────────────────────────────
// 1. Kehadiran (40%) — hadir+terlambat / total hari absensi tercatat
$skor_kehadiran = $total_absensi > 0
    ? ($hadir_total / $total_absensi) * 100
    : 0;

// 2. Ketepatan Waktu (30%) — hadir tepat / semua yang masuk
$skor_ketepatan = $hadir_total > 0
    ? ($rekap['hadir'] / $hadir_total) * 100
    : 0;

// 3. Disiplin Cuti (30%) — skor penuh jika cuti ≤2, turun 10 tiap cuti lebih
$skor_cuti = max(0, 100 - (max(0, $total_cuti - 2) * 10));

// Nilai akhir (bobot)
$nilai = round(
    ($skor_kehadiran * 0.40) +
    ($skor_ketepatan * 0.30) +
    ($skor_cuti      * 0.30)
);
$nilai = min(100, max(0, $nilai));

// ─── KATEGORI ────────────────────────────────────────────────────
if ($nilai >= 85)      $kategori = 'sangat_baik';
elseif ($nilai >= 70)  $kategori = 'baik';
elseif ($nilai >= 55)  $kategori = 'cukup';
else                   $kategori = 'kurang';

// ─── TARGET & REALISASI (otomatis) ───────────────────────────────
$target_kerja = "Kehadiran minimal 90%, ketepatan waktu minimal 85%, penggunaan cuti wajar.";

$realisasi = sprintf(
    "Kehadiran: %d/%d hari (%.0f%%) | Tepat waktu: %d/%d hari (%.0f%%) | Cuti disetujui: %d kali.",
    $hadir_total, $total_absensi, $skor_kehadiran,
    $rekap['hadir'], $hadir_total, $skor_ketepatan,
    $total_cuti
);

// ─── SIMPAN ──────────────────────────────────────────────────────
$stmt3 = $pdo->prepare("
    INSERT INTO kpi (pegawai_id, periode, nilai, kategori, target_kerja, realisasi, catatan)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt3->execute([$pegawai_id, $periode, $nilai, $kategori, $target_kerja, $realisasi, $catatan]);

header("Location: index.php?msg=simpan");
exit;
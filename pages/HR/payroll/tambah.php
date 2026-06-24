<?php
require_once __DIR__ . '/../../../config/database.php';

// ─────────────────────────────────────────────
// AJAX: GET ?action=get_absensi
// Mengembalikan JSON {hadir, terlambat, gaji_pokok} untuk preview di index.php
// ─────────────────────────────────────────────
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'get_absensi' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');

    $pegawai_id = (int)($_GET['pegawai_id'] ?? 0);
    $bulan      = (int)($_GET['bulan']      ?? 0);
    $tahun      = (int)($_GET['tahun']      ?? 0);

    if (!$pegawai_id || !$bulan || !$tahun) {
        echo json_encode(['hadir' => 0, 'terlambat' => 0, 'gaji_pokok' => 0]);
        exit;
    }

    // Ambil data absensi
    $absStmt = $pdo->prepare("
        SELECT
            SUM(CASE WHEN status='hadir'     THEN 1 ELSE 0 END) AS hadir,
            SUM(CASE WHEN status='terlambat' THEN 1 ELSE 0 END) AS terlambat
        FROM absensi
        WHERE pegawai_id=? AND MONTH(tanggal)=? AND YEAR(tanggal)=?
    ");
    $absStmt->execute([$pegawai_id, $bulan, $tahun]);
    $abs = $absStmt->fetch(PDO::FETCH_ASSOC);

    // Ambil gaji pokok dari payroll terakhir pegawai ini
    $gajiStmt = $pdo->prepare("
        SELECT gaji_pokok FROM payroll
        WHERE pegawai_id=?
        ORDER BY tahun DESC, bulan DESC
        LIMIT 1
    ");
    $gajiStmt->execute([$pegawai_id]);
    $gajiRow    = $gajiStmt->fetch(PDO::FETCH_ASSOC);
    $gaji_pokok = (float)($gajiRow['gaji_pokok'] ?? 0);

    echo json_encode([
        'hadir'      => (int)($abs['hadir']     ?? 0),
        'terlambat'  => (int)($abs['terlambat'] ?? 0),
        'gaji_pokok' => $gaji_pokok,
    ]);
    exit;
}

// ─────────────────────────────────────────────
// POST: update status (Draft → Approved → Final)
// ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_status') {
    $id          = (int)$_POST['id'];
    $status_baru = $_POST['status_baru'] ?? '';
    $bulan       = (int)($_POST['bulan'] ?? date('m'));
    $tahun       = (int)($_POST['tahun'] ?? date('Y'));

    if ($id && in_array($status_baru, ['approved', 'final'])) {
        $cek = $pdo->prepare("SELECT status FROM payroll WHERE id=?");
        $cek->execute([$id]);
        $row = $cek->fetch();

        $valid = $row && (
            ($row['status'] === 'draft'    && $status_baru === 'approved') ||
            ($row['status'] === 'approved' && $status_baru === 'final')
        );

        if ($valid) {
            $pdo->prepare("UPDATE payroll SET status=? WHERE id=?")->execute([$status_baru, $id]);
            header("Location: index.php?bulan={$bulan}&tahun={$tahun}&msg_status=1");
        } else {
            header("Location: index.php?bulan={$bulan}&tahun={$tahun}&msg_locked=1");
        }
    } else {
        header("Location: index.php?bulan={$bulan}&tahun={$tahun}");
    }
    exit;
}

// ─────────────────────────────────────────────
// POST: generate/simpan payroll (form utama)
// ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pegawai_id = (int)($_POST['pegawai_id'] ?? 0);
    $bulan      = (int)($_POST['bulan']      ?? date('m'));
    $tahun      = (int)($_POST['tahun']      ?? date('Y'));

    if (!$pegawai_id || !$bulan || !$tahun) {
        header("Location: index.php?bulan={$bulan}&tahun={$tahun}");
        exit;
    }

    // Ambil gaji pokok dari payroll terakhir pegawai ini
    $gajiStmt = $pdo->prepare("
        SELECT gaji_pokok FROM payroll
        WHERE pegawai_id=?
        ORDER BY tahun DESC, bulan DESC
        LIMIT 1
    ");
    $gajiStmt->execute([$pegawai_id]);
    $gajiRow    = $gajiStmt->fetch(PDO::FETCH_ASSOC);
    $gaji_pokok = (float)($gajiRow['gaji_pokok'] ?? 0);

    // Jika belum ada riwayat payroll sama sekali → tidak bisa generate
    if ($gaji_pokok <= 0) {
        header("Location: index.php?bulan={$bulan}&tahun={$tahun}&msg_nogaji=1");
        exit;
    }

    $tunjangan = 500000;

    // Hitung potongan dari keterlambatan (Rp 50.000 per kejadian)
    $absStmt = $pdo->prepare("
        SELECT SUM(CASE WHEN status='terlambat' THEN 1 ELSE 0 END) AS terlambat
        FROM absensi
        WHERE pegawai_id=? AND MONTH(tanggal)=? AND YEAR(tanggal)=?
    ");
    $absStmt->execute([$pegawai_id, $bulan, $tahun]);
    $absRow    = $absStmt->fetch(PDO::FETCH_ASSOC);
    $terlambat = (int)($absRow['terlambat'] ?? 0);
    $potongan  = $terlambat * 50000;
    $total     = $gaji_pokok + $tunjangan - $potongan;

    // Cek duplikat
    $cek = $pdo->prepare("SELECT id, status FROM payroll WHERE pegawai_id=? AND bulan=? AND tahun=?");
    $cek->execute([$pegawai_id, $bulan, $tahun]);
    $existing = $cek->fetch();

    if ($existing) {
        if ($existing['status'] === 'draft') {
            $pdo->prepare("
                UPDATE payroll SET gaji_pokok=?, tunjangan=?, potongan=?, total=?, status='draft'
                WHERE pegawai_id=? AND bulan=? AND tahun=?
            ")->execute([$gaji_pokok, $tunjangan, $potongan, $total, $pegawai_id, $bulan, $tahun]);
        } else {
            header("Location: index.php?bulan={$bulan}&tahun={$tahun}&msg_locked=1");
            exit;
        }
    } else {
        $pdo->prepare("
            INSERT INTO payroll (pegawai_id, bulan, tahun, gaji_pokok, tunjangan, potongan, total, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'draft')
        ")->execute([$pegawai_id, $bulan, $tahun, $gaji_pokok, $tunjangan, $potongan, $total]);
    }

    header("Location: index.php?bulan={$bulan}&tahun={$tahun}&msg=simpan");
    exit;
}

header('Location: index.php');
exit;

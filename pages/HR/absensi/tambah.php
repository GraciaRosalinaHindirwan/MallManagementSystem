<?php
require_once __DIR__ . '/../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pegawai_id = (int)($_POST['pegawai_id'] ?? 0);
    $tanggal    = date('Y-m-d');
    $jam        = date('H:i:s');
    $foto_name  = null;

    if (!$pegawai_id) {
        header("Location: index.php?error=pegawai_kosong");
        exit;
    }

    // Cegah absen 2x di hari yang sama
    $cek = $pdo->prepare("SELECT id FROM absensi WHERE pegawai_id=? AND tanggal=?");
    $cek->execute([$pegawai_id, $tanggal]);
    if ($cek->fetch()) {
        header("Location: index.php?error=sudah_absen");
        exit;
    }

    // Foto wajib (anti manipulasi)
    if (!empty($_FILES['foto']['name'])) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];

        if (in_array($ext, $allowed)) {
            $foto_name = 'absen_' . $pegawai_id . '_' . date('Ymd_His') . '.' . $ext;
            $target = __DIR__ . '/../../../uploads/absensi/' . $foto_name;
            move_uploaded_file($_FILES['foto']['tmp_name'], $target);
        }
    } else {
        header("Location: index.php?error=foto_wajib");
        exit;
    }

    // Cek jadwal shift hari ini buat tentukan status otomatis
    $shift = $pdo->prepare("
        SELECT s.jam_masuk 
        FROM jadwal_shift js
        JOIN shift s ON js.shift_id = s.id
        WHERE js.pegawai_id = ? AND js.tanggal = ?
    ");
    $shift->execute([$pegawai_id, $tanggal]);
    $jadwal = $shift->fetch();

    $status = 'hadir';
    if ($jadwal && $jam > $jadwal['jam_masuk']) {
        $status = 'terlambat';
    }

    $stmt = $pdo->prepare("
        INSERT INTO absensi (pegawai_id, tanggal, jam_masuk, status, foto_masuk, lokasi_masuk)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $pegawai_id,
        $tanggal,
        $jam,
        $status,
        $foto_name,
        $_POST['lokasi'] ?? null
    ]);
}

header("Location: index.php?tanggal=" . date('Y-m-d'));
exit;

<?php
require_once __DIR__ . '/../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pegawai_id = (int)$_POST['pegawai_id'];
    $tanggal    = $_POST['tanggal']    ?? date('Y-m-d');
    $jam_masuk  = $_POST['jam_masuk']  ?: null;
    $jam_keluar = $_POST['jam_keluar'] ?: null;
    $status     = $_POST['status']     ?? 'hadir';
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($pegawai_id && $tanggal) {
        $stmt = $pdo->prepare("INSERT INTO absensi (pegawai_id, tanggal, jam_masuk, jam_keluar, status, keterangan) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$pegawai_id, $tanggal, $jam_masuk, $jam_keluar, $status, $keterangan]);
    }
}

header("Location: index.php?tanggal=" . ($tanggal ?? date('Y-m-d')));
exit;

?>

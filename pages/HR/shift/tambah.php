<?php
require_once __DIR__ . '/../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pegawai_id = (int)$_POST['pegawai_id'];
    $shift_id   = (int)$_POST['shift_id'];
    $tanggal    = $_POST['tanggal'] ?? '';

    if ($pegawai_id && $shift_id && $tanggal) {
        $stmt = $pdo->prepare("INSERT INTO jadwal_shift (pegawai_id, shift_id, tanggal) VALUES (?,?,?)");
        $stmt->execute([$pegawai_id, $shift_id, $tanggal]);
        header("Location: index.php?msg=tambah");
        exit;
    }
}

header("Location: index.php");
exit;

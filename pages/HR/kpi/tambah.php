<?php
require_once __DIR__ . '/../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pegawai_id  = (int)$_POST['pegawai_id'];
    $periode     = trim($_POST['periode']     ?? '');
    $nilai       = (int)$_POST['nilai'];
    $kategori    = $_POST['kategori']         ?? 'cukup';
    $target_kerja= trim($_POST['target_kerja']?? '');
    $realisasi   = trim($_POST['realisasi']   ?? '');
    $catatan     = trim($_POST['catatan']     ?? '');

    if ($pegawai_id && $periode) {
        $stmt = $pdo->prepare("INSERT INTO kpi (pegawai_id,periode,nilai,kategori,target_kerja,realisasi,catatan) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$pegawai_id,$periode,$nilai,$kategori,$target_kerja,$realisasi,$catatan]);
    }
}

header("Location: index.php?msg=simpan");
exit;

<?php
require_once __DIR__ . '/../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pegawai_id = (int)$_POST['pegawai_id'];
    $tgl_mulai  = $_POST['tgl_mulai']   ?? '';
    $tgl_selesai= $_POST['tgl_selesai'] ?? '';
    $jenis_cuti = $_POST['jenis_cuti']  ?? 'tahunan';
    $alasan     = trim($_POST['alasan'] ?? '');

    if ($pegawai_id && $tgl_mulai && $tgl_selesai && $alasan) {
        $stmt = $pdo->prepare("INSERT INTO cuti (pegawai_id,tgl_mulai,tgl_selesai,jenis_cuti,alasan) VALUES (?,?,?,?,?)");
        $stmt->execute([$pegawai_id,$tgl_mulai,$tgl_selesai,$jenis_cuti,$alasan]);
    }
}

header("Location: index.php?msg=tambah");
exit;

?>
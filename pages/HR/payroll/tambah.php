<?php
require_once __DIR__ . '/../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pegawai_id = (int)$_POST['pegawai_id'];
    $bulan      = (int)$_POST['bulan'];
    $tahun      = (int)$_POST['tahun'];
    $gaji_pokok = (float)$_POST['gaji_pokok'];
    $tunjangan  = (float)($_POST['tunjangan'] ?? 0);
    $potongan   = (float)($_POST['potongan']  ?? 0);

    if ($pegawai_id && $bulan && $tahun && $gaji_pokok) {
        // Cek duplikat
        $cek = $pdo->prepare("SELECT id FROM payroll WHERE pegawai_id=? AND bulan=? AND tahun=?");
        $cek->execute([$pegawai_id, $bulan, $tahun]);
        if ($cek->fetch()) {
            // Update jika sudah ada
            $stmt = $pdo->prepare("UPDATE payroll SET gaji_pokok=?,tunjangan=?,potongan=? WHERE pegawai_id=? AND bulan=? AND tahun=?");
            $stmt->execute([$gaji_pokok,$tunjangan,$potongan,$pegawai_id,$bulan,$tahun]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO payroll (pegawai_id,bulan,tahun,gaji_pokok,tunjangan,potongan) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$pegawai_id,$bulan,$tahun,$gaji_pokok,$tunjangan,$potongan]);
        }
    }
}

header("Location: index.php?bulan={$bulan}&tahun={$tahun}&msg=simpan");
exit;
?>
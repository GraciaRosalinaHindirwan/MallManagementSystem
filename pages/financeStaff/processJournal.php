<?php
/** @var mysqli $conn */
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../../config/koneksi.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal        = $_POST['tanggal'];
    $ref_no         = !empty($_POST['reference_no']) ? $_POST['reference_no'] : 'JV-' . date('YmdHis') . '-' . rand(100, 999);
    $keterangan     = $_POST['keterangan'];
    
    // Pastikan menangkap ID akun sebagai integer (Angka ID)
    $akun_debit     = (int)$_POST['akun_debit'];
    $nominal_debit  = floatval($_POST['nominal_debit']);
    $akun_kredit    = (int)$_POST['akun_kredit'];
    $nominal_kredit = floatval($_POST['nominal_kredit']);

    if ($nominal_debit != $nominal_kredit) {
        $_SESSION['error_msg'] = "Gagal Posting! Nilai Debit dan Kredit tidak seimbang.";
        header("Location: journalManagement.php?ref=" . urlencode($ref_no));
        exit;
    }

    try {
        $conn->begin_transaction();

        $source_type = 'manual';
        if (strpos(strtolower($keterangan), 'iklan') !== false) {
            $source_type = 'ad';
        } elseif (strpos(strtolower($keterangan), 'event') !== false) {
            $source_type = 'event';
        }
        
        $status_jurnal = 'posted';

        // 1. INSERT KE TABEL HEADER (Disesuaikan dengan kolom database asli: total_debit, total_credit, status)
        $queryHeader = "INSERT INTO 06_journal_entries (journal_number, journal_date, description, source_type, total_debit, total_credit, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmtHeader  = $conn->prepare($queryHeader);
        // Menggunakan kombinasi parameter string (s) dan double (d) yang valid
        $stmtHeader->bind_param("ssssdds", $ref_no, $tanggal, $keterangan, $source_type, $nominal_debit, $nominal_kredit, $status_jurnal);
        $stmtHeader->execute();
        
        $journal_entry_id = $conn->insert_id; // Ambil ID Auto Increment yang baru dibuat

        // 2. INSERT DETAIL DEBIT (Menggunakan "iid" karena account_id adalah integer ID)
        $queryDebit = "INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) VALUES (?, ?, ?, 0)";
        $stmtDebit  = $conn->prepare($queryDebit);
        $stmtDebit->bind_param("iid", $journal_entry_id, $akun_debit, $nominal_debit); // Berubah jadi iid
        $stmtDebit->execute();

        // 3. INSERT DETAIL KREDIT (Menggunakan "iid" karena account_id adalah integer ID)
        $queryKredit = "INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) VALUES (?, ?, 0, ?)";
        $stmtKredit  = $conn->prepare($queryKredit);
        $stmtKredit->bind_param("iid", $journal_entry_id, $akun_kredit, $nominal_kredit); // Berubah jadi iid
        $stmtKredit->execute();

        $conn->commit();

        $_SESSION['success_msg'] = "Berhasil! Jurnal <strong>#$ref_no</strong> telah dicatat ke Buku Besar.";
        header("Location: journalManagement.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_msg'] = "Gagal memproses database: " . $e->getMessage();
        header("Location: journalManagement.php?ref=" . urlencode($ref_no));
        exit;
    }
} else {
    header("Location: journalManagement.php");
    exit;
};
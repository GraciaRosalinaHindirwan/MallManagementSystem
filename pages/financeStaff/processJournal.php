<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/** @var mysqli $conn */
if (session_status() == PHP_SESSION_NONE) { 
    session_start(); 
}

if (file_exists('../../config/konek.php')) {
    require_once '../../config/konek.php';
} else {
    require_once '../../config/connection.php';
}

date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal    = trim($_POST['tanggal']);
    $ref_no     = !empty($_POST['reference_no']) ? trim($_POST['reference_no']) : 'JV-' . date('YmdHis') . '-' . rand(100, 999);
    $keterangan = trim($_POST['keterangan']);
    
    $akun_debit     = (int)$_POST['akun_debit'];
    $nominal_debit  = floatval($_POST['nominal_debit']);
    $akun_kredit    = (int)$_POST['akun_kredit'];
    $nominal_kredit = floatval($_POST['nominal_kredit']);

    if ($nominal_debit <= 0 || $nominal_debit != $nominal_kredit) {
        $_SESSION['error_msg'] = "<div class='alert' style='background:rgba(239,68,68,0.15); color:#ef4444; padding:12px; border-radius:6px; margin-bottom:15px;'>Nominal tidak seimbang atau tidak valid!</div>";
        header("Location: journalStaffManagement.php");
        exit;
    }

    try {
        $conn->begin_transaction();

        // Identifikasi tipe transaksi otomatis
        $source_type = 'manual';
        $check_text = strtolower($keterangan);
        if (strpos($check_text, 'iklan') !== false) { $source_type = 'ad'; }
        elseif (strpos($check_text, 'event') !== false) { $source_type = 'event'; }
        elseif (strpos($check_text, 'parkir') !== false) { $source_type = 'parking'; }

        // 1. Insert ke tabel entri utama (header)
        $q_header = "INSERT INTO 06_journal_entries (journal_number, journal_date, description, source_type, total_debit, total_credit, status) VALUES (?, ?, ?, ?, ?, ?, 'posted')";
        $stmt_h = $conn->prepare($q_header);
        $stmt_h->bind_param("ssssdd", $ref_no, $tanggal, $keterangan, $source_type, $nominal_debit, $nominal_kredit);
        $stmt_h->execute();
        
        $entry_id = $conn->insert_id;

        // 2. Insert baris DEBIT (Menggunakan kolom account_id sebagai foreign key relasi COA)
        $q_debit = "INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) VALUES (?, ?, ?, 0)";
        $stmt_d = $conn->prepare($q_debit);
        $stmt_d->bind_param("iid", $entry_id, $akun_debit, $nominal_debit);
        $stmt_d->execute();

        // 3. Insert baris KREDIT
        $q_kredit = "INSERT INTO 06_journal_lines (journal_entry_id, account_id, debit, credit) VALUES (?, ?, 0, ?)";
        $stmt_k = $conn->prepare($q_kredit);
        $stmt_k->bind_param("iid", $entry_id, $akun_kredit, $nominal_kredit);
        $stmt_k->execute();

        $conn->commit();
        $_SESSION['success_msg'] = "<div class='alert' style='background:rgba(16,185,129,0.15); color:#10b981; padding:12px; border-radius:6px; margin-bottom:15px;'>Sukses! Jurnal <strong>#$ref_no</strong> berhasil di-posting.</div>";
        header("Location: journalStaffManagement.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_msg'] = "<div class='alert' style='background:rgba(239,68,68,0.15); color:#ef4444; padding:12px; border-radius:6px; margin-bottom:15px;'>Gagal menyimpan: " . htmlspecialchars($e->getMessage()) . "</div>";
        header("Location: journalStaffManagement.php");
        exit;
    }
} else {
    header("Location: journalStaffManagement.php");
    exit;
}

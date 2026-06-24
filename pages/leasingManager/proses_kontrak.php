<?php
// 1. Panggil koneksi database
require_once '../../config/conn.php';

// 2. Cek apakah tombol "Simpan Kontrak" benar-benar ditekan
if (isset($_POST['submit_kontrak'])) {
    
    // 3. Tangkap data dari form
    $id_tenant = $_POST['id_tenant'];
    $id_unit = $_POST['id_unit'];
    $start_date = $_POST['tanggal_mulai'];
    $end_date = $_POST['tanggal_selesai'];
    
    // 4. Generate Nomor Kontrak Otomatis (Contoh: CONT-2026-018)
    $tahun_ini = date("Y");
    $query_last = mysqli_query($conn, "SELECT MAX(id_contract) as max_id FROM 02_contracts");
    $data_last = mysqli_fetch_assoc($query_last);
    $id_baru = $data_last['max_id'] + 1;
    $contract_number = "CONT-" . $tahun_ini . "-" . str_pad($id_baru, 3, "0", STR_PAD_LEFT);
    
    // 5. Status default untuk kontrak baru
    $status = 'Draft'; 
    
    // 6. Siapkan Query SQL untuk menyimpan data ke database
    $query_insert = "INSERT INTO 02_contracts (contract_number, id_tenant, id_unit, start_date, end_date, contract_status) 
                     VALUES ('$contract_number', '$id_tenant', '$id_unit', '$start_date', '$end_date', '$status')";
    
    // 7. Eksekusi Query dan berikan alert (pesan sukses/gagal)
    if (mysqli_query($conn, $query_insert)) {
        // Jika sukses, munculkan pop-up dan kembalikan ke halaman form
        echo "<script>
                alert('Berhasil! Kontrak baru dengan nomor $contract_number telah disimpan.');
                window.location.href = 'buat_kontrak.php';
              </script>";
    } else {
        // Jika gagal, tampilkan errornya
        echo "Gagal menyimpan data: " . mysqli_error($conn);
    }
    
} else {
    // Jika file ini diakses langsung tanpa lewat form, tendang balik ke halaman form
    header("Location: buat_kontrak.php");
    exit();
}
?>
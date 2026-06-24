<?php
// 1. TETAP NYALAKAN PELACAK ERROR
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config/conn.php'; 

// 2. VARIABEL TEMPLATE
$department_name = "Leasing Management"; 
$page_title = "Buat Kontrak Sewa"; 
$user_name = "Leasing Manager"; 

$menu_items = [
    [
        'icon' => 'fa-solid fa-file-signature',
        'label' => 'Buat Kontrak',
        'link' => 'buat_kontrak.php',
        'active_page' => 'buat_kontrak'
    ],
    [
        'icon' => 'fa-solid fa-file-pen',
        'label' => 'Buat Addendum',
        'link' => 'buat_addendum.php',
        'active_page' => 'buat_addendum'
    ],
    [
        'icon' => 'fa-solid fa-bell',
        'label' => 'Notifikasi',
        'link' => 'notifikasi_kontrak.php',
        'active_page' => 'notifikasi'
    ]
];

// 3. QUERY DATABASE
$query_tenant = mysqli_query($conn, "SELECT id_tenant, brand_name FROM 02_tenants WHERE status = 'Active'");
$query_unit = mysqli_query($conn, "SELECT id_units, unit_code FROM 01_units WHERE status = 'available'");

// 4. MULAI TANGKAP KONTEN
ob_start(); 
?>

<div style="padding: 20px;">
    <h2>Formulir Pembuatan Kontrak Multi-Periode</h2>
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <form action="proses_kontrak.php" method="POST">
            <div style="margin-bottom: 15px;">
                <label>Pilih Tenant (Brand):</label><br>
                <select name="id_tenant" style="width: 100%; padding: 8px;" required>
                    <option value="">-- Pilih Tenant --</option>
                    <?php 
                    while($row = mysqli_fetch_assoc($query_tenant)) { 
                        echo "<option value='".$row['id_tenant']."'>".$row['brand_name']."</option>";
                    } 
                    ?>
                </select>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label>Pilih Unit / Kios:</label><br>
                <select name="id_unit" style="width: 100%; padding: 8px;" required>
                    <option value="">-- Pilih Unit Available --</option>
                    <?php 
                    while($row = mysqli_fetch_assoc($query_unit)) { 
                        echo "<option value='".$row['id_units']."'>".$row['unit_code']."</option>";
                    } 
                    ?>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label>Tanggal Mulai Kontrak:</label><br>
                <input type="date" name="tanggal_mulai" style="width: 100%; padding: 8px;" required>
            </div>

            <div style="margin-bottom: 15px;">
                <label>Tanggal Selesai Kontrak (Akhir Periode):</label><br>
                <input type="date" name="tanggal_selesai" style="width: 100%; padding: 8px;" required>
            </div>

            <button type="submit" name="submit_kontrak" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Simpan Kontrak</button>
        </form>
    </div>
</div>

<?php
// 5. SIMPAN KONTEN & PANGGIL TEMPLATE
$content = ob_get_clean(); 
require_once '../../includes/navbar.php'; 
?>
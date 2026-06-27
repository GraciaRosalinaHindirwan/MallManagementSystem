<?php
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "mall_erp";

// Menghubungkan ke MySQL database
$conn = new mysqli($host, $user, $pass, $db_name);

// Cek koneksi, kalau gagal biar muncul pesan rapi
if ($conn->connect_error) {
    die("<div style='color: red; padding: 20px; font-weight: bold;'>
            ⚠️ Koneksi Database Gagal: " . $conn->connect_error . "
         </div>");
}
?>

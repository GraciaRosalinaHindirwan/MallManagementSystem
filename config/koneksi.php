<?php
$host = "localhost";
$user = "root";
$pass = "";
<<<<<<< HEAD
$db_name = "mall_erp";

$conn = new mysqli($host, $user, $pass, $db_name);

if ($conn->connect_error) {
    die("<div style='color: red; padding: 20px; font-weight: bold;'>
            ⚠️ Koneksi Database Gagal: " . $conn->connect_error . "
        </div>");
}
=======
$db_name = "mall_management";

// Menghubungkan ke MySQL database
$conn = new mysqli($host, $user, $pass, $db_name);

// Cek koneksi, kalau gagal biar muncul pesan rapi
if ($conn->connect_error) {
    die("<div style='color: red; padding: 20px; font-weight: bold;'>
            ⚠️ Koneksi Database Gagal: " . $conn->connect_error . "
         </div>");
}
?>
>>>>>>> 566c94f9b4e907effbc09fbd6e3a4579cf056628

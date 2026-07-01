<?php
$host = "localhost";
$user = "root"; // Default XAMPP
$pass = "";     // Default XAMPP (kosong)
$db   = "mall_erp";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}
?>
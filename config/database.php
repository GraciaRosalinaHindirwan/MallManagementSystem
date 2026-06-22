<?php
$conn = new mysqli("localhost", "root", "", "mall_erp_global");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
<?php
$conn = new mysqli("localhost", "root", "", "mall_manajemen");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
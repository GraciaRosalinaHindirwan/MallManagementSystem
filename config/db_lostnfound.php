<?php
$conn = new mysqli("localhost", "root", "", "mall_management");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
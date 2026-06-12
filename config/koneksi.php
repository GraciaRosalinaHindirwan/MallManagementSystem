<?php
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "mall_management";

$conn = new mysqli($host, $user, $pass, $db_name);

if ($conn->connect_error) {
    die("<div style='color: red; padding: 20px; font-weight: bold;'>
            ⚠️ Koneksi Database Gagal: " . $conn->connect_error . "
        </div>");
}
?>
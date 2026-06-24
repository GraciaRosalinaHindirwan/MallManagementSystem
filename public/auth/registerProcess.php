<?php
session_start();
/*
if (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} else {
    require_once '../../config/connection.php';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $role     = mysqli_real_escape_string($conn, $_POST['role']); // Menerima: 'Finance Manager', 'Finance Staff', dll.
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Cek duplikasi
    $cek_user = "SELECT * FROM users WHERE username='$username' OR email='$email' LIMIT 1";
    $result_cek = $conn->query($cek_user);

    if ($result_cek && $result_cek->num_rows > 0) {
        echo "<script>alert('Username atau Email sudah digunakan!'); window.history.back();</script>";
        exit();
    }

    // Query simpan data
    $query_insert = "INSERT INTO users (username, nama, email, password, role) VALUES ('$username', '$nama', '$email', '$password', '$role')";

    if ($conn->query($query_insert) === TRUE) {
        echo "<script>alert('Registrasi akun berhasil! Silakan login.'); window.location.href = '../index.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal mendaftar: " . $conn->error . "'); window.history.back();</script>";
    }
}
*/

echo "Proses Register (Backend Siap Pakai - Mode Komen Teraktifkan)";

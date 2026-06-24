<?php
session_start();

/*
if (file_exists('../../config/koneksi.php')) {
    require_once '../../config/koneksi.php';
} else {
    require_once '../../config/connection.php';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $role     = mysqli_real_escape_string($conn, $_POST['role']); // Diambil dari <select> di form HTML

    // 1. Validasi Input Kosong
    if (empty($nama) || empty($username) || empty($password) || empty($role)) {
        echo "<script>alert('Semua data wajib diisi!'); window.history.back();</script>";
        exit();
    }

    // 2. Cek apakah username sudah terdaftar sebelumnya
    $check_user = "SELECT username FROM users WHERE username='$username' LIMIT 1";
    $result_check = $conn->query($check_user);

    if ($result_check && $result_check->num_rows > 0) {
        echo "<script>alert('Username sudah digunakan! Silakan pilih username lain.'); window.history.back();</script>";
        exit();
    }

    // 3. Hash password demi keamanan database
    $password_hashed = password_hash($password, PASSWORD_BCRYPT);

    // 4. Proses Insert data user baru ke database
    $query_insert = "INSERT INTO users (nama, username, password, role) VALUES ('$nama', '$username', '$password_hashed', '$role')";
    
    if ($conn->query($query_insert)) {
        echo "<script>alert('Registrasi Berhasil! Silakan Login.'); window.location.href = '../../index.php';</script>";
        exit();
    } else {
        echo "<script>alert('Registrasi Gagal! Terjadi kesalahan pada sistem.'); window.history.back();</script>";
        exit();
    }
}
*/

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register - Mall ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: #021F42; color: #fff;">
<div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card p-4" style="background-color: #082A53; width: 100%; max-width: 450px; border: 1px solid rgba(255,255,255,0.1); border-radius: 12px;">
        <h3 class="text-center mb-4" style="color: #FFB62A; font-weight: 700;">Buat Akun Mall ERP</h3>
        
        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" placeholder="Masukkan nama lengkap..." required>
            </div>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username..." required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password..." required>
            </div>
            <div class="mb-4">
                <label class="form-label">Akses Role Keuangan / Logistik</label>
                <select name="role" class="form-select" required>
                    <option value="" disabled selected>-- Pilih Role Kamu --</option>
                    <option value="Finance Manager">Finance Manager</option>
                    <option value="Finance Staff">Finance Staff</option>
                    <option value="Purchasing Manager">Purchasing Manager</option>
                    <option value="Purchasing Staff">Purchasing Staff</option>
                </select>
            </div>
            <button type="submit" class="btn w-100 style-btn" style="background-color: #FFB62A; color: #021F42; font-weight: 700;">Daftar Akun Baru</button>
        </form>
    </div>
</div>
</body>
</html>

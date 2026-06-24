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
    $password = $_POST['password'];

    // Ambil data user berdasarkan username
    $query = "SELECT * FROM users WHERE username='$username' LIMIT 1";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verifikasi password yang di-hash
        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role']; 
            $_SESSION['nama']     = $user['nama'];

            // === REDIRECT SESUAI 4 ROLE ===
            if ($user['role'] === 'Finance Manager') {
                header("Location: ../../pages/financeManager/dashboardManager.php");
            } else if ($user['role'] === 'Finance Staff') {
                header("Location: ../../pages/financeStaff/dashboardStaff.php");
            } else if ($user['role'] === 'Purchasing Manager') {
                header("Location: ../../pages/purchasingManager/dashboardPurchasingManager.php"); // Sesuaikan nama file dashboardnya nanti ya
            } else if ($user['role'] === 'Purchasing Staff') {
                header("Location: ../../pages/purchasingStaff/dashboardPurchasingStaff.php");     // Sesuaikan nama file dashboardnya nanti ya
            } else {
                header("Location: ../index.php?error=role_tidak_dikenal");
            }
            exit();
        } else {
            echo "<script>alert('Password salah!'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Username tidak ditemukan!'); window.history.back();</script>";
    }
}
*/

echo "Proses Login (Backend Siap Pakai - Mode Komen Teraktifkan)";

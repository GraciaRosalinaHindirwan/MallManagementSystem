<?php
session_start();
require_once __DIR__.'/../../repositories/UserRepository.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: adminResetPassword.php');
    exit;
}

$userId = (int)($_POST['user_id'] ?? 0);

if($userId <= 0){
    $_SESSION['error'] = 'User ID tidak valid.';
    header('Location: adminResetPassword.php');
    exit;
}

$userRepository = new UserRepository();
$user = $userRepository->findById($userId);

if(!$user){
    $_SESSION['error'] = 'Pengguna tidak ditemukan.';
    header('Location: adminResetPassword.php');
    exit;
}

$newPassword = $user->username . '@123';
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
$success = $userRepository->adminResetPassword($user->id, $hashedPassword);

if($success){
    // Hapus session lock jika masih ada
    if(isset($_SESSION['lock_until_' . $user->id])){
        unset($_SESSION['lock_until_' . $user->id]);
    }

    $_SESSION['success'] = "Password pengguna berhasil direset. Pengguna wajib mengganti password saat login berikutnya.";
} else {
    $_SESSION['error'] = 'Gagal melakukan reset password. Silakan coba lagi.';
}

header('Location: adminResetPassword.php');
exit;

<?php
session_start();
require_once __DIR__.'/../../repositories/UserRepositoryFactory.php';
require_once __DIR__.'/../../dto/changePasswordDto.php';
require_once __DIR__.'/../../services/authService.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: ../../public/changePassword.php');
    exit;
}

$newPassword = trim($_POST['new_password']);
$confirmPassword = trim($_POST['confirm_password']);

if (empty($newPassword) || empty($confirmPassword)) {

    $_SESSION['error'] =
        'Semua field wajib diisi';

    header('Location: ../../public/changePassword.php');
    exit;
} else if($newPassword !== $confirmPassword){
    $_SESSION['error'] = 'Konfirmasi password tidak cocok';
    header('Location: ../../public/changePassword.php');
    exit;
}

if(
    !preg_match(
        '/^(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9])(?!.*[+{}]).{8,}$/',
        $newPassword
    )
){
    $_SESSION['error'] =
        'Password minimal 8 karakter, harus mengandung huruf kapital, angka, dan simbol. Simbol +, {, } tidak diperbolehkan.';

    header('Location: ../../public/changePassword.php');
    exit;
}

$dto = new changePasswordDto(
    $_SESSION['user_id'],
    $newPassword
);

$userRepository = UserRepositoryFactory::getInstance();
$authService = new AuthService($userRepository);

$result = $authService->changePassword($dto);
if (!$result) {
    $_SESSION['error'] =
        'Gagal mengubah password. Silakan coba lagi.';

    header('Location: ../../public/changePassword.php');
    exit;
}

$userRepository->clearMustChangePassword($_SESSION['user_id']);
unset($_SESSION['warning']);

header(
    'Location: ../../testing/dashboardTest.php'
);

exit;
?>
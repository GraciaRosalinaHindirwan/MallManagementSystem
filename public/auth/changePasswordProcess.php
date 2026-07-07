<?php
session_start();
<<<<<<< HEAD

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: ../auth/changePassword.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {

    header('Location: ../index.php');
=======
require_once __DIR__.'/../../repositories/UserRepositoryFactory.php';
require_once __DIR__.'/../../dto/changePasswordDto.php';
require_once __DIR__.'/../../services/authService.php';
require_once __DIR__.'/../../config/log_helper.php';
require_once __DIR__.'/AfterLoginProcess.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: ../../public/changePassword.php');
>>>>>>> 55bf5912288eaf5072aa118db5e7a3075d14d273
    exit;
}

$newPassword = trim($_POST['new_password']);
$confirmPassword = trim($_POST['confirm_password']);

if (empty($newPassword) || empty($confirmPassword)) {

    $_SESSION['error'] =
        'Semua field wajib diisi';

<<<<<<< HEAD
    header('Location: changePassword.php');
    exit;
}

$hashedPassword = password_hash(
    $newPassword,
    PASSWORD_DEFAULT
);

//update database


$_SESSION['success'] =
    'Password berhasil diubah';

header('Location: ../dashboard.php');
exit;

=======
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

simpanLog(
    $_SESSION['username'],
    'CHANGE_PASSWORD'
);

unset($_SESSION['warning']);

$role = $_SESSION['user_role'] ?? '';
$process = new RedirectByRoleAction($role);
(new AfterLoginProcess())->execute($process);
>>>>>>> 55bf5912288eaf5072aa118db5e7a3075d14d273
?>
<?php
require_once __DIR__.'/../../repositories/UserRepository.php';
require_once __DIR__.'/../../dto/LoginDto.php';
require_once __DIR__.'/../../services/authService.php';

session_start();

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: ../index.php');
    exit;
}

$username = trim($_POST['username']);
$password = trim($_POST['password']);

//validasi captcha 
$captchaInput = strtoupper(
    trim($_POST['captcha'])
);

if (
    $captchaInput !== $_SESSION['captcha']
) {

    $_SESSION['error'] =
        'Kode captcha salah';

    header('Location: ../index.php');

    exit;
}
unset($_SESSION['captcha']);

//validasi input 
if (
    empty($username) ||
    empty($password)
) {
    $_SESSION['error'] =
        'Username dan password wajib diisi';

    header('Location: ../index.php');
    exit;
}

$dto = new LoginDto($username, $password);
$userRepository = new UserRepository();
$authService = new AuthService($userRepository);

$user = $authService->login($dto);
if (!$user) {
    $_SESSION['error'] =
        'Username atau password salah';

    header('Location: ../index.php');
    exit;
}

$_SESSION['user_id'] = $user->id;
$_SESSION['username'] = $user->username;
$_SESSION['last_activity'] = time();

if ($user->mustChangePassword) {

    $_SESSION['warning'] =
        'Silakan ganti password terlebih dahulu';

    header(
        'Location: ../changePassword.php'
    );
    exit;
} else{
    header(
        'Location: ../testing/dashboard.php'
    );
    exit;
}



?>
<?php
<<<<<<< HEAD
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

            // Lempar ke halaman dashboard yang sesuai berdasarkan role masing-masing
            if ($user['role'] === 'Finance Manager') {
                header("Location: ../../pages/financeManager/dashboardManager.php");
            } else if ($user['role'] === 'Finance Staff') {
                header("Location: ../../pages/financeStaff/dashboardStaff.php");
            } else if ($user['role'] === 'Purchasing Manager') {
                header("Location: ../../pages/purchasingManager/dashboardManager.php");
            } else if ($user['role'] === 'Purchasing Staff') {
                header("Location: ../../pages/purchasingStaff/dashboardStaff.php");
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
=======
require_once __DIR__.'/../../repositories/UserRepositoryFactory.php';
require_once __DIR__.'/../../dto/LoginDto.php';
require_once __DIR__.'/../../services/authService.php';
require_once __DIR__.'/AfterLoginProcess.php';

interface LoginHandler{
    public function setNext(LoginHandler $loginHandler): LoginHandler;
    public function handle(array $request);
}

abstract class BaseLoginHandler implements LoginHandler{
    protected ?LoginHandler $next = null;

    public function setNext(LoginHandler $loginHandler): LoginHandler{
        $this->next = $loginHandler;
        return $loginHandler;
    }
    public function handle(array $request){
        if($this->next) {
            return $this->next->handle($request);
        }

        return;
    }
}

class CaptchaHandler extends BaseLoginHandler {
    public function handle(array $request): void {
        $captchaInput = strtoupper(trim($request['captcha'] ?? ''));
        
        if ($captchaInput !== ($_SESSION['captcha'] ?? '')) {
            $_SESSION['error'] = 'Kode captcha salah';
            header('Location: ../index.php');
            exit;
        }
        
        unset($_SESSION['captcha']);
        parent::handle($request); // Lanjut ke handler berikutnya
    }
}

class InputValidationHandler extends BaseLoginHandler {
    public function handle(array $request): void {
        $username = trim($request['username'] ?? '');
        $password = trim($request['password'] ?? '');

        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Username dan password wajib diisi';
            header('Location: ../index.php');
            exit;
        }

        parent::handle($request);
    }
}

class AuthenticationHandler extends BaseLoginHandler {
    public function __construct(
        private AuthService $authService
    ) {}

    public function handle(array $request): void {
        $dto = new LoginDto(trim($request['username']), trim($request['password']));
        $result = $this->authService->login($dto);

        if (!$result['success']) {
            $_SESSION['error'] = $result['message'];
            header('Location: ../index.php');
            exit;
        }

        // Simpan data user ke request untuk dipakai di handler berikutnya
        $request['authenticated_user'] = $result['user']; 
        
        parent::handle($request);
    }
}

class SessionAndPostLoginHandler extends BaseLoginHandler {
    public function handle(array $request): void {
        $user = $request['authenticated_user'];

        // Set Session
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['last_activity'] = time();

        // Pengecekan setelah login (dari kode Anda sebelumnya)
        $process = new MustChangePasswordAction();
        if (!$user->mustChangePassword) {
            $process = new RedirectByRoleAction($user->role);
        }

        // Panggil AfterLoginProcess (Pastikan method execute statis atau sesuaikan instansiasinya)
        (new AfterLoginProcess())->execute($process);
    }
}

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

// 1. Inisialisasi Service pendukung
$userRepository = UserRepositoryFactory::getInstance();
$authService = new AuthService($userRepository);

// 2. Inisialisasi semua Handler
$captchaHandler = new CaptchaHandler();
$validationHandler = new InputValidationHandler();
$authHandler = new AuthenticationHandler($authService);
$sessionHandler = new SessionAndPostLoginHandler();

// 3. Susun Rantainya (Chain of Responsibility)
$captchaHandler
    ->setNext($validationHandler)
    ->setNext($authHandler)
    ->setNext($sessionHandler);



// 4. Jalankan Chain dengan data POST
// Kalau captcha hidup
$captchaHandler->handle($_POST);
// Kalau captcha mati
//$validationHandler->handle($_POST);
?>
>>>>>>> main

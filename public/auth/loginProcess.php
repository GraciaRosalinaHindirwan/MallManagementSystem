<?php
require_once __DIR__.'/../../repositories/UserRepositoryFactory.php';
require_once __DIR__.'/../../dto/LoginDto.php';
require_once __DIR__.'/../../services/authService.php';
require_once __DIR__.'/AfterLoginProcess.php';
require_once __DIR__.'/../../config/log_helper.php';

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

        simpanLog(
            $user->username,
            'LOGIN'
        );

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

<<<<<<< HEAD
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: ../index.php');
    exit;
}

//ambil data 
$username = trim($_POST['username']);
$password = trim($_POST['password']);

//cek password supaya tidak di baypass, harusnya register page ini 
if(!preg_match('/^(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9])(?!.*[+{}]).{8,}$/', $password)){
    $_SESSION['error'] = 'Password tidak memenuhi format yang ditentukan';

    header('Location: ../index.php');
    exit;
}

// buat captcha
$captchaInput = strtoupper(
    trim($_POST['captcha'])
);

if (
    $captchaInput !== $_SESSION['captcha']
) {

    $_SESSION['error'] =
        'Kode captcha salah';

=======
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
>>>>>>> 55bf5912288eaf5072aa118db5e7a3075d14d273
    header('Location: ../index.php');
    exit;
}

<<<<<<< HEAD
if(password_verify($password, $user['password'])){
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['last_activity'] = time();
}

if($user['must_change_password'] == 1){

    $_SESSION['warning'] =
        'Anda wajib mengganti password sebelum menggunakan sistem.';

    header('Location: ../changePassword.php');
    exit;
} else{
    header('Location: ../index.php');
    exit;
}

=======
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
>>>>>>> 55bf5912288eaf5072aa118db5e7a3075d14d273
?>
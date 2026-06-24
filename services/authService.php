<?php
require_once __DIR__.'/../repositories/UserRepositoryInterface.php';
require_once __DIR__.'/../dto/LoginDto.php';
require_once __DIR__.'/../dto/userDto.php';
require_once __DIR__.'/../dto/changePasswordDto.php';

class AuthService{
    public function __construct(private UserRepositoryInterface $UserRepository){}

    public function login(LoginDto $dto): array {
        $user = $this->UserRepository->findByUsername($dto->username);
        if(!$user){
            return ['success' => false, 'message' => 'Username atau password salah'];
        }

        if($user->isBlocked){
            return ['success' => false, 'message' => "<script>alert('Akun Anda telah diblokir karena terlalu banyak percobaan login yang gagal. Silakan hubungi administrator untuk melakukan reset password.'); document.currentScript.parentElement.style.display = 'none';</script>"];
        }

        // Check lock session
        if(isset($_SESSION['lock_until_' . $user->id]) && time() < $_SESSION['lock_until_' . $user->id]){
            $remainingSeconds = $_SESSION['lock_until_' . $user->id] - time();
            $message = 'Terlalu banyak percobaan login gagal. Silakan coba kembali dalam <span id="lock-timer"></span>.';
            $message .= "<script>
                let remaining = $remainingSeconds;
                const timerEl = document.getElementById('lock-timer');
                const updateTimer = () => {
                    if (remaining <= 0) {
                        timerEl.innerText = 'sekarang. Silakan muat ulang (refresh) halaman.';
                    } else {
                        let m = Math.floor(remaining / 60);
                        let s = remaining % 60;
                        let text = '';
                        if (m > 0) text += m + ' menit ';
                        text += s + ' detik';
                        timerEl.innerText = text;
                        remaining--;
                        setTimeout(updateTimer, 1000);
                    }
                };
                updateTimer();
            </script>";
            return ['success' => false, 'message' => $message];
        }

        if(!password_verify($dto->password, $user->password)){
            $this->UserRepository->incrementFailedLogin($user->id);
            $newAttempts = $user->failedLoginAttempts + 1;
            
            if($newAttempts >= 5){
                $this->UserRepository->blockUser($user->id);
                return ['success' => false, 'message' => "<script>alert('Akun Anda telah diblokir karena terlalu banyak percobaan login yang gagal. Silakan hubungi administrator untuk melakukan reset password.'); document.currentScript.parentElement.style.display = 'none';</script>"];
            } else if($newAttempts == 3){
                $_SESSION['lock_until_' . $user->id] = time() + (15 * 60);
                $remainingSeconds = 15 * 60;
                $message = 'Terlalu banyak percobaan login gagal. Silakan coba kembali dalam <span id="new-lock-timer"></span>.';
                $message .= "<script>
                    let remainingNew = $remainingSeconds;
                    const timerNewEl = document.getElementById('new-lock-timer');
                    const updateNewTimer = () => {
                        if (remainingNew <= 0) {
                            timerNewEl.innerText = 'sekarang. Silakan muat ulang (refresh) halaman.';
                        } else {
                            let m = Math.floor(remainingNew / 60);
                            let s = remainingNew % 60;
                            let text = '';
                            if (m > 0) text += m + ' menit ';
                            text += s + ' detik';
                            timerNewEl.innerText = text;
                            remainingNew--;
                            setTimeout(updateNewTimer, 1000);
                        }
                    };
                    updateNewTimer();
                </script>";
                return ['success' => false, 'message' => $message];
            }

            return ['success' => false, 'message' => 'Username atau password salah'];
        }

        // Success
        $this->UserRepository->resetFailedLogin($user->id);
        if(isset($_SESSION['lock_until_' . $user->id])){
            unset($_SESSION['lock_until_' . $user->id]);
        }

        return ['success' => true, 'user' => $user];
    }

    public function changePassword(changePasswordDto $dto): bool{
        $hashedPassword = password_hash(
            $dto->newPassword,
            PASSWORD_DEFAULT
        );

        return $this->UserRepository->updatePassword(
            $dto->userId,
            $hashedPassword
        );
    }
}
?>
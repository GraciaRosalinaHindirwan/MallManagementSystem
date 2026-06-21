<?php
require_once __DIR__.'/../repositories/UserRepositoryInterface.php';
require_once __DIR__.'/../dto/LoginDto.php';
require_once __DIR__.'/../dto/userDto.php';
require_once __DIR__.'/../dto/changePasswordDto.php';

class AuthService{
    public function __construct(private UserRepositoryInterface $UserRepository){}

    public function login(LoginDto $dto): ?UserDto{
        $user = $this->UserRepository->findByUsername($dto->username);
        if(!$user){
            return null;
        }

        if(!password_verify($dto->password, $user->password)){
            return null;
        }

        return $user;
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
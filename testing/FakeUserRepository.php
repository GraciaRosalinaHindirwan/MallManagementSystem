<?php
require_once __DIR__.'/../repositories/UserRepositoryInterface.php';
require_once __DIR__.'/../dto/userDto.php';

class FakeUserRepository implements UserRepositoryInterface{
    public string $savedPassword = '';

    public function findByUsername(string $username): ?UserDto
    {
        return null;
    }

    public function updatePassword(
        int $userId,
        string $hashedPassword
    ): bool {
        $this->savedPassword = $hashedPassword;
        return true;
    }
}
?>
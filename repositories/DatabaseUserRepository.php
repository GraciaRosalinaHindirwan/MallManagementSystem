<?php
require_once __DIR__.'/userRepositoryInterface.php';
require_once __DIR__.'/../dto/userDto.php';

class DatabaseUserRepository implements UserRepositoryInterface
{
    public function findByUsername(
        string $username
        ): ?userDTO {
            return null;
        }

    public function updatePassword(
        int $userId,
        string $hashedPassword
    ): bool {
        return true;
    }
}
?>
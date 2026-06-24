<?php
// kontrak untuk repository User
require_once __DIR__.'/../dto/userDTO.php';

Interface UserRepositoryInterface{
    public function getAllUsersWithRole(): array;
    public function findById(int $userId): ?UserDto;
    public function findByUsername(
        string $username
        ): ?userDTO;

    public function updatePassword(
        int $userId,
        string $hashedPassword
    ): bool;

    public function incrementFailedLogin(int $userId): bool;
    public function resetFailedLogin(int $userId): bool;
    public function blockUser(int $userId): bool;
    public function adminResetPassword(int $userId, string $hashedPassword): bool;
    public function clearMustChangePassword(int $userId): bool;
}

?>
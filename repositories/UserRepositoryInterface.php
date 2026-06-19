<?php
// kontrak untuk repository User
require_once __DIR__.'/../dto/userDTO.php';

Interface UserRepositoryInterface{
    public function findByUsername(
        string $username
        ): ?userDTO;

    public function updatePassword(
        int $userId,
        string $hashedPassword
    ): bool;
}

?>
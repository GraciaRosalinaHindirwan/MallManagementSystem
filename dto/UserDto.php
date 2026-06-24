<?php
class UserDto{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $password,
        public readonly string $role,
        public readonly bool $mustChangePassword,
        public readonly int $failedLoginAttempts = 0,
        public readonly bool $isBlocked = false
    ){}
}

?>
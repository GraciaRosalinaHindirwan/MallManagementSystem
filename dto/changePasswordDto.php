<?php

class ChangePasswordDto
{
    public function __construct(
        public readonly int $userId,
        public readonly string $newPassword
    ) {}
}
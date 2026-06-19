<?php
class LoginDto
{
    public function __construct(
        public readonly string $username,
        public readonly string $password
    ) {}
}

?>
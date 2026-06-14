<?php

class Recipient
{
    public readonly string $email;
    public readonly string $name;

    public function __construct(string $email, string $name)
    {
        $this->email = $email;
        $this->name = $name;
    }
}

<?php

class Recipient
{
    public string $email;
    public string $name;

    public function __construct(string $email, string $name)
    {
        $this->email = $email;
        $this->name = $name;
    }
}

<?php

require_once __DIR__ . "/UserType.php";

class User
{
    public int $id;
    public string $username;
    public string $email;
    public UserType $type;

    public function __construct(int $id, string $username, string $email, UserType $type)
    {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->type = $type;
    }
}

<?php
// temporarily add user since I don't know the columns yet
class User
{
    public int $id;
    public string $username;
    public string $email;

    public function __construct(int $id, string $username, string $email)
    {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
    }
}

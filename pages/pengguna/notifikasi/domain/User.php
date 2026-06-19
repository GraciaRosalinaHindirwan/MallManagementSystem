<?php

require_once __DIR__ . "/UserType.php";

class User
{
    public int $id;
    public string $fullname;
    public string $username;
    public string $email;
    public string $password;
    public bool $must_change_password;
    public int $role_page_id;
    public int $failed_login_attempt;
    public bool $is_blocked;

    public function __construct(
        int $id,
        string $fullname,
        string $username,
        string $email,
        string $password,
        bool $must_change_password,
        int $role_page_id,
        int $failed_login_attempt,
        bool $is_blocked,
    ) {
        $this->id = $id;
        $this->fullname = $fullname;
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->must_change_password = $must_change_password;
        $this->role_page_id = $role_page_id;
        $this->failed_login_attempt = $failed_login_attempt;
        $this->is_blocked = $is_blocked;
    }
}

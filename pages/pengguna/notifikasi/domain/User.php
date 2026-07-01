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
    public int $failed_login_attempts;
    public bool $is_blocked;

    public function __construct(
        int $id,
        string $fullname,
        string $username,
        string $email,
        string $password,
        bool $must_change_password,
        int $role_page_id,
        int $failed_login_attempts,
        bool $is_blocked,
    ) {
        $this->id = $id;
        $this->fullname = $fullname;
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->must_change_password = $must_change_password;
        $this->role_page_id = $role_page_id;
        $this->failed_login_attempts = $failed_login_attempts;
        $this->is_blocked = $is_blocked;
    }

    // returns user with all fields filled with dummy data
    public static function create_default(int $id, string $username, string $email): User
    {
        return new User(
            id: $id,
            fullname: "",
            username: $username,
            email: $email,
            password: "",
            must_change_password: false,
            role_page_id: 1,
            failed_login_attempts: 0,
            is_blocked: false,
        );
    }
}

<?php

require_once __DIR__ . "/../IUserQuery.php";

class MysqlsUserQuery implements IUserQuery
{
    public mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function get_by_id(int $id): User | null
    {
        $stmt = $this->db->prepare("SELECT * FROM 09_users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();

        return new User(
            id: $result["id"],
            fullname: $result["fullname"],
            username: $result["username"],
            email: $result["email"],
            password: $result["password"],
            must_change_password: $result["must_change_password"],
            role_page_id: $result["role_page_id"],
            failed_login_attempt: $result["failed_login_attempt"],
            is_blocked: $result["is_blocked"],
        );
    }

    public function get_by_username(string $username): User | null
    {
        $stmt = $this->db->prepare("SELECT * FROM 09_users WHERE username = ?");
    }

    public function get_all(): array {}
}

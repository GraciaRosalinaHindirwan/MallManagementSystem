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

        if ($result == null) return null;

        return new User(
            id: $result["id"],
            fullname: $result["full_name"],
            username: $result["username"],
            email: $result["email"],
            password: $result["password"],
            must_change_password: $result["must_change_password"],
            role_page_id: $result["role_page_id"],
            failed_login_attempts: $result["failed_login_attempts"],
            is_blocked: $result["is_blocked"],
        );
    }

    public function get_by_username(string $username): User | null
    {
        $stmt = $this->db->prepare("SELECT * FROM 09_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();
        if ($result == null) {
            return null;
        }

        return new User(
            id: $result["id"],
            fullname: $result["full_name"],
            username: $result["username"],
            email: $result["email"],
            password: $result["password"],
            must_change_password: $result["must_change_password"],
            role_page_id: $result["role_page_id"],
            failed_login_attempts: $result["failed_login_attempts"],
            is_blocked: $result["is_blocked"],
        );
    }

    //** @return User[] */
    public function get_all(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM 09_users");
        $stmt->execute();

        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if ($results == null) return [];

        $users = [];

        foreach ($results as $row) {
            array_push(
                $users,
                new User(
                    id: $row["id"],
                    fullname: $row["full_name"],
                    username: $row["username"],
                    email: $row["email"],
                    password: $row["password"],
                    must_change_password: $row["must_change_password"],
                    role_page_id: $row["role_page_id"],
                    failed_login_attempts: $row["failed_login_attempts"],
                    is_blocked: $row["is_blocked"],
                )
            );
        }

        return $users;
    }
}

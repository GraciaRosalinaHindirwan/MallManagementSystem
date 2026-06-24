<?php
require_once __DIR__.'/userRepositoryInterface.php';
require_once __DIR__.'/../dto/userDto.php';

class UserRepository implements UserRepositoryInterface{
    private mysqli $conn;

    public function __construct(){
        global $conn;
        if(!isset($conn) || !$conn){
            require __DIR__.'/../config/konek.php';
        }
        $this->conn = $conn;
    }

    public function findByUsername(string $username): ?UserDto{
        $stmt = $this->conn->prepare("SELECT * FROM 09_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if($row = $result->fetch_assoc()){
            return new UserDto(
                (int)$row['id'],
                $row['username'],
                $row['password'],
                (string)$row['role_page_id'],
                (bool)$row['must_change_password'],
                (int)$row['failed_login_attempts'],
                (bool)$row['is_blocked']
            );
        }
        return null;
    }

    public function findById(int $userId): ?UserDto{
        $stmt = $this->conn->prepare("SELECT * FROM 09_users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if($row = $result->fetch_assoc()){
            return new UserDto(
                (int)$row['id'],
                $row['username'],
                $row['password'],
                (string)$row['role_page_id'],
                (bool)$row['must_change_password'],
                (int)$row['failed_login_attempts'],
                (bool)$row['is_blocked']
            );
        }
        return null;
    }

    public function getAllUsersWithRole(): array{
        $stmt = $this->conn->prepare("
            SELECT
                u.id,
                u.full_name,
                u.username,
                u.email,
                u.failed_login_attempts,
                u.is_blocked,
                r.role
            FROM 09_users u
            JOIN 09_role_pages r ON u.role_page_id = r.id
            ORDER BY u.id ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];
        while($row = $result->fetch_assoc()){
            $users[] = $row;
        }
        return $users;
    }

    public function updatePassword(int $userId, string $hashedPassword): bool{
        $stmt = $this->conn->prepare("UPDATE 09_users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        return $stmt->execute();
    }

    public function incrementFailedLogin(int $userId): bool{
        $stmt = $this->conn->prepare("UPDATE 09_users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }

    public function resetFailedLogin(int $userId): bool{
        $stmt = $this->conn->prepare("UPDATE 09_users SET failed_login_attempts = 0 WHERE id = ?");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }

    public function blockUser(int $userId): bool{
        $stmt = $this->conn->prepare("UPDATE 09_users SET is_blocked = 1 WHERE id = ?");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }

    public function adminResetPassword(int $userId, string $hashedPassword): bool{
        $stmt = $this->conn->prepare("UPDATE 09_users SET password = ?, failed_login_attempts = 0, is_blocked = 0, must_change_password = 1 WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        return $stmt->execute();
    }

    public function clearMustChangePassword(int $userId): bool{
        $stmt = $this->conn->prepare("UPDATE 09_users SET must_change_password = 0 WHERE id = ?");
        $stmt->bind_param("i", $userId);
        return $stmt->execute();
    }
}
?>
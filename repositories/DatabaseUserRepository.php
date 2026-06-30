<?php
require_once __DIR__.'/../config/konek.php';
require_once __DIR__.'/userRepositoryInterface.php';
require_once __DIR__.'/../dto/userDto.php';

class DatabaseUserRepository implements UserRepositoryInterface
{
    private $koneksi;
    public function __construct()
    {
        global $conn;
        $this->koneksi = $conn;
    }

    public function findByUsername(string $username): ?UserDTO
    {
        $sql = " SELECT u.*, r.role 
        FROM 09_users u 
        JOIN 09_role_pages r ON u.role_page_id = r.id 
        WHERE u.username = ?";

        $stmt = $this->koneksi->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return new UserDTO(
                $row['id'],
                $row['username'],
                $row['password'],
                $row['role'],
                (bool)$row['must_change_password']
            );
        }

        return null;
    }

    public function updatePassword(
        int $userId,
        string $hashedPassword
    ): bool {
        $sql = "UPDATE 09_users SET password = ?, must_change_password = false WHERE id = ?";

        $stmt = $this->koneksi->prepare($sql);
        $stmt->bind_param("si", $hashedPassword, $userId);

        $stmt->execute();

        return $stmt->affected_rows > 0;
    }
}
?>
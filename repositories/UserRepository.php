<?php
require_once __DIR__.'/userRepositoryInterface.php';
require_once __DIR__.'/../dto/userDto.php';

class UserRepository implements UserRepositoryInterface{
    private array $users;

    public function __construct(){
        $this->users = [
            new UserDto(
                '1', 
                'admin',  
                password_hash('Admin123!', PASSWORD_DEFAULT), 
                'admin',
                false),
            
            new UserDto(
                '2', 
                'operator',  
                password_hash('Operator123!', PASSWORD_DEFAULT), 
                'manager',
                true),
        ];
    }

    public function findByUsername(string $username): ?UserDto{
        foreach($this->users as $user){
            if($user->username === $username){
                return $user;
            }
        }
        return null;
    }

    public function updatePassword(int $userId, string $hashedPassword): bool{
        foreach($this->users as $index => $user){
            if($user->id === $userId){
                $this->users[$index] = new UserDto(
                    $user->id,
                    $user->username,
                    $hashedPassword,
                    $user->role,
                    false
                );
                return true;
            }
        }

        return false;
    }
}
?>
<?php
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__ . '/../repositories/UserRepository.php';

use PHPUnit\Framework\TestCase;

class UserRepositoryTest extends TestCase{
    public function testFindUser(){
        $repo = new UserRepository();
        $user = $repo->findByUsername('admin');
        $this->assertNotNull($user);
        $this->assertEquals('admin', $user->username);
    }

    public function testPasswordHash(){
        $repo = new UserRepository();
        $user = $repo->findByUsername('admin');

        $this->assertNotEquals(
            'Admin123!',
            $user->password
        );
    }

    public function testPasswordHashCanBeVerified()
    {
        $repo = new UserRepository();

        $user = $repo->findByUsername('admin');

        $this->assertTrue(
            password_verify(
                'Admin123!',
                $user->password
            )
        );
    }
}

?>
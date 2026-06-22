<?php
use PHPUnit\Framework\TestCase;
Require_once __DIR__.'/../services/AuthService.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__.'/../dto/LoginDto.php';
require_once __DIR__.'/../dto/changePasswordDto.php';
require_once __DIR__.'/../testing/FakeUserRepository.php';

class AuthServiceTest extends TestCase{
    private AuthService $service;
    private UserRepository $repo;

    protected function setUp(): void{
        $this->repo = new UserRepository();
        $this->service = new AuthService($this->repo);
    }

    public function testLoginSuccess()
    {
        $dto = new LoginDto(
            'admin',
            'Admin123!'
        );

        $user = $this->service->login($dto);

        $this->assertNotNull($user);
    }

    public function testLoginUserNotFound()
    {
        $dto = new LoginDto(
            'tidakAda',
            'Admin123!'
        );

        $user = $this->service->login($dto);

        $this->assertNull($user);
    }

    public function testLoginWrongPassword()
    {
        $dto = new LoginDto(
            'admin',
            'salah'
        );

        $user = $this->service->login($dto);

        $this->assertNull($user);
    }

    public function testDefaultPasswordUser()
    {
        $dto = new LoginDto(
            'admin',
            'Admin123!'
        );

        $user = $this->service->login($dto);

        $this->assertFalse(
            $user->mustChangePassword
        );
    }

    public function testChangePasswordSuccess()
    {
        $dto = new changePasswordDto(
            1,
            'PasswordBaru123'
        );

        $result = $this->service->changePassword($dto);

        $this->assertTrue($result);
    }

    public function testPasswordIsHashed(){
        $repo = new FakeUserRepository();
        $service = new AuthService($repo);
        $dto = new changePasswordDto(
            1,
            'PasswordBaru123'
        );

        $service->changePassword($dto);

        $this->assertTrue(
            password_verify(
                'PasswordBaru123',
                $repo->savedPassword
            )
        );
    }
}

?>
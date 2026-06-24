<?php

interface AfterLoginAction{
    public function handle();
}

class RedirectByRoleAction implements AfterLoginAction{
    public function __construct(
        public string $role
    ) {}

    public function handle() {

    //redirect per role disini yeah
        if($this->role == 'admin') {
            // header('Location: google.com');
            header('Location: ../../testing/dashboardTest.php');
        } else {
            // header('Location: youtube.com');
            header('Location: ../../testing/dashboardTest.php');
        }
        
        exit;
    }
}

class MustChangePasswordAction implements AfterLoginAction {
    public function handle() {
        $_SESSION['warning'] =
        'Silakan ganti password terlebih dahulu';

        header(
            'Location: ../changePassword.php'
        );
        exit;
    }
}

class AfterLoginProcess {
    public static function execute(AfterLoginAction $process) {
        $process->handle();
    }
}
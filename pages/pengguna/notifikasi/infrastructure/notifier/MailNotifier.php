<?php

require_once __DIR__ . "/INotifier.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/../../../../../vendor/autoload.php";

class PHPMailerNotifier implements INotifier
{
    #[Override]
    public function notify(NotificationContent $message, User $user)
    {
        $mailer = new PHPMailer(true);

        try {
            $mailer->isMail();
            $mailer->setFrom("");
            $mailer->addAddress($user);

            $mailer->Subject = $message->subject;
            $mailer->Body = $message->body;

            $mailer->send();
        } catch (Exception $e) {
            echo "failed to send email: " . $e;
        }
    }
}

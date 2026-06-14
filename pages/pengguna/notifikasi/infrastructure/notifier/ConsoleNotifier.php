<?php

require_once __DIR__ . "/INotifier.php";

class ConsoleNotifier implements INotifier
{
    public function notify(NotificationContent $message, User $user)
    {
        echo "notification sent!\n";
        echo "subject: " . $message->subject . "\n";
        echo "target: " . $user->email . "\n";
        echo $message->body . "\n";
    }
}

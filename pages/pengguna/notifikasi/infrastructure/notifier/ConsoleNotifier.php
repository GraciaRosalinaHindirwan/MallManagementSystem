<?php
require_once __DIR__ . "/../../INotifier.php";

class ConsoleNotifier implements INotifier
{
    public function notify(NotificationMessage $message, User $user)
    {
        echo "notification sent!\n";
        echo "subject: " . $message->subject . "\n";
        echo "target: " . $user->username . "\n";
        echo $message->body . "\n";
    }
}

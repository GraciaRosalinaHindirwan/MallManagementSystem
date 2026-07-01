<?php

require_once __DIR__ . "/../../domain/Notification/NotificationContent.php";

interface INotifier
{
    public function notify(NotificationContent $message, User $user);
}

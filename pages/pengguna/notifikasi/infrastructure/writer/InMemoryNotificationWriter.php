<?php

require_once __DIR__ . "/INotificationWriter.php";
require_once __DIR__ . "/../../domain/Notification.php";

class InMemoryNotificationWriter implements INotificationWriter
{
    public array $notifications;

    public function insert(string $message)
    {
        array_push($this->notifications, new Notification(count($this->notifications) + 1, $message, new DateTime()));
    }
}

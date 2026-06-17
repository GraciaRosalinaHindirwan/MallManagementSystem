<?php

require_once __DIR__ . "/INotificationWriter.php";
require_once __DIR__ . "/../../domain/Notification/Notification.php";

class InMemoryNotificationWriter implements INotificationWriter
{
    public array $notifications;

    public function __construct(array $notifications)
    {
        $this->notifications = $notifications;
    }

    public function insert(NotificationContent $notification)
    {
        array_push($this->notifications, new Notification(count($this->notifications) + 1, $notification, new DateTime()));
    }
}

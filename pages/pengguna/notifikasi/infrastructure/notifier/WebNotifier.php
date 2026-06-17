<?php

require_once __DIR__ . "/../notifier/INotifier.php";
require_once __DIR__ . "/../../domain/User.php";
require_once __DIR__ . "/../../domain/Notification/NotificationContent.php";
require_once __DIR__ . "/../writer/INotificationWriter.php";

class WebNotifier implements INotifier
{
    private INotificationWriter $_notification_writer;

    public function __construct(INotificationWriter $_notification_writer)
    {
        $this->_notification_writer = $_notification_writer;
    }


    public function notify(NotificationContent $message, User $user)
    {
        $this->_notification_writer->insert($message);
    }
}

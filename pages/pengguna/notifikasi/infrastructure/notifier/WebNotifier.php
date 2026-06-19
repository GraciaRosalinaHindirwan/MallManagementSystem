<?php

require_once __DIR__ . "/../notifier/INotifier.php";
require_once __DIR__ . "/../../domain/User.php";
require_once __DIR__ . "/../../domain/Notification/NotificationContent.php";
require_once __DIR__ . "/../writer/INotificationWriter.php";

class WebNotifier implements INotifier
{
    private INotificationWriter $_notification_writer;
    private INotificationQuery $_notification_query;

    public function __construct(INotificationWriter $_notification_writer, INotificationQuery $_notification_query)
    {
        $this->_notification_writer = $_notification_writer;
        $this->_notification_query = $_notification_query;
    }


    public function notify(NotificationContent $message, User $user)
    {
        $id = count($this->_notification_query->get_all());
        $this->_notification_writer->insert(Notification::create($id, $message));
    }
}

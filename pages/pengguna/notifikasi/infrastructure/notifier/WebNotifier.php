<?php

require_once __DIR__ . "/../notifier/INotifier.php";
require_once __DIR__ . "/../../domain/User.php";
require_once __DIR__ . "/../../domain/Notification/NotificationContent.php";
require_once __DIR__ . "/../writer/INotificationWriter.php";
require_once __DIR__ . "/../writer/INotificationLogWriter.php";

class WebNotifier implements INotifier
{
    private INotificationWriter $_notification_writer;
    private INotificationQuery $_notification_query;
    private INotificationLogWriter $_logger;

    public function __construct(
        INotificationWriter $_notification_writer,
        INotificationQuery $_notification_query,
        INotificationLogWriter $_notification_log_writer
    ) {
        $this->_notification_writer = $_notification_writer;
        $this->_notification_query = $_notification_query;
        $this->_logger = $_notification_log_writer;
    }


    public function notify(NotificationContent $message, User $user)
    {
        $id = count($this->_notification_query->get_all());
        $this->_notification_writer->insert(Notification::create($id, $message));

        $this->_logger->insert(NotificationLog::pending(
            new Recipient($user->email, $user->username),
            $message,
            NotificationChannel::inapp,
            "NOTIF-" . $id,
            $user->id
        ));
    }
}

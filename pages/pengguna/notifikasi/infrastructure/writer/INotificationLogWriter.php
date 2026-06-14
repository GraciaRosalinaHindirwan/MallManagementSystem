<?php

require_once __DIR__ . "/../../domain/Notification/NotificationLog.php";

interface INotificationLogWriter
{
    public function insert(NotificationLog $log);
}
